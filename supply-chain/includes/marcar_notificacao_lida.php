<?php
define('SUPPLY_CHAIN', true);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funcoes.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(SESSION_NAME);
    session_start();
}

if (empty($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false]);
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if ($id) {
    // Se for admin, ele "lê" a notificação globalmente (mas isso pode apagar para o setor também, o que é um comportamento desejado para simplificar).
    // Se não for admin, só apaga se for dele ou do setor dele.
    $is_admin = $_SESSION['usuario_admin'];
    
    if ($is_admin) {
        db()->prepare("UPDATE notificacoes SET lida = 1 WHERE id = ?")->execute([$id]);
    } else {
        // Pega o setor
        $setor_nome = $_SESSION['usuario_setor'];
        $setor_id = null;
        if ($setor_nome) {
            $stmt = db()->prepare("SELECT id FROM setores WHERE nome = ?");
            $stmt->execute([$setor_nome]);
            $setor_id = $stmt->fetchColumn();
        }
        
        db()->prepare("UPDATE notificacoes SET lida = 1 WHERE id = ? AND (usuario_id = ? OR setor_id = ?)")
            ->execute([$id, $_SESSION['usuario_id'], $setor_id]);
    }
    
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false]);
}
exit;