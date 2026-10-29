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
if ($usuario['setor'] !== 'logistica' && !$usuario['admin']) {
    flash('erro', 'Acesso restrito ao setor de Logística.');
    redirecionar(BASE_URL . '/index.php');
}

$sql = "
    SELECT o.id, o.codigo, o.descricao, o.status, 
           r.origem, r.destino, r.status as rota_status
    FROM ordens o
    LEFT JOIN pedidos_compra p ON p.ordem_id = o.id
    LEFT JOIN rotas r ON r.pedido_id = p.id
    WHERE o.status IN ('logistica', 'estoque') 
    ORDER BY o.criado_em DESC
";
$ordens = db()->query($sql)->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>Setor de Logística</h2>
        <p>Acompanhamento de rotas de entrega.</p>
    </div>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <table class="card-table">
        <thead>
            <tr>
                <th>Código OP</th>
                <th>Rota</th>
                <th>Status da Entrega</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($ordens)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: var(--gray-500); padding: 32px;">
                        Nenhuma ordem aguardando logística no momento.
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($ordens as $o): ?>
            <tr>
                <td><strong><?= e($o['codigo']) ?></strong></td>
                <td>
                    <?php if ($o['origem']): ?>
                        <?= e($o['origem']) ?> <span style="color:var(--gray-400);">&rarr;</span> <?= e($o['destino']) ?>
                    <?php else: ?>
                        <span style="color: var(--gray-400); font-style: italic;">Rota não definida</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($o['rota_status'] === 'em_transito'): ?>
                        <span class="badge badge-info">Em Trânsito</span>
                    <?php elseif ($o['rota_status'] === 'chegou'): ?>
                        <span class="badge badge-success">Chegou</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Aguardando</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?= BASE_URL ?>/setores/logistica/rota_form.php?id=<?= $o['id'] ?>" style="font-weight: 600;">
                        Gerenciar Rota
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>