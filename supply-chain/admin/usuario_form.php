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
exigir_admin();

// Busca setores para o select
$setores = db()->query("SELECT * FROM setores ORDER BY nome")->fetchAll();

$erro = '';
$senhaGerada = '';
$nomeSalvo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $nome = limpar($_POST['nome'] ?? '');
    $email = limpar($_POST['email'] ?? '');
    $setor_id = $_POST['setor_id'] ?? null;
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    // Validações
    if (empty($nome) || empty($email)) {
        $erro = 'Nome e email são obrigatórios.';
    } elseif (!email_valido($email)) {
        $erro = 'Email inválido.';
    } else {
        // Verifica se email já existe
        $check = db()->prepare('SELECT id FROM usuarios WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $erro = 'Este email já está cadastrado no sistema.';
        } else {
            // Gera a senha temporária
            $senhaGerada = gerar_senha_temporaria(10);
            $hash = hash_senha($senhaGerada);
            $expira = date('Y-m-d H:i:s', time() + (SENHA_TEMP_EXPIRA_HORAS * 3600));

            // Insere no banco
            $sql = "INSERT INTO usuarios (nome, email, senha_hash, setor_id, is_admin, deve_trocar_senha, senha_temporaria_expira) 
                    VALUES (?, ?, ?, ?, ?, 1, ?)";
            $stmt = db()->prepare($sql);
            $stmt->execute([$nome, $email, $hash, $setor_id ?: null, $is_admin, $expira]);

            $nomeSalvo = $nome;
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>

<h2>Cadastrar Novo Usuário</h2>

<?php if ($senhaGerada): ?>
    <!-- TELA DE SUCESSO (mostra a senha temporária) -->
    <div style="background: #fff; padding: 2rem; border-radius: 8px; max-width: 600px; margin-top: 1rem; border-top: 4px solid #166534;">
        <h3 style="color: #166534; margin-top: 0;">Usuário criado com sucesso!</h3>
        <p>Envie as credenciais abaixo para <strong><?= e($nomeSalvo) ?></strong>.</p>
        
        <div style="background: #f3f4f6; padding: 1rem; border-radius: 6px; margin: 1rem 0; font-family: monospace; font-size: 1.1rem;">
            <strong>Senha Temporária:</strong> <?= e($senhaGerada) ?>
        </div>
        
        <p style="color: #991b1b; font-size: 0.9rem;">
            <strong>Atenção:</strong> Esta senha não será mostrada novamente. Copie e envie agora.<br>
            No primeiro acesso, o sistema exigirá a troca desta senha.
        </p>

        <a href="<?= BASE_URL ?>/admin/usuarios.php" style="display: inline-block; margin-top: 1rem; background: #111827; color: #fff; padding: 0.6rem 1rem; border-radius: 6px; text-decoration: none;">Voltar para a lista</a>
    </div>

<?php else: ?>
    <!-- FORMULÁRIO DE CADASTRO -->
    <?php if ($erro): ?>
        <div class="flash flash-erro"><?= e($erro) ?></div>
    <?php endif; ?>

    <form method="post" style="background: #fff; padding: 2rem; border-radius: 8px; max-width: 600px; display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
        <?= csrf_campo() ?>

        <label style="display: flex; flex-direction: column; gap: 0.3rem;">
            Nome Completo
            <input type="text" name="nome" required style="padding: 0.6rem; border: 1px solid #d1d5db; border-radius: 6px;">
        </label>

        <label style="display: flex; flex-direction: column; gap: 0.3rem;">
            Email de Acesso
            <input type="email" name="email" required style="padding: 0.6rem; border: 1px solid #d1d5db; border-radius: 6px;">
        </label>

        <label style="display: flex; flex-direction: column; gap: 0.3rem;">
            Setor
            <select name="setor_id" required style="padding: 0.6rem; border: 1px solid #d1d5db; border-radius: 6px;">
                <option value="">Selecione o setor...</option>
                <?php foreach ($setores as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= ucfirst(e($s['nome'])) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
            <input type="checkbox" name="is_admin" value="1">
            Conceder acesso de Administrador Geral
        </label>

        <div style="margin-top: 1rem; display: flex; gap: 1rem;">
            <button type="submit" style="background: #2563eb; color: #fff; border: none; padding: 0.7rem 1.5rem; border-radius: 6px; font-weight: bold; cursor: pointer;">Cadastrar e Gerar Senha</button>
            <a href="<?= BASE_URL ?>/admin/usuarios.php" style="padding: 0.7rem 1rem; text-decoration: none; color: #6b7280;">Cancelar</a>
        </div>
    </form>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>