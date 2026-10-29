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
$usuario = usuario_logado();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar(BASE_URL . '/index.php');
}

csrf_validar();

$ordem_id = filter_input(INPUT_POST, 'ordem_id', FILTER_VALIDATE_INT);
$redirect_url = limpar($_POST['redirect_url'] ?? '/index.php');

if (!$ordem_id || !isset($_FILES['arquivo'])) {
    flash('erro', 'Nenhum arquivo enviado ou ordem inválida.');
    redirecionar(BASE_URL . $redirect_url);
}

// Processa o upload usando a função segura que criamos
$resultado = processar_upload($_FILES['arquivo'], 'documento', 'documentos');

if (!$resultado['ok']) {
    flash('erro', 'Erro no upload: ' . $resultado['erro']);
    redirecionar(BASE_URL . $redirect_url);
}

// Salva no banco de dados
$sql = "INSERT INTO anexos (ordem_id, nome_original, caminho_arquivo, mime_type, tamanho_bytes, enviado_por, setor_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
db()->prepare($sql)->execute([
    $ordem_id,
    $resultado['nome_original'],
    $resultado['caminho'],
    $resultado['mime'],
    $resultado['tamanho'],
    $usuario['id'],
    $usuario['setor'] ? db()->query("SELECT id FROM setores WHERE nome = '{$usuario['setor']}'")->fetchColumn() : null
]);

flash('sucesso', 'Documento anexado com sucesso!');
redirecionar(BASE_URL . $redirect_url);