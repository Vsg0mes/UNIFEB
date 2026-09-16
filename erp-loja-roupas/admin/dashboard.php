<?php
/**
 * admin/dashboard.php
 * Dashboard principal da área administrativa.
 * Exibe KPIs, gráfico de vendas e últimas transações.
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

// Apenas administradores
verificarAcesso('admin');

// Define BASE_URL para este nível de diretório
if (!defined('BASE_URL')) {
    define('BASE_URL', '../');
}

$pdo = getDB();

// -----------------------------------------------------------
// KPI 1: Total de vendas do dia (quantidade e valor)
// -----------------------------------------------------------
$stmtVendasDia = $pdo->prepare("
    SELECT COUNT(*) AS qtd_vendas, COALESCE(SUM(valor_total - desconto), 0) AS faturamento_dia
    FROM vendas
    WHERE DATE(data_venda) = CURRENT_DATE
      AND status = 'concluida'
");
$stmtVendasDia->execute();
$vendasDia = $stmtVendasDia->fetch();

// -----------------------------------------------------------
// KPI 2: Faturamento do mês
// -----------------------------------------------------------
$stmtMes = $pdo->prepare("
    SELECT COALESCE(SUM(valor_total - desconto), 0) AS faturamento_mes
    FROM vendas
    WHERE EXTRACT(MONTH FROM data_venda) = EXTRACT(MONTH FROM CURRENT_DATE)
      AND EXTRACT(YEAR  FROM data_venda) = EXTRACT(YEAR  FROM CURRENT_DATE)
      AND status = 'concluida'
");
$stmtMes->execute();
$faturamentoMes = $stmtMes->fetchColumn();

// -----------------------------------------------------------
// KPI 3: Produtos com estoque abaixo do mínimo
// -----------------------------------------------------------
$stmtEstoqueBaixo = $pdo->prepare("
    SELECT COUNT(*) FROM produtos
    WHERE quantidade_estoque <= estoque_minimo AND ativo = TRUE
");
$stmtEstoqueBaixo->execute();
$qtdEstoqueBaixo = $stmtEstoqueBaixo->fetchColumn();

// -----------------------------------------------------------
// KPI 4: Total de clientes cadastrados
// -----------------------------------------------------------
$stmtClientes = $pdo->prepare("SELECT COUNT(*) FROM clientes");
$stmtClientes->execute();
$totalClientes = $stmtClientes->fetchColumn();

// -----------------------------------------------------------
// KPI 5: Total de produtos ativos
// -----------------------------------------------------------
$stmtProdutos = $pdo->prepare("SELECT COUNT(*) FROM produtos WHERE ativo = TRUE");
$stmtProdutos->execute();
$totalProdutos = $stmtProdutos->fetchColumn();

// -----------------------------------------------------------
// Gráfico: Vendas dos últimos 7 dias
// -----------------------------------------------------------
$stmtGrafico = $pdo->prepare("
    SELECT
        TO_CHAR(gs.dia, 'DD/MM') AS dia_label,
        COALESCE(SUM(v.valor_total - v.desconto), 0) AS total
    FROM generate_series(
        CURRENT_DATE - INTERVAL '6 days',
        CURRENT_DATE,
        '1 day'::interval
    ) AS gs(dia)
    LEFT JOIN vendas v
        ON DATE(v.data_venda) = gs.dia AND v.status = 'concluida'
    GROUP BY gs.dia
    ORDER BY gs.dia ASC
");
$stmtGrafico->execute();
$dadosGrafico = $stmtGrafico->fetchAll();

$graficoLabels = json_encode(array_column($dadosGrafico, 'dia_label'));
$graficoValores = json_encode(array_column($dadosGrafico, 'total'));

// -----------------------------------------------------------
// Últimas 5 vendas realizadas
// -----------------------------------------------------------
$stmtUltimasVendas = $pdo->prepare("
    SELECT
        v.id,
        v.data_venda,
        v.valor_total,
        v.desconto,
        (v.valor_total - v.desconto) AS valor_liquido,
        v.forma_pagamento,
        v.status,
        COALESCE(c.nome, 'Cliente Avulso') AS cliente_nome,
        u.nome AS vendedor_nome
    FROM vendas v
    LEFT JOIN clientes  c ON c.id = v.cliente_id
    INNER JOIN usuarios u ON u.id = v.usuario_id
    ORDER BY v.data_venda DESC
    LIMIT 5
");
$stmtUltimasVendas->execute();
$ultimasVendas = $stmtUltimasVendas->fetchAll();

// -----------------------------------------------------------
// Produtos com estoque crítico (para alerta na tabela)
// -----------------------------------------------------------
$stmtCriticos = $pdo->prepare("
    SELECT p.id, p.nome, p.quantidade_estoque, p.estoque_minimo, c.nome AS categoria
    FROM produtos p
    LEFT JOIN categorias c ON c.id = p.categoria_id
    WHERE p.quantidade_estoque <= p.estoque_minimo AND p.ativo = TRUE
    ORDER BY p.quantidade_estoque ASC
    LIMIT 5
");
$stmtCriticos->execute();
$produtosCriticos = $stmtCriticos->fetchAll();

$pageTitle = 'Dashboard Administrativo';

// Labels para forma de pagamento
$formaPagamentoLabel = [
    'dinheiro'        => '<span class="badge bg-success">Dinheiro</span>',
    'cartao_credito'  => '<span class="badge bg-primary">Cartão Créd.</span>',
    'cartao_debito'   => '<span class="badge bg-info text-dark">Cartão Déb.</span>',
    'pix'             => '<span class="badge bg-warning text-dark">PIX</span>',
    'fiado'           => '<span class="badge bg-secondary">Fiado</span>',
];

$statusVendaLabel = [
    'concluida'  => '<span class="badge bg-success">Concluída</span>',
    'cancelada'  => '<span class="badge bg-danger">Cancelada</span>',
    'pendente'   => '<span class="badge bg-warning text-dark">Pendente</span>',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | ERP Moda</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>

<!-- Overlay mobile -->
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<div class="app-wrapper">
    <!-- SIDEBAR -->
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <!-- CONTEÚDO PRINCIPAL -->
    <div class="content-wrapper">

        <!-- TOPBAR -->
        <div class="topbar">
            <button class="btn btn-link text-dark d-lg-none me-2 p-0" id="sidebarToggleBtn">
                <i class="bi bi-list fs-4"></i>
            </button>
            <div>
                <div class="topbar-title">Dashboard</div>
                <div class="topbar-breadcrumb">
                    <i class="bi bi-house me-1"></i>Início / Painel Administrativo
                </div>
            </div>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-muted small d-none d-md-block">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= date('d/m/Y') ?>
                </span>
                <!-- Botão acesso rápido ao PDV -->
                <a href="../usuario/pdv.php" class="btn btn-warning btn-sm fw-semibold">
                    <i class="bi bi-cash-register me-1"></i>PDV
                </a>
            </div>
        </div>

        <!-- CORPO DA PÁGINA -->
        <main class="p-4 flex-grow-1 fade-in">

            <!-- Alerta de sessão expirada / acesso negado -->
            <?php if (!empty($_GET['erro'])): ?>
                <div class="alert alert-warning alert-dismissible alert-auto-close fade show mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= htmlspecialchars($mensagensErro[$_GET['erro']] ?? 'Ocorreu um erro.') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="page-header d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="mb-1"><i class="bi bi-speedometer2 me-2 text-primary-custom"></i>Painel Administrativo</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </nav>
                </div>
                <div class="text-muted small">
                    Bem-vindo, <strong><?= usuarioLogadoNome() ?></strong>!
                </div>
            </div>

            <!-- ========== KPI CARDS ========== -->
            <div class="row g-3 mb-4">

                <!-- Vendas do Dia -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="kpi-card kpi-primary">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <div class="kpi-label">Vendas Hoje</div>
                                <div class="kpi-value"><?= (int)$vendasDia['qtd_vendas'] ?></div>
                                <div class="kpi-trend text-muted mt-1">
                                    <i class="bi bi-currency-dollar me-1"></i>
                                    <?= 'R$ ' . number_format($vendasDia['faturamento_dia'], 2, ',', '.') ?>
                                </div>
                            </div>
                            <div class="kpi-icon bg-primary-soft">
                                <i class="bi bi-cart-check"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Faturamento do Mês -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="kpi-card kpi-success">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <div class="kpi-label">Faturamento do Mês</div>
                                <div class="kpi-value" style="font-size:1.4rem;">
                                    R$ <?= number_format($faturamentoMes, 2, ',', '.') ?>
                                </div>
                                <div class="kpi-trend text-muted mt-1">
                                    <i class="bi bi-calendar me-1"></i>
                                    <?= date('F Y') ?>
                                </div>
                            </div>
                            <div class="kpi-icon bg-success-soft">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estoque Baixo -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="kpi-card kpi-<?= $qtdEstoqueBaixo > 0 ? 'danger' : 'success' ?>">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <div class="kpi-label">Estoque Crítico</div>
                                <div class="kpi-value"><?= (int)$qtdEstoqueBaixo ?></div>
                                <div class="kpi-trend mt-1">
                                    <?php if ($qtdEstoqueBaixo > 0): ?>
                                        <a href="admin/estoque/movimentacoes.php" class="text-danger text-decoration-none small">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>Ver produtos
                                        </a>
                                    <?php else: ?>
                                        <span class="text-success small"><i class="bi bi-check-circle me-1"></i>Tudo ok!</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="kpi-icon <?= $qtdEstoqueBaixo > 0 ? 'bg-danger-soft' : 'bg-success-soft' ?>">
                                <i class="bi bi-<?= $qtdEstoqueBaixo > 0 ? 'exclamation-triangle' : 'boxes' ?>"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total de Clientes -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="kpi-card kpi-info">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <div class="kpi-label">Clientes Cadastrados</div>
                                <div class="kpi-value"><?= (int)$totalClientes ?></div>
                                <div class="kpi-trend text-muted mt-1">
                                    <a href="clientes/listar.php" class="text-decoration-none small text-muted">
                                        <i class="bi bi-arrow-right me-1"></i>Ver todos
                                    </a>
                                </div>
                            </div>
                            <div class="kpi-icon bg-info-soft">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /KPI row -->

            <!-- ========== GRÁFICO + ESTOQUE CRÍTICO ========== -->
            <div class="row g-3 mb-4">

                <!-- Gráfico de Vendas (últimos 7 dias) -->
                <div class="col-12 col-xl-8">
                    <div class="chart-card">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="chart-title">
                                <i class="bi bi-bar-chart-line me-2 text-primary-custom"></i>
                                Faturamento — Últimos 7 Dias
                            </div>
                            <a href="relatorios/vendas.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye me-1"></i>Relatório Completo
                            </a>
                        </div>
                        <canvas id="graficoVendas" height="100"></canvas>
                    </div>
                </div>

                <!-- Produtos com Estoque Crítico -->
                <div class="col-12 col-xl-4">
                    <div class="chart-card h-100">
                        <div class="chart-title mb-3">
                            <i class="bi bi-exclamation-triangle me-2 text-warning"></i>
                            Estoque Crítico
                        </div>
                        <?php if (empty($produtosCriticos)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-check-circle fs-2 text-success d-block mb-2"></i>
                                Todos os produtos com estoque adequado!
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($produtosCriticos as $p): ?>
                                    <div class="list-group-item px-0 d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="fw-semibold small"><?= htmlspecialchars($p['nome']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($p['categoria'] ?? '-') ?></small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-danger"><?= (int)$p['quantidade_estoque'] ?> un.</span>
                                            <div class="estoque-baixo-indicator">
                                                <i class="bi bi-arrow-down-circle-fill"></i>Mín: <?= (int)$p['estoque_minimo'] ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <a href="produtos/listar.php?filtro=estoque_baixo" class="btn btn-sm btn-outline-danger w-100 mt-3">
                                <i class="bi bi-arrow-right me-1"></i>Ver todos com estoque baixo
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

            </div><!-- /gráfico row -->

            <!-- ========== ÚLTIMAS VENDAS ========== -->
            <div class="row g-3">
                <div class="col-12">
                    <div class="table-card">
                        <div class="d-flex align-items-center justify-content-between p-3 pb-0">
                            <h6 class="fw-bold mb-0">
                                <i class="bi bi-clock-history me-2 text-primary-custom"></i>
                                Últimas Vendas
                            </h6>
                            <a href="../usuario/vendas/historico.php" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-list-ul me-1"></i>Ver Histórico Completo
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Data / Hora</th>
                                        <th>Cliente</th>
                                        <th>Vendedor</th>
                                        <th>Pagamento</th>
                                        <th class="text-end">Valor</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($ultimasVendas)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                                Nenhuma venda registrada ainda.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($ultimasVendas as $v): ?>
                                            <tr>
                                                <td>
                                                    <a href="../usuario/vendas/historico.php?id=<?= $v['id'] ?>"
                                                       class="fw-semibold text-decoration-none text-primary-custom">
                                                        #<?= str_pad($v['id'], 5, '0', STR_PAD_LEFT) ?>
                                                    </a>
                                                </td>
                                                <td class="text-muted small">
                                                    <?= date('d/m/Y', strtotime($v['data_venda'])) ?><br>
                                                    <span class="text-muted"><?= date('H:i', strtotime($v['data_venda'])) ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($v['cliente_nome']) ?></td>
                                                <td class="text-muted"><?= htmlspecialchars($v['vendedor_nome']) ?></td>
                                                <td><?= $formaPagamentoLabel[$v['forma_pagamento']] ?? $v['forma_pagamento'] ?></td>
                                                <td class="text-end fw-semibold">
                                                    R$ <?= number_format($v['valor_liquido'], 2, ',', '.') ?>
                                                </td>
                                                <td class="text-center">
                                                    <?= $statusVendaLabel[$v['status']] ?? $v['status'] ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div><!-- /últimas vendas -->

        </main><!-- /main -->

        <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    </div><!-- /content-wrapper -->
</div><!-- /app-wrapper -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmXKzlVzSwh/so7GXpTzm+O1oeN" crossorigin="anonymous"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="../public/js/scripts.js"></script>

<script>
// -------------------------------------------------------
// Gráfico de vendas dos últimos 7 dias
// -------------------------------------------------------
const ctxVendas = document.getElementById('graficoVendas');
if (ctxVendas) {
    new Chart(ctxVendas, {
        type: 'bar',
        data: {
            labels: <?= $graficoLabels ?>,
            datasets: [{
                label: 'Faturamento (R$)',
                data: <?= $graficoValores ?>,
                backgroundColor: 'rgba(99, 102, 241, 0.15)',
                borderColor: 'rgba(99, 102, 241, 0.9)',
                borderWidth: 2,
                borderRadius: 6,
                borderSkipped: false,
                hoverBackgroundColor: 'rgba(99, 102, 241, 0.3)',
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ' R$ ' + parseFloat(ctx.raw).toLocaleString('pt-BR', {minimumFractionDigits:2})
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: {
                        callback: (v) => 'R$ ' + v.toLocaleString('pt-BR')
                    }
                },
                x: { grid: { display: false } }
            }
        }
    });
}
</script>
</body>
</html>
