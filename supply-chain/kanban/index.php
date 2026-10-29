<?php
define('SUPPLY_CHAIN', true);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../includes/csrf.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(SESSION_NAME);
    session_start();
}

require_once __DIR__ . '/../includes/auth_guard.php';
$usuario = usuario_logado();

// Processa a mudança de coluna via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'mover_tarefa') {
    csrf_validar();
    $tarefa_id = filter_input(INPUT_POST, 'tarefa_id', FILTER_VALIDATE_INT);
    $nova_coluna = limpar($_POST['nova_coluna'] ?? '');

    if ($tarefa_id && in_array($nova_coluna, ['a_fazer', 'em_andamento', 'concluido'])) {
        // Registrar histórico
        $stmt = db()->prepare("SELECT ordem_id, titulo FROM kanban_tarefas WHERE id = ?");
        $stmt->execute([$tarefa_id]);
        $t_info = $stmt->fetch();
        
        if ($t_info) {
            $msg = "Moveu a tarefa '{$t_info['titulo']}' para " . str_replace('_', ' ', $nova_coluna);
            registrar_historico($t_info['ordem_id'], $usuario['id'], 'Movimentação Kanban', $msg);
        }

        if ($nova_coluna === 'em_andamento') {
            db()->prepare("UPDATE kanban_tarefas SET coluna = ?, iniciado_em = COALESCE(iniciado_em, NOW()) WHERE id = ?")
                ->execute([$nova_coluna, $tarefa_id]);
        } elseif ($nova_coluna === 'concluido') {
            db()->prepare("UPDATE kanban_tarefas SET coluna = ?, concluido_em = NOW() WHERE id = ?")
                ->execute([$nova_coluna, $tarefa_id]);
        } else {
            db()->prepare("UPDATE kanban_tarefas SET coluna = ?, iniciado_em = NULL, concluido_em = NULL WHERE id = ?")
                ->execute([$nova_coluna, $tarefa_id]);
        }
    }
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['status' => 'ok']);
        exit;
    }
    
    redirecionar(BASE_URL . '/kanban/index.php');
}

$filtro_setor = filter_input(INPUT_GET, 'setor', FILTER_VALIDATE_INT);

// --- REGRA DE SEGURANÇA DO KANBAN ---
// Se o usuário não for admin, ele só pode ver o Kanban do próprio setor.
$meu_setor_id = null;
if ($usuario['setor']) {
    $stmt_s = db()->prepare("SELECT id FROM setores WHERE nome = ?");
    $stmt_s->execute([$usuario['setor']]);
    $meu_setor_id = $stmt_s->fetchColumn();
}

$sql = "
    SELECT t.*, o.codigo as ordem_codigo, s.nome as setor_nome,
    (SELECT COUNT(*) FROM historico_ordens h WHERE h.ordem_id = o.id) as tem_historico
    FROM kanban_tarefas t
    JOIN ordens o ON o.id = t.ordem_id
    JOIN setores s ON s.id = t.setor_id
";
$params = [];
$where = [];

// Se não for admin, trava a busca para o setor dele
if (!$usuario['admin'] && $meu_setor_id) {
    $where[] = "t.setor_id = ?";
    $params[] = $meu_setor_id;
} else {
    // Se for admin, permite usar o filtro do select
    if ($filtro_setor) {
        $where[] = "t.setor_id = ?";
        $params[] = $filtro_setor;
    }
}

if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY t.criado_em ASC";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$tarefas = $stmt->fetchAll();

$colunas = ['a_fazer' => [], 'em_andamento' => [], 'concluido' => []];

foreach ($tarefas as $t) {
    $colunas[$t['coluna']][] = $t;
}

// Só busca a lista de setores se for admin (para o select)
$setores = [];
if ($usuario['admin']) {
    $setores = db()->query("SELECT * FROM setores ORDER BY nome")->fetchAll();
}

$csrfToken = csrf_token();

