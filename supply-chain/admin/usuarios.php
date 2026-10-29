<?php
define('SUPPLY_CHAIN', true);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../includes/csrf.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(SESSION_NAME);
    session_start();
}

require_once __DIR__ . '/../includes/auth_guard.php';
exigir_admin(); // Só admin entra aqui

// Busca todos os usuários e seus setores
$stmt = db()->query("
    SELECT u.*, s.nome as setor_nome 
    FROM usuarios u 
    LEFT JOIN setores s ON s.id = u.setor_id 
    ORDER BY u.criado_em DESC
");
$usuarios = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h2>Gerenciar Usuários</h2>
    <a href="<?= BASE_URL ?>/admin/usuario_form.php" style="background: #2563eb; color: #fff; padding: 0.6rem 1rem; border-radius: 6px; text-decoration: none; font-weight: bold;">+ Novo Usuário</a>
</div>

<table style="width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden;">
    <thead style="background: #f3f4f6; text-align: left;">
        <tr>
            <th style="padding: 1rem; border-bottom: 1px solid #e5e7eb;">Nome</th>
            <th style="padding: 1rem; border-bottom: 1px solid #e5e7eb;">Email</th>
            <th style="padding: 1rem; border-bottom: 1px solid #e5e7eb;">Setor</th>
            <th style="padding: 1rem; border-bottom: 1px solid #e5e7eb;">Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($usuarios as $u): ?>
        <tr style="border-bottom: 1px solid #e5e7eb;">
            <td style="padding: 1rem;"><?= e($u['nome']) ?> <?= $u['is_admin'] ? '<strong>(Admin)</strong>' : '' ?></td>
            <td style="padding: 1rem;"><?= e($u['email']) ?></td>
            <td style="padding: 1rem;"><?= e($u['setor_nome'] ?? '-') ?></td>
            <td style="padding: 1rem;">
                <?php if ($u['ativo']): ?>
                    <span style="color: #166534; background: #dcfce7; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.85rem;">Ativo</span>
                <?php else: ?>
                    <span style="color: #991b1b; background: #fee2e2; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.85rem;">Inativo</span>
                <?php endif; ?>
                
                <?php if ($u['deve_trocar_senha']): ?>
                    <span style="color: #92400e; background: #fef3c7; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.85rem; margin-left: 0.5rem;">Senha Temporária</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../includes/footer.php'; ?>