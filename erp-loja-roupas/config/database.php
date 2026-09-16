<?php
/**
 * config/database.php
 * Conexão PDO com PostgreSQL
 * Lê variáveis de ambiente do arquivo .env (carregado no início da aplicação)
 * ou usa valores padrão para desenvolvimento local.
 */

// -------------------------------------------------------
// Carrega variáveis do arquivo .env se existir
// -------------------------------------------------------
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignora linhas de comentário
        if (str_starts_with(trim($line), '#')) continue;
        // Extrai chave=valor e define como variável de ambiente
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// -------------------------------------------------------
// Parâmetros de conexão (lidos do .env ou com fallback)
// -------------------------------------------------------
define('DB_HOST',   $_ENV['DB_HOST']   ?? 'localhost');
define('DB_PORT',   $_ENV['DB_PORT']   ?? '5432');
define('DB_NAME',   $_ENV['DB_NAME']   ?? 'erp_loja_roupas');
define('DB_USER',   $_ENV['DB_USER']   ?? 'postgres');
define('DB_PASS',   $_ENV['DB_PASS']   ?? '');
define('DB_CHARSET',                      'utf8');

/**
 * Retorna uma instância PDO conectada ao PostgreSQL.
 * Usa padrão Singleton para reutilizar a mesma conexão na requisição.
 *
 * @return PDO
 * @throws PDOException Se a conexão falhar
 */
function getDB(): PDO
{
    static $pdo = null; // Singleton — uma conexão por ciclo de requisição

    if ($pdo === null) {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;options=--client_encoding=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Lança exceção em erros
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Retorna arrays associativos
                PDO::ATTR_EMULATE_PREPARES   => false,                   // Prepared statements reais (segurança)
            ]);
        } catch (PDOException $e) {
            // Em produção, nunca exiba detalhes do erro ao usuário final
            error_log('Erro de conexão com o banco: ' . $e->getMessage());
            die(json_encode([
                'erro' => true,
                'mensagem' => 'Falha na conexão com o banco de dados. Contate o administrador.'
            ]));
        }
    }

    return $pdo;
}
