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
if ($usuario['setor'] !== 'estoque' && !$usuario['admin']) {
    flash('erro', 'Acesso restrito ao setor de Estoque.');
    redirecionar(BASE_URL . '/index.php');
}

$stmt = db()->prepare("SELECT * FROM ordens WHERE status = 'estoque' ORDER BY criado_em ASC");
$stmt->execute();
$ordens = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>Setor de Estoque</h2>
        <p>Recebimento e conferência de materiais.</p>
    </div>
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
                        Nenhuma ordem aguardando recebimento no momento.
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($ordens as $o): ?>
            <tr>
                <td><strong><?= e($o['codigo']) ?></strong></td>
                <td><?= e($o['descricao']) ?></td>
                <td>
                    <a href="<?= BASE_URL ?>/setores/estoque/receber.php?id=<?= $o['id'] ?>" class="btn-primary" style="padding: 6px 12px; font-size: 12px;">
                        Receber Material
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>