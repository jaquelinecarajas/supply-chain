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
    echo json_encode(['total' => 0, 'html' => '']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$is_admin = $_SESSION['usuario_admin'];
$setor_nome = $_SESSION['usuario_setor'];

$setor_id = null;
if ($setor_nome) {
    $stmt = db()->prepare("SELECT id FROM setores WHERE nome = ?");
    $stmt->execute([$setor_nome]);
    $setor_id = $stmt->fetchColumn();
}

// Lógica de busca:
// Se for admin, busca TODAS as notificações (lida = 0)
// Se não for admin, busca as do seu usuário OU do seu setor
if ($is_admin) {
    $sql = "SELECT * FROM notificacoes WHERE lida = 0 ORDER BY criado_em DESC LIMIT 10";
    $stmt = db()->prepare($sql);
    $stmt->execute();
} else {
    $sql = "SELECT * FROM notificacoes WHERE lida = 0 AND (usuario_id = ?";
    $params = [$usuario_id];

    if ($setor_id) {
        $sql .= " OR setor_id = ?";
        $params[] = $setor_id;
    }
    $sql .= ") ORDER BY criado_em DESC LIMIT 10";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
}

$notifs = $stmt->fetchAll();
$total = count($notifs);
$html = '';

if ($total === 0) {
    $html = '<div style="padding: 24px; text-align: center; color: var(--gray-500); font-size: 13px;">Nenhuma notificação nova.</div>';
} else {
    foreach ($notifs as $n) {
        $data = date('d/m H:i', strtotime($n['criado_em']));
        
        // Verifica se tem link, se não tiver, o clique não faz nada
        $link = $n['link'] ? BASE_URL . $n['link'] : 'javascript:void(0)';
        
        // O onclick vai marcar como lida no banco e depois redirecionar
        $onclick = "marcarLida({$n['id']}, '{$link}')";
        
        $html .= "<div style='padding: 12px 16px; border-bottom: 1px solid var(--gray-200); background: #eef3ff; cursor: pointer; transition: 0.2s;' onmouseover=\"this.style.background='#e0e7ff'\" onmouseout=\"this.style.background='#eef3ff'\" onclick=\"{$onclick}\">";
        $html .= "<div style='font-size: 13px; font-weight: 700; color: var(--primary); margin-bottom: 4px;'>{$n['titulo']}</div>";
        $html .= "<div style='font-size: 13px; color: var(--gray-600); line-height: 1.4;'>{$n['mensagem']}</div>";
        $html .= "<div style='font-size: 11px; color: var(--gray-400); margin-top: 6px;'>{$data}</div>";
        $html .= "</div>";
    }
}

echo json_encode([
    'total' => $total,
    'html' => $html
]);
exit;