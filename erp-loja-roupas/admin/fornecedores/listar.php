<?php
/**
 * admin/fornecedores/listar.php
 * CRUD de Fornecedores — Listagem e ações.
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
verificarAcesso('admin');

if (!defined('BASE_URL')) define('BASE_URL', '../../');

$pdo   = getDB();
$msg   = $_GET['msg'] ?? '';
$busca = trim($_GET['busca'] ?? '');

// Exclusão de fornecedor
if (isset($_GET['excluir'])) {
    $idExcluir = (int)$_GET['excluir'];
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM produtos WHERE fornecedor_id = :id");
    $stmtCheck->execute([':id' => $idExcluir]);
    if ((int)$stmtCheck->fetchColumn() > 0) {
        $msg = 'erro_vinculado';
    } else {
        $pdo->prepare("DELETE FROM fornecedores WHERE id = :id")->execute([':id' => $idExcluir]);
        $msg = 'excluido';
    }
}

// Toggle ativo/inativo
if (isset($_GET['toggle'])) {
    $idToggle = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE fornecedores SET ativo = NOT ativo WHERE id = :id")->execute([':id' => $idToggle]);
    header('Location: listar.php?msg=atualizado');
    exit;
}

// Busca
$where  = $busca ? "WHERE (f.nome ILIKE :busca OR f.email ILIKE :busca OR f.cnpj ILIKE :busca)" : '';
$params = $busca ? [':busca' => "%{$busca}%"] : [];
$stmt   = $pdo->prepare("
    SELECT f.*, COUNT(p.id) AS qtd_produtos
    FROM fornecedores f
    LEFT JOIN produtos p ON p.fornecedor_id = f.id
    $where
    GROUP BY f.id
    ORDER BY f.nome ASC
");
$stmt->execute($params);
$fornecedores = $stmt->fetchAll();

$mensagens = [
    'criado'        => ['tipo'=>'success','texto'=>'Fornecedor cadastrado com sucesso!'],
    'editado'       => ['tipo'=>'success','texto'=>'Fornecedor atualizado com sucesso!'],
    'excluido'      => ['tipo'=>'success','texto'=>'Fornecedor excluído com sucesso!'],
    'atualizado'    => ['tipo'=>'success','texto'=>'Status do fornecedor atualizado!'],
    'erro_vinculado'=> ['tipo'=>'warning','texto'=>'Não é possível excluir: existem produtos vinculados a este fornecedor.'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fornecedores | ERP Moda</title>
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
            <div><div class="topbar-title">Fornecedores</div><div class="topbar-breadcrumb">Admin / Fornecedores</div></div>
            <div class="ms-auto">
                <a href="cadastrar.php" class="btn btn-primary btn-sm fw-semibold">
                    <i class="bi bi-plus-circle me-1"></i>Novo Fornecedor
                </a>
            </div>
        </div>
        <main class="p-4 flex-grow-1 fade-in">
            <div class="page-header"><h2><i class="bi bi-truck me-2 text-primary-custom"></i>Fornecedores</h2></div>

            <?php if ($msg && isset($mensagens[$msg])): ?>
                <div class="alert alert-<?= $mensagens[$msg]['tipo'] ?> alert-auto-close alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($mensagens[$msg]['texto']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Busca -->
            <div class="form-card mb-4">
                <form method="GET" class="row g-2">
                    <div class="col">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" name="busca"
                                   placeholder="Buscar por nome, CNPJ ou e-mail..."
                                   value="<?= htmlspecialchars($busca) ?>">
                        </div>
                    </div>
                    <div class="col-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Buscar</button>
                        <a href="listar.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                    </div>
                </form>
            </div>

            <div class="table-card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nome / CNPJ</th>
                                <th>Contato</th>
                                <th>Endereço</th>
                                <th class="text-center">Produtos</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($fornecedores)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">Nenhum fornecedor encontrado.</td></tr>
                            <?php else: ?>
                                <?php foreach ($fornecedores as $f): ?>
                                    <tr>
                                        <td class="text-muted small"><?= (int)$f['id'] ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($f['nome']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($f['cnpj'] ?? '-') ?></small>
                                        </td>
                                        <td>
                                            <div class="small"><?= htmlspecialchars($f['telefone'] ?? '-') ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($f['email'] ?? '-') ?></small>
                                        </td>
                                        <td class="text-muted small" style="max-width:200px;">
                                            <?= htmlspecialchars($f['endereco'] ?? '-') ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary bg-opacity-10 text-primary"><?= (int)$f['qtd_produtos'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?= $f['ativo']
                                                ? '<span class="badge bg-success">Ativo</span>'
                                                : '<span class="badge bg-secondary">Inativo</span>'
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="cadastrar.php?editar=<?= $f['id'] ?>"
                                                   class="btn btn-warning btn-action" title="Editar" data-bs-toggle="tooltip">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="listar.php?excluir=<?= $f['id'] ?>"
                                                   class="btn btn-danger btn-action" title="Excluir" data-bs-toggle="tooltip"
                                                   onclick="return confirmarExclusao('Excluir o fornecedor &quot;<?= addslashes($f['nome']) ?>&quot;?', this.href)">
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
        </main>
        <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmXKzlVzSwh/so7GXpTzm+O1oeN" crossorigin="anonymous"></script>
<script src="../../public/js/scripts.js"></script>
</body>
</html>
