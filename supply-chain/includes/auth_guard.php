<?php
// =============================================
// Protege páginas: exige login
// =============================================

if (!defined('SUPPLY_CHAIN')) {
    http_response_code(403);
    exit('Acesso negado.');
}

// Garante que a sessão está ativa
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(SESSION_NAME);
    session_start();
}

// Se não logado, volta pro login
if (empty($_SESSION['usuario_id'])) {
    redirecionar(BASE_URL . '/auth/login.php');
}

// Se precisa trocar a senha, força ir pra tela de troca
$paginaAtual = basename($_SERVER['PHP_SELF'] ?? '');
if (!empty($_SESSION['deve_trocar_senha'])
    && !in_array($paginaAtual, ['trocar_senha.php', 'logout.php'], true)) {
    redirecionar(BASE_URL . '/auth/trocar_senha.php');
}

// Helper: retorna dados do usuário logado
function usuario_logado(): array
{
    return [
        'id'     => $_SESSION['usuario_id'] ?? 0,
        'nome'   => $_SESSION['usuario_nome'] ?? '',
        'email'  => $_SESSION['usuario_email'] ?? '',
        'setor'  => $_SESSION['usuario_setor'] ?? '',
        'admin'  => (bool) ($_SESSION['usuario_admin'] ?? false),
        'foto'   => $_SESSION['usuario_foto'] ?? null,
    ];
}

// Helper: exige admin
function exigir_admin(): void
{
    if (empty($_SESSION['usuario_admin'])) {
        http_response_code(403);
        exit('Acesso restrito ao administrador.');
    }
}