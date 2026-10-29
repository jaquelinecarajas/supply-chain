<?php
define('SUPPLY_CHAIN', true);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funcoes.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(SESSION_NAME);
    session_start();
}

require_once __DIR__ . '/../includes/auth_guard.php';

header('Content-Type: application/json');

$tarefa_id = filter_input(INPUT_GET, 'tarefa_id', FILTER_VALIDATE_INT);
if (!$tarefa_id) {
    echo json_encode(['erro' => 'ID inválido']);
    exit;
}

// Busca a tarefa, a ordem e o nome de quem criou a ordem
$sql = "
    SELECT t.*, o.codigo, o.descricao, o.id as ordem_id, o.criado_em as ordem_criada_em, s.nome as setor_nome, u.nome as criador_nome
    FROM kanban_tarefas t
    JOIN ordens o ON o.id = t.ordem_id
    JOIN setores s ON s.id = t.setor_id
    LEFT JOIN usuarios u ON u.id = o.criado_por
    WHERE t.id = ?
";
$stmt = db()->prepare($sql);
$stmt->execute([$tarefa_id]);
$tarefa = $stmt->fetch();

if (!$tarefa) {
    echo json_encode(['erro' => 'Tarefa não encontrada']);
    exit;
}

// Busca o histórico completo da ordem
$stmt_hist = db()->prepare("
    SELECT h.*, u.nome as usuario_nome, s.nome as setor_usuario
    FROM historico_ordens h 
    JOIN usuarios u ON u.id = h.usuario_id 
    LEFT JOIN setores s ON s.id = u.setor_id
    WHERE h.ordem_id = ? 
    ORDER BY h.criado_em DESC
");
$stmt_hist->execute([$tarefa['ordem_id']]);
$historico = $stmt_hist->fetchAll();

$historico_formatado = [];
foreach ($historico as $h) {
    $historico_formatado[] = [
        'acao' => $h['acao'],
        'detalhes' => $h['detalhes'],
        'usuario' => $h['usuario_nome'],
        'setor' => $h['setor_usuario'] ? ucfirst($h['setor_usuario']) : 'Administração',
        'data' => date('d/m/Y \à\s H:i', strtotime($h['criado_em']))
    ];
}

echo json_encode([
    'ordem_codigo' => $tarefa['codigo'],
    'ordem_descricao' => $tarefa['descricao'],
    'tarefa_titulo' => $tarefa['titulo'],
    'setor' => ucfirst($tarefa['setor_nome']),
    'status' => $tarefa['coluna'],
    'criador' => $tarefa['criador_nome'] ?? 'Desconhecido',
    'data_abertura' => date('d/m/Y \à\s H:i', strtotime($tarefa['ordem_criada_em'])),
    'historico' => $historico_formatado
]);
exit;