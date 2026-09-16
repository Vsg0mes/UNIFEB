<?php
/**
 * usuario/vendas/historico.php
 * Histórico de vendas do vendedor atual (ou de todos para o admin).
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
verificarAcesso();

if (!defined('BASE_URL')) define('BASE_URL', '../../');

$pdo       = getDB();
$isAdmin   = isAdmin();
$usuarioId = usuarioLogadoId();

$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim    = $_GET['data_fim']    ?? date('Y-m-d');
$status     = $_GET['status']      ?? 'todos';

// Admin vê todas; vendedor vê apenas as suas
$where  = ["DATE(v.data_venda) BETWEEN :di AND :df"];
$params = [':di' => $dataInicio, ':df' => $dataFim];
if (!$isAdmin)           { $where[] = "v.usuario_id = :uid"; $params[':uid'] = $usuarioId; }
if ($status !== 'todos') { $where[] = "v.status = :st";      $params[':st']  = $status; }

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT v.id, v.data_venda, v.valor_total, v.desconto, (v.valor_total - v.desconto) AS liquido,
           v.forma_pagamento, v.status, v.observacao,
           COALESCE(c.nome, 'Avulso') AS cliente, u.nome AS vendedor,
           COUNT(vi.id) AS qtd_itens
    FROM vendas v
    LEFT JOIN clientes  c  ON c.id = v.cliente_id
    INNER JOIN usuarios u  ON u.id = v.usuario_id
    LEFT JOIN venda_itens vi ON vi.venda_id = v.id
    $whereSQL
    GROUP BY v.id, c.nome, u.nome
    ORDER BY v.data_venda DESC
    LIMIT 300
");
$stmt->execute($params);
$vendas = $stmt->fetchAll();

$statusLabel = [
    'concluida' => '<span class="badge bg-success">Concluída</span>',
    'cancelada' => '<span class="badge bg-danger">Cancelada</span>',
    'pendente'  => '<span class="badge bg-warning text-dark">Pendente</span>',
];
$fpLabel = ['dinheiro'=>'Dinheiro','cartao_credito'=>'Cred.','cartao_debito'=>'Déb.','pix'=>'PIX','fiado'=>'Fiado'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Vendas | ERP Moda</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/style.css">
</head>
<body>
<div id="sidebarOverlay" class="sidebar-overlay"></div>
<div class="app-wrapper">
    <?php
    if ($isAdmin) { require_once __DIR__ . '/../../includes/sidebar_admin.php'; }
    else          { require_once __DIR__ . '/../../includes/sidebar_user.php';  }
    ?>
    <div class="content-wrapper">
        <div class="topbar">
            <button class="btn btn-link text-dark d-lg-none me-2 p-0" id="sidebarToggleBtn"><i class="bi bi-list fs-4"></i></button>
            <div><div class="topbar-title">Histórico de Vendas</div></div>
            <div class="ms-auto">
                <a href="../pdv.php" class="btn btn-success btn-sm fw-semibold">
                    <i class="bi bi-plus-circle me-1"></i>Nova Venda
                </a>
            </div>
        </div>
        <main class="p-4 flex-grow-1 fade-in">
            <div class="page-header"><h2><i class="bi bi-clock-history me-2 text-primary-custom"></i>Histórico de Vendas</h2></div>

            <!-- Filtros -->
            <div class="form-card mb-4">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label">De</label>
                        <input type="date" class="form-control form-control-sm" name="data_inicio" value="<?= $dataInicio ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Até</label>
                        <input type="date" class="form-control form-control-sm" name="data_fim" value="<?= $dataFim ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select form-select-sm" name="status">
                            <option value="todos"     <?= $status === 'todos'     ? 'selected':'' ?>>Todos</option>
                            <option value="concluida" <?= $status === 'concluida' ? 'selected':'' ?>>Concluídas</option>
                            <option value="cancelada" <?= $status === 'cancelada' ? 'selected':'' ?>>Canceladas</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i>Filtrar</button>
                    </div>
                </form>
            </div>

            <div class="table-card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Data</th>
                                <th>Cliente</th>
                                <?php if ($isAdmin): ?><th>Vendedor</th><?php endif; ?>
                                <th class="text-center">Itens</th>
                                <th>Pagamento</th>
                                <th class="text-end">Líquido</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($vendas)): ?>
                                <tr><td colspan="<?= $isAdmin ? 9 : 8 ?>" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>Nenhuma venda no período.
                                </td></tr>
                            <?php else: ?>
                                <?php foreach ($vendas as $v): ?>
                                    <tr>
                                        <td class="text-muted small fw-semibold">#<?= str_pad($v['id'],5,'0',STR_PAD_LEFT) ?></td>
                                        <td class="small"><?= date('d/m/Y H:i', strtotime($v['data_venda'])) ?></td>
                                        <td class="small"><?= htmlspecialchars($v['cliente']) ?></td>
                                        <?php if ($isAdmin): ?>
                                            <td class="small text-muted"><?= htmlspecialchars($v['vendedor']) ?></td>
                                        <?php endif; ?>
                                        <td class="text-center"><span class="badge bg-secondary"><?= (int)$v['qtd_itens'] ?></span></td>
                                        <td class="small"><?= htmlspecialchars($fpLabel[$v['forma_pagamento']] ?? $v['forma_pagamento']) ?></td>
                                        <td class="text-end fw-semibold text-success">R$ <?= number_format($v['liquido'], 2, ',', '.') ?></td>
                                        <td class="text-center"><?= $statusLabel[$v['status']] ?? $v['status'] ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-info btn-action" data-bs-toggle="modal"
                                                    data-bs-target="#modalVenda<?= $v['id'] ?>" title="Detalhes">
                                                <i class="bi bi-eye"></i>
                                            </button>
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
