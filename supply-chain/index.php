<?php
define('SUPPLY_CHAIN', true);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/funcoes.php';
require_once __DIR__ . '/includes/auth_guard.php';

$usuario = usuario_logado();

// Busca algumas estatísticas básicas
$total_ordens = db()->query("SELECT COUNT(*) FROM ordens")->fetchColumn();
$ordens_abertas = db()->query("SELECT COUNT(*) FROM ordens WHERE status NOT IN ('concluida', 'cancelada')")->fetchColumn();
$total_fornecedores = db()->query("SELECT COUNT(*) FROM fornecedores")->fetchColumn();

require __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>Bem-vindo, <?= e($usuario['nome']) ?></h2>
        <p>Setor: <strong><?= e($usuario['setor'] ?: 'Administração') ?></strong></p>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 32px;">
    <div class="card" style="border-left: 4px solid var(--accent);">
        <h3 style="font-size: 32px; font-weight: 800; margin-bottom: 4px;"><?= $total_ordens ?></h3>
        <p style="font-size: 12px; font-weight: 700; color: var(--gray-500); text-transform: uppercase;">Total de Ordens</p>
    </div>
    
    <div class="card" style="border-left: 4px solid var(--warning);">
        <h3 style="font-size: 32px; font-weight: 800; margin-bottom: 4px;"><?= $ordens_abertas ?></h3>
        <p style="font-size: 12px; font-weight: 700; color: var(--gray-500); text-transform: uppercase;">Em Andamento</p>
    </div>

    <div class="card" style="border-left: 4px solid var(--success);">
        <h3 style="font-size: 32px; font-weight: 800; margin-bottom: 4px;"><?= $total_fornecedores ?></h3>
        <p style="font-size: 12px; font-weight: 700; color: var(--gray-500); text-transform: uppercase;">Fornecedores</p>
    </div>
</div>

<div class="card" style="padding: 32px; text-align: center; color: var(--gray-500);">
    <p>Use o menu superior para acessar os setores, o Kanban de tarefas ou gerenciar os usuários do sistema.</p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>