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

$sql = "SELECT o.*, p.id as pedido_id, p.valor_total FROM ordens o JOIN pedidos_compra p ON p.ordem_id = o.id WHERE o.id = ? AND o.status = 'financeiro'";
$stmt = db()->prepare($sql);
$stmt->execute([$ordem_id]);
$dados = $stmt->fetch();

if (!$dados) {
    flash('erro', 'Ordem não encontrada ou já processada.');
    redirecionar(BASE_URL . '/setores/financeiro/index.php');
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $valor_taxa = str_replace(',', '.', $_POST['valor_taxa'] ?? '');
    $nota_fiscal = limpar($_POST['nota_fiscal'] ?? '');

    if (empty($valor_taxa) || !is_numeric($valor_taxa) || empty($nota_fiscal)) {
        $erro = 'Preencha o valor da taxa e a nota fiscal corretamente.';
    } else {
        // 1. Registra o pagamento da taxa
        $sql_pag = "INSERT INTO pagamentos (pedido_id, tipo, valor, nota_fiscal, pago_em) VALUES (?, 'taxa_chegada', ?, ?, NOW())";
        db()->prepare($sql_pag)->execute([$dados['pedido_id'], $valor_taxa, $nota_fiscal]);

        // 2. Atualiza status da ordem para 'estoque'
        db()->prepare("UPDATE ordens SET status = 'estoque' WHERE id = ?")->execute([$ordem_id]);

        // 3. REGISTRA NO HISTÓRICO
        registrar_historico(
            $ordem_id, 
            $usuario['id'], 
            'Taxa de Chegada Paga', 
            'A taxa no valor de R$ ' . number_format($valor_taxa, 2, ',', '.') . ' foi paga (NF: ' . $nota_fiscal . ').'
        );

        // 4. Conclui a tarefa do Kanban
        $setor_fin = db()->query("SELECT id FROM setores WHERE nome = 'financeiro'")->fetchColumn();
        if ($setor_fin) {
            db()->prepare("UPDATE kanban_tarefas SET coluna = 'concluido', concluido_em = NOW() WHERE ordem_id = ? AND setor_id = ? AND titulo LIKE '%taxa%'")
                ->execute([$ordem_id, $setor_fin]);
        }

        // 5. Cria notificação para o Estoque
        $setor_est = db()->query("SELECT id FROM setores WHERE nome = 'estoque'")->fetchColumn();
        if ($setor_est) {
            $sql_notif = "INSERT INTO notificacoes (setor_id, titulo, mensagem, link) VALUES (?, ?, ?, ?)";
            db()->prepare($sql_notif)->execute([
                $setor_est, 
                'Material Chegou', 
                'A taxa da ordem ' . $dados['codigo'] . ' foi paga. Receba o material.',
                '/setores/estoque/receber.php?id=' . $ordem_id
            ]);
        }

        flash('sucesso', 'Taxa de chegada paga! Ordem liberada para o Estoque.');
        redirecionar(BASE_URL . '/setores/financeiro/index.php');
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 1.5rem;">
    <a href="<?= BASE_URL ?>/setores/financeiro/index.php" style="color: #6b7280; text-decoration: none; font-size: 0.9rem;">&larr; Voltar para Financeiro</a>
    <h2 style="margin-top: 0.5rem;">Pagamento de Taxa de Chegada</h2>
    <p style="color: #4b5563;">Ordem: <strong><?= e($dados['codigo']) ?></strong> - <?= e($dados['descricao']) ?></p>
</div>

<?php if ($erro): ?>
    <div class="flash flash-erro"><?= e($erro) ?></div>
<?php endif; ?>

<div style="max-width: 600px;">
    <form method="post" class="card" style="display: flex; flex-direction: column; gap: 1.5rem;">
        <?= csrf_campo() ?>

        <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 1rem; border-radius: 6px;">
            <p style="margin: 0; font-size: 0.9rem; color: #92400e;">A Logística confirmou a chegada do material. Efetue o pagamento da taxa para liberar a descarga.</p>
        </div>

        <label style="display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.9rem;">
            Valor da Taxa (R$)
            <input type="text" name="valor_taxa" required placeholder="Ex: 150.00" style="padding: 0.6rem; border: 1px solid #d1d5db; border-radius: 6px;">
        </label>

        <label style="display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.9rem;">
            Nota Fiscal da Taxa
            <input type="text" name="nota_fiscal" required placeholder="Ex: 000123456" style="padding: 0.6rem; border: 1px solid #d1d5db; border-radius: 6px;">
        </label>

        <button type="submit" class="btn-primary" style="justify-content: center; background: var(--warning);">
            Confirmar Pagamento da Taxa
        </button>
    </form>
</div>

<!-- INCLUSÃO DO HISTÓRICO -->
<?php require __DIR__ . '/../../includes/historico_widget.php'; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>