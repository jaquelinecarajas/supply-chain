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
if ($usuario['setor'] !== 'compras' && !$usuario['admin']) {
    flash('erro', 'Acesso restrito ao setor de Compras.');
    redirecionar(BASE_URL . '/index.php');
}

$stmt = db()->prepare("SELECT o.* FROM ordens o WHERE o.status = 'compras' ORDER BY o.criado_em ASC");
$stmt->execute();
$ordens = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>Setor de Compras</h2>
        <p>Gestão de fornecedores e pedidos de compra.</p>
    </div>
    <a href="<?= BASE_URL ?>/setores/compras/fornecedores.php" class="btn-primary" style="background: var(--white); color: var(--primary); border: 1.5px solid var(--gray-200); box-shadow: none;">
        Gerenciar Fornecedores
    </a>
</div>

<div class="flash flash-info" style="margin-bottom: 24px;">
    <strong>Atenção:</strong> Ordens aguardando a criação de Pedido de Compra.
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <table class="card-table">
        <thead>
            <tr>
                <th>Código OP</th>
                <th>Descrição</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($ordens)): ?>
                <tr>
                    <td colspan="3" style="text-align: center; color: var(--gray-500); padding: 32px;">
                        Nenhuma ordem aguardando compras no momento.
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($ordens as $o): ?>
            <tr>
                <td><strong><?= e($o['codigo']) ?></strong></td>
                <td><?= e($o['descricao']) ?></td>
                <td>
                    <a href="<?= BASE_URL ?>/setores/compras/pedido_form.php?id=<?= $o['id'] ?>" style="font-weight: 600;">
                        Criar Pedido
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>