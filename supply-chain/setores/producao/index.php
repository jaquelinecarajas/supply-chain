<?php
define('SUPPLY_CHAIN', true);

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/funcoes.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(SESSION_NAME);
    session_start();
}

require_once __DIR__ . '/../../includes/auth_guard.php';
$usuario = usuario_logado();

// BLOQUEIO DE ACESSO
if ($usuario['setor'] !== 'producao' && !$usuario['admin']) {
    flash('erro', 'Acesso restrito ao setor de Produção.');
    redirecionar(BASE_URL . '/index.php');
}

$stmt = db()->prepare("
    SELECT o.*, u.nome as criador_nome 
    FROM ordens o 
    LEFT JOIN usuarios u ON u.id = o.criado_por 
    WHERE o.status IN ('aberta', 'remontagem')
    ORDER BY o.criado_em DESC
");
$stmt->execute();
$ordens = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>Setor de Produção</h2>
        <p>Gestão de entrada e remontagem de trens de pouso.</p>
    </div>
    <a href="<?= BASE_URL ?>/setores/producao/ordem_form.php" class="btn-primary">
        + Nova Entrada
    </a>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <table class="card-table">
        <thead>
            <tr>
                <th>Código OP</th>
                <th>Descrição</th>
                <th>Status</th>
                <th>Criado por</th>
                <th>Data</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($ordens)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--gray-500); padding: 32px;">
                        Nenhuma ordem de produção pendente. Clique em "+ Nova Entrada" para começar.
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($ordens as $o): ?>
            <tr>
                <td><strong><?= e($o['codigo']) ?></strong></td>
                <td><?= e($o['descricao']) ?></td>
                <td>
                    <?php if ($o['status'] === 'remontagem'): ?>
                        <span class="badge badge-warning">Pronto p/ Remontar</span>
                    <?php else: ?>
                        <span class="badge badge-info"><?= e($o['status']) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= e($o['criador_nome'] ?? 'Desconhecido') ?></td>
                <td><?= date('d/m/Y H:i', strtotime($o['criado_em'])) ?></td>
                <td>
                    <a href="<?= BASE_URL ?>/setores/producao/ordem_detalhe.php?id=<?= $o['id'] ?>" style="font-weight: 600;">
                        Abrir OP
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>