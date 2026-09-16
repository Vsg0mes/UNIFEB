<?php
/**
 * admin/produtos/excluir.php
 * Exclui (ou desativa) um produto. Redireciona imediatamente com status.
 * Se o produto tiver histórico de vendas, é desativado ao invés de excluído.
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
verificarAcesso('admin');

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: listar.php?msg=erro&tipo=erro');
    exit;
}

// Verifica se o produto tem histórico de vendas (não pode ser excluído fisicamente)
$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM venda_itens WHERE produto_id = :id");
$stmtCheck->execute([':id' => $id]);
$temVendas = (int)$stmtCheck->fetchColumn() > 0;

if ($temVendas) {
    // Apenas desativa o produto (exclusão lógica)
    $stmt = $pdo->prepare("UPDATE produtos SET ativo = FALSE WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header('Location: listar.php?msg=desativado');
} else {
    // Exclusão física (sem histórico de vendas)
    $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header('Location: listar.php?msg=excluido');
}
exit;
