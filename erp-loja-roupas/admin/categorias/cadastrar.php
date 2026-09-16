<?php
/**
 * admin/categorias/cadastrar.php
 * Redirecionamento para a página de listagem que integra o formulário.
 */
require_once __DIR__ . '/../../includes/auth_check.php';
verificarAcesso('admin');
header('Location: listar.php');
exit;
