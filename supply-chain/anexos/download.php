<?php
define('SUPPLY_CHAIN', true);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funcoes.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(SESSION_NAME);
    session_start();
}

require_once __DIR__ . '/../includes/auth_guard.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    exit('Arquivo inválido.');
}

$stmt = db()->prepare("SELECT * FROM anexos WHERE id = ?");
$stmt->execute([$id]);
$anexo = $stmt->fetch();

if (!$anexo) {
    exit('Arquivo não encontrado no banco.');
}

$caminho_fisico = UPLOAD_PATH . '/' . $anexo['caminho_arquivo'];

if (!file_exists($caminho_fisico)) {
    exit('Arquivo físico não encontrado no servidor.');
}

// Força o download
header('Content-Description: File Transfer');
header('Content-Type: ' . $anexo['mime_type']);
header('Content-Disposition: attachment; filename="' . basename($anexo['nome_original']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($caminho_fisico));
readfile($caminho_fisico);
exit;