<?php
if (!defined('SUPPLY_CHAIN')) {
    http_response_code(403);
    exit('Acesso negado.');
}
$usuario = function_exists('usuario_logado') ? usuario_logado() : null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supply Chain</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=2026">
</head>
<body>
<header class="app-header">
    <div class="app-header-inner">
        
        <!-- LOGO -->
        <a href="<?= BASE_URL ?>/index.php" class="app-logo">
            <div class="app-logo-icon" style="width:34px; height:34px; background:linear-gradient(135deg, #020035, #4a5cff); border-radius:9px; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:bold;">SC</div>
            <span class="app-logo-text">Supply Chain</span>
        </a>

        <!-- MENU DE NAVEGAÇÃO (Desktop e Mobile) -->
        <nav class="app-nav" id="app-nav">
            <?php if ($usuario): ?>
                <?php if (!$usuario['admin']): ?>
                    <!-- VISÃO DO USUÁRIO COMUM -->
                    <a href="<?= BASE_URL ?>/setores/<?= e($usuario['setor']) ?>/index.php" class="app-nav-link">Meu Setor (<?= ucfirst(e($usuario['setor'])) ?>)</a>
                    <a href="<?= BASE_URL ?>/kanban/index.php" class="app-nav-link">Kanban do Setor</a>
                <?php else: ?>
                    <!-- VISÃO DO ADMINISTRADOR (Vê tudo) -->
                    <a href="<?= BASE_URL ?>/setores/producao/index.php" class="app-nav-link">Produção</a>
                    <a href="<?= BASE_URL ?>/setores/planejamento/index.php" class="app-nav-link">Planejamento</a>
                    <a href="<?= BASE_URL ?>/setores/compras/index.php" class="app-nav-link">Compras</a>
                    <a href="<?= BASE_URL ?>/setores/financeiro/index.php" class="app-nav-link">Financeiro</a>
                    <a href="<?= BASE_URL ?>/setores/logistica/index.php" class="app-nav-link">Logística</a>
                    <a href="<?= BASE_URL ?>/setores/estoque/index.php" class="app-nav-link">Estoque</a>
                    <a href="<?= BASE_URL ?>/admin/usuarios.php" class="app-nav-link">Usuários</a>
                    <a href="<?= BASE_URL ?>/admin/sla.php" class="app-nav-link" style="color: var(--danger); font-weight: 700;">SLA</a>
                <?php endif; ?>
                
                <!-- Esses só aparecem no mobile -->
                <a href="<?= BASE_URL ?>/auth/logout.php" class="app-nav-link mobile-only">Sair</a>
            <?php endif; ?>
        </nav>

        <!-- LADO DIREITO -->
        <?php if ($usuario): ?>
            <div class="app-header-right">
                
                <!-- BOTÃO MOBILE -->
                <button class="mobile-menu-btn" id="mobile-menu-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- NOTIFICAÇÃO -->
                <div style="position: relative;" id="notif-wrapper">
                    <button id="btn-notif" class="app-icon-btn" style="background:none; border:none; cursor:pointer;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:var(--gray-500);">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span id="notif-badge" class="notif-badge" style="display:none;">0</span>
                    </button>
                    <div id="notif-panel" class="notif-panel">
                        <div style="padding: 14px 16px; border-bottom: 1px solid var(--gray-200); background: var(--gray-50); font-weight: 700;">Notificações</div>
                        <div id="notif-list" style="max-height: 320px; overflow-y: auto;"></div>
                    </div>
                </div>

                <!-- PERFIL -->
                <a href="<?= BASE_URL ?>/perfil/index.php" class="app-user">
                    <?php if ($usuario['foto']): ?>
                        <img src="<?= UPLOAD_URL . '/' . e($usuario['foto']) ?>" class="avatar-mini" alt="">
                    <?php else: ?>
                        <div class="avatar-mini" style="background:var(--accent); color:#fff; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:bold;"><?= strtoupper(substr($usuario['nome'], 0, 1)) ?></div>
                    <?php endif; ?>
                    <span class="app-user-name"><?= e(explode(' ', $usuario['nome'])[0]) ?></span>
                </a>
                
                <a href="<?= BASE_URL ?>/auth/logout.php" class="app-logout desktop-only">Sair</a>
            </div>
        <?php endif; ?>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnMobile = document.getElementById('mobile-menu-btn');
    const nav = document.getElementById('app-nav');
    if (btnMobile) {
        btnMobile.addEventListener('click', function(e) {
            e.stopPropagation();
            nav.classList.toggle('mobile-active');
        });
    }

    const btnNotif = document.getElementById('btn-notif');
    const panelNotif = document.getElementById('notif-panel');
    const badgeNotif = document.getElementById('notif-badge');
    const listNotif = document.getElementById('notif-list');

    if (btnNotif) {
        btnNotif.addEventListener('click', (e) => {
            e.stopPropagation();
            const isVisible = panelNotif.style.display === 'block';
            panelNotif.style.display = isVisible ? 'none' : 'block';
        });
        document.addEventListener('click', (e) => {
            if (!panelNotif.contains(e.target) && !btnNotif.contains(e.target)) panelNotif.style.display = 'none';
            if (btnMobile && !nav.contains(e.target) && !btnMobile.contains(e.target)) nav.classList.remove('mobile-active');
        });
    }

    function buscarNotificacoes() {
        if (!btnNotif) return;
        fetch('<?= BASE_URL ?>/includes/api_notificacoes.php')
            .then(res => res.json())
            .then(data => {
                if (data.total > 0) {
                    badgeNotif.innerText = data.total;
                    badgeNotif.style.display = 'block';
                    listNotif.innerHTML = data.html;
                } else {
                    badgeNotif.style.display = 'none';
                    listNotif.innerHTML = '<div style="padding: 24px; text-align: center; color: var(--gray-500); font-size: 13px;">Nenhuma notificação nova.</div>';
                }
            });
    }
    buscarNotificacoes();
    setInterval(buscarNotificacoes, 30000);
});

function marcarLida(id, link) {
    const formData = new FormData();
    formData.append('id', id);
    fetch('<?= BASE_URL ?>/includes/marcar_notificacao_lida.php', { method: 'POST', body: formData })
        .then(() => { if (link && link !== 'javascript:void(0)') window.location.href = link; else window.location.reload(); });
}
</script>

<main class="conteudo">
    <?php foreach (pegar_flashes() as $f): ?>
        <div class="flash flash-<?= e($f['tipo']) ?>"><?= e($f['msg']) ?></div>
    <?php endforeach; ?>