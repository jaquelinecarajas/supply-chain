<?php
define('SUPPLY_CHAIN', true);

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funcoes.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(SESSION_NAME);
    session_start();
}

require_once __DIR__ . '/../../includes/auth_guard.php';
$usuario = usuario_logado();

// BLOQUEIO DE ACESSO
if ($usuario['setor'] !== 'financeiro' && !$usuario['admin']) {
    flash('erro', 'Acesso restrito ao setor Financeiro.');
    redirecionar(BASE_URL . '/index.php');
}

$sql = "
    SELECT o.id, o.codigo, o.descricao, o.status as ordem_status,
           p.id as pedido_id, p.valor_total, p.status as pedido_status,
           (SELECT COUNT(*) FROM pagamentos pg WHERE pg.pedido_id = p.id AND pg.tipo = 'pedido') as pago_pedido,
           (SELECT COUNT(*) FROM pagamentos pg WHERE pg.pedido_id = p.id AND pg.tipo = 'taxa_chegada') as pago_taxa
    FROM ordens o
    JOIN pedidos_compra p ON p.ordem_id = o.id
    ORDER BY o.criado_em DESC
";
$ordens = db()->query($sql)->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>Setor Financeiro</h2>
        <p>Controle de pagamentos e notas fiscais.</p>
    </div>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <table class="card-table">
        <thead>
            <tr>
                <th>Código OP</th>
                <th>Descrição</th>
                <th>Valor (R$)</th>
                <th>Etapa Atual</th>
                <th>Status Pgto</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($ordens)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--gray-500); padding: 32px;">
                        Nenhum pedido de compra gerado no sistema ainda.
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($ordens as $o): ?>
            <tr>
                <td><strong><?= e($o['codigo']) ?></strong></td>
                <td><?= e($o['descricao']) ?></td>
                <td style="color: var(--success); font-weight: 700;">R$ <?= number_format($o['valor_total'], 2, ',', '.') ?></td>
                
                <td>
                    <?php if ($o['ordem_status'] === 'financeiro' && $o['pago_pedido'] > 0 && $o['pago_taxa'] == 0): ?>
                        <span class="badge badge-warning">Taxa de Chegada</span>
                    <?php elseif ($o['ordem_status'] === 'logistica'): ?>
                        <span style="color: var(--gray-500); font-size: 13px;">Em Logística</span>
                    <?php elseif (in_array($o['ordem_status'], ['estoque', 'remontagem', 'concluida'])): ?>
                        <span style="color: var(--gray-500); font-size: 13px;">Finalizado</span>
                    <?php else: ?>
                        <span style="color: var(--gray-500); font-size: 13px;">Pedido</span>
                    <?php endif; ?>
                </td>

                <td>
                    <?php if ($o['pago_taxa'] > 0): ?>
                        <span class="badge badge-success">Pago</span>
                    <?php elseif ($o['pago_pedido'] > 0 && $o['ordem_status'] === 'financeiro'): ?>
                        <span class="badge badge-warning">Taxa Pendente</span>
                    <?php elseif ($o['pago_pedido'] > 0): ?>
                        <span class="badge badge-success">Pago</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Pendente</span>
                    <?php endif; ?>
                </td>
                
                <td>
                    <?php if ($o['ordem_status'] === 'financeiro' && $o['pago_pedido'] > 0 && $o['pago_taxa'] == 0): ?>
                        <a href="<?= BASE_URL ?>/setores/financeiro/pagar_taxa.php?id=<?= $o['id'] ?>" class="btn-primary" style="background: var(--warning); padding: 6px 12px; font-size: 12px;">
                            Pagar Taxa
                        </a>
                    <?php elseif ($o['pago_pedido'] == 0): ?>
                        <a href="<?= BASE_URL ?>/setores/financeiro/pagar.php?id=<?= $o['id'] ?>" class="btn-primary" style="padding: 6px 12px; font-size: 12px;">
                            Pagar e Emitir NF
                        </a>
                    <?php else: ?>
                        <span style="color: var(--gray-400); font-size: 13px; font-style: italic;">Finalizado</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>