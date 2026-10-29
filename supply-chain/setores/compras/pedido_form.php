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

if ($usuario['setor'] !== 'compras' && !$usuario['admin']) {
    flash('erro', 'Acesso restrito ao setor de Compras.');
    redirecionar(BASE_URL . '/index.php');
}

$ordem_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$ordem_id) {
    redirecionar(BASE_URL . '/setores/compras/index.php');
}

$stmt = db()->prepare("SELECT * FROM ordens WHERE id = ?");
$stmt->execute([$ordem_id]);
$ordem = $stmt->fetch();

if (!$ordem || $ordem['status'] !== 'compras') {
    flash('erro', 'Ordem não encontrada ou já processada por Compras.');
    redirecionar(BASE_URL . '/setores/compras/index.php');
}

$fornecedores = db()->query("SELECT * FROM fornecedores ORDER BY nome ASC")->fetchAll();

$stmt_bom = db()->prepare("SELECT * FROM bom_itens WHERE ordem_id = ?");
$stmt_bom->execute([$ordem_id]);
$bom_itens = $stmt_bom->fetchAll();

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $fornecedor_id = filter_input(INPUT_POST, 'fornecedor_id', FILTER_VALIDATE_INT);
    $valor_total = str_replace(',', '.', $_POST['valor_total'] ?? '');
    $prazo_entrega = limpar($_POST['prazo_entrega'] ?? '');

    if (!$fornecedor_id || empty($valor_total) || !is_numeric($valor_total) || empty($prazo_entrega)) {
        $erro = 'Preencha todos os campos corretamente.';
    } else {
        $sql = "INSERT INTO pedidos_compra (ordem_id, fornecedor_id, valor_total, prazo_entrega, status) VALUES (?, ?, ?, ?, 'aberto')";
        db()->prepare($sql)->execute([$ordem_id, $fornecedor_id, $valor_total, $prazo_entrega]);

        db()->prepare("UPDATE ordens SET status = 'financeiro' WHERE id = ?")->execute([$ordem_id]);

        // REGISTRA NO HISTÓRICO
        registrar_historico(
            $ordem_id, 
            $usuario['id'], 
            'Pedido de Compra Gerado', 
            'Foi criado um pedido no valor de R$ ' . number_format($valor_total, 2, ',', '.') . ' com prazo de entrega para ' . date('d/m/Y', strtotime($prazo_entrega)) . '.'
        );

        $setor_comp = db()->query("SELECT id FROM setores WHERE nome = 'compras'")->fetchColumn();
        if ($setor_comp) {
            db()->prepare("UPDATE kanban_tarefas SET coluna = 'concluido', concluido_em = NOW() WHERE ordem_id = ? AND setor_id = ?")
                ->execute([$ordem_id, $setor_comp]);
        }

        $setor_fin = db()->query("SELECT id FROM setores WHERE nome = 'financeiro'")->fetchColumn();
        if ($setor_fin) {
            $sql_kanban = "INSERT INTO kanban_tarefas (ordem_id, setor_id, titulo, coluna, sla_horas) VALUES (?, ?, ?, 'a_fazer', 24)";
            db()->prepare($sql_kanban)->execute([$ordem_id, $setor_fin, "Efetuar pagamento do pedido da " . $ordem['codigo']]);

            $sql_notif = "INSERT INTO notificacoes (setor_id, titulo, mensagem, link) VALUES (?, ?, ?, ?)";
            db()->prepare($sql_notif)->execute([
                $setor_fin, 
                'Pedido para Pagamento', 
                'O pedido da ordem ' . $ordem['codigo'] . ' foi gerado e precisa ser pago.',
                '/setores/financeiro/pagar.php?id=' . $ordem_id
            ]);
        }

        flash('sucesso', 'Pedido de compra gerado com sucesso! Enviado para o Financeiro.');
        redirecionar(BASE_URL . '/setores/compras/index.php');
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 1.5rem;">
    <a href="<?= BASE_URL ?>/setores/compras/index.php" style="color: #6b7280; text-decoration: none; font-size: 0.9rem;">&larr; Voltar para Compras</a>
    <h2 style="margin-top: 0.5rem;">Gerar Pedido de Compra</h2>
    <p style="color: #4b5563;">Ordem: <strong><?= e($ordem['codigo']) ?></strong> - <?= e($ordem['descricao']) ?></p>
</div>

<?php if ($erro): ?>
    <div class="flash flash-erro"><?= e($erro) ?></div>
<?php endif; ?>

<?php if (empty($fornecedores)): ?>
    <div class="flash flash-erro">
        Você precisa cadastrar pelo menos um fornecedor antes de gerar um pedido.
        <br><br>
        <a href="<?= BASE_URL ?>/setores/compras/fornecedores.php" style="font-weight: bold;">Clique aqui para cadastrar</a>
    </div>
<?php else: ?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
    
    <div>
        <h3>Itens Solicitados (BOM)</h3>
        <table class="card-table" style="margin-top: 1rem;">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th>Qtd</th>
                </tr>
            </thead>
            <tbody>
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

    <div>
        <h3>Dados do Pedido</h3>
        <form method="post" class="card" style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
            <?= csrf_campo() ?>

            <label style="display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.9rem;">
                Fornecedor Escolhido
                <select name="fornecedor_id" required style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                    <option value="">Selecione...</option>
                    <?php foreach ($fornecedores as $f): ?>
                        <option value="<?= $f['id'] ?>"><?= e($f['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label style="display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.9rem;">
                Valor Total do Pedido (R$)
                <input type="text" name="valor_total" required placeholder="Ex: 1500.50" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
            </label>

            <label style="display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.9rem;">
                Prazo de Entrega
                <input type="date" name="prazo_entrega" required style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
            </label>

            <button type="submit" class="btn-primary" style="margin-top: 0.5rem; justify-content: center;">
                Gerar Pedido e Enviar p/ Financeiro
            </button>
        </form>
    </div>

</div>
<?php endif; ?>

<!-- INCLUSÃO DO HISTÓRICO -->
<?php require __DIR__ . '/../../includes/historico_widget.php'; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>