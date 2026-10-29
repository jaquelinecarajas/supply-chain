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

$ordem_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$ordem_id) {
    redirecionar(BASE_URL . '/setores/producao/index.php');
}

// Busca a ordem
$stmt = db()->prepare("SELECT * FROM ordens WHERE id = ?");
$stmt->execute([$ordem_id]);
$ordem = $stmt->fetch();

if (!$ordem) {
    flash('erro', 'Ordem não encontrada.');
    redirecionar(BASE_URL . '/setores/producao/index.php');
}

$erro = '';

// Processa a adição de um item no BOM (só se estiver 'aberta')
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'add_bom') {
    csrf_validar();

    if ($ordem['status'] !== 'aberta') {
        flash('erro', 'Não é possível adicionar itens ao BOM nesta etapa.');
        redirecionar(BASE_URL . '/setores/producao/ordem_detalhe.php?id=' . $ordem_id);
    }

    $codigo_material = limpar($_POST['codigo_material'] ?? '');
    $descricao = limpar($_POST['descricao'] ?? '');
    $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);
    $unidade = limpar($_POST['unidade'] ?? 'un');

    if (empty($codigo_material) || empty($descricao) || !$quantidade || $quantidade <= 0) {
        $erro = 'Preencha todos os campos corretamente. A quantidade deve ser maior que zero.';
    } else {
        $sql = "INSERT INTO bom_itens (ordem_id, codigo_material, descricao, quantidade, unidade) VALUES (?, ?, ?, ?, ?)";
        db()->prepare($sql)->execute([$ordem_id, $codigo_material, $descricao, $quantidade, $unidade]);
        flash('sucesso', 'Material adicionado à lista BOM.');
        redirecionar(BASE_URL . '/setores/producao/ordem_detalhe.php?id=' . $ordem_id);
    }
}

// Busca os itens do BOM
$stmt_bom = db()->prepare("SELECT * FROM bom_itens WHERE ordem_id = ? ORDER BY id ASC");
$stmt_bom->execute([$ordem_id]);
$bom_itens = $stmt_bom->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 1.5rem;">
    <a href="<?= BASE_URL ?>/setores/producao/index.php" style="color: #6b7280; text-decoration: none; font-size: 0.9rem;">&larr; Voltar para lista</a>
    <h2 style="margin-top: 0.5rem;">Ordem de Produção: <?= e($ordem['codigo']) ?></h2>
    <p style="color: #4b5563;"><?= e($ordem['descricao']) ?></p>
</div>

<?php if ($erro): ?>
    <div class="flash flash-erro"><?= e($erro) ?></div>
<?php endif; ?>

<!-- PAINEL DE STATUS E AÇÕES -->
<div style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <span style="font-size: 0.9rem; color: #6b7280;">Status atual:</span>
        <strong style="text-transform: capitalize; font-size: 1.1rem; color: #1e40af;"><?= e($ordem['status']) ?></strong>
    </div>

    <div>
        <?php if ($ordem['status'] === 'aberta'): ?>
            <form method="post" action="<?= BASE_URL ?>/setores/producao/ordem_acao.php" style="margin: 0;">
                <?= csrf_campo() ?>
                <input type="hidden" name="ordem_id" value="<?= $ordem_id ?>">
                <input type="hidden" name="acao" value="iniciar_desmontagem">
                <button type="submit" style="background: #16a34a; color: #fff; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; font-weight: bold; cursor: pointer;">
                    Concluir Desmontagem e Enviar p/ Planejamento
                </button>
            </form>
        <?php elseif ($ordem['status'] === 'remontagem'): ?>
            <form method="post" action="<?= BASE_URL ?>/setores/producao/ordem_acao.php" style="margin: 0;">
                <?= csrf_campo() ?>
                <input type="hidden" name="ordem_id" value="<?= $ordem_id ?>">
                <input type="hidden" name="acao" value="concluir_remontagem">
                <button type="submit" style="background: #16a34a; color: #fff; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; font-weight: bold; cursor: pointer;">
                    Concluir Remontagem e Finalizar Ordem
                </button>
            </form>
        <?php else: ?>
            <span style="color: #6b7280; font-style: italic;">Aguardando ação de outro setor.</span>
        <?php endif; ?>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
    
    <!-- Coluna Esquerda: Lista BOM -->
    <div>
        <h3>Lista de Materiais (BOM)</h3>
        <table style="width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; margin-top: 1rem;">
            <thead style="background: #f3f4f6; text-align: left;">
                <tr>
                    <th style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">Código</th>
                    <th style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">Descrição</th>
                    <th style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">Qtd</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bom_itens)): ?>
                    <tr>
                        <td colspan="3" style="padding: 1.5rem; text-align: center; color: #6b7280;">
                            Nenhum material adicionado ao BOM ainda.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($bom_itens as $item): ?>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 0.75rem 1rem;"><strong><?= e($item['codigo_material']) ?></strong></td>
                    <td style="padding: 0.75rem 1rem;"><?= e($item['descricao']) ?></td>
                    <td style="padding: 0.75rem 1rem;"><?= $item['quantidade'] ?> <?= e($item['unidade']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Coluna Direita: Adicionar Material (se estiver aberta) -->
    <div>
        <h3>Adicionar Material</h3>
        
        <?php if ($ordem['status'] === 'aberta'): ?>
            <form method="post" style="background: #fff; padding: 1.5rem; border-radius: 8px; display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <?= csrf_campo() ?>
                <input type="hidden" name="acao" value="add_bom">

                <label style="display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.9rem;">
                    Código da Peça
                    <input type="text" name="codigo_material" required placeholder="Ex: P-1024" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                </label>

                <label style="display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.9rem;">
                    Descrição
                    <input type="text" name="descricao" required placeholder="Ex: Parafuso de aço 10mm" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                </label>

                <div style="display: flex; gap: 0.5rem;">
                    <label style="display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.9rem; flex: 1;">
                        Qtd
                        <input type="number" name="quantidade" required min="1" value="1" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                    </label>
                    <label style="display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.9rem; width: 80px;">
                        Unid.
                        <input type="text" name="unidade" required value="un" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                    </label>
                </div>

                <button type="submit" style="background: #2563eb; color: #fff; border: none; padding: 0.6rem; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 0.5rem;">
                    Adicionar ao BOM
                </button>
            </form>
        <?php elseif ($ordem['status'] === 'remontagem'): ?>
            <div style="background: #f0fdf4; padding: 1.5rem; border-radius: 8px; margin-top: 1rem; color: #166534; font-size: 0.9rem; border: 1px solid #bbf7d0;">
                O material já está no chão de fábrica. Realize a remontagem mecânica do trem de pouso e clique no botão verde acima para finalizar a ordem.
            </div>
        <?php else: ?>
            <div style="background: #f3f4f6; padding: 1.5rem; border-radius: 8px; margin-top: 1rem; color: #6b7280; font-size: 0.9rem; text-align: center;">
                O BOM não pode mais ser alterado porque a ordem já avançou de status.
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- INCLUSÃO DO WIDGET DE ANEXOS -->
<?php require __DIR__ . '/../../includes/anexos_widget.php'; ?>

<!-- NOVO: INCLUSÃO DO HISTÓRICO -->
<?php require __DIR__ . '/../../includes/historico_widget.php'; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>