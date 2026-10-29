<?php
// =============================================
// Constantes globais do sistema
// =============================================

if (!defined('SUPPLY_CHAIN')) {
    http_response_code(403);
    exit('Acesso negado.');
}

// ---- MODO DE DESENVOLVIMENTO ----
// Quando o site estiver pronto, mude para 0
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ---- Caminhos ----
define('BASE_PATH', dirname(__DIR__));                     // raiz do projeto
define('BASE_URL', '/supply-chain');                        // URL base relativa
define('UPLOAD_PATH', BASE_PATH . '/assets/uploads');       // uploads no disco
define('UPLOAD_URL', BASE_URL . '/assets/uploads');         // URL de uploads

// ---- Uploads: avatares ----
define('AVATAR_MAX_BYTES', 2 * 1024 * 1024);                // 2 MB
define('AVATAR_EXTENSOES', ['jpg', 'jpeg', 'png', 'webp']);
define('AVATAR_MIMES', ['image/jpeg', 'image/png', 'image/webp']);

// ---- Uploads: documentos ----
define('DOC_MAX_BYTES', 10 * 1024 * 1024);                  // 10 MB
define('DOC_EXTENSOES', ['pdf', 'docx', 'xlsx', 'png', 'jpg', 'jpeg']);
define('DOC_MIMES', [
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'image/jpeg',
    'image/png',
]);

// ---- Login ----
define('LOGIN_MAX_TENTATIVAS', 5);                          // tentativas antes de bloquear
define('LOGIN_BLOQUEIO_MINUTOS', 15);                       // tempo de bloqueio
define('SENHA_TEMP_EXPIRA_HORAS', 24);                      // validade da senha temporária

// ---- Sessão ----
define('SESSION_NAME', 'SUPPLYCHAIN_SESSID');