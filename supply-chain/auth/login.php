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

if (!empty($_SESSION['usuario_id'])) {
    redirecionar(BASE_URL . '/index.php');
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $email = limpar($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!email_valido($email) || $senha === '') {
        $erro = 'Preencha email e senha corretamente.';
    } else {
        $stmt = db()->prepare('SELECT * FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if (!$u || !$u['ativo']) {
            $erro = 'Email ou senha inválidos.';
        } elseif ($u['bloqueado_ate'] && strtotime($u['bloqueado_ate']) > time()) {
            $restante = ceil((strtotime($u['bloqueado_ate']) - time()) / 60);
            $erro = "Conta bloqueada. Tente novamente em {$restante} min.";
        } elseif (!verificar_senha($senha, $u['senha_hash'])) {
            $tentativas = $u['tentativas_login'] + 1;
            if ($tentativas >= LOGIN_MAX_TENTATIVAS) {
                $bloqueio = date('Y-m-d H:i:s', time() + LOGIN_BLOQUEIO_MINUTOS * 60);
                db()->prepare('UPDATE usuarios SET tentativas_login = 0, bloqueado_ate = ? WHERE id = ?')
                    ->execute([$bloqueio, $u['id']]);
                $erro = 'Muitas tentativas. Conta bloqueada por ' . LOGIN_BLOQUEIO_MINUTOS . ' min.';
            } else {
                db()->prepare('UPDATE usuarios SET tentativas_login = ? WHERE id = ?')
                    ->execute([$tentativas, $u['id']]);
                $restam = LOGIN_MAX_TENTATIVAS - $tentativas;
                $erro = "Email ou senha inválidos. Restam {$restam} tentativa(s).";
            }
        } else {
            session_regenerate_id(true);

            $setorNome = '';
            if ($u['setor_id']) {
                $s = db()->prepare('SELECT nome FROM setores WHERE id = ?');
                $s->execute([$u['setor_id']]);
                $setorNome = $s->fetchColumn() ?: '';
            }

            $_SESSION['usuario_id']      = (int) $u['id'];
            $_SESSION['usuario_nome']    = $u['nome'];
            $_SESSION['usuario_email']   = $u['email'];
            $_SESSION['usuario_setor']   = $setorNome;
            $_SESSION['usuario_admin']   = (bool) $u['is_admin'];
            $_SESSION['usuario_foto']    = $u['foto_perfil'];
            $_SESSION['deve_trocar_senha'] = (bool) $u['deve_trocar_senha'];

            db()->prepare('UPDATE usuarios SET tentativas_login = 0, bloqueado_ate = NULL WHERE id = ?')
                ->execute([$u['id']]);

            if ($u['deve_trocar_senha']) {
                redirecionar(BASE_URL . '/auth/trocar_senha.php');
            }
            redirecionar(BASE_URL . '/index.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login · Supply Chain</title>
    <!-- O link do CSS está fixo aqui para garantir que ele carregue -->
    <link rel="stylesheet" href="/supply-chain/assets/css/style.css?v=2026">
</head>
<body class="tela-login">
    <form method="post" class="card-login">
        <div>
            <h1>Supply Chain</h1>
            <p class="subtitulo">Acesse o painel de gestão</p>
        </div>

        <?php if ($erro): ?>
            <div class="flash flash-erro" style="margin:0;"><?= e($erro) ?></div>
        <?php endif; ?>

        <?= csrf_campo() ?>

        <label>Email
            <input type="email" name="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
        </label>

        <label>Senha
            <input type="password" name="senha" required>
        </label>

        <button type="submit">Entrar no Sistema</button>
    </form>
</body>
</html>