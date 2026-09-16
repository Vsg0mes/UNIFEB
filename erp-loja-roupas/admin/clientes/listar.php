<?php
/**
 * admin/clientes/listar.php
 * CRUD de Clientes.
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
verificarAcesso('admin');

if (!defined('BASE_URL')) define('BASE_URL', '../../');

$pdo   = getDB();
$msg   = $_GET['msg'] ?? '';
$busca = trim($_GET['busca'] ?? '');

// Exclusão
if (isset($_GET['excluir'])) {
    $idExcluir = (int)$_GET['excluir'];
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM vendas WHERE cliente_id = :id");
    $stmtCheck->execute([':id' => $idExcluir]);
    if ((int)$stmtCheck->fetchColumn() > 0) {
        $msg = 'erro_vinculado';
    } else {
        $pdo->prepare("DELETE FROM clientes WHERE id = :id")->execute([':id' => $idExcluir]);
        $msg = 'excluido';
    }
}

// Lista clientes
$where  = $busca ? "WHERE (c.nome ILIKE :busca OR c.cpf ILIKE :busca OR c.email ILIKE :busca)" : '';
$params = $busca ? [':busca' => "%{$busca}%"] : [];

$stmt = $pdo->prepare("
    SELECT c.*, COUNT(v.id) AS qtd_compras, COALESCE(SUM(v.valor_total - v.desconto), 0) AS total_gasto
    FROM clientes c
    LEFT JOIN vendas v ON v.cliente_id = c.id AND v.status = 'concluida'
    $where
    GROUP BY c.id
    ORDER BY c.nome ASC
");
$stmt->execute($params);
$clientes = $stmt->fetchAll();

$mensagens = [
    'criado'        => ['tipo'=>'success','texto'=>'Cliente cadastrado com sucesso!'],
    'editado'       => ['tipo'=>'success','texto'=>'Cliente atualizado com sucesso!'],
    'excluido'      => ['tipo'=>'success','texto'=>'Cliente excluído com sucesso!'],
    'erro_vinculado'=> ['tipo'=>'warning','texto'=>'Não é possível excluir: cliente possui compras registradas.'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes | ERP Moda</title>
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
            <div><div class="topbar-title">Clientes</div><div class="topbar-breadcrumb">Admin / Clientes</div></div>
            <div class="ms-auto">
                <a href="cadastrar.php" class="btn btn-primary btn-sm fw-semibold">
                    <i class="bi bi-plus-circle me-1"></i>Novo Cliente
                </a>
            </div>
        </div>
        <main class="p-4 flex-grow-1 fade-in">
            <div class="page-header"><h2><i class="bi bi-people me-2 text-primary-custom"></i>Clientes</h2></div>

            <?php if ($msg && isset($mensagens[$msg])): ?>
                <div class="alert alert-<?= $mensagens[$msg]['tipo'] ?> alert-auto-close alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($mensagens[$msg]['texto']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="form-card mb-4">
                <form method="GET" class="row g-2">
                    <div class="col"><div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" name="busca" placeholder="Nome, CPF ou e-mail..." value="<?= htmlspecialchars($busca) ?>">
                    </div></div>
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
                                <th>Nome / CPF</th>
                                <th>Contato</th>
                                <th class="text-center">Compras</th>
                                <th class="text-end">Total Gasto</th>
                                <th>Cadastro</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($clientes)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">Nenhum cliente encontrado.</td></tr>
                            <?php else: ?>
                                <?php foreach ($clientes as $c): ?>
                                    <tr>
                                        <td class="text-muted small"><?= (int)$c['id'] ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($c['nome']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($c['cpf'] ?? '-') ?></small>
                                        </td>
                                        <td>
                                            <div class="small"><?= htmlspecialchars($c['telefone'] ?? '-') ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($c['email'] ?? '-') ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info bg-opacity-15 text-info fw-semibold"><?= (int)$c['qtd_compras'] ?></span>
                                        </td>
                                        <td class="text-end fw-semibold text-success">
                                            R$ <?= number_format($c['total_gasto'], 2, ',', '.') ?>
                                        </td>
                                        <td class="text-muted small"><?= date('d/m/Y', strtotime($c['data_cadastro'])) ?></td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="cadastrar.php?editar=<?= $c['id'] ?>" class="btn btn-warning btn-action" title="Editar" data-bs-toggle="tooltip"><i class="bi bi-pencil"></i></a>
                                                <a href="listar.php?excluir=<?= $c['id'] ?>" class="btn btn-danger btn-action" title="Excluir" data-bs-toggle="tooltip"
                                                   onclick="return confirmarExclusao('Excluir o cliente &quot;<?= addslashes($c['nome']) ?>&quot;?', this.href)">
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
