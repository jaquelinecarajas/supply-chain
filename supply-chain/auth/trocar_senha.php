<?php
define('SUPPLY_CHAIN', true);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../includes/csrf.php';

// ---- AQUI ESTAVA O ERRO: Iniciar a sessão antes do auth_guard ----
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(SESSION_NAME);
    session_start();
}

require_once __DIR__ . '/../includes/auth_guard.php';

$erro = '';
$usuario = usuario_logado();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $nova = $_POST['nova_senha'] ?? '';
    $conf = $_POST['confirmar_senha'] ?? '';

    if (strlen($nova) < 8) {
        $erro = 'A senha deve ter no mínimo 8 caracteres.';
    } elseif ($nova !== $conf) {
        $erro = 'As senhas não coincidem.';
    } elseif ($nova === 'Admin@123') {
        $erro = 'Escolha uma senha diferente da temporária.';
    } else {
        db()->prepare('UPDATE usuarios SET senha_hash = ?, deve_trocar_senha = 0 WHERE id = ?')
            ->execute([hash_senha($nova), $usuario['id']]);

        $_SESSION['deve_trocar_senha'] = false;
        flash('sucesso', 'Senha alterada com sucesso!');
        redirecionar(BASE_URL . '/index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Trocar Senha · Supply Chain</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="tela-login">
    <form method="post" class="card-login">
        <h1>Trocar Senha</h1>
        <p class="subtitulo">Você precisa definir uma nova senha para continuar.</p>

        <?php if ($erro): ?>
            <div class="flash flash-erro"><?= e($erro) ?></div>
        <?php endif; ?>

        <?= csrf_campo() ?>

        <label>Nova senha (mín. 8 caracteres)
            <input type="password" name="nova_senha" required minlength="8">
        </label>

        <label>Confirmar nova senha
            <input type="password" name="confirmar_senha" required minlength="8">
        </label>

        <button type="submit">Salvar nova senha</button>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="link-sair">Cancelar e sair</a>
    </form>
</body>
</html>