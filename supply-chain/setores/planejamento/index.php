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
if ($usuario['setor'] !== 'planejamento' && !$usuario['admin']) {
    flash('erro', 'Acesso restrito ao setor de Planejamento.');
    redirecionar(BASE_URL . '/index.php');
}

$stmt = db()->prepare("SELECT o.*, u.nome as criador_nome FROM ordens o LEFT JOIN usuarios u ON u.id = o.criado_por WHERE o.status = 'planejamento' ORDER BY o.criado_em ASC");
$stmt->execute();
$ordens = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>Setor de Planejamento</h2>
        <p>Análise e aprovação de listas de materiais (BOM).</p>
    </div>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <table class="card-table">
        <thead>
            <tr>
                <th>Código OP</th>
                <th>Descrição</th>
                <th>Criado por</th>
                <th>Data de Envio</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($ordens)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: var(--gray-500); padding: 32px;">
                        Nenhuma ordem aguardando planejamento no momento.
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($ordens as $o): ?>
            <tr>
                <td><strong><?= e($o['codigo']) ?></strong></td>
                <td><?= e($o['descricao']) ?></td>
                <td><?= e($o['criador_nome'] ?? 'Desconhecido') ?></td>
                <td><?= date('d/m/Y H:i', strtotime($o['criado_em'])) ?></td>
                <td>
                    <a href="<?= BASE_URL ?>/setores/planejamento/ordem_detalhe.php?id=<?= $o['id'] ?>" style="font-weight: 600;">
                        Analisar BOM
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>