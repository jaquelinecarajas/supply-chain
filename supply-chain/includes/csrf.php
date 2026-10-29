<?php
// =============================================
// Proteção CSRF (Cross-Site Request Forgery)
// =============================================

if (!defined('SUPPLY_CHAIN')) {
    http_response_code(403);
    exit('Acesso negado.');
}

/**
 * Gera (uma vez por sessão) e retorna o token CSRF.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Retorna input hidden pronto para formulários.
 */
function csrf_campo(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Valida o token recebido via POST. Encerra se inválido.
 */
function csrf_validar(): void
{
    $enviado = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $enviado)) {
        http_response_code(403);
        exit('Token CSRF inválido. Recarregue a página.');
    }
}