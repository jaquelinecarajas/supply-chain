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

if ($usuario['setor'] !== 'financeiro' && !$usuario['admin']) {
    flash('erro', 'Acesso restrito ao setor Financeiro.');
    redirecionar(BASE_URL . '/index.php');
}

$ordem_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$ordem_id) {
    redirecionar(BASE_URL . '/setores/financeiro/index.php');
}

// Vamos buscar o pedido e checar se já foi pago
$stmt = db()->prepare("
    SELECT o.*, p.id as pedido_id, p.valor_total, 
           (SELECT COUNT(*) FROM pagamentos pg WHERE pg.pedido_id = p.id AND pg.tipo = 'pedido') as pago
    FROM ordens o 
    JOIN pedidos_compra p ON p.ordem_id = o.id 
    WHERE o.id = ?
");
$stmt->execute([$ordem_id]);
$dados = $stmt->fetch();

if (!$dados) {
    flash('erro', 'Ordem não encontrada.');
    redirecionar(BASE_URL . '/setores/financeiro/index.php');
}

// Verifica se já foi pago
if ($dados['pago'] > 0) {
    flash('erro', 'Este pedido já foi pago anteriormente.');
    redirecionar(BASE_URL . '/setores/financeiro/index.php');
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $nota_fiscal = limpar($_POST['nota_fiscal'] ?? '');

    if (empty($nota_fiscal)) {
        $erro = 'O número da Nota Fiscal é obrigatório para registrar o pagamento.';
    } else {
        // 1. Registra o pagamento
        $sql_pag = "INSERT INTO pagamentos (pedido_id, tipo, valor, nota_fiscal, pago_em) VALUES (?, 'pedido', ?, ?, NOW())";
        db()->prepare($sql_pag)->execute([$dados['pedido_id'], $dados['valor_total'], $nota_fiscal]);

        // 2. Atualiza status do pedido
        db()->prepare("UPDATE pedidos_compra SET status = 'pago' WHERE id = ?")->execute([$dados['pedido_id']]);

        // 3. Atualiza status da ordem
        db()->prepare("UPDATE ordens SET status = 'logistica' WHERE id = ?")->execute([$ordem_id]);

        // 4. REGISTRA NO HISTÓRICO
        registrar_historico(
            $ordem_id, 
            $usuario['id'], 
            'Pagamento Efetuado', 
            'O pedido no valor de R$ ' . number_format($dados['valor_total'], 2, ',', '.') . ' foi pago. Nota Fiscal: ' . $nota_fiscal . '.'
        );

        // 5. Conclui a tarefa do Kanban
        $setor_fin = db()->query("SELECT id FROM setores WHERE nome = 'financeiro'")->fetchColumn();
        if ($setor_fin) {
            db()->prepare("UPDATE kanban_tarefas SET coluna = 'concluido', concluido_em = NOW() WHERE ordem_id = ? AND setor_id = ?")
                ->execute([$ordem_id, $setor_fin]);
        }

        // 6. Cria a tarefa no Kanban para a Logística e a Notificação
        $setor_log = db()->query("SELECT id FROM setores WHERE nome = 'logistica'")->fetchColumn();
        if ($setor_log) {
            $sql_kanban = "INSERT INTO kanban_tarefas (ordem_id, setor_id, titulo, coluna, sla_horas) VALUES (?, ?, ?, 'a_fazer', 72)";
            db()->prepare($sql_kanban)->execute([$ordem_id, $setor_log, "Acompanhar rota de entrega da " . $dados['codigo']]);

            $sql_notif = "INSERT INTO notificacoes (setor_id, titulo, mensagem, link) VALUES (?, ?, ?, ?)";
            db()->prepare($sql_notif)->execute([
                $setor_log, 
                'Pedido Pago', 
                'O pedido da ordem ' . $dados['codigo'] . ' foi pago. Acompanhe a entrega.',
                '/setores/logistica/rota_form.php?id=' . $ordem_id
            ]);
        }

        flash('sucesso', 'Pagamento registrado! Ordem enviada para a Logística.');
        redirecionar(BASE_URL . '/setores/financeiro/index.php');
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 1.5rem;">
    <a href="<?= BASE_URL ?>/setores/financeiro/index.php" style="color: #6b7280; text-decoration: none; font-size: 0.9rem;">&larr; Voltar para Financeiro</a>
    <h2 style="margin-top: 0.5rem;">Pagamento de Pedido</h2>
    <p style="color: #4b5563;">Ordem: <strong><?= e($dados['codigo']) ?></strong> - <?= e($dados['descricao']) ?></p>
</div>

<?php if ($erro): ?>
    <div class="flash flash-erro"><?= e($erro) ?></div>
<?php endif; ?>

<div style="max-width: 600px;">
    <form method="post" class="card" style="display: flex; flex-direction: column; gap: 1.5rem;">
        <?= csrf_campo() ?>

        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 1rem; border-radius: 6px;">
            <p style="margin: 0; font-size: 0.9rem; color: #166534;">Valor a pagar:</p>
            <h3 style="margin: 0.2rem 0 0 0; color: #15803d;">R$ <?= number_format($dados['valor_total'], 2, ',', '.') ?></h3>
        </div>

        <label style="display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.9rem;">
            Número da Nota Fiscal (NF)
            <input type="text" name="nota_fiscal" required placeholder="Ex: 000123456" style="padding: 0.6rem; border: 1px solid #d1d5db; border-radius: 6px;">
        </label>

        <p style="font-size: 0.85rem; color: #6b7280; margin: 0;">
            Ao confirmar, o sistema registrará o pagamento e enviará a ordem para a Logística acompanhar a entrega.
        </p>

        <button type="submit" class="btn-primary" style="justify-content: center; background: var(--success);">
            Confirmar Pagamento e Enviar p/ Logística
        </button>
    </form>
</div>

<!-- INCLUSÃO DO HISTÓRICO -->
<?php require __DIR__ . '/../../includes/historico_widget.php'; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>