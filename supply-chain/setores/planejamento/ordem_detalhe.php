<?php
define('SUPPLY_CHAIN', true);

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../includes/csrf.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(SESSION_NAME);
    session_start();
}

require_once __DIR__ . '/../../includes/auth_guard.php';
$usuario = usuario_logado();

if ($usuario['setor'] !== 'planejamento' && !$usuario['admin']) {
    flash('erro', 'Acesso restrito ao setor de Planejamento.');
    redirecionar(BASE_URL . '/index.php');
}

$ordem_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$ordem_id) {
    redirecionar(BASE_URL . '/setores/planejamento/index.php');
}

// Busca a ordem
$stmt = db()->prepare("SELECT * FROM ordens WHERE id = ?");
$stmt->execute([$ordem_id]);
$ordem = $stmt->fetch();

if (!$ordem || $ordem['status'] !== 'planejamento') {
    flash('erro', 'Ordem não encontrada ou já processada.');
    redirecionar(BASE_URL . '/setores/planejamento/index.php');
}

$erro = '';

// Processa a aprovação do planejamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'aprovar') {
    csrf_validar();

    // 1. Atualiza o status da ordem para 'compras'
    db()->prepare("UPDATE ordens SET status = 'compras' WHERE id = ?")->execute([$ordem_id]);

    // 2. Registra no histórico
    registrar_historico(
        $ordem_id, 
        $usuario['id'], 
        'Planejamento Aprovado', 
        'A lista BOM foi revisada e aprovada. A ordem foi enviada para o setor de Compras.'
    );

    // 3. Conclui a tarefa do Kanban deste setor para esta ordem
    $setor_plan = db()->query("SELECT id FROM setores WHERE nome = 'planejamento'")->fetchColumn();
    if ($setor_plan) {
        db()->prepare("UPDATE kanban_tarefas SET coluna = 'concluido', concluido_em = NOW() WHERE ordem_id = ? AND setor_id = ?")
            ->execute([$ordem_id, $setor_plan]);
    }

    // 4. Cria a tarefa no Kanban para o setor de Compras
    $setor_comp = db()->query("SELECT id FROM setores WHERE nome = 'compras'")->fetchColumn();
    if ($setor_comp) {
        $sql_kanban = "INSERT INTO kanban_tarefas (ordem_id, setor_id, titulo, coluna, sla_horas) VALUES (?, ?, ?, 'a_fazer', 48)";
        db()->prepare($sql_kanban)->execute([$ordem_id, $setor_comp, "Cotar preços e fornecedores para a " . $ordem['codigo']]);

        // 5. Cria a notificação para o setor de Compras
        $sql_notif = "INSERT INTO notificacoes (setor_id, titulo, mensagem, link) VALUES (?, ?, ?, ?)";
        db()->prepare($sql_notif)->execute([
            $setor_comp, 
            'BOM Aprovado', 
            'A ordem ' . $ordem['codigo'] . ' foi aprovada e precisa de cotação.',
            '/setores/compras/pedido_form.php?id=' . $ordem_id
        ]);
    }

    flash('sucesso', 'Planejamento aprovado! A ordem foi enviada para o setor de Compras.');
    redirecionar(BASE_URL . '/setores/planejamento/index.php');
}

// Busca os itens do BOM
$stmt_bom = db()->prepare("SELECT * FROM bom_itens WHERE ordem_id = ? ORDER BY id ASC");
$stmt_bom->execute([$ordem_id]);
$bom_itens = $stmt_bom->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 1.5rem;">
    <a href="<?= BASE_URL ?>/setores/planejamento/index.php" style="color: #6b7280; text-decoration: none; font-size: 0.9rem;">&larr; Voltar para lista</a>
    <h2 style="margin-top: 0.5rem;">Análise de Planejamento: <?= e($ordem['codigo']) ?></h2>
    <p style="color: #4b5563;"><?= e($ordem['descricao']) ?></p>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
    
    <!-- Lista BOM -->
    <div>
        <h3>Revisão do BOM (Lista de Materiais)</h3>
        <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 1rem;">Verifique os itens desmontados pela produção antes de enviar para a cotação.</p>
        
        <table class="card-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th>Qtd</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bom_itens)): ?>
                    <tr><td colspan="3" style="text-align: center;">Nenhum material no BOM.</td></tr>
                <?php endif; ?>
                <?php foreach ($bom_itens as $item): ?>
                <tr>
                    <td><strong><?= e($item['codigo_material']) ?></strong></td>
                    <td><?= e($item['descricao']) ?></td>
                    <td><?= $item['quantidade'] ?> <?= e($item['unidade']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Ação de Aprovação -->
    <div>
        <h3>Ações</h3>
        <form method="post" class="card" style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="aprovar">

            <p style="font-size: 0.9rem; color: #4b5563; margin: 0;">
                Ao aprovar, a ordem será enviada para o setor de <strong>Compras</strong> iniciar a cotação de fornecedores.
            </p>

            <button type="submit" class="btn-primary" style="justify-content: center; margin-top: 0.5rem; background: var(--success);">
                Aprovar e Enviar p/ Compras
            </button>
        </form>
    </div>

</div>

<!-- INCLUSÃO DO HISTÓRICO -->
<?php require __DIR__ . '/../../includes/historico_widget.php'; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>