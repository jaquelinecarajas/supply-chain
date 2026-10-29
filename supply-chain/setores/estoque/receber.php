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

if ($usuario['setor'] !== 'estoque' && !$usuario['admin']) {
    flash('erro', 'Acesso restrito ao setor de Estoque.');
    redirecionar(BASE_URL . '/index.php');
}

$ordem_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$ordem_id) {
    redirecionar(BASE_URL . '/setores/estoque/index.php');
}

$stmt = db()->prepare("SELECT * FROM ordens WHERE id = ? AND status = 'estoque'");
$stmt->execute([$ordem_id]);
$ordem = $stmt->fetch();

if (!$ordem) {
    flash('erro', 'Ordem não encontrada ou já processada pelo Estoque.');
    redirecionar(BASE_URL . '/setores/estoque/index.php');
}

$stmt_bom = db()->prepare("SELECT * FROM bom_itens WHERE ordem_id = ?");
$stmt_bom->execute([$ordem_id]);
$bom_itens = $stmt_bom->fetchAll();

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $sql_mov = "INSERT INTO estoque_movimentacoes (ordem_id, codigo_material, tipo, quantidade, registrado_por) VALUES (?, ?, 'entrada', ?, ?)";
    $stmt_mov = db()->prepare($sql_mov);
    
    foreach ($bom_itens as $item) {
        $stmt_mov->execute([$ordem_id, $item['codigo_material'], $item['quantidade'], $usuario['id']]);
    }

    db()->prepare("UPDATE ordens SET status = 'remontagem' WHERE id = ?")->execute([$ordem_id]);

    // REGISTRA NO HISTÓRICO
    registrar_historico(
        $ordem_id, 
        $usuario['id'], 
        'Material Recebido', 
        'Os materiais deram entrada no estoque e a ordem retornou para a Produção.'
    );

    $setor_est = db()->query("SELECT id FROM setores WHERE nome = 'estoque'")->fetchColumn();
    if ($setor_est) {
        db()->prepare("UPDATE kanban_tarefas SET coluna = 'concluido', concluido_em = NOW() WHERE ordem_id = ? AND setor_id = ?")
            ->execute([$ordem_id, $setor_est]);
    }

    $setor_prod = db()->query("SELECT id FROM setores WHERE nome = 'producao'")->fetchColumn();
    if ($setor_prod) {
        $sql_kanban = "INSERT INTO kanban_tarefas (ordem_id, setor_id, titulo, coluna, sla_horas) VALUES (?, ?, ?, 'a_fazer', 24)";
        db()->prepare($sql_kanban)->execute([$ordem_id, $setor_prod, "Iniciar remontagem da " . $ordem['codigo']]);

        $sql_notif = "INSERT INTO notificacoes (setor_id, titulo, mensagem, link) VALUES (?, ?, ?, ?)";
        db()->prepare($sql_notif)->execute([
            $setor_prod, 
            'Material no Estoque', 
            'O material da ordem ' . $ordem['codigo'] . ' chegou ao estoque. Inicie a remontagem.',
            '/setores/producao/ordem_detalhe.php?id=' . $ordem_id
        ]);
    }

    flash('sucesso', 'Materiais recebidos no estoque! Ordem enviada para Remontagem na Produção.');
    redirecionar(BASE_URL . '/setores/estoque/index.php');
}

require __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 1.5rem;">
    <a href="<?= BASE_URL ?>/setores/estoque/index.php" style="color: #6b7280; text-decoration: none; font-size: 0.9rem;">&larr; Voltar para Estoque</a>
    <h2 style="margin-top: 0.5rem;">Receber Material</h2>
    <p style="color: #4b5563;">Ordem: <strong><?= e($ordem['codigo']) ?></strong> - <?= e($ordem['descricao']) ?></p>
</div>

<div style="max-width: 800px;">
    <div class="card">
        <h3>Conferência de Materiais (BOM)</h3>
        
        <table class="card-table" style="margin-bottom: 1.5rem; margin-top: 1rem;">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th>Qtd Solicitada</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bom_itens)): ?>
                    <tr><td colspan="3" style="text-align: center;">Nenhum item no BOM.</td></tr>
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

        <form method="post">
            <?= csrf_campo() ?>
            <div style="background: #f0fdf4; padding: 1rem; border-radius: 6px; border: 1px solid #bbf7d0; margin-bottom: 1rem;">
                <p style="margin: 0; color: #166534; font-size: 0.9rem;">
                    Ao confirmar, o sistema dará entrada de <strong>todos</strong> os itens listados acima no estoque e enviará a ordem para a Produção iniciar a remontagem.
                </p>
            </div>
            <button type="submit" class="btn-primary" style="background: var(--success);">
                Confirmar Recebimento e Entrada no Estoque
            </button>
        </form>
    </div>
</div>

<!-- INCLUSÃO DO HISTÓRICO -->
<?php require __DIR__ . '/../../includes/historico_widget.php'; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>