<?php
// =============================================
// Funções utilitárias do sistema
// =============================================

if (!defined('SUPPLY_CHAIN')) {
    http_response_code(403);
    exit('Acesso negado.');
}

// ---------- SANITIZAÇÃO / SAÍDA ----------

/**
 * Escapa texto para exibir em HTML (evita XSS).
 */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Limpa espaços e remove tags de uma entrada de texto.
 */
function limpar(?string $valor): string
{
    return trim(strip_tags($valor ?? ''));
}

/**
 * Valida email.
 */
function email_valido(?string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// ---------- SENHA ----------

/**
 * Gera hash seguro (bcrypt) da senha.
 */
function hash_senha(string $senha): string
{
    return password_hash($senha, PASSWORD_BCRYPT);
}

/**
 * Verifica senha contra hash.
 */
function verificar_senha(string $senha, string $hash): bool
{
    return password_verify($senha, $hash);
}

/**
 * Gera senha temporária aleatória (ex: "Kp7#tR2m").
 * Sempre tem maiúscula, minúscula, número e símbolo.
 */
function gerar_senha_temporaria(int $tamanho = 10): string
{
    $minusculas = 'abcdefghijkmnpqrstuvwxyz';
    $maiusculas = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $numeros    = '23456789';
    $simbolos   = '#@$%&*!';
    $todos      = $minusculas . $maiusculas . $numeros . $simbolos;

    // Garante 1 de cada categoria
    $senha = $minusculas[random_int(0, strlen($minusculas) - 1)]
           . $maiusculas[random_int(0, strlen($maiusculas) - 1)]
           . $numeros[random_int(0, strlen($numeros) - 1)]
           . $simbolos[random_int(0, strlen($simbolos) - 1)];

    // Completa o resto
    for ($i = strlen($senha); $i < $tamanho; $i++) {
        $senha .= $todos[random_int(0, strlen($todos) - 1)];
    }

    // Embaralha
    return str_shuffle($senha);
}

// ---------- UPLOAD SEGURO ----------

/**
 * Valida e move um arquivo de upload.
 * Retorna ['ok' => bool, 'erro' => string, 'caminho' => string, 'nome_original' => string, 'mime' => string, 'tamanho' => int]
 *
 * @param array  $arquivo     $_FILES['campo']
 * @param string $tipo        'avatar' ou 'documento'
 * @param string $subpasta    'avatars' ou 'documentos'
 */
function processar_upload(array $arquivo, string $tipo, string $subpasta): array
{
    $resposta = ['ok' => false, 'erro' => '', 'caminho' => '', 'nome_original' => '', 'mime' => '', 'tamanho' => 0];

    // 1. Verifica erro de upload
    if (!isset($arquivo['error']) || $arquivo['error'] !== UPLOAD_ERR_OK) {
        $resposta['erro'] = 'Falha no upload do arquivo.';
        return $resposta;
    }

    // 2. Define limites por tipo
    if ($tipo === 'avatar') {
        $maxBytes   = AVATAR_MAX_BYTES;
        $extensoes  = AVATAR_EXTENSOES;
        $mimes      = AVATAR_MIMES;
    } else {
        $maxBytes   = DOC_MAX_BYTES;
        $extensoes  = DOC_EXTENSOES;
        $mimes      = DOC_MIMES;
    }

    // 3. Tamanho
    $tamanho = (int) $arquivo['size'];
    if ($tamanho <= 0 || $tamanho > $maxBytes) {
        $resposta['erro'] = 'Arquivo muito grande ou vazio (máx ' . round($maxBytes / 1024 / 1024, 1) . ' MB).';
        return $resposta;
    }

    // 4. Extensão (do nome original)
    $nomeOriginal = basename($arquivo['name']);
    $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
    if (!in_array($extensao, $extensoes, true)) {
        $resposta['erro'] = 'Extensão não permitida.';
        return $resposta;
    }

    // 5. MIME real (não confia na extensão)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeReal = $finfo->file($arquivo['tmp_name']) ?: '';
    if (!in_array($mimeReal, $mimes, true)) {
        $resposta['erro'] = 'Tipo de arquivo não permitido.';
        return $resposta;
    }

    // 6. Avatar: confirma que é imagem mesmo
    if ($tipo === 'avatar') {
        if (@getimagesize($arquivo['tmp_name']) === false) {
            $resposta['erro'] = 'Arquivo não é uma imagem válida.';
            return $resposta;
        }
    }

    // 7. Gera nome novo (nunca usa o original no disco)
    $novoNome = bin2hex(random_bytes(16)) . '.' . $extensao;
    $destinoDir = UPLOAD_PATH . '/' . $subpasta;
    $destino = $destinoDir . '/' . $novoNome;

    // 8. Garante que a pasta existe
    if (!is_dir($destinoDir) && !mkdir($destinoDir, 0775, true)) {
        $resposta['erro'] = 'Não foi possível criar a pasta de destino.';
        return $resposta;
    }

    // 9. Move
    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        $resposta['erro'] = 'Falha ao salvar o arquivo no servidor.';
        return $resposta;
    }

    // Sucesso
    $resposta['ok'] = true;
    $resposta['caminho'] = $subpasta . '/' . $novoNome;     // caminho relativo ao upload
    $resposta['nome_original'] = $nomeOriginal;
    $resposta['mime'] = $mimeReal;
    $resposta['tamanho'] = $tamanho;

    return $resposta;
}

// ---------- FLASH MESSAGES (mensagens temporárias) ----------

/**
 * Define mensagem flash para próxima página.
 */
function flash(string $tipo, string $msg): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'msg' => $msg];
}

/**
 * Pega e limpa mensagens flash.
 */
function pegar_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ---------- OUTROS ----------

/**
 * Redireciona e encerra.
 */
function redirecionar(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Registra uma ação no histórico da ordem.
 */
function registrar_historico(int $ordem_id, int $usuario_id, string $acao, string $detalhes = ''): void
{
    try {
        $sql = "INSERT INTO historico_ordens (ordem_id, usuario_id, acao, detalhes) VALUES (?, ?, ?, ?)";
        db()->prepare($sql)->execute([$ordem_id, $usuario_id, $acao, $detalhes]);
    } catch (Exception $e) {
        error_log("Erro ao registrar histórico: " . $e->getMessage());
    }
}