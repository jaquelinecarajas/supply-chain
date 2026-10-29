<?php
// =============================================
// Conexão com o banco de dados (MySQL)
// =============================================

// Impede acesso direto ao arquivo via URL
if (!defined('SUPPLY_CHAIN')) {
    http_response_code(403);
    exit('Acesso negado.');
}

// Configurações do banco (XAMPP padrão: user=root, senha vazia)
define('DB_HOST', 'localhost');
define('DB_NAME', 'supply_chain');
define('DB_USER', 'root');
define('DB_PASS', '');       // XAMPP padrão não tem senha
define('DB_CHARSET', 'utf8mb4');

/**
 * Retorna uma conexão PDO com o banco.
 * Usa singleton: abre só uma vez por requisição.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $opcoes = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opcoes);
        } catch (PDOException $e) {
            // Em produção: logar e mostrar mensagem genérica
            error_log('Erro DB: ' . $e->getMessage());
            http_response_code(500);
            exit('Erro ao conectar ao banco de dados.');
        }
    }
    return $pdo;
}