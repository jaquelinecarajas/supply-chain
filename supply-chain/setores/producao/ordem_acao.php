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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar(BASE_URL . '/setores/producao/index.php');
}

csrf_validar();

$ordem_id = filter_input(INPUT_POST, 'ordem_id', FILTER_VALIDATE_INT);
$acao = limpar($_POST['acao'] ?? '');

if (!$ordem_id || empty($acao)) {
    flash('erro', 'Ação inválida.');
    redirecionar(BASE_URL . '/setores/producao/index.php');
}

// Busca a ordem atual
$stmt = db()->prepare("SELECT * FROM ordens WHERE id = ?");
$stmt->execute([$ordem_id]);
$ordem = $stmt->fetch();

if (!$ordem) {
    flash('erro', 'Ordem não encontrada.');
    redirecionar(BASE_URL . '/setores/producao/index.php');
}

// Processa a ação
if ($acao === 'iniciar_desmontagem') {
    if ($ordem['status'] === 'aberta') {
        $check_bom = db()->prepare("SELECT COUNT(*) FROM bom_itens WHERE ordem_id = ?");
        $check_bom->execute([$ordem_id]);
        if ($check_bom->fetchColumn() == 0) {
            flash('erro', 'Você precisa adicionar pelo menos um item ao BOM antes de enviar para o Planejamento.');
            redirecionar(BASE_URL . '/setores/producao/ordem_detalhe.php?id=' . $ordem_id);
        }

        // 1. Atualiza o status
        db()->prepare("UPDATE ordens SET status = 'planejamento' WHERE id = ?")->execute([$ordem_id]);
        
        // 2. Registra no histórico
        registrar_historico(
            $ordem_id, 
            $usuario['id'], 
            'Desmontagem Concluída', 
            'O trem de pouso foi desmontado e a lista BOM foi enviada para o Planejamento.'
        );
        
        // 3. Cria as tarefas no Kanban para o Planejamento
        $setor_plan = db()->query("SELECT id FROM setores WHERE nome = 'planejamento'")->fetchColumn();
        if ($setor_plan) {
            $sql_kanban = "INSERT INTO kanban_tarefas (ordem_id, setor_id, titulo, coluna, sla_horas) VALUES (?, ?, ?, 'a_fazer', 24)";
            db()->prepare($sql_kanban)->execute([$ordem_id, $setor_plan, "Analisar BOM da " . $ordem['codigo']]);

            // 4. Cria a notificação para o setor de planejamento com link direto
            $sql_notif = "INSERT INTO notificacoes (setor_id, titulo, mensagem, link) VALUES (?, ?, ?, ?)";
            db()->prepare($sql_notif)->execute([
                $setor_plan, 
                'Nova Ordem Recebida', 
                'A ordem ' . $ordem['codigo'] . ' foi enviada pela Produção para análise do BOM.',
                '/setores/planejamento/ordem_detalhe.php?id=' . $ordem_id
            ]);
        }

        flash('sucesso', 'Desmontagem registrada! A ordem foi enviada para o Planejamento.');
    }
} 
elseif ($acao === 'concluir_remontagem') {
    if ($ordem['status'] === 'remontagem') {
        // 1. Atualiza a ordem para concluída
        db()->prepare("UPDATE ordens SET status = 'concluida' WHERE id = ?")->execute([$ordem_id]);
        
        // 2. Registra no histórico
        registrar_historico(
            $ordem_id, 
            $usuario['id'], 
            'Remontagem Finalizada', 
            'O trem de pouso foi remontado com sucesso e a ordem foi finalizada.'
        );

        // 3. Conclui a tarefa no Kanban da Produção
        $setor_prod = db()->query("SELECT id FROM setores WHERE nome = 'producao'")->fetchColumn();
        if ($setor_prod) {
            db()->prepare("UPDATE kanban_tarefas SET coluna = 'concluido', concluido_em = NOW() WHERE ordem_id = ? AND setor_id = ?")
                ->execute([$ordem_id, $setor_prod]);
        }

        flash('sucesso', 'Remontagem concluída com sucesso! Ordem finalizada.');
    }
} else {
    flash('erro', 'Ação desconhecida.');
}

redirecionar(BASE_URL . '/setores/producao/ordem_detalhe.php?id=' . $ordem_id);