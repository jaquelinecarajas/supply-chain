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
    redirecionar(BASE_URL . '/perfil/index.php');
}

csrf_validar();
$acao = limpar($_POST['acao'] ?? '');

// --- AÇÃO 1: ATUALIZAR FOTO ---
if ($acao === 'atualizar_foto') {
    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        flash('erro', 'Nenhuma imagem enviada ou ocorreu um erro.');
        redirecionar(BASE_URL . '/perfil/index.php');
    }

    $resultado = processar_upload($_FILES['foto'], 'avatar', 'avatars');

    if (!$resultado['ok']) {
        flash('erro', 'Erro na foto: ' . $resultado['erro']);
        redirecionar(BASE_URL . '/perfil/index.php');
    }

    // Salva o caminho no banco
    db()->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?")
        ->execute([$resultado['caminho'], $usuario['id']]);

    // Atualiza na sessão para o header mudar na hora
    $_SESSION['usuario_foto'] = $resultado['caminho'];

    flash('sucesso', 'Foto de perfil atualizada com sucesso!');
    redirecionar(BASE_URL . '/perfil/index.php');
}

// --- AÇÃO 2: TROCAR SENHA ---
elseif ($acao === 'trocar_senha') {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar = $_POST['confirmar_senha'] ?? '';

    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar)) {
        flash('erro', 'Preencha todos os campos de senha.');
        redirecionar(BASE_URL . '/perfil/index.php');
    }

    if (strlen($nova_senha) < 8) {
        flash('erro', 'A nova senha deve ter no mínimo 8 caracteres.');
        redirecionar(BASE_URL . '/perfil/index.php');
    }

    if ($nova_senha !== $confirmar) {
        flash('erro', 'A nova senha e a confirmação não coincidem.');
        redirecionar(BASE_URL . '/perfil/index.php');
    }

    // Busca a senha atual no banco
    $stmt = db()->prepare("SELECT senha_hash FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario['id']]);
    $hash_atual = $stmt->fetchColumn();

    // Verifica se a senha atual digitada está correta
    if (!verificar_senha($senha_atual, $hash_atual)) {
        flash('erro', 'A sua senha atual está incorreta.');
        redirecionar(BASE_URL . '/perfil/index.php');
    }

    // Atualiza para a nova senha
    db()->prepare("UPDATE usuarios SET senha_hash = ? WHERE id = ?")
        ->execute([hash_senha($nova_senha), $usuario['id']]);

    registrar_historico(0, $usuario['id'], 'Segurança', 'O usuário alterou a própria senha.');

    flash('sucesso', 'Sua senha foi alterada com sucesso!');
    redirecionar(BASE_URL . '/perfil/index.php');
}

else {
    redirecionar(BASE_URL . '/perfil/index.php');
}