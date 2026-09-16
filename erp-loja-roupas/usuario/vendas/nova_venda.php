<?php
/**
 * usuario/vendas/nova_venda.php
 * Redireciona para o PDV (ponto de venda).
 */
require_once __DIR__ . '/../../includes/auth_check.php';
verificarAcesso();
header('Location: ../pdv.php');
exit;
