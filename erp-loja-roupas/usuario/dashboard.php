<?php
/**
 * usuario/dashboard.php
 * Dashboard do vendedor: resumo do dia e acesso rápido ao PDV.
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
verificarAcesso(); // Qualquer usuário logado

if (!defined('BASE_URL')) define('BASE_URL', '../');

$pdo      = getDB();
$usuarioId = usuarioLogadoId();

// Vendas do próprio vendedor hoje
$stmtDia = $pdo->prepare("
    SELECT COUNT(*) AS qtd, COALESCE(SUM(valor_total - desconto), 0) AS total
    FROM vendas
    WHERE usuario_id = :id AND DATE(data_venda) = CURRENT_DATE AND status = 'concluida'
");
$stmtDia->execute([':id' => $usuarioId]);
$vendasDia = $stmtDia->fetch();

// Últimas 5 vendas do vendedor
$stmtRecentes = $pdo->prepare("
    SELECT v.id, v.data_venda, (v.valor_total - v.desconto) AS liquido, v.forma_pagamento,
           COALESCE(c.nome, 'Avulso') AS cliente
    FROM vendas v
    LEFT JOIN clientes c ON c.id = v.cliente_id
    WHERE v.usuario_id = :id AND v.status = 'concluida'
    ORDER BY v.data_venda DESC LIMIT 5
");
$stmtRecentes->execute([':id' => $usuarioId]);
$vendasRecentes = $stmtRecentes->fetchAll();

// Vendas do mês
$stmtMes = $pdo->prepare("
    SELECT COUNT(*) AS qtd, COALESCE(SUM(valor_total - desconto), 0) AS total
    FROM vendas
    WHERE usuario_id = :id
      AND EXTRACT(MONTH FROM data_venda) = EXTRACT(MONTH FROM CURRENT_DATE)
      AND EXTRACT(YEAR  FROM data_venda) = EXTRACT(YEAR  FROM CURRENT_DATE)
      AND status = 'concluida'
");
$stmtMes->execute([':id' => $usuarioId]);
$vendasMes = $stmtMes->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Painel | ERP Moda</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
<div id="sidebarOverlay" class="sidebar-overlay"></div>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar_user.php'; ?>
    <div class="content-wrapper">
        <div class="topbar">
            <button class="btn btn-link text-dark d-lg-none me-2 p-0" id="sidebarToggleBtn"><i class="bi bi-list fs-4"></i></button>
            <div>
                <div class="topbar-title">Meu Painel</div>
                <div class="topbar-breadcrumb"><?= date('l, d \d\e F \d\e Y') ?></div>
            </div>
            <div class="ms-auto">
                <a href="pdv.php" class="btn btn-success fw-bold">
                    <i class="bi bi-cash-register me-2"></i>Abrir PDV
                </a>
            </div>
        </div>
        <main class="p-4 flex-grow-1 fade-in">

            <!-- Saudação personalizada -->
            <div class="mb-4">
                <h2 class="mb-1">
                    <?php
                    $hora = (int)date('H');
                    echo $hora < 12 ? '☀️ Bom dia' : ($hora < 18 ? '🌤️ Boa tarde' : '🌙 Boa noite');
                    ?>, <strong><?= usuarioLogadoNome() ?></strong>!
                </h2>
                <p class="text-muted">Aqui está um resumo das suas atividades de hoje.</p>
            </div>

            <!-- Botão principal — PDV -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <a href="pdv.php" class="text-decoration-none">
                        <div style="background: linear-gradient(135deg, #10b981, #059669); border-radius: 16px; padding: 2rem; color:#fff; cursor:pointer; transition: transform .2s, box-shadow .2s;"
                             onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 30px rgba(16,185,129,.4)'"
                             onmouseout="this.style.transform='';this.style.boxShadow=''">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-cash-register" style="font-size:3rem;"></i>
                                <div>
                                    <div style="font-size:1.4rem;font-weight:700;">Ponto de Venda (PDV)</div>
                                    <div style="opacity:.85;">Clique para abrir o caixa e realizar uma nova venda</div>
                                </div>
                                <i class="bi bi-arrow-right-circle-fill ms-auto" style="font-size:2rem;opacity:.7;"></i>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- KPIs do vendedor -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="kpi-card kpi-primary">
                        <div class="kpi-label">Vendas Hoje</div>
                        <div class="kpi-value"><?= (int)$vendasDia['qtd'] ?></div>
                        <div class="kpi-trend text-success mt-1">R$ <?= number_format($vendasDia['total'], 2, ',', '.') ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card kpi-success">
                        <div class="kpi-label">Vendas do Mês</div>
                        <div class="kpi-value"><?= (int)$vendasMes['qtd'] ?></div>
                        <div class="kpi-trend text-muted mt-1">R$ <?= number_format($vendasMes['total'], 2, ',', '.') ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-label">Ticket Médio</div>
                        <div class="kpi-value" style="font-size:1.3rem;">
                            R$ <?= (int)$vendasMes['qtd'] > 0
                                ? number_format($vendasMes['total'] / $vendasMes['qtd'], 2, ',', '.')
                                : '0,00'
                            ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-label">Meu Perfil</div>
                        <div class="kpi-value" style="font-size:1rem;margin-top:.5rem;">
                            <i class="bi bi-person-circle me-2 text-primary-custom"></i>
                            <a href="perfil.php" class="text-decoration-none">Ver perfil</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Últimas vendas -->
            <div class="table-card">
                <div class="d-flex align-items-center justify-content-between p-3 pb-0">
                    <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary-custom"></i>Minhas Últimas Vendas</h6>
                    <a href="vendas/historico.php" class="btn btn-sm btn-outline-primary">Ver Tudo</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>#</th><th>Data/Hora</th><th>Cliente</th><th>Pagamento</th><th class="text-end">Valor</th></tr></thead>
                        <tbody>
                            <?php if (empty($vendasRecentes)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>Nenhuma venda realizada ainda hoje.
                                </td></tr>
                            <?php else: ?>
                                <?php foreach ($vendasRecentes as $v): ?>
                                    <tr>
                                        <td class="text-muted small">#<?= str_pad($v['id'],5,'0',STR_PAD_LEFT) ?></td>
                                        <td class="small"><?= date('d/m H:i', strtotime($v['data_venda'])) ?></td>
                                        <td class="small"><?= htmlspecialchars($v['cliente']) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($v['forma_pagamento']) ?></span></td>
                                        <td class="text-end fw-semibold text-success">R$ <?= number_format($v['liquido'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
        <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmXKzlVzSwh/so7GXpTzm+O1oeN" crossorigin="anonymous"></script>
<script src="../public/js/scripts.js"></script>
</body>
</html>
