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

// Busca dados frescos do banco
$stmt = db()->prepare("SELECT u.*, s.nome as setor_nome FROM usuarios u LEFT JOIN setores s ON s.id = u.setor_id WHERE u.id = ?");
$stmt->execute([$usuario['id']]);
$dados = $stmt->fetch();

// Formata a data de cadastro
$data_cadastro = date('d/m/Y', strtotime($dados['criado_em']));

require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>Meu Perfil</h2>
        <p>Gerencie suas informações pessoais, foto e segurança.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 300px 1fr; gap: 24px; align-items: start;">
    
    <!-- COLUNA ESQUERDA: CARTÃO DE IDENTIDADE -->
    <div class="card" style="text-align: center; padding: 32px 24px;">
        <form method="post" action="<?= BASE_URL ?>/perfil/atualizar.php" enctype="multipart/form-data" style="display: flex; flex-direction: column; align-items: center;">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="atualizar_foto">
            
            <!-- FOTO -->
            <div style="width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--accent)); display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 16px; box-shadow: var(--shadow-md); position: relative;">
                <?php if ($dados['foto_perfil']): ?>
                    <img src="<?= UPLOAD_URL . '/' . e($dados['foto_perfil']) ?>" alt="Foto" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <span style="font-size: 48px; color: #fff; font-weight: 700;"><?= strtoupper(substr($dados['nome'], 0, 1)) ?></span>
                <?php endif; ?>
            </div>

            <h3 style="font-size: 20px; font-weight: 800; color: var(--primary); margin-bottom: 4px;"><?= e($dados['nome']) ?></h3>
            <p style="color: var(--gray-500); font-size: 14px; margin-bottom: 12px;"><?= e($dados['email']) ?></p>
            
            <span class="badge <?= $dados['is_admin'] ? 'badge-danger' : 'badge-info' ?>" style="margin-bottom: 24px;">
                <?= $dados['is_admin'] ? 'Administrador' : ucfirst(e($dados['setor_nome'])) ?>
            </span>

            <!-- INPUT DE FOTO -->
            <label for="foto" style="cursor: pointer; color: var(--accent); font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                Alterar Foto
            </label>
            <input type="file" name="foto" id="foto" accept="image/jpeg, image/png, image/webp" style="display: none;" onchange="this.form.submit()">
        </form>
    </div>

    <!-- COLUNA DIREITA: ABAS -->
    <div class="card" style="padding: 0;">
        <!-- Menu de Abas -->
        <div style="display: flex; border-bottom: 1px solid var(--gray-200);">
            <button class="tab-btn active" onclick="openTab('dados')">Meus Dados</button>
            <button class="tab-btn" onclick="openTab('senha')">Segurança</button>
        </div>

        <!-- CONTEÚDO DAS ABAS -->
        <div style="padding: 32px;">
            
            <!-- ABA 1: DADOS -->
            <div id="tab-dados" class="tab-content" style="display: block;">
                <h3 style="margin-bottom: 24px; font-size: 18px;">Informações do Colaborador</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: var(--gray-500); text-transform: uppercase;">Nome Completo</label>
                        <div style="padding: 12px; background: var(--gray-50); border-radius: 8px; font-weight: 600; margin-top: 4px;"><?= e($dados['nome']) ?></div>
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: var(--gray-500); text-transform: uppercase;">E-mail Corporativo</label>
                        <div style="padding: 12px; background: var(--gray-50); border-radius: 8px; font-weight: 600; margin-top: 4px;"><?= e($dados['email']) ?></div>
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: var(--gray-500); text-transform: uppercase;">Setor</label>
                        <div style="padding: 12px; background: var(--gray-50); border-radius: 8px; font-weight: 600; margin-top: 4px;"><?= $dados['setor_nome'] ? ucfirst(e($dados['setor_nome'])) : 'Administração Geral' ?></div>
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: var(--gray-500); text-transform: uppercase;">Data de Admissão (Cadastro)</label>
                        <div style="padding: 12px; background: var(--gray-50); border-radius: 8px; font-weight: 600; margin-top: 4px;"><?= $data_cadastro ?></div>
                    </div>
                </div>

                <div style="background: #eff6ff; border-left: 4px solid var(--info); padding: 16px; border-radius: 6px; margin-top: 32px;">
                    <p style="margin: 0; color: #1e40af; font-size: 13px;">
                        <strong>Nota:</strong> Para alterar seu Nome, E-mail ou Setor, entre em contato com o Administrador do sistema.
                    </p>
                </div>
            </div>

            <!-- ABA 2: SEGURANÇA (Trocar Senha) -->
            <div id="tab-senha" class="tab-content" style="display: none;">
                <h3 style="margin-bottom: 24px; font-size: 18px;">Alterar Senha de Acesso</h3>
                
                <form method="post" action="<?= BASE_URL ?>/perfil/atualizar.php" style="max-width: 500px; display: flex; flex-direction: column; gap: 16px;">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="acao" value="trocar_senha">

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Senha Atual</label>
                        <input type="password" name="senha_atual" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-200); border-radius: 8px;">
                    </div>
                    
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nova Senha (mín. 8 caracteres)</label>
                        <input type="password" name="nova_senha" required minlength="8" style="width: 100%; padding: 12px; border: 1px solid var(--gray-200); border-radius: 8px;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Confirmar Nova Senha</label>
                        <input type="password" name="confirmar_senha" required minlength="8" style="width: 100%; padding: 12px; border: 1px solid var(--gray-200); border-radius: 8px;">
                    </div>

                    <button type="submit" class="btn-primary" style="margin-top: 10px; justify-content: center;">
                        Salvar Nova Senha
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
function openTab(tabName) {
    // Esconde todos os conteúdos
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    // Tira o active de todos os botões
    document.querySelectorAll('.tab-btn').forEach(el => {
        el.classList.remove('active');
        el.style.color = 'var(--gray-500)';
        el.style.borderBottom = 'none';
    });

    // Mostra o conteúdo clicado
    document.getElementById('tab-' + tabName).style.display = 'block';
    
    // Ativa o botão clicado
    event.currentTarget.classList.add('active');
    event.currentTarget.style.color = 'var(--primary)';
    event.currentTarget.style.borderBottom = '2px solid var(--primary)';
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>