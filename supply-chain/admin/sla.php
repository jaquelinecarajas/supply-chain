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
exigir_admin();

$agora = time();

$sql = "
    SELECT t.*, o.codigo as ordem_codigo, s.nome as setor_nome 
    FROM kanban_tarefas t
    JOIN ordens o ON o.id = t.ordem_id
    JOIN setores s ON s.id = t.setor_id
";
$tarefas = db()->query($sql)->fetchAll();

$stats = [
    'no_prazo' => 0,
    'em_risco' => 0,
    'atrasadas' => 0
];

$setores_atrasados = [];
$tarefas_criticas = [];

foreach ($tarefas as $t) {
    if (!$t['sla_horas']) {
        continue;
    }

    if ($t['coluna'] === 'concluido') {
        $stats['no_prazo']++;
        continue;
    }

    $inicio = $t['iniciado_em'] ? strtotime($t['iniciado_em']) : strtotime($t['criado_em']);
    $limite = $inicio + ($t['sla_horas'] * 3600);
    $tempo_total = $t['sla_horas'] * 3600;
    $tempo_decorrido = $agora - $inicio;
    
    if ($agora > $limite) {
        $stats['atrasadas']++;
        $setor_nome = ucfirst($t['setor_nome']);
        if (!isset($setores_atrasados[$setor_nome])) {
            $setores_atrasados[$setor_nome] = 0;
        }
        $setores_atrasados[$setor_nome]++;

        $horas_atraso = ceil(($agora - $limite) / 3600);
        $t['horas_atraso'] = $horas_atraso;
        $tarefas_criticas[] = $t;

    } elseif ($tempo_decorrido >= ($tempo_total * 0.8)) {
        $stats['em_risco']++;
    } else {
        $stats['no_prazo']++;
    }
}

usort($tarefas_criticas, function($a, $b) {
    return $b['horas_atraso'] <=> $a['horas_atraso'];
});

$labels_setores = json_encode(array_keys($setores_atrasados));
$data_setores = json_encode(array_values($setores_atrasados));

require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>Painel de SLA</h2>
        <p>Visão geral de prazos, gargalos por setor e tarefas críticas.</p>
    </div>
</div>

<div class="metric-grid">
    <div class="metric-card metric-success">
        <div class="metric-value"><?= $stats['no_prazo'] ?></div>
        <div class="metric-label">Tarefas no Prazo</div>
    </div>
    
    <div class="metric-card metric-warning">
        <div class="metric-value"><?= $stats['em_risco'] ?></div>
        <div class="metric-label">Em Risco (Quase Estourando)</div>
    </div>

    <div class="metric-card metric-danger">
        <div class="metric-value"><?= $stats['atrasadas'] ?></div>
        <div class="metric-label">Tarefas Atrasadas</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
    
    <div class="chart-container">
        <h3 class="chart-title">Gargalos por Setor (Atrasos)</h3>
        <?php if (empty($setores_atrasados)): ?>
            <div style="display: flex; align-items: center; justify-content: center; flex: 1; color: var(--gray-500);">
                Nenhuma tarefa atrasada no momento. Tudo sob controle!
            </div>
        <?php else: ?>
            <div style="position: relative; flex: 1; display: flex; justify-content: center; align-items: center; min-height: 250px;">
                <canvas id="graficoSetores"></canvas>
            </div>
        <?php endif; ?>
    </div>

    <div class="chart-container">
        <h3 class="chart-title">Resumo de Atrasos</h3>
        <?php if (empty($setores_atrasados)): ?>
            <div style="display: flex; align-items: center; justify-content: center; flex: 1; color: var(--gray-500);">
                Sem dados de atraso para exibir.
            </div>
        <?php else: ?>
            <div style="flex: 1;">
                <table style="width:100%; border-collapse: collapse;">
                    <?php foreach ($setores_atrasados as $setor => $qtd): ?>
                    <tr style="border-bottom: 1px solid var(--gray-200);">
                        <td style="padding: 14px 0; font-weight: 600; color: var(--primary);"><?= $setor ?></td>
                        <td style="padding: 14px 0; text-align: right;">
                            <span class="badge badge-danger"><?= $qtd ?> tarefas</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="padding: 0; overflow: hidden; border: 1px solid #fecaca;">
    <div style="padding: 20px 24px; background: #fef2f2; border-bottom: 1px solid #fecaca;">
        <h3 style="color: #991b1b; margin: 0; font-size: 16px; font-weight: 800;">Atenção Imediata: Tarefas Mais Atrasadas</h3>
    </div>
    <table class="card-table">
        <thead>
            <tr>
                <th>Código OP</th>
                <th>Tarefa</th>
                <th>Setor</th>
                <th>Atraso</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tarefas_criticas)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: var(--gray-500); padding: 32px;">
                        Nenhuma tarefa estourou o SLA. Excelente trabalho!
                    </td>
                </tr>
            <?php endif; ?>
            
            <?php foreach (array_slice($tarefas_criticas, 0, 5) as $t): ?>
            <tr>
                <td><strong><?= e($t['ordem_codigo']) ?></strong></td>
                <td><?= e($t['titulo']) ?></td>
                <td><span class="badge badge-info"><?= ucfirst(e($t['setor_nome'])) ?></span></td>
                <td><span class="badge badge-danger"><?= $t['horas_atraso'] ?> horas</span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (!empty($setores_atrasados)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('graficoSetores').getContext('2d');
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= $labels_setores ?>,
            datasets: [{
                data: <?= $data_setores ?>,
                backgroundColor: [
                    '#ef4444', '#f59e0b', '#3b82f6', '#8b5cf6', '#10b981', '#020035'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: { family: 'Inter', size: 12, weight: '600' },
                        padding: 20,
                        usePointStyle: true,
                        boxWidth: 8
                    }
                }
            },
            cutout: '75%'
        }
    });
});
</script>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>