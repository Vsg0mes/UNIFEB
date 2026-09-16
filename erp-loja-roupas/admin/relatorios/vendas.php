<?php
/**
 * admin/relatorios/vendas.php
 * Relatório de vendas por período com totais e gráfico.
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
verificarAcesso('admin');

if (!defined('BASE_URL')) define('BASE_URL', '../../');

$pdo = getDB();

// Filtros
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim    = $_GET['data_fim']    ?? date('Y-m-d');
$vendedorId = (int)($_GET['vendedor'] ?? 0);
$formaPage  = $_GET['forma_pagamento'] ?? '';

$where  = ["DATE(v.data_venda) BETWEEN :di AND :df", "v.status = 'concluida'"];
$params = [':di' => $dataInicio, ':df' => $dataFim];
if ($vendedorId > 0) { $where[] = "v.usuario_id = :vend"; $params[':vend'] = $vendedorId; }
if ($formaPage)       { $where[] = "v.forma_pagamento = :fp"; $params[':fp'] = $formaPage; }
$whereSQL = 'WHERE ' . implode(' AND ', $where);

// Total do período
$stmtTotal = $pdo->prepare("
    SELECT COUNT(*) AS qtd, COALESCE(SUM(v.valor_total - v.desconto), 0) AS faturamento,
           COALESCE(SUM(v.desconto), 0) AS total_desconto,
           COALESCE(AVG(v.valor_total - v.desconto), 0) AS ticket_medio
    FROM vendas v $whereSQL
");
$stmtTotal->execute($params);
$totais = $stmtTotal->fetch();

// Listagem de vendas
$stmtVendas = $pdo->prepare("
    SELECT v.id, v.data_venda, v.valor_total, v.desconto, (v.valor_total - v.desconto) AS liquido,
           v.forma_pagamento, v.status,
           COALESCE(c.nome, 'Avulso') AS cliente,
           u.nome AS vendedor,
           COUNT(vi.id) AS qtd_itens
    FROM vendas v
    LEFT JOIN clientes   c  ON c.id = v.cliente_id
    INNER JOIN usuarios  u  ON u.id = v.usuario_id
    LEFT JOIN venda_itens vi ON vi.venda_id = v.id
    $whereSQL
    GROUP BY v.id, c.nome, u.nome
    ORDER BY v.data_venda DESC
    LIMIT 500
");
$stmtVendas->execute($params);
$vendas = $stmtVendas->fetchAll();

// Produtos mais vendidos no período
$stmtMaisVendidos = $pdo->prepare("
    SELECT p.nome, SUM(vi.quantidade) AS total_vendido, SUM(vi.subtotal) AS receita
    FROM venda_itens vi
    INNER JOIN produtos p ON p.id = vi.produto_id
    INNER JOIN vendas   v ON v.id = vi.venda_id
    $whereSQL
    GROUP BY p.id, p.nome
    ORDER BY total_vendido DESC
    LIMIT 10
");
$stmtMaisVendidos->execute($params);
$maisVendidos = $stmtMaisVendidos->fetchAll();

// Vendedores para o filtro
$vendedores = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome")->fetchAll();

$formaPagLabel = [
    'dinheiro'       => 'Dinheiro',
    'cartao_credito' => 'Cartão Crédito',
    'cartao_debito'  => 'Cartão Débito',
    'pix'            => 'PIX',
    'fiado'          => 'Fiado',
];

// Dados para gráfico de forma de pagamento
$stmtGrafFP = $pdo->prepare("
    SELECT forma_pagamento, COUNT(*) AS qtd, SUM(valor_total - desconto) AS total
    FROM vendas $whereSQL
    GROUP BY forma_pagamento ORDER BY total DESC
");
$stmtGrafFP->execute($params);
$dadosFP = $stmtGrafFP->fetchAll();
$fpLabels = json_encode(array_map(fn($r) => $formaPagLabel[$r['forma_pagamento']] ?? $r['forma_pagamento'], $dadosFP));
$fpValores = json_encode(array_column($dadosFP, 'total'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Vendas | ERP Moda</title>
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
            <div><div class="topbar-title">Relatório de Vendas</div><div class="topbar-breadcrumb">Admin / Relatórios / Vendas</div></div>
            <div class="ms-auto">
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-printer me-1"></i>Imprimir
                </button>
            </div>
        </div>
        <main class="p-4 flex-grow-1 fade-in">
            <div class="page-header"><h2><i class="bi bi-graph-up me-2 text-primary-custom"></i>Relatório de Vendas</h2></div>

            <!-- Filtros -->
            <div class="form-card mb-4">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-6 col-md-2">
                        <label class="form-label">Início</label>
                        <input type="date" class="form-control form-control-sm" name="data_inicio" value="<?= $dataInicio ?>">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Fim</label>
                        <input type="date" class="form-control form-control-sm" name="data_fim" value="<?= $dataFim ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Vendedor</label>
                        <select class="form-select form-select-sm" name="vendedor">
                            <option value="0">Todos</option>
                            <?php foreach ($vendedores as $v): ?>
                                <option value="<?= $v['id'] ?>" <?= $vendedorId == $v['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($v['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Forma de Pagamento</label>
                        <select class="form-select form-select-sm" name="forma_pagamento">
                            <option value="">Todas</option>
                            <?php foreach ($formaPagLabel as $k => $label): ?>
                                <option value="<?= $k ?>" <?= $formaPage === $k ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i>Filtrar</button>
                    </div>
                </form>
            </div>

            <!-- KPIs do período -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="kpi-card kpi-primary">
                        <div class="kpi-label">Total de Vendas</div>
                        <div class="kpi-value"><?= number_format($totais['qtd']) ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card kpi-success">
                        <div class="kpi-label">Faturamento</div>
                        <div class="kpi-value" style="font-size:1.3rem;">R$ <?= number_format($totais['faturamento'], 2, ',', '.') ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card kpi-warning">
                        <div class="kpi-label">Ticket Médio</div>
                        <div class="kpi-value" style="font-size:1.3rem;">R$ <?= number_format($totais['ticket_medio'], 2, ',', '.') ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card kpi-danger">
                        <div class="kpi-label">Descontos</div>
                        <div class="kpi-value" style="font-size:1.3rem;">R$ <?= number_format($totais['total_desconto'], 2, ',', '.') ?></div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <!-- Gráfico forma de pagamento -->
                <div class="col-12 col-md-5">
                    <div class="chart-card">
                        <div class="chart-title">Formas de Pagamento</div>
                        <canvas id="grafFP" height="180"></canvas>
                    </div>
                </div>

                <!-- Produtos mais vendidos -->
                <div class="col-12 col-md-7">
                    <div class="table-card">
                        <div class="p-3 pb-0"><h6 class="fw-bold mb-0">Top 10 Produtos Mais Vendidos</h6></div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead>
                                    <tr><th>#</th><th>Produto</th><th class="text-center">Unidades</th><th class="text-end">Receita</th></tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($maisVendidos)): ?>
                                        <tr><td colspan="4" class="text-center text-muted py-3">Sem dados.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($maisVendidos as $i => $mv): ?>
                                            <tr>
                                                <td class="text-muted"><?= $i+1 ?></td>
                                                <td class="fw-semibold small"><?= htmlspecialchars($mv['nome']) ?></td>
                                                <td class="text-center"><span class="badge bg-primary"><?= (int)$mv['total_vendido'] ?></span></td>
                                                <td class="text-end text-success fw-semibold small">R$ <?= number_format($mv['receita'], 2, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabela detalhada de vendas -->
            <div class="table-card">
                <div class="p-3 pb-0"><h6 class="fw-bold">Detalhamento das Vendas</h6></div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Data</th>
                                <th>Cliente</th>
                                <th>Vendedor</th>
                                <th class="text-center">Itens</th>
                                <th>Pagamento</th>
                                <th class="text-end">Desconto</th>
                                <th class="text-end">Líquido</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($vendas)): ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">Nenhuma venda no período selecionado.</td></tr>
                            <?php else: ?>
                                <?php foreach ($vendas as $v): ?>
                                    <tr>
                                        <td class="text-muted small">#<?= str_pad($v['id'],5,'0',STR_PAD_LEFT) ?></td>
                                        <td class="small"><?= date('d/m/Y H:i', strtotime($v['data_venda'])) ?></td>
                                        <td class="small"><?= htmlspecialchars($v['cliente']) ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars($v['vendedor']) ?></td>
                                        <td class="text-center"><span class="badge bg-secondary"><?= (int)$v['qtd_itens'] ?></span></td>
                                        <td class="small"><?= htmlspecialchars($formaPagLabel[$v['forma_pagamento']] ?? $v['forma_pagamento']) ?></td>
                                        <td class="text-end text-muted small">R$ <?= number_format($v['desconto'], 2, ',', '.') ?></td>
                                        <td class="text-end fw-semibold text-success small">R$ <?= number_format($v['liquido'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($vendas)): ?>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="7" class="text-end">TOTAL DO PERÍODO:</td>
                                <td class="text-end text-success">R$ <?= number_format($totais['faturamento'], 2, ',', '.') ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </main>
        <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmXKzlVzSwh/so7GXpTzm+O1oeN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="../../public/js/scripts.js"></script>
<script>
const ctxFP = document.getElementById('grafFP');
if (ctxFP && <?= count($dadosFP) ?> > 0) {
    new Chart(ctxFP, {
        type: 'doughnut',
        data: {
            labels: <?= $fpLabels ?>,
            datasets: [{
                data: <?= $fpValores ?>,
                backgroundColor: ['#10b981','#6366f1','#06b6d4','#f59e0b','#ef4444'],
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ctx.label + ': R$ ' + parseFloat(ctx.raw).toLocaleString('pt-BR',{minimumFractionDigits:2})
                    }
                }
            }
        }
    });
}
</script>
</body>
</html>
