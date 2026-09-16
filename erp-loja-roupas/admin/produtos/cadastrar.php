<?php
/**
 * admin/produtos/cadastrar.php
 * Formulário para cadastro de novo produto.
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
verificarAcesso('admin');

if (!defined('BASE_URL')) define('BASE_URL', '../../');

$pdo   = getDB();
$erros = [];
$dados = []; // Repopula o formulário em caso de erro

// -----------------------------------------------------------
// Carrega categorias e fornecedores para os selects
// -----------------------------------------------------------
$categorias   = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();
$fornecedores = $pdo->query("SELECT id, nome FROM fornecedores WHERE ativo = TRUE ORDER BY nome")->fetchAll();

// -----------------------------------------------------------
// Processamento do formulário (POST)
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        'nome'               => trim($_POST['nome']               ?? ''),
        'descricao'          => trim($_POST['descricao']          ?? ''),
        'categoria_id'       => (int)($_POST['categoria_id']      ?? 0),
        'fornecedor_id'      => (int)($_POST['fornecedor_id']     ?? 0),
        'tamanho'            => trim($_POST['tamanho']            ?? ''),
        'cor'                => trim($_POST['cor']                ?? ''),
        'preco_custo'        => str_replace(',', '.', trim($_POST['preco_custo']  ?? '0')),
        'preco_venda'        => str_replace(',', '.', trim($_POST['preco_venda']  ?? '0')),
        'quantidade_estoque' => (int)($_POST['quantidade_estoque'] ?? 0),
        'estoque_minimo'     => (int)($_POST['estoque_minimo']    ?? 5),
        'codigo_barras'      => trim($_POST['codigo_barras']      ?? ''),
        'ativo'              => isset($_POST['ativo']) ? true : false,
    ];

    // Validações
    if (empty($dados['nome']))                        $erros[] = 'O nome do produto é obrigatório.';
    if (!is_numeric($dados['preco_venda']) || $dados['preco_venda'] < 0)
                                                      $erros[] = 'Preço de venda inválido.';
    if (!is_numeric($dados['preco_custo']) || $dados['preco_custo'] < 0)
                                                      $erros[] = 'Preço de custo inválido.';
    if ($dados['quantidade_estoque'] < 0)             $erros[] = 'Quantidade em estoque não pode ser negativa.';
    if ($dados['estoque_minimo'] < 0)                 $erros[] = 'Estoque mínimo não pode ser negativo.';

    // Verifica código de barras duplicado
    if (!empty($dados['codigo_barras'])) {
        $stmtCB = $pdo->prepare("SELECT id FROM produtos WHERE codigo_barras = :cb LIMIT 1");
        $stmtCB->execute([':cb' => $dados['codigo_barras']]);
        if ($stmtCB->fetch()) $erros[] = 'Código de barras já cadastrado para outro produto.';
    }

    if (empty($erros)) {
        $stmt = $pdo->prepare("
            INSERT INTO produtos
                (nome, descricao, categoria_id, fornecedor_id, tamanho, cor,
                 preco_custo, preco_venda, quantidade_estoque, estoque_minimo, codigo_barras, ativo)
            VALUES
                (:nome, :descricao, :categoria_id, :fornecedor_id, :tamanho, :cor,
                 :preco_custo, :preco_venda, :quantidade_estoque, :estoque_minimo, :codigo_barras, :ativo)
        ");
        $stmt->execute([
            ':nome'               => $dados['nome'],
            ':descricao'          => $dados['descricao'] ?: null,
            ':categoria_id'       => $dados['categoria_id'] ?: null,
            ':fornecedor_id'      => $dados['fornecedor_id'] ?: null,
            ':tamanho'            => $dados['tamanho'] ?: null,
            ':cor'                => $dados['cor'] ?: null,
            ':preco_custo'        => $dados['preco_custo'],
            ':preco_venda'        => $dados['preco_venda'],
            ':quantidade_estoque' => $dados['quantidade_estoque'],
            ':estoque_minimo'     => $dados['estoque_minimo'],
            ':codigo_barras'      => $dados['codigo_barras'] ?: null,
            ':ativo'              => $dados['ativo'] ? 'true' : 'false',
        ]);

        // Se quantidade inicial > 0, registra entrada de estoque
        if ($dados['quantidade_estoque'] > 0) {
            $stmtMov = $pdo->prepare("
                INSERT INTO movimentacao_estoque (produto_id, usuario_id, tipo, quantidade, motivo)
                VALUES (:prod_id, :usr_id, 'entrada', :qtd, 'Estoque inicial no cadastro do produto')
            ");
            $stmtMov->execute([
                ':prod_id' => $pdo->lastInsertId(),
                ':usr_id'  => usuarioLogadoId(),
                ':qtd'     => $dados['quantidade_estoque'],
            ]);
        }

        header('Location: listar.php?msg=criado');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Produto | ERP Moda</title>
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
                <div class="topbar-title">Novo Produto</div>
                <div class="topbar-breadcrumb"><i class="bi bi-house me-1"></i>Admin / Produtos / Novo</div>
            </div>
            <div class="ms-auto">
                <a href="listar.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Voltar
                </a>
            </div>
        </div>

        <main class="p-4 flex-grow-1 fade-in">
            <div class="page-header">
                <h2><i class="bi bi-plus-circle me-2 text-primary-custom"></i>Cadastrar Produto</h2>
            </div>

            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Corrija os erros abaixo:</strong>
                    <ul class="mb-0 mt-1">
                        <?php foreach ($erros as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="form-card">
                <div class="row g-3">

                    <!-- Nome -->
                    <div class="col-12 col-md-8">
                        <label for="nome" class="form-label">Nome do Produto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nome" name="nome"
                               value="<?= htmlspecialchars($dados['nome'] ?? '') ?>"
                               placeholder="Ex: Blusa Feminina Floral" required>
                    </div>

                    <!-- Código de Barras -->
                    <div class="col-12 col-md-4">
                        <label for="codigo_barras" class="form-label">Código de Barras</label>
                        <input type="text" class="form-control" id="codigo_barras" name="codigo_barras"
                               value="<?= htmlspecialchars($dados['codigo_barras'] ?? '') ?>"
                               placeholder="EAN-13 ou código interno">
                    </div>

                    <!-- Categoria -->
                    <div class="col-12 col-md-6">
                        <label for="categoria_id" class="form-label">Categoria</label>
                        <select class="form-select" id="categoria_id" name="categoria_id">
                            <option value="0">— Selecione a categoria —</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"
                                    <?= ($dados['categoria_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Fornecedor -->
                    <div class="col-12 col-md-6">
                        <label for="fornecedor_id" class="form-label">Fornecedor</label>
                        <select class="form-select" id="fornecedor_id" name="fornecedor_id">
                            <option value="0">— Selecione o fornecedor —</option>
                            <?php foreach ($fornecedores as $f): ?>
                                <option value="<?= $f['id'] ?>"
                                    <?= ($dados['fornecedor_id'] ?? 0) == $f['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($f['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Tamanho -->
                    <div class="col-6 col-md-3">
                        <label for="tamanho" class="form-label">Tamanho</label>
                        <input type="text" class="form-control" id="tamanho" name="tamanho"
                               value="<?= htmlspecialchars($dados['tamanho'] ?? '') ?>"
                               placeholder="PP, P, M, G, 36...">
                    </div>

                    <!-- Cor -->
                    <div class="col-6 col-md-3">
                        <label for="cor" class="form-label">Cor</label>
                        <input type="text" class="form-control" id="cor" name="cor"
                               value="<?= htmlspecialchars($dados['cor'] ?? '') ?>"
                               placeholder="Ex: Azul, Rosa...">
                    </div>

                    <!-- Preço de Custo -->
                    <div class="col-6 col-md-3">
                        <label for="preco_custo" class="form-label">Preço de Custo (R$) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" class="form-control" id="preco_custo" name="preco_custo"
                                   value="<?= htmlspecialchars($dados['preco_custo'] ?? '0.00') ?>"
                                   min="0" step="0.01" required>
                        </div>
                    </div>

                    <!-- Preço de Venda -->
                    <div class="col-6 col-md-3">
                        <label for="preco_venda" class="form-label">Preço de Venda (R$) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" class="form-control" id="preco_venda" name="preco_venda"
                                   value="<?= htmlspecialchars($dados['preco_venda'] ?? '0.00') ?>"
                                   min="0" step="0.01" required>
                        </div>
                    </div>

                    <!-- Quantidade em Estoque -->
                    <div class="col-6 col-md-3">
                        <label for="quantidade_estoque" class="form-label">Qtd. em Estoque</label>
                        <input type="number" class="form-control" id="quantidade_estoque" name="quantidade_estoque"
                               value="<?= htmlspecialchars($dados['quantidade_estoque'] ?? '0') ?>"
                               min="0" required>
                    </div>

                    <!-- Estoque Mínimo -->
                    <div class="col-6 col-md-3">
                        <label for="estoque_minimo" class="form-label">Estoque Mínimo</label>
                        <input type="number" class="form-control" id="estoque_minimo" name="estoque_minimo"
                               value="<?= htmlspecialchars($dados['estoque_minimo'] ?? '5') ?>"
                               min="0" required>
                        <div class="form-text">Alerta quando atingir este valor.</div>
                    </div>

                    <!-- Descrição -->
                    <div class="col-12">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"
                                  placeholder="Descrição detalhada do produto..."><?= htmlspecialchars($dados['descricao'] ?? '') ?></textarea>
                    </div>

                    <!-- Ativo -->
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="ativo" name="ativo"
                                   <?= ($dados['ativo'] ?? true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ativo">Produto ativo (disponível para venda)</label>
                        </div>
                    </div>

                    <!-- Botões -->
                    <div class="col-12 d-flex gap-2 pt-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-floppy me-1"></i>Salvar Produto
                        </button>
                        <a href="listar.php" class="btn btn-outline-secondary px-4">Cancelar</a>
                    </div>

                </div>
            </form>
        </main>
        <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmXKzlVzSwh/so7GXpTzm+O1oeN" crossorigin="anonymous"></script>
<script src="../../public/js/scripts.js"></script>
</body>
</html>
