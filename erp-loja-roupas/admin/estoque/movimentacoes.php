<?php
/**
 * admin/estoque/movimentacoes.php
 * Histórico completo de movimentações de estoque + formulário para entrada manual.
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
verificarAcesso('admin');

if (!defined('BASE_URL')) define('BASE_URL', '../../');

$pdo   = getDB();
$erros = [];
$msg   = $_GET['msg'] ?? '';

// -----------------------------------------------------------
// Processamento POST — entrada manual de estoque
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produtoId = (int)($_POST['produto_id']  ?? 0);
    $tipo      = in_array($_POST['tipo'] ?? '', ['entrada','saida']) ? $_POST['tipo'] : 'entrada';
    $quantidade = (int)($_POST['quantidade'] ?? 0);
    $motivo    = trim($_POST['motivo'] ?? '');

    if ($produtoId <= 0)     $erros[] = 'Selecione um produto.';
    if ($quantidade <= 0)    $erros[] = 'A quantidade deve ser maior que zero.';
    if (empty($motivo))      $erros[] = 'Informe o motivo da movimentação.';

    if (empty($erros)) {
        $pdo->beginTransaction();
        try {
            // Verifica estoque se for saída
            if ($tipo === 'saida') {
                $stmtEst = $pdo->prepare("SELECT quantidade_estoque FROM produtos WHERE id = :id FOR UPDATE");
                $stmtEst->execute([':id' => $produtoId]);
                $estqAtual = (int)$stmtEst->fetchColumn();
                if ($estqAtual < $quantidade) {
                    throw new Exception('Estoque insuficiente. Disponível: ' . $estqAtual . ' unidades.');
                }
            }

            // Atualiza o estoque
            $sinal = $tipo === 'entrada' ? '+' : '-';
            $pdo->prepare("UPDATE produtos SET quantidade_estoque = quantidade_estoque {$sinal} :qtd WHERE id = :id")
                ->execute([':qtd' => $quantidade, ':id' => $produtoId]);

            // Registra movimentação
            $pdo->prepare("
                INSERT INTO movimentacao_estoque (produto_id, usuario_id, tipo, quantidade, motivo)
                VALUES (:prod_id, :usr_id, :tipo, :qtd, :motivo)
            ")->execute([
                ':prod_id' => $produtoId,
                ':usr_id'  => usuarioLogadoId(),
                ':tipo'    => $tipo,
                ':qtd'     => $quantidade,
                ':motivo'  => $motivo,
            ]);

            $pdo->commit();
            header('Location: movimentacoes.php?msg=registrado');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $erros[] = $e->getMessage();
        }
    }
}

// Filtros
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim    = $_GET['data_fim']    ?? date('Y-m-d');
$filtroTipo = $_GET['tipo']        ?? '';

$where  = ["DATE(m.data_movimentacao) BETWEEN :di AND :df"];
$params = [':di' => $dataInicio, ':df' => $dataFim];
if ($filtroTipo) { $where[] = "m.tipo = :tipo"; $params[':tipo'] = $filtroTipo; }

$sql = "
    SELECT m.*, p.nome AS produto_nome, p.quantidade_estoque AS estq_atual,
           u.nome AS usuario_nome
    FROM movimentacao_estoque m
    INNER JOIN produtos  p ON p.id = m.produto_id
    LEFT JOIN  usuarios  u ON u.id = m.usuario_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY m.data_movimentacao DESC
    LIMIT 200
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movimentacoes = $stmt->fetchAll();

// Produtos para o select do formulário
$produtos = $pdo->query("SELECT id, nome, quantidade_estoque FROM produtos WHERE ativo = TRUE ORDER BY nome")->fetchAll();

$mensagens = [
    'registrado' => ['tipo'=>'success','texto'=>'Movimentação de estoque registrada com sucesso!'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque | ERP Moda</title>
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
            <div><div class="topbar-title">Controle de Estoque</div><div class="topbar-breadcrumb">Admin / Estoque</div></div>
        </div>
        <main class="p-4 flex-grow-1 fade-in">
            <div class="page-header"><h2><i class="bi bi-boxes me-2 text-primary-custom"></i>Movimentações de Estoque</h2></div>

            <?php if ($msg && isset($mensagens[$msg])): ?>
                <div class="alert alert-<?= $mensagens[$msg]['tipo'] ?> alert-auto-close alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($mensagens[$msg]['texto']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger mb-3">
                    <?php foreach ($erros as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Formulário de movimentação manual -->
                <div class="col-12 col-lg-4">
                    <div class="form-card">
                        <h6 class="fw-bold mb-3">
                            <i class="bi bi-plus-slash-minus me-2 text-primary-custom"></i>
                            Registrar Movimentação
                        </h6>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Produto <span class="text-danger">*</span></label>
                                <select class="form-select" name="produto_id" required>
                                    <option value="0">— Selecione —</option>
                                    <?php foreach ($produtos as $p): ?>
                                        <option value="<?= $p['id'] ?>">
                                            <?= htmlspecialchars($p['nome']) ?> (Estq: <?= $p['quantidade_estoque'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tipo <span class="text-danger">*</span></label>
                                <select class="form-select" name="tipo" required>
                                    <option value="entrada">⬆ Entrada (Compra/Reposição)</option>
                                    <option value="saida">⬇ Saída (Perda/Ajuste)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Quantidade <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="quantidade" min="1" value="1" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Motivo <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="motivo" rows="2" required
                                          placeholder="Ex: Recebimento NF-e 1234, Perda por avaria..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-floppy me-1"></i>Registrar Movimentação
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Histórico de movimentações -->
                <div class="col-12 col-lg-8">
                    <!-- Filtros -->
                    <div class="form-card mb-3">
                        <form method="GET" class="row g-2 align-items-end">
                            <div class="col-6 col-md-3">
                                <label class="form-label">Data Início</label>
                                <input type="date" class="form-control form-control-sm" name="data_inicio" value="<?= $dataInicio ?>">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">Data Fim</label>
                                <input type="date" class="form-control form-control-sm" name="data_fim" value="<?= $dataFim ?>">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">Tipo</label>
                                <select class="form-select form-select-sm" name="tipo">
                                    <option value="">Todos</option>
                                    <option value="entrada" <?= $filtroTipo === 'entrada' ? 'selected' : '' ?>>Entradas</option>
                                    <option value="saida"   <?= $filtroTipo === 'saida'   ? 'selected' : '' ?>>Saídas</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="bi bi-search me-1"></i>Filtrar
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="table-card">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Data/Hora</th>
                                        <th>Produto</th>
                                        <th class="text-center">Tipo</th>
                                        <th class="text-center">Qtd</th>
                                        <th>Motivo</th>
                                        <th>Usuário</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($movimentacoes)): ?>
                                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma movimentação no período.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($movimentacoes as $m): ?>
                                            <tr>
                                                <td class="small text-muted">
                                                    <?= date('d/m/Y', strtotime($m['data_movimentacao'])) ?><br>
                                                    <?= date('H:i', strtotime($m['data_movimentacao'])) ?>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold small"><?= htmlspecialchars($m['produto_nome']) ?></div>
                                                    <small class="text-muted">Estq. atual: <?= $m['estq_atual'] ?></small>
                                                </td>
                                                <td class="text-center">
                                                    <?= $m['tipo'] === 'entrada'
                                                        ? '<span class="badge bg-success"><i class="bi bi-arrow-up me-1"></i>Entrada</span>'
                                                        : '<span class="badge bg-danger"><i class="bi bi-arrow-down me-1"></i>Saída</span>'
                                                    ?>
                                                </td>
                                                <td class="text-center fw-bold"><?= (int)$m['quantidade'] ?></td>
                                                <td class="small text-muted"><?= htmlspecialchars($m['motivo'] ?? '-') ?></td>
                                                <td class="small text-muted"><?= htmlspecialchars($m['usuario_nome'] ?? 'Sistema') ?></td>
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
