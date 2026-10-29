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

if ($usuario['setor'] !== 'logistica' && !$usuario['admin']) {
    flash('erro', 'Acesso restrito ao setor de Logística.');
    redirecionar(BASE_URL . '/index.php');
}

$ordem_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$ordem_id) {
    redirecionar(BASE_URL . '/setores/logistica/index.php');
}

$sql = "
    SELECT o.*, p.id as pedido_id, f.nome as fornecedor_nome, f.cidade, f.estado 
    FROM ordens o 
    JOIN pedidos_compra p ON p.ordem_id = o.id 
    JOIN fornecedores f ON f.id = p.fornecedor_id
    WHERE o.id = ?
";
$stmt = db()->prepare($sql);
$stmt->execute([$ordem_id]);
$dados = $stmt->fetch();

if (!$dados) {
    flash('erro', 'Ordem ou pedido não encontrado.');
    redirecionar(BASE_URL . '/setores/logistica/index.php');
}

$origem_padrao = $dados['cidade'] . ' - ' . $dados['estado'];

$stmt_rota = db()->prepare("SELECT * FROM rotas WHERE pedido_id = ? ORDER BY id DESC LIMIT 1");
$stmt_rota->execute([$dados['pedido_id']]);
$rota = $stmt_rota->fetch();

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $acao = limpar($_POST['acao'] ?? '');

    if ($acao === 'salvar_rota') {
        $origem = limpar($_POST['origem'] ?? '');
        $destino = limpar($_POST['destino'] ?? '');
        $status_rota = limpar($_POST['status_rota'] ?? 'aguardando');

        if (empty($origem) || empty($destino)) {
            $erro = 'Origem e destino são obrigatórios.';
        } else {
            if ($rota) {
                db()->prepare("UPDATE rotas SET origem = ?, destino = ?, status = ? WHERE id = ?")
                    ->execute([$origem, $destino, $status_rota, $rota['id']]);
            } else {
                db()->prepare("INSERT INTO rotas (pedido_id, origem, destino, status) VALUES (?, ?, ?, ?)")
                    ->execute([$dados['pedido_id'], $origem, $destino, $status_rota]);
            }

            // REGISTRA NO HISTÓRICO
            registrar_historico(
                $ordem_id, 
                $usuario['id'], 
                'Rota Atualizada', 
                'A rota foi salva. Origem: ' . $origem . ' | Destino: ' . $destino . ' | Status: ' . $status_rota
            );

            flash('sucesso', 'Rota atualizada com sucesso!');
            redirecionar(BASE_URL . '/setores/logistica/rota_form.php?id=' . $ordem_id);
        }
    } 
    
    elseif ($acao === 'confirmar_chegada') {
        if ($rota) {
            db()->prepare("UPDATE rotas SET status = 'chegou' WHERE id = ?")->execute([$rota['id']]);
        }

        db()->prepare("UPDATE ordens SET status = 'financeiro' WHERE id = ?")->execute([$ordem_id]);

        // REGISTRA NO HISTÓRICO
        registrar_historico(
            $ordem_id, 
            $usuario['id'], 
            'Material Entregue', 
            'A Logística confirmou a chegada do material na portaria.'
        );

        $setor_log = db()->query("SELECT id FROM setores WHERE nome = 'logistica'")->fetchColumn();
        if ($setor_log) {
            db()->prepare("UPDATE kanban_tarefas SET coluna = 'concluido', concluido_em = NOW() WHERE ordem_id = ? AND setor_id = ?")
                ->execute([$ordem_id, $setor_log]);
        }

        $setor_fin = db()->query("SELECT id FROM setores WHERE nome = 'financeiro'")->fetchColumn();
        if ($setor_fin) {
            $sql_kanban = "INSERT INTO kanban_tarefas (ordem_id, setor_id, titulo, coluna, sla_horas) VALUES (?, ?, ?, 'a_fazer', 12)";
            db()->prepare($sql_kanban)->execute([$ordem_id, $setor_fin, "Pagar taxa de chegada da " . $dados['codigo']]);

            $sql_notif = "INSERT INTO notificacoes (setor_id, titulo, mensagem, link) VALUES (?, ?, ?, ?)";
            db()->prepare($sql_notif)->execute([
                $setor_fin, 
                'Taxa de Chegada Pendente', 
                'O material da ordem ' . $dados['codigo'] . ' chegou. Pague a taxa.',
                '/setores/financeiro/pagar_taxa.php?id=' . $ordem_id
            ]);
        }

        $setor_est = db()->query("SELECT id FROM setores WHERE nome = 'estoque'")->fetchColumn();
        if ($setor_est) {
            $sql_kanban = "INSERT INTO kanban_tarefas (ordem_id, setor_id, titulo, coluna, sla_horas) VALUES (?, ?, ?, 'a_fazer', 24)";
            db()->prepare($sql_kanban)->execute([$ordem_id, $setor_est, "Receber material da " . $dados['codigo']]);
        }

        flash('sucesso', 'Chegada confirmada! Financeiro e Estoque notificados.');
        redirecionar(BASE_URL . '/setores/logistica/index.php');
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom: 1.5rem;">
    <a href="<?= BASE_URL ?>/setores/logistica/index.php" style="color: #6b7280; text-decoration: none; font-size: 0.9rem;">&larr; Voltar para Logística</a>
    <h2 style="margin-top: 0.5rem;">Gerenciar Rota</h2>
    <p style="color: #4b5563;">Ordem: <strong><?= e($dados['codigo']) ?></strong> | Fornecedor: <strong><?= e($dados['fornecedor_nome']) ?></strong></p>
</div>

<?php if ($erro): ?>
    <div class="flash flash-erro"><?= e($erro) ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
    
    <div>
        <form method="post" class="card" style="display: flex; flex-direction: column; gap: 1rem;">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="salvar_rota">

            <h3 style="margin-top: 0;">Dados da Rota</h3>

            <label style="display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.9rem;">
                Origem (Definida pela Compras)
                <input type="text" name="origem" readonly value="<?= e($rota['origem'] ?? $origem_padrao) ?>" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; background: #f3f4f6; color: #4b5563; cursor: not-allowed;">
            </label>

            <label style="display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.9rem;">
                Destino (Nossa Fábrica)
                <input type="text" name="destino" required value="<?= e($rota['destino'] ?? 'Fábrica Matriz') ?>" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
            </label>

            <label style="display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.9rem;">
                Status do Transporte
                <select name="status_rota" style="padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px;">
                    <option value="aguardando" <?= ($rota['status'] ?? '') === 'aguardando' ? 'selected' : '' ?>>Aguardando Coleta</option>
                    <option value="em_transito" <?= ($rota['status'] ?? '') === 'em_transito' ? 'selected' : '' ?>>Em Trânsito</option>
                    <option value="chegou" <?= ($rota['status'] ?? '') === 'chegou' ? 'selected' : '' ?>>Chegou</option>
                </select>
            </label>

            <button type="submit" class="btn-primary" style="justify-content: center; margin-top: 0.5rem;">
                Salvar Rota
            </button>
        </form>
    </div>

    <div>
        <h3>Confirmar Chegada</h3>
        <div class="card" style="margin-top: 1rem;">
            <?php if ($rota && $rota['status'] === 'chegou'): ?>
                <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 6px; text-align: center; font-weight: bold;">
                    Material já recebido e confirmado.
                </div>
            <?php else: ?>
                <p style="font-size: 0.9rem; color: #4b5563; margin-top: 0;">
                    Clique no botão abaixo apenas quando o caminhão do fornecedor estiver na portaria. Isso notificará o Financeiro (taxa) e o Estoque (descarga).
                </p>
                <form method="post" style="margin-top: 1rem;">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="acao" value="confirmar_chegada">
                    <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; background: var(--success);" onclick="return confirm('Tem certeza que o material chegou?');">
                        Confirmar Chegada do Material
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- INCLUSÃO DO HISTÓRICO -->
<?php require __DIR__ . '/../../includes/historico_widget.php'; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>