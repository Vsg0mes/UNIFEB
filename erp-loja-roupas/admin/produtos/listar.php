<?php
/**
 * admin/produtos/listar.php
 * Lista todos os produtos com paginação, filtros e ações de CRUD.
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
verificarAcesso('admin');

if (!defined('BASE_URL')) define('BASE_URL', '../../');

$pdo = getDB();

// -----------------------------------------------------------
// Filtros e paginação
// -----------------------------------------------------------
$busca        = trim($_GET['busca']       ?? '');
$filtroCateg  = (int)($_GET['categoria'] ?? 0);
$filtroStatus = $_GET['status']           ?? 'ativos'; // ativos | inativos | todos
$filtroEstoque = $_GET['filtro']          ?? '';

$pagina      = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina   = 15;
$offset      = ($pagina - 1) * $porPagina;

// Monta condições WHERE dinâmicas
$where   = [];
$params  = [];

if ($filtroStatus === 'ativos')   { $where[] = 'p.ativo = TRUE';  }
if ($filtroStatus === 'inativos') { $where[] = 'p.ativo = FALSE'; }
if ($filtroEstoque === 'estoque_baixo') { $where[] = 'p.quantidade_estoque <= p.estoque_minimo'; }
if ($filtroCateg > 0) {
    $where[] = 'p.categoria_id = :categoria_id';
    $params[':categoria_id'] = $filtroCateg;
}
if ($busca !== '') {
    $where[] = '(p.nome ILIKE :busca OR p.codigo_barras ILIKE :busca)';
    $params[':busca'] = "%{$busca}%";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Total de registros para paginação
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM produtos p $whereSQL");
$stmtTotal->execute($params);
$totalRegistros = (int)$stmtTotal->fetchColumn();
$totalPaginas   = (int)ceil($totalRegistros / $porPagina);

// Busca paginada
$paramsLimitados = $params;
$paramsLimitados[':limit']  = $porPagina;
$paramsLimitados[':offset'] = $offset;

$sql = "
    SELECT p.*, c.nome AS categoria_nome, f.nome AS fornecedor_nome
    FROM produtos p
    LEFT JOIN categorias  c ON c.id = p.categoria_id
    LEFT JOIN fornecedores f ON f.id = p.fornecedor_id
    $whereSQL
    ORDER BY p.nome ASC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':limit',  $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,    PDO::PARAM_INT);
$stmt->execute();
$produtos = $stmt->fetchAll();

// Categorias para o filtro
$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();

// Mensagem de feedback (após ações)
$msg     = $_GET['msg']  ?? '';
$msgTipo = $_GET['tipo'] ?? 'success';
$mensagens = [
    'criado'    => 'Produto cadastrado com sucesso!',
    'editado'   => 'Produto atualizado com sucesso!',
    'excluido'  => 'Produto excluído com sucesso!',
    'ativado'   => 'Produto ativado com sucesso!',
    'desativado'=> 'Produto desativado com sucesso!',
    'erro'      => 'Ocorreu um erro. Tente novamente.',
];
$pageTitle = 'Produtos';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos | ERP Moda</title>
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
            <div>
                <div class="topbar-title">Produtos</div>
                <div class="topbar-breadcrumb"><i class="bi bi-house me-1"></i>Admin / Produtos</div>
            </div>
            <div class="ms-auto">
                <a href="cadastrar.php" class="btn btn-primary btn-sm fw-semibold">
                    <i class="bi bi-plus-circle me-1"></i>Novo Produto
                </a>
            </div>
        </div>

        <main class="p-4 flex-grow-1 fade-in">

            <?php if ($msg && isset($mensagens[$msg])): ?>
                <div class="alert alert-<?= $msgTipo === 'erro' ? 'danger' : 'success' ?> alert-auto-close alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?= $msgTipo === 'erro' ? 'x-circle' : 'check-circle' ?>-fill me-2"></i>
                    <?= htmlspecialchars($mensagens[$msg]) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="page-header">
                <h2><i class="bi bi-bag me-2 text-primary-custom"></i>Produtos</h2>
            </div>

            <!-- Filtros -->
            <div class="form-card mb-4">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label">Buscar</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" name="busca" placeholder="Nome ou código de barras..."
                                   value="<?= htmlspecialchars($busca) ?>">
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Categoria</label>
                        <select class="form-select" name="categoria">
                            <option value="0">Todas as categorias</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $filtroCateg == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="ativos"   <?= $filtroStatus === 'ativos'   ? 'selected' : '' ?>>Ativos</option>
                            <option value="inativos" <?= $filtroStatus === 'inativos' ? 'selected' : '' ?>>Inativos</option>
                            <option value="todos"    <?= $filtroStatus === 'todos'    ? 'selected' : '' ?>>Todos</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-search me-1"></i>Filtrar
                        </button>
                        <a href="listar.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Tabela de produtos -->
            <div class="table-card">
                <div class="d-flex align-items-center justify-content-between p-3 pb-0">
                    <small class="text-muted">
                        <strong><?= $totalRegistros ?></strong> produto(s) encontrado(s)
                    </small>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Produto</th>
                                <th>Categoria</th>
                                <th>Tamanho/Cor</th>
                                <th class="text-end">P. Custo</th>
                                <th class="text-end">P. Venda</th>
                                <th class="text-center">Estoque</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($produtos)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        Nenhum produto encontrado.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($produtos as $p): ?>
                                    <tr>
                                        <td class="text-muted small"><?= (int)$p['id'] ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($p['nome']) ?></div>
                                            <?php if ($p['codigo_barras']): ?>
                                                <small class="text-muted"><i class="bi bi-upc me-1"></i><?= htmlspecialchars($p['codigo_barras']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary bg-opacity-10 text-dark">
                                                <?= htmlspecialchars($p['categoria_nome'] ?? '-') ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted">
                                            <?= htmlspecialchars($p['tamanho'] ?? '-') ?> /
                                            <?= htmlspecialchars($p['cor'] ?? '-') ?>
                                        </td>
                                        <td class="text-end text-muted small">
                                            R$ <?= number_format($p['preco_custo'], 2, ',', '.') ?>
                                        </td>
                                        <td class="text-end fw-semibold text-success">
                                            R$ <?= number_format($p['preco_venda'], 2, ',', '.') ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($p['quantidade_estoque'] <= $p['estoque_minimo']): ?>
                                                <span class="badge bg-danger" data-bs-toggle="tooltip"
                                                      title="Estoque mínimo: <?= $p['estoque_minimo'] ?>">
                                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                                    <?= (int)$p['quantidade_estoque'] ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success fw-semibold">
                                                    <?= (int)$p['quantidade_estoque'] ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($p['ativo']): ?>
                                                <span class="badge bg-success">Ativo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inativo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="editar.php?id=<?= $p['id'] ?>"
                                                   class="btn btn-warning btn-action"
                                                   data-bs-toggle="tooltip" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="excluir.php?id=<?= $p['id'] ?>"
                                                   class="btn btn-danger btn-action"
                                                   data-bs-toggle="tooltip" title="Excluir"
                                                   onclick="return confirmarExclusao('Excluir o produto &quot;<?= addslashes($p['nome']) ?>&quot;?', this.href)">
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

                <!-- Paginação -->
                <?php if ($totalPaginas > 1): ?>
                    <div class="d-flex justify-content-center p-3">
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                    <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                        <a class="page-link"
                                           href="?pagina=<?= $i ?>&busca=<?= urlencode($busca) ?>&categoria=<?= $filtroCateg ?>&status=<?= $filtroStatus ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>

        </main>
        <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmXKzlVzSwh/so7GXpTzm+O1oeN" crossorigin="anonymous"></script>
<script src="../../public/js/scripts.js"></script>
</body>
</html>