require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>Kanban de Tarefas</h2>
        <p>Arraste os cartões para atualizar o status. Clique em um cartão para ver o histórico.</p>
    </div>
    
    <?php if ($usuario['admin']): ?>
    <form method="get" style="margin: 0;">
        <select name="setor" onchange="this.form.submit()" style="padding: 10px 14px; border: 1.5px solid var(--gray-200); border-radius: var(--radius); font-size: 14px; background: var(--white); color: var(--primary); font-weight: 600;">
            <option value="">Todos os Setores</option>
            <?php foreach ($setores as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $filtro_setor == $s['id'] ? 'selected' : '' ?>>
                    <?= ucfirst(e($s['nome'])) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php endif; ?>
</div>

<div class="kanban-board">
    <?php foreach (['a_fazer' => 'A Fazer', 'em_andamento' => 'Em Andamento', 'concluido' => 'Concluído'] as $key => $titulo): ?>
        
        <div class="kanban-col" id="col-<?= $key ?>" data-coluna="<?= $key ?>">
            <h3>
                <?= $titulo ?>
                <span class="badge badge-info" id="count-<?= $key ?>"><?= count($colunas[$key]) ?></span>
            </h3>
            
            <?php foreach ($colunas[$key] as $t): 
                $isAtrasado = false;
                $tempoRestante = '';
                
                if ($key === 'em_andamento' && $t['iniciado_em'] && $t['sla_horas']) {
                    $inicio = strtotime($t['iniciado_em']);
                    $limite = $inicio + ($t['sla_horas'] * 3600);
                    $agora = time();
                    
                    if ($agora > $limite) {
                        $isAtrasado = true;
                        $horasAtraso = ceil(($agora - $limite) / 3600);
                        $tempoRestante = "Atrasado há {$horasAtraso}h";
                    } else {
                        $horasRestantes = ceil(($limite - $agora) / 3600);
                        $tempoRestante = "Faltam {$horasRestantes}h";
                    }
                }

                $isLida = ($t['tem_historico'] > 0) ? 'lida' : '';
            ?>
                <div class="kanban-card <?= $isAtrasado ? 'sla-warning' : '' ?> <?= $isLida ?>" 
                     draggable="true" 
                     data-id="<?= $t['id'] ?>"
                     onclick="abrirDetalhes(<?= $t['id'] ?>)">
                    <div class="kanban-card-header">
                        <span><?= e($t['ordem_codigo']) ?></span>
                        <span><?= ucfirst(e($t['setor_nome'])) ?></span>
                    </div>
                    
                    <div class="kanban-card-title"><?= e($t['titulo']) ?></div>
                    
                    <div class="kanban-card-meta">
                        <?php if ($key === 'em_andamento'): ?>
                            SLA: <span class="sla-badge <?= $isAtrasado ? 'sla-overdue' : 'sla-ok' ?>"><?= $tempoRestante ?></span>
                        <?php elseif ($t['sla_horas']): ?>
                            SLA: <span class="sla-badge" style="background:var(--gray-200); color:var(--gray-600);"><?= $t['sla_horas'] ?>h</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
        </div>
    <?php endforeach; ?>
</div>

<!-- MODAL DE DETALHES (Pop-up) -->
<div class="modal-overlay" id="modalDetalhes">
    <div class="modal-box" style="max-width: 700px;">
        
        <div class="modal-header" style="background: var(--gray-50); border-bottom: 1px solid var(--gray-200); border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
            <div>
                <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                    <span class="badge badge-info" id="modal-ordem-codigo">OP-XXXX</span>
                    <span class="badge badge-warning" id="modal-setor">Setor</span>
                    <span class="badge badge-success" id="modal-status">Status</span>
                </div>
                <h3 id="modal-tarefa-titulo" style="margin: 0;">Título da Tarefa</h3>
                <p id="modal-ordem-descricao" style="margin-top: 6px;">Descrição</p>
            </div>
            <button class="btn-fechar-modal" onclick="fecharDetalhes()">&times;</button>
        </div>
        
        <div style="display: flex; border-bottom: 1px solid var(--gray-200); padding: 16px 24px; gap: 32px; background: var(--white);">
            <div>
                <div style="font-size: 11px; font-weight: 800; color: var(--gray-400); text-transform: uppercase;">Abertura da OP</div>
                <div id="modal-data-abertura" style="font-size: 13px; font-weight: 600; color: var(--primary); margin-top: 4px;">-</div>
            </div>
            <div>
                <div style="font-size: 11px; font-weight: 800; color: var(--gray-400); text-transform: uppercase;">Criado por</div>
                <div id="modal-criador" style="font-size: 13px; font-weight: 600; color: var(--primary); margin-top: 4px;">-</div>
            </div>
        </div>

        <div class="modal-body" style="background: var(--white); max-height: 400px;">
            <h4>Histórico de Ações</h4>
            <div id="modal-historico-lista" style="margin-top: 24px;"></div>
        </div>

        <div class="modal-footer" style="padding: 16px 24px; background: var(--gray-50); border-top: 1px solid var(--gray-200); border-radius: 0 0 var(--radius-lg) var(--radius-lg); display: flex; justify-content: flex-end; gap: 12px;">
            <button onclick="fecharDetalhes()" class="btn-primary" style="background: var(--white); color: var(--primary); border: 1px solid var(--gray-200); box-shadow: none;">Fechar</button>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.kanban-card');
    const cols = document.querySelectorAll('.kanban-col');

    cards.forEach(card => {
        card.addEventListener('dragstart', (e) => {
            card.classList.add('dragging');
            e.dataTransfer.setData('text/plain', card.dataset.id);
            e.dataTransfer.effectAllowed = 'move';
            card.dataset.isDragging = 'true';
        });

        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
            setTimeout(() => { card.dataset.isDragging = 'false'; }, 100);
        });
    });

    cols.forEach(col => {
        col.addEventListener('dragover', (e) => {
            e.preventDefault();
            col.classList.add('drag-over');
        });

        col.addEventListener('dragleave', () => col.classList.remove('drag-over'));

        col.addEventListener('drop', (e) => {
            e.preventDefault();
            col.classList.remove('drag-over');

            const tarefaId = e.dataTransfer.getData('text/plain');
            const novaColuna = col.dataset.coluna;
            
            const card = document.querySelector(`.kanban-card[data-id="${tarefaId}"]`);
            if (card && card.parentElement !== col) {
                moverTarefa(tarefaId, novaColuna);
            }
        });
    });

    function moverTarefa(id, coluna) {
        const formData = new FormData();
        formData.append('acao', 'mover_tarefa');
        formData.append('tarefa_id', id);
        formData.append('nova_coluna', coluna);
        formData.append('csrf_token', '<?= $csrfToken ?>');

        fetch('<?= BASE_URL ?>/kanban/index.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(() => window.location.reload());
    }
});

