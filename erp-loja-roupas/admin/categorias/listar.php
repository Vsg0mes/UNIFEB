<?php
/**
 * admin/categorias/listar.php
 * CRUD de Categorias — Listagem com edição inline e exclusão.
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
verificarAcesso('admin');

if (!defined('BASE_URL')) define('BASE_URL', '../../');

$pdo   = getDB();
$erros = [];
$msg   = $_GET['msg'] ?? '';

// -----------------------------------------------------------
// Exclusão de categoria
// -----------------------------------------------------------
if (isset($_GET['excluir'])) {
    $idExcluir = (int)$_GET['excluir'];
    // Verifica se há produtos vinculados
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM produtos WHERE categoria_id = :id");
    $stmtCheck->execute([':id' => $idExcluir]);
    if ((int)$stmtCheck->fetchColumn() > 0) {
        $msg = 'erro_vinculado';
    } else {
        $pdo->prepare("DELETE FROM categorias WHERE id = :id")->execute([':id' => $idExcluir]);
        $msg = 'excluido';
    }
}

// -----------------------------------------------------------
// Processamento POST (criação de nova categoria)
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome      = trim($_POST['nome']      ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    if (empty($nome)) {
        $erros[] = 'O nome da categoria é obrigatório.';
    } else {
        // Verifica nome duplicado
        $stmtDup = $pdo->prepare("SELECT id FROM categorias WHERE nome ILIKE :nome LIMIT 1");
        $stmtDup->execute([':nome' => $nome]);
        if ($stmtDup->fetch()) {
            $erros[] = 'Já existe uma categoria com este nome.';
        }
    }

    if (empty($erros)) {
        $stmt = $pdo->prepare("INSERT INTO categorias (nome, descricao) VALUES (:nome, :desc)");
        $stmt->execute([':nome' => $nome, ':desc' => $descricao ?: null]);
        header('Location: listar.php?msg=criado');
        exit;
    }
}

// Carrega todas as categorias com contagem de produtos
$categorias = $pdo->query("
    SELECT c.id, c.nome, c.descricao, COUNT(p.id) AS qtd_produtos
    FROM categorias c
    LEFT JOIN produtos p ON p.categoria_id = c.id
    GROUP BY c.id, c.nome, c.descricao
    ORDER BY c.nome
")->fetchAll();

$mensagens = [
    'criado'        => ['tipo' => 'success', 'texto' => 'Categoria criada com sucesso!'],
    'editado'       => ['tipo' => 'success', 'texto' => 'Categoria atualizada com sucesso!'],
    'excluido'      => ['tipo' => 'success', 'texto' => 'Categoria excluída com sucesso!'],
    'erro_vinculado'=> ['tipo' => 'warning', 'texto' => 'Não é possível excluir: existem produtos vinculados a esta categoria.'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias | ERP Moda</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body>
<div id="sidebarOverlay" class="sidebar-overlay"></div>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    <div class="content-wrapper">
        <div class="topbar">
            <button class="btn btn-link text-dark d-lg-none me-2 p-0" id="sidebarToggleBtn"><i class="bi bi-list fs-4"></i></button>
            <div><div class="topbar-title">Categorias</div><div class="topbar-breadcrumb">Admin / Categorias</div></div>
            <div class="ms-auto">
                <a href="cadastrar.php" class="btn btn-primary btn-sm fw-semibold">
                    <i class="bi bi-plus-circle me-1"></i>Nova Categoria
                </a>
            </div>
        </div>
        <main class="p-4 flex-grow-1 fade-in">
            <div class="page-header"><h2><i class="bi bi-tag me-2 text-primary-custom"></i>Categorias</h2></div>

            <?php if ($msg && isset($mensagens[$msg])): ?>
                <div class="alert alert-<?= $mensagens[$msg]['tipo'] ?> alert-auto-close alert-dismissible fade show">
                    <i class="bi bi-<?= $mensagens[$msg]['tipo'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill me-2"></i>
                    <?= htmlspecialchars($mensagens[$msg]['texto']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($erros as $e): ?><div><i class="bi bi-x-circle me-1"></i><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Formulário de nova categoria -->
                <div class="col-12 col-md-4">
                    <div class="form-card">
                        <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2 text-primary-custom"></i>Nova Categoria</h6>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nome" name="nome"
                                       placeholder="Ex: Feminino, Masculino..." required>
                            </div>
                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição</label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="2"
                                          placeholder="Descrição opcional..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-floppy me-1"></i>Salvar Categoria
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Listagem de categorias -->
                <div class="col-12 col-md-8">
                    <div class="table-card">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nome</th>
                                        <th>Descrição</th>
                                        <th class="text-center">Produtos</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categorias)): ?>
                                        <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma categoria cadastrada.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($categorias as $cat): ?>
                                            <tr>
                                                <td class="text-muted small"><?= (int)$cat['id'] ?></td>
                                                <td class="fw-semibold"><?= htmlspecialchars($cat['nome']) ?></td>
                                                <td class="text-muted small"><?= htmlspecialchars($cat['descricao'] ?? '-') ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold">
                                                        <?= (int)$cat['qtd_produtos'] ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <a href="cadastrar.php?editar=<?= $cat['id'] ?>"
                                                           class="btn btn-warning btn-action"
                                                           data-bs-toggle="tooltip" title="Editar">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <a href="listar.php?excluir=<?= $cat['id'] ?>"
                                                           class="btn btn-danger btn-action"
                                                           data-bs-toggle="tooltip" title="Excluir"
                                                           onclick="return confirmarExclusao('Excluir a categoria &quot;<?= addslashes($cat['nome']) ?>&quot;?', this.href)">
                                                            <i class="bi bi-trash3"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmXKzlVzSwh/so7GXpTzm+O1oeN" crossorigin="anonymous"></script>
<script src="../../public/js/scripts.js"></script>
</body>
</html>
