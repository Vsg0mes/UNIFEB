<?php
/**
 * usuario/pdv.php
 * Ponto de Venda — tela principal do caixa.
 * Integra busca de produtos via AJAX, carrinho dinâmico em JS
 * e finalização de venda com baixa automática no estoque (via trigger).
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
verificarAcesso();

if (!defined('BASE_URL')) define('BASE_URL', '../');

$pdo      = getDB();
$erros    = [];
$sucesso  = '';

// -----------------------------------------------------------
// Finalização da Venda (POST)
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_venda'])) {
    $carrinhoJSON  = trim($_POST['carrinho_json'] ?? '[]');
    $clienteId     = (int)($_POST['cliente_id']     ?? 0);
    $desconto      = (float)str_replace(',', '.', $_POST['desconto']    ?? '0');
    $valorPago     = (float)str_replace(',', '.', $_POST['valor_pago']  ?? '0');
    $formaPag      = $_POST['forma_pagamento'] ?? 'dinheiro';
    $observacao    = trim($_POST['observacao'] ?? '');

    $formasValidas = ['dinheiro','cartao_credito','cartao_debito','pix','fiado'];
    if (!in_array($formaPag, $formasValidas)) $formaPag = 'dinheiro';

    $itens = json_decode($carrinhoJSON, true);

    if (empty($itens)) {
        $erros[] = 'O carrinho está vazio. Adicione pelo menos um produto.';
    }
    if ($desconto < 0) {
        $erros[] = 'O desconto não pode ser negativo.';
    }

    if (empty($erros)) {
        // Calcula valor total bruto
        $valorTotalBruto = array_sum(array_column($itens, 'subtotal'));

        $pdo->beginTransaction();
        try {
            // Insere o cabeçalho da venda
            $stmtVenda = $pdo->prepare("
                INSERT INTO vendas
                    (cliente_id, usuario_id, valor_total, desconto, valor_pago, forma_pagamento, status, observacao)
                VALUES
                    (:cliente_id, :usuario_id, :valor_total, :desconto, :valor_pago, :forma_pag, 'concluida', :obs)
                RETURNING id
            ");
            $stmtVenda->execute([
                ':cliente_id' => $clienteId > 0 ? $clienteId : null,
                ':usuario_id' => usuarioLogadoId(),
                ':valor_total'=> $valorTotalBruto,
                ':desconto'   => $desconto,
                ':valor_pago' => $valorPago,
                ':forma_pag'  => $formaPag,
                ':obs'        => $observacao ?: null,
            ]);
            $vendaId = (int)$stmtVenda->fetchColumn();

            // Insere os itens (a trigger faz a baixa no estoque automaticamente)
            $stmtItem = $pdo->prepare("
                INSERT INTO venda_itens (venda_id, produto_id, quantidade, preco_unitario, subtotal)
                VALUES (:venda_id, :prod_id, :qtd, :preco, :subtotal)
            ");
            foreach ($itens as $item) {
                $stmtItem->execute([
                    ':venda_id' => $vendaId,
                    ':prod_id'  => (int)$item['id'],
                    ':qtd'      => (int)$item['quantidade'],
                    ':preco'    => (float)$item['preco_unitario'],
                    ':subtotal' => (float)$item['subtotal'],
                ]);
            }

            $pdo->commit();

            $valorLiquido = $valorTotalBruto - $desconto;
            $troco        = max($valorPago - $valorLiquido, 0);
            $sucesso      = [
                'venda_id'    => $vendaId,
                'total'       => $valorLiquido,
                'troco'       => $troco,
                'forma_pag'   => $formaPag,
            ];

        } catch (Exception $e) {
            $pdo->rollBack();
            $erros[] = 'Erro ao finalizar a venda: ' . $e->getMessage();
        }
    }
}

// Clientes para o select (opcional)
$clientes = $pdo->query("SELECT id, nome, cpf FROM clientes ORDER BY nome LIMIT 200")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDV — Ponto de Venda | ERP Moda</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .pdv-layout { display: grid; grid-template-columns: 1fr 420px; gap: 1.25rem; min-height: calc(100vh - 130px); }
        @media (max-width: 991.98px) { .pdv-layout { grid-template-columns: 1fr; } }
        .pdv-cart { position: sticky; top: 75px; max-height: calc(100vh - 90px); display: flex; flex-direction: column; }
        .pdv-cart-body { flex: 1; overflow-y: auto; }
    </style>
</head>
<body>
<div id="sidebarOverlay" class="sidebar-overlay"></div>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar_user.php'; ?>
    <div class="content-wrapper">
        <div class="topbar">
            <button class="btn btn-link text-dark d-lg-none me-2 p-0" id="sidebarToggleBtn"><i class="bi bi-list fs-4"></i></button>
            <div>
                <div class="topbar-title"><i class="bi bi-cash-register me-2 text-success"></i>PDV — Ponto de Venda</div>
                <div class="topbar-breadcrumb"><?= date('d/m/Y H:i') ?> | <?= usuarioLogadoNome() ?></div>
            </div>
            <div class="ms-auto">
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Painel
                </a>
            </div>
        </div>

        <main class="p-3 flex-grow-1">

            <!-- Modal de sucesso da venda -->
            <?php if ($sucesso): ?>
            <div class="modal fade show d-block" id="modalVendaSucesso" style="background:rgba(0,0,0,.6);" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-body text-center p-4">
                            <div style="width:70px;height:70px;background:#10b981;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:2rem;color:#fff;">
                                <i class="bi bi-check-lg"></i>
                            </div>
                            <h4 class="fw-bold text-success mb-1">Venda Finalizada!</h4>
                            <p class="text-muted mb-3">Venda <strong>#<?= str_pad($sucesso['venda_id'],5,'0',STR_PAD_LEFT) ?></strong> registrada com sucesso.</p>

                            <div class="list-group list-group-flush mb-3 text-start">
                                <div class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">Total da venda:</span>
                                    <strong>R$ <?= number_format($sucesso['total'], 2, ',', '.') ?></strong>
                                </div>
                                <div class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">Forma de pagamento:</span>
                                    <strong><?= htmlspecialchars($sucesso['forma_pag']) ?></strong>
                                </div>
                                <?php if ($sucesso['troco'] > 0): ?>
                                <div class="list-group-item d-flex justify-content-between bg-success bg-opacity-10">
                                    <span class="fw-semibold text-success">Troco:</span>
                                    <strong class="text-success fs-5">R$ <?= number_format($sucesso['troco'], 2, ',', '.') ?></strong>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex gap-2 justify-content-center">
                                <button class="btn btn-primary" onclick="novaVenda()">
                                    <i class="bi bi-plus-circle me-1"></i>Nova Venda
                                </button>
                                <a href="vendas/historico.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-clock-history me-1"></i>Histórico
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-3">
                    <?php foreach ($erros as $e): ?><div><i class="bi bi-x-circle me-1"></i><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Layout PDV: Esquerda = Busca | Direita = Carrinho -->
            <div class="pdv-layout">

                <!-- ===== COLUNA ESQUERDA: Busca de Produto ===== -->
                <div>
                    <div class="pdv-search-card">
                        <div class="p-3 border-bottom">
                            <div class="fw-semibold mb-2">
                                <i class="bi bi-search me-2 text-primary-custom"></i>Buscar Produto
                            </div>
                            <!-- Campo de busca -->
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-primary-custom text-white border-0">
                                    <i class="bi bi-upc-scan"></i>
                                </span>
                                <input type="text"
                                       class="form-control form-control-lg border-start-0"
                                       id="campoBusca"
                                       placeholder="Nome ou código de barras..."
                                       oninput="buscarProdutoPDV(this.value)"
                                       autocomplete="off"
                                       autofocus>
                                <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('campoBusca').value=''; document.getElementById('resultadosBusca').innerHTML='';">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Resultados da busca AJAX -->
                        <div id="resultadosBusca" style="min-height:100px; max-height:400px; overflow-y:auto;">
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-bag-heart fs-2 d-block mb-2"></i>
                                <small>Digite para buscar produtos...</small>
                            </div>
                        </div>
                    </div>

                    <!-- Painel de cliente e observação -->
                    <div class="form-card mt-3">
                        <h6 class="fw-semibold mb-3">
                            <i class="bi bi-person me-2 text-primary-custom"></i>
                            Dados da Venda
                            <small class="text-muted fw-normal">(opcionais)</small>
                        </h6>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Cliente</label>
                                <select class="form-select" id="selectCliente" name="cliente_id_temp">
                                    <option value="0">— Venda Avulsa (sem identificar) —</option>
                                    <?php foreach ($clientes as $c): ?>
                                        <option value="<?= $c['id'] ?>">
                                            <?= htmlspecialchars($c['nome']) ?>
                                            <?= $c['cpf'] ? ' - ' . $c['cpf'] : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Observação</label>
                                <input type="text" class="form-control" id="inputObservacao" placeholder="Ex: Presente, troca...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== COLUNA DIREITA: Carrinho + Finalização ===== -->
                <div class="pdv-cart">
                    <form method="POST" id="formVenda" class="d-flex flex-column h-100">
                        <input type="hidden" name="finalizar_venda" value="1">
                        <input type="hidden" name="carrinho_json" id="carrinhoJSON">
                        <input type="hidden" name="cliente_id"   id="hiddenClienteId">
                        <input type="hidden" name="observacao"   id="hiddenObservacao">

                        <!-- Cabeçalho do carrinho -->
                        <div class="pdv-search-card d-flex flex-column flex-grow-1" style="border-radius:12px; overflow:hidden;">
                            <div class="p-3 border-bottom d-flex align-items-center justify-content-between"
                                 style="background:linear-gradient(135deg,#6366f1,#4f46e5); color:#fff;">
                                <div>
                                    <div class="fw-bold"><i class="bi bi-cart3 me-2"></i>Carrinho</div>
                                    <small style="opacity:.8;">Itens adicionados à venda</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-light btn-outline-light"
                                        onclick="if(confirm('Limpar o carrinho?')) PDV.limpar()">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>

                            <!-- Itens do carrinho (renderizados pelo JS) -->
                            <div class="pdv-cart-body" style="max-height:280px;">
                                <table class="table table-sm mb-0" style="font-size:.85rem;">
                                    <tbody id="carrinhoBody">
                                        <tr id="trCarrinhoVazio">
                                            <td colspan="5" class="text-center text-muted py-4">
                                                <i class="bi bi-cart3 fs-2 d-block mb-2"></i>
                                                Nenhum produto adicionado
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Totais e finalização -->
                            <div class="p-3 border-top mt-auto">
                                <!-- Total bruto -->
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Subtotal:</span>
                                    <span class="fw-semibold" id="totalBruto">R$ 0,00</span>
                                </div>

                                <!-- Desconto -->
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <label class="text-muted small mb-0 flex-shrink-0">Desconto (R$):</label>
                                    <div class="input-group input-group-sm ms-auto" style="max-width:130px;">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" class="form-control" id="inputDesconto" name="desconto"
                                               value="0" min="0" step="0.01">
                                    </div>
                                </div>

                                <!-- Total líquido -->
                                <div class="d-flex justify-content-between align-items-center mb-3 pt-2 border-top">
                                    <span class="fw-bold fs-6">TOTAL:</span>
                                    <span class="fw-bold fs-4 text-success" id="totalLiquido">R$ 0,00</span>
                                </div>

                                <!-- Forma de pagamento -->
                                <div class="mb-2">
                                    <label class="form-label small text-muted">Forma de Pagamento</label>
                                    <select class="form-select form-select-sm" name="forma_pagamento" id="formaPag">
                                        <option value="dinheiro">💵 Dinheiro</option>
                                        <option value="cartao_debito">💳 Cartão Débito</option>
                                        <option value="cartao_credito">💳 Cartão Crédito</option>
                                        <option value="pix">📱 PIX</option>
                                        <option value="fiado">📝 Fiado</option>
                                    </select>
                                </div>

                                <!-- Valor pago (visível apenas para dinheiro) -->
                                <div id="campoValorPago" class="mb-2">
                                    <label class="form-label small text-muted">Valor Recebido (R$)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" class="form-control" id="inputValorPago" name="valor_pago"
                                               value="0" min="0" step="0.01">
                                    </div>
                                </div>

                                <!-- Troco -->
                                <div id="campoTroco" class="alert alert-success py-2 d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-semibold"><i class="bi bi-cash me-1"></i>Troco:</span>
                                    <span class="fw-bold fs-5" id="valorTroco">R$ 0,00</span>
                                </div>

                                <!-- Botão finalizar -->
                                <button type="submit" class="btn btn-success w-100 btn-lg fw-bold" id="btnFinalizarVenda" disabled>
                                    <i class="bi bi-check-circle me-2"></i>Finalizar Venda
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </div><!-- /pdv-layout -->
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmXKzlVzSwh/so7GXpTzm+O1oeN" crossorigin="anonymous"></script>
<script src="../public/js/scripts.js"></script>
<script>
// Limpa modal e reseta PDV após nova venda
function novaVenda() {
    document.getElementById('modalVendaSucesso')?.remove();
    PDV.limpar();
    document.getElementById('campoBusca').value = '';
    document.getElementById('resultadosBusca').innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-bag-heart fs-2 d-block mb-2"></i><small>Digite para buscar produtos...</small></div>';
    document.getElementById('inputDesconto').value = 0;
    document.getElementById('inputValorPago').value = 0;
    document.getElementById('campoBusca').focus();
}

// Mostra/oculta campo de troco e valor pago conforme forma de pagamento
document.getElementById('formaPag')?.addEventListener('change', function() {
    const isDinheiro = this.value === 'dinheiro';
    document.getElementById('campoValorPago').style.display = isDinheiro ? '' : 'none';
    document.getElementById('campoTroco').style.display     = isDinheiro ? '' : 'none';
});

// Copia cliente e observação dos campos visuais para o form de submit
document.getElementById('formVenda')?.addEventListener('submit', function() {
    document.getElementById('hiddenClienteId').value = document.getElementById('selectCliente').value;
    document.getElementById('hiddenObservacao').value = document.getElementById('inputObservacao').value;
});
</script>
</body>
</html>