function abrirDetalhes(id) {
    const card = document.querySelector(`.kanban-card[data-id="${id}"]`);
    if (card && card.dataset.isDragging === 'true') return;

    const modal = document.getElementById('modalDetalhes');
    modal.classList.add('active');
    
    document.getElementById('modal-ordem-codigo').innerText = 'Carregando...';
    document.getElementById('modal-tarefa-titulo').innerText = '';
    document.getElementById('modal-ordem-descricao').innerText = '';
    document.getElementById('modal-historico-lista').innerHTML = '<p style="color:var(--gray-500); font-size:13px;">Buscando histórico...</p>';

    fetch('<?= BASE_URL ?>/kanban/detalhes.php?tarefa_id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.erro) {
                alert(data.erro);
                fecharDetalhes();
                return;
            }

            document.getElementById('modal-ordem-codigo').innerText = data.ordem_codigo;
            document.getElementById('modal-tarefa-titulo').innerText = data.tarefa_titulo;
            document.getElementById('modal-ordem-descricao').innerText = data.ordem_descricao;
            document.getElementById('modal-setor').innerText = data.setor;
            
            const statusBadge = document.getElementById('modal-status');
            statusBadge.innerText = data.status.replace('_', ' ');
            statusBadge.className = 'badge ' + (data.status === 'concluido' ? 'badge-success' : (data.status === 'em_andamento' ? 'badge-warning' : 'badge-info'));

            document.getElementById('modal-data-abertura').innerText = data.data_abertura;
            document.getElementById('modal-criador').innerText = data.criador;

            const lista = document.getElementById('modal-historico-lista');
            lista.innerHTML = '';

            if (data.historico.length === 0) {
                lista.innerHTML = '<p style="color:var(--gray-500); font-size:13px;">Nenhuma ação registrada ainda.</p>';
            } else {
                data.historico.forEach(h => {
                    const html = `
                        <div class="historico-item">
                            <div class="historico-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div class="historico-content">
                                <strong>${h.acao}</strong>
                                <p>${h.detalhes}</p>
                                <div class="historico-meta">${h.usuario} (${h.setor}) &middot; ${h.data}</div>
                            </div>
                        </div>
                    `;
                    lista.innerHTML += html;
                });
            }
        })
        .catch(err => {
            console.error(err);
            document.getElementById('modal-historico-lista').innerHTML = '<p style="color:red; font-size:13px;">Erro ao carregar histórico.</p>';
        });
}

function fecharDetalhes() {
    document.getElementById('modalDetalhes').classList.remove('active');
}

document.getElementById('modalDetalhes').addEventListener('click', function(e) {
    if (e.target === this) fecharDetalhes();
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>