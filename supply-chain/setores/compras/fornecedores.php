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

if ($usuario['setor'] !== 'compras' && !$usuario['admin']) {
    flash('erro', 'Acesso restrito ao setor de Compras.');
    redirecionar(BASE_URL . '/index.php');
}

$erro = '';

// Cadastrar fornecedor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'add_fornecedor') {
    csrf_validar();
    $nome = limpar($_POST['nome'] ?? '');
    $cnpj = limpar($_POST['cnpj'] ?? '');
    $cidade = limpar($_POST['cidade'] ?? '');
    $estado = strtoupper(limpar($_POST['estado'] ?? ''));
    $prazo = filter_input(INPUT_POST, 'prazo', FILTER_VALIDATE_INT);

    if (empty($nome) || empty($cidade) || empty($estado)) {
        $erro = 'Nome, Cidade e Estado são obrigatórios.';
    } else {
        db()->prepare("INSERT INTO fornecedores (nome, cnpj, cidade, estado, prazo_medio_dias) VALUES (?, ?, ?, ?, ?)")
            ->execute([$nome, $cnpj, $cidade, $estado, $prazo ?: null]);
        flash('sucesso', 'Fornecedor cadastrado com sucesso!');
        redirecionar(BASE_URL . '/setores/compras/fornecedores.php');
    }
}

// Busca fornecedores
$fornecedores = db()->query("SELECT * FROM fornecedores ORDER BY nome ASC")->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h2>Fornecedores</h2>
    <a href="<?= BASE_URL ?>/setores/compras/index.php" style="color: #2563eb; text-decoration: none; font-weight: bold;">&larr; Voltar para Pedidos</a>
</div>

<?php if ($erro): ?>
    <div class="flash flash-erro"><?= e($erro) ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
    <!-- Formulário -->
    <div>
        <h3>Novo Fornecedor</h3>
        <form method="post" style="background: #fff; padding: 1.5rem; border-radius: 8px; display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="add_fornecedor">
            
            <label style="display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.9rem;">
                Nome / Razão Social
                <input type="text" name="nome" required style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
            </label>
            <label style="display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.9rem;">
                CNPJ (opcional)
                <input type="text" name="cnpj" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
            </label>
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 0.5rem;">
                <label style="display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.9rem;">
                    Cidade
                    <input type="text" name="cidade" required style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                </label>
                <label style="display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.9rem;">
                    UF
                    <input type="text" name="estado" required maxlength="2" placeholder="SP" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; text-transform: uppercase;">
                </label>
            </div>
            <label style="display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.9rem;">
                Prazo Médio (dias)
                <input type="number" name="prazo" min="1" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
            </label>
            <button type="submit" style="background: #2563eb; color: #fff; border: none; padding: 0.6rem; border-radius: 6px; font-weight: bold; cursor: pointer;">Cadastrar</button>
        </form>
    </div>

    <!-- Lista -->
    <div>
        <h3>Fornecedores Cadastrados</h3>
        <table style="width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; margin-top: 1rem;">
            <thead style="background: #f3f4f6; text-align: left;">
                <tr>
                    <th style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">Nome</th>
                    <th style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">Localização</th>
                    <th style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">Prazo Médio</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fornecedores)): ?>
                    <tr><td colspan="3" style="padding: 1.5rem; text-align: center; color: #6b7280;">Nenhum fornecedor cadastrado.</td></tr>
                <?php endif; ?>
                <?php foreach ($fornecedores as $f): ?>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 0.75rem 1rem;">
                        <strong><?= e($f['nome']) ?></strong><br>
                        <span style="font-size: 0.8rem; color: #6b7280;"><?= e($f['cnpj'] ?: 'CNPJ não informado') ?></span>
                    </td>
                    <td style="padding: 0.75rem 1rem;"><?= e($f['cidade']) ?> - <?= e($f['estado']) ?></td>
                    <td style="padding: 0.75rem 1rem;"><?= $f['prazo_medio_dias'] ? $f['prazo_medio_dias'].' dias' : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>