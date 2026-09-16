<?php
/**
 * auth/register.php
 * Cadastro de novos usuários — acesso EXCLUSIVO para administradores.
 * Redireciona para admin/funcionarios/cadastrar.php
 */
require_once __DIR__ . '/../includes/auth_check.php';
verificarAcesso('admin');
header('Location: ../admin/funcionarios/cadastrar.php');
exit;
