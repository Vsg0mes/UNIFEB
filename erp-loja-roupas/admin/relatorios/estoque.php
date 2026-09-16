<?php
/**
 * admin/relatorios/estoque.php
 * Relatório de posição de estoque atual com alertas de produtos críticos.
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
verificarAcesso('admin');

if (!defined('BASE_URL')) define('BASE_URL', '../../');

$pdo = getDB();

$filtroCateg  = (int)($_GET['categoria'] ?? 0);
$filtroStatus = $_GET['status'] ?? 'todos'; // todos | critico | ok

$where  = ['p.ativo = TRUE'];
$params = [];
if ($filtroCateg > 0) { $where[] = 'p.categoria_id = :cat'; $params[':cat'] = $filtroCateg; }
if ($filtroStatus === 'critico') { $where[] = 'p.quantidade_estoque <= p.estoque_minimo'; }
if ($filtroStatus === 'ok')      { $where[] = 'p.quantidade_estoque > p.estoque_minimo'; }

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT p.id, p.nome, p.tamanho, p.cor, p.codigo_barras,
           p.quantidade_estoque, p.estoque_minimo, p.preco_custo, p.preco_venda,
           c.nome AS categoria, f.nome AS fornecedor,
           (p.quantidade_estoque * p.preco_custo) AS valor_custo_estoque,
           (p.quantidade_estoque * p.preco_venda) AS valor_venda_estoque
    FROM produtos p
    LEFT JOIN categorias  c ON c.id = p.categoria_id
    LEFT JOIN fornecedores f ON f.id = p.fornecedor_id
    $whereSQL
    ORDER BY p.quantidade_estoque ASC, p.nome ASC
");
$stmt->execute($params);
$produtos = $stmt->fetchAll();

// Totais
$totalItens  = count($produtos);
$totalCritico = count(array_filter($produtos, fn($p) => $p['quantidade_estoque'] <= $p['estoque_minimo']));
$totalValorCusto = array_sum(array_column($produtos, 'valor_custo_estoque'));
$totalValorVenda = array_sum(array_column($produtos, 'valor_venda_estoque'));

$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Estoque | ERP Moda</title>
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
            <div><div class="topbar-title">Relatório de Estoque</div></div>
            <div class="ms-auto">
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-printer me-1"></i>Imprimir
                </button>
            </div>
        </div>
        <main class="p-4 flex-grow-1 fade-in">
            <div class="page-header"><h2><i class="bi bi-clipboard-data me-2 text-primary-custom"></i>Posição de Estoque</h2></div>

            <!-- KPIs -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-label">Total de SKUs</div>
                        <div class="kpi-value"><?= $totalItens ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card <?= $totalCritico > 0 ? 'kpi-danger' : '' ?>">
                        <div class="kpi-label">Produtos Críticos</div>
                        <div class="kpi-value"><?= $totalCritico ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card kpi-warning">
                        <div class="kpi-label">Valor (Custo)</div>
                        <div class="kpi-value" style="font-size:1.2rem;">R$ <?= number_format($totalValorCusto, 2, ',', '.') ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card kpi-success">
                        <div class="kpi-label">Valor (Venda)</div>
                        <div class="kpi-value" style="font-size:1.2rem;">R$ <?= number_format($totalValorVenda, 2, ',', '.') ?></div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="form-card mb-4">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label">Categoria</label>
                        <select class="form-select form-select-sm" name="categoria">
                            <option value="0">Todas</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $filtroCateg == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Situação</label>
                        <select class="form-select form-select-sm" name="status">
                            <option value="todos"   <?= $filtroStatus === 'todos'   ? 'selected' : '' ?>>Todos</option>
                            <option value="critico" <?= $filtroStatus === 'critico' ? 'selected' : '' ?>>Críticos</option>
                            <option value="ok"      <?= $filtroStatus === 'ok'      ? 'selected' : '' ?>>OK</option>
                        </select>
                    </div>
                    <div class="col-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filtrar</button>
                        <a href="estoque.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
                    </div>
                </form>
            </div>

            <div class="table-card">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Categoria</th>
                                <th>Tam./Cor</th>
                                <th class="text-center">Estoque</th>
                                <th class="text-center">Mínimo</th>
                                <th class="text-end">P. Custo</th>
                                <th class="text-end">P. Venda</th>
                                <th class="text-end">Vl. Estoque</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($produtos)): ?>
                                <tr><td colspan="9" class="text-center text-muted py-4">Nenhum produto encontrado.</td></tr>
                            <?php else: ?>
                                <?php foreach ($produtos as $p): ?>
                                    <?php $critico = $p['quantidade_estoque'] <= $p['estoque_minimo']; ?>
                                    <tr <?= $critico ? 'class="table-danger"' : '' ?>>
                                        <td>
                                            <div class="fw-semibold small"><?= htmlspecialchars($p['nome']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($p['codigo_barras'] ?? '') ?></small>
                                        </td>
                                        <td class="small text-muted"><?= htmlspecialchars($p['categoria'] ?? '-') ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars(($p['tamanho'] ?? '-') . ' / ' . ($p['cor'] ?? '-')) ?></td>
                                        <td class="text-center fw-bold <?= $critico ? 'text-danger' : 'text-success' ?>">
                                            <?= (int)$p['quantidade_estoque'] ?>
                                            <?= $critico ? '<i class="bi bi-exclamation-triangle-fill ms-1"></i>' : '' ?>
                                        </td>
                                        <td class="text-center text-muted small"><?= (int)$p['estoque_minimo'] ?></td>
                                        <td class="text-end small text-muted">R$ <?= number_format($p['preco_custo'], 2, ',', '.') ?></td>
                                        <td class="text-end small fw-semibold">R$ <?= number_format($p['preco_venda'], 2, ',', '.') ?></td>
                                        <td class="text-end small text-success fw-semibold">R$ <?= number_format($p['valor_venda_estoque'], 2, ',', '.') ?></td>
                                        <td class="text-center">
                                            <?= $critico
                                                ? '<span class="badge bg-danger">Crítico</span>'
                                                : '<span class="badge bg-success">Normal</span>'
                                            ?>
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
