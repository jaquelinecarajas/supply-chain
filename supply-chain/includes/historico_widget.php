<?php
if (!defined('SUPPLY_CHAIN')) {
    http_response_code(403);
    exit('Acesso negado.');
}

if (!isset($ordem_id)) {
    return; // Se não tiver ordem_id, não faz nada
}

// Busca os dados da ordem para o painel
$stmt_info = db()->prepare("
    SELECT o.criado_em, u.nome as criador_nome, s.nome as setor_criador 
    FROM ordens o 
    LEFT JOIN usuarios u ON u.id = o.criado_por 
    LEFT JOIN setores s ON s.id = u.setor_id
    WHERE o.id = ?
");
$stmt_info->execute([$ordem_id]);
$info_ordem = $stmt_info->fetch();

// Busca o histórico
$stmt_hist = db()->prepare("
    SELECT h.*, u.nome as usuario_nome, s.nome as setor_usuario
    FROM historico_ordens h 
    JOIN usuarios u ON u.id = h.usuario_id 
    LEFT JOIN setores s ON s.id = u.setor_id
    WHERE h.ordem_id = ? 
    ORDER BY h.criado_em DESC
");
$stmt_hist->execute([$ordem_id]);
$historico = $stmt_hist->fetchAll();
?>

<div class="card" style="margin-top: 24px;">
    <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 20px; border-bottom: 1px solid var(--gray-200); padding-bottom: 12px;">Histórico da Solicitação</h3>

    <!-- Info de criação -->
    <div style="background: var(--gray-50); padding: 16px; border-radius: 8px; margin-bottom: 24px; display: flex; gap: 32px;">
        <div>
            <div style="font-size: 11px; font-weight: 800; color: var(--gray-400); text-transform: uppercase;">Criada em</div>
            <div style="font-size: 14px; font-weight: 600; color: var(--primary); margin-top: 4px;"><?= date('d/m/Y \à\s H:i', strtotime($info_ordem['criado_em'])) ?></div>
        </div>
        <div>
            <div style="font-size: 11px; font-weight: 800; color: var(--gray-400); text-transform: uppercase;">Criada por</div>
            <div style="font-size: 14px; font-weight: 600; color: var(--primary); margin-top: 4px;"><?= e($info_ordem['criador_nome'] ?? 'Sistema') ?> (<?= e(ucfirst($info_ordem['setor_criador'] ?? 'Admin')) ?>)</div>
        </div>
    </div>

    <!-- Linha do Tempo -->
    <div style="max-height: 400px; overflow-y: auto; padding-right: 8px;">
        <?php if (empty($historico)): ?>
            <p style="color: var(--gray-500); font-size: 13px;">Nenhuma ação registrada ainda.</p>
        <?php else: ?>
            <?php foreach ($historico as $h): ?>
                <div class="historico-item">
                    <div class="historico-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div class="historico-content">
                        <strong><?= e($h['acao']) ?></strong>
                        <p><?= e($h['detalhes']) ?></p>
                        <div class="historico-meta"><?= e($h['usuario_nome']) ?> (<?= e(ucfirst($h['setor_usuario'] ?? 'Admin')) ?>) &middot; <?= date('d/m/Y \à\s H:i', strtotime($h['criado_em'])) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>