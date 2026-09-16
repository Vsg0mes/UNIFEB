<?php
/**
 * includes/auth_check.php
 * Verifica autenticação e nível de acesso.
 * Deve ser incluído no topo de CADA página protegida.
 *
 * Uso:
 *   require_once __DIR__ . '/../includes/auth_check.php';
 *   verificarAcesso();             // qualquer usuário logado
 *   verificarAcesso('admin');      // somente admin
 */

// Inicia a sessão se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica se o usuário está autenticado e tem o perfil exigido.
 *
 * @param string|null $perfilExigido 'admin', 'usuario' ou null (qualquer autenticado)
 */
function verificarAcesso(?string $perfilExigido = null): void
{
    // Se não há sessão ativa, redireciona para login
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_perfil'])) {
        header('Location: ' . BASE_URL . '/auth/login.php?erro=sessao_expirada');
        exit;
    }

    // Verifica se o usuário está ativo (pode ter sido desativado pelo admin)
    if (isset($_SESSION['usuario_ativo']) && !$_SESSION['usuario_ativo']) {
        session_destroy();
        header('Location: ' . BASE_URL . '/auth/login.php?erro=conta_desativada');
        exit;
    }

    // Se um perfil específico é exigido, valida o perfil da sessão
    if ($perfilExigido !== null && $_SESSION['usuario_perfil'] !== $perfilExigido) {
        // Redireciona para o dashboard apropriado com mensagem de acesso negado
        if ($_SESSION['usuario_perfil'] === 'admin') {
            header('Location: ' . BASE_URL . '/admin/dashboard.php?erro=acesso_negado');
        } else {
            header('Location: ' . BASE_URL . '/usuario/dashboard.php?erro=acesso_negado');
        }
        exit;
    }
}

/**
 * Retorna o ID do usuário logado.
 * @return int
 */
function usuarioLogadoId(): int
{
    return (int)($_SESSION['usuario_id'] ?? 0);
}

/**
 * Retorna o nome do usuário logado.
 * @return string
 */
function usuarioLogadoNome(): string
{
    return htmlspecialchars($_SESSION['usuario_nome'] ?? 'Usuário');
}

/**
 * Retorna o perfil do usuário logado.
 * @return string 'admin' | 'usuario'
 */
function usuarioLogadoPerfil(): string
{
    return $_SESSION['usuario_perfil'] ?? 'usuario';
}

/**
 * Verifica se o usuário logado é admin.
 * @return bool
 */
function isAdmin(): bool
{
    return usuarioLogadoPerfil() === 'admin';
}

// Define a URL base da aplicação (usado nos redirects acima)
// Ajuste conforme seu ambiente
if (!defined('BASE_URL')) {
    define('BASE_URL', rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));
}
