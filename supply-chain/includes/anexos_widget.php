<?php
if (!defined('SUPPLY_CHAIN')) {
    http_response_code(403);
    exit('Acesso negado.');
}
if (!isset($ordem_id)) {
    exit('Variável $ordem_id não definida.');
}

// Busca os anexos da ordem
$stmt_anexos = db()->prepare("SELECT a.*, u.nome as usuario_nome FROM anexos a JOIN usuarios u ON u.id = a.enviado_por WHERE a.ordem_id = ? ORDER BY a.criado_em DESC");
$stmt_anexos->execute([$ordem_id]);
$anexos = $stmt_anexos->fetchAll();
?>

<div style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 2rem;">
    <h3 style="margin-top: 0;">Documentos Anexos</h3>
    
    <!-- Formulário de Upload -->
    <form method="post" action="<?= BASE_URL ?>/anexos/upload.php" enctype="multipart/form-data" style="display: flex; gap: 1rem; align-items: center; background: #f9fafb; padding: 1rem; border-radius: 6px; border: 1px dashed #d1d5db; margin-bottom: 1.5rem;">
        <?= csrf_campo() ?>
        <input type="hidden" name="ordem_id" value="<?= $ordem_id ?>">
        <input type="hidden" name="redirect_url" value="<?= $_SERVER['REQUEST_URI'] ?>">
        
        <input type="file" name="arquivo" required style="font-size: 0.9rem;">
        <button type="submit" style="background: #2563eb; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: bold; cursor: pointer;">
            Anexar Documento
        </button>
        <span style="font-size: 0.8rem; color: #6b7280;">Máx: 10MB (PDF, Imagens, Office)</span>
    </form>

    <!-- Lista de Anexos -->
    <?php if (empty($anexos)): ?>
        <p style="color: #6b7280; font-size: 0.9rem;">Nenhum documento anexado a esta ordem.</p>
    <?php else: ?>
        <ul style="list-style: none; padding: 0; margin: 0;">
            <?php foreach ($anexos as $a): ?>
                <li style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb;">
                    <div>
                        <a href="<?= BASE_URL ?>/anexos/download.php?id=<?= $a['id'] ?>" style="color: #2563eb; font-weight: bold; text-decoration: none;" target="_blank">
                            <?= e($a['nome_original']) ?>
                        </a>
                        <br>
                        <span style="font-size: 0.75rem; color: #6b7280;">
                            Enviado por <?= e($a['usuario_nome']) ?> em <?= date('d/m/Y H:i', strtotime($a['criado_em'])) ?>
                            (<?= round($a['tamanho_bytes'] / 1024, 1) ?> KB)
                        </span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>