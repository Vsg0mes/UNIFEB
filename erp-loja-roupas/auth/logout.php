<?php
/**
 * auth/logout.php
 * Encerra a sessão do usuário de forma segura.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpa todos os dados da sessão
$_SESSION = [];

// Invalida o cookie de sessão
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Destrói a sessão no servidor
session_destroy();

// Redireciona para login com mensagem de sucesso
header('Location: ../auth/login.php?sucesso=logout');
exit;
