<?php
/**
 * index.php
 * Ponto de entrada da aplicação.
 * Redireciona automaticamente baseado no status da sessão e perfil do usuário.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se já há sessão ativa, redireciona para o dashboard correto
if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_perfil'])) {
    if ($_SESSION['usuario_perfil'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: usuario/dashboard.php');
    }
    exit;
}

// Caso contrário, redireciona para login
header('Location: auth/login.php');
exit;
