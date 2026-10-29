<?php
define('SUPPLY_CHAIN', true);

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../includes/csrf.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(SESSION_NAME);
    session_start();
}

require_once __DIR__ . '/../../includes/auth_guard.php';
$usuario = usuario_logado();

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $descricao = limpar($_POST['descricao'] ?? '');

    if (empty($descricao)) {
        $erro = 'A descrição do trem de pouso é obrigatória.';
    } else {
        // Gera um código sequencial (Ex: OP-2026-0001)
        $ano = date('Y');
        $stmt = db()->prepare("SELECT COUNT(*) FROM ordens WHERE YEAR(criado_em) = ?");
        $stmt->execute([$ano]);
        $sequencial = (int)$stmt->fetchColumn() + 1;
        $codigo = sprintf("OP-%s-%04d", $ano, $sequencial);

        // Insere a ordem com status 'aberta'
        $sql = "INSERT INTO ordens (codigo, descricao, status, criado_por) VALUES (?, ?, 'aberta', ?)";
        db()->prepare($sql)->execute([$codigo, $descricao, $usuario['id']]);

        flash('sucesso', "Ordem de Produção {$codigo} criada com sucesso!");
        redirecionar(BASE_URL . '/setores/producao/index.php');
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<h2>Nova Entrada de Trem de Pouso</h2>

<?php if ($erro): ?>
    <div class="flash flash-erro"><?= e($erro) ?></div>
<?php endif; ?>

<form method="post" style="background: #fff; padding: 2rem; border-radius: 8px; max-width: 600px; display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
    <?= csrf_campo() ?>

    <p style="color: #4b5563; font-size: 0.95rem;">
        Ao criar esta ordem, o sistema gerará um código único (Ex: OP-2026-0001) e marcará o status como <strong>Aberta</strong>.
    </p>

    <label style="display: flex; flex-direction: column; gap: 0.3rem;">
        Descrição / Identificação do Trem de Pouso
        <input type="text" name="descricao" required placeholder="Ex: Trem de pouso Boeing 737 - Lote 45" style="padding: 0.6rem; border: 1px solid #d1d5db; border-radius: 6px;">
    </label>

    <div style="margin-top: 1rem; display: flex; gap: 1rem;">
        <button type="submit" style="background: #2563eb; color: #fff; border: none; padding: 0.7rem 1.5rem; border-radius: 6px; font-weight: bold; cursor: pointer;">
            Registrar Entrada
        </button>
        <a href="<?= BASE_URL ?>/setores/producao/index.php" style="padding: 0.7rem 1rem; text-decoration: none; color: #6b7280;">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/../../includes/footer.php'; ?>