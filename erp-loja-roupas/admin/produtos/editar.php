<?php
/**
 * admin/produtos/editar.php
 * Formulário de edição de produto existente.
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
verificarAcesso('admin');

if (!defined('BASE_URL')) define('BASE_URL', '../../');

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

// Carrega o produto pelo ID
$stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = :id");
$stmt->execute([':id' => $id]);
$produto = $stmt->fetch();

if (!$produto) {
    header('Location: listar.php?msg=erro&tipo=erro');
    exit;
}

$categorias   = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();
$fornecedores = $pdo->query("SELECT id, nome FROM fornecedores WHERE ativo = TRUE ORDER BY nome")->fetchAll();
$erros = [];

// -----------------------------------------------------------
// Processamento do POST (atualização)
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

    if (empty($dados['nome']))                $erros[] = 'O nome do produto é obrigatório.';
    if (!is_numeric($dados['preco_venda']))   $erros[] = 'Preço de venda inválido.';

    // Verifica código de barras duplicado (excluindo o próprio produto)
    if (!empty($dados['codigo_barras'])) {
        $stmtCB = $pdo->prepare("SELECT id FROM produtos WHERE codigo_barras = :cb AND id != :id LIMIT 1");
        $stmtCB->execute([':cb' => $dados['codigo_barras'], ':id' => $id]);
        if ($stmtCB->fetch()) $erros[] = 'Código de barras já cadastrado para outro produto.';
    }

    if (empty($erros)) {
        // Verifica se houve ajuste de estoque manual (diferença para registrar movimentação)
        $diffEstoque = $dados['quantidade_estoque'] - (int)$produto['quantidade_estoque'];

        $stmt = $pdo->prepare("
            UPDATE produtos SET
                nome = :nome, descricao = :descricao, categoria_id = :categoria_id,
                fornecedor_id = :fornecedor_id, tamanho = :tamanho, cor = :cor,
                preco_custo = :preco_custo, preco_venda = :preco_venda,
                quantidade_estoque = :quantidade_estoque, estoque_minimo = :estoque_minimo,
                codigo_barras = :codigo_barras, ativo = :ativo
            WHERE id = :id
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
            ':id'                 => $id,
        ]);

        // Registra movimentação de estoque se houve alteração
        if ($diffEstoque !== 0) {
            $tipo   = $diffEstoque > 0 ? 'entrada' : 'saida';
            $qtd    = abs($diffEstoque);
            $stmtMov = $pdo->prepare("
                INSERT INTO movimentacao_estoque (produto_id, usuario_id, tipo, quantidade, motivo)
                VALUES (:prod_id, :usr_id, :tipo, :qtd, 'Ajuste manual via edição do produto')
            ");
            $stmtMov->execute([
                ':prod_id' => $id,
                ':usr_id'  => usuarioLogadoId(),
                ':tipo'    => $tipo,
                ':qtd'     => $qtd,
            ]);
        }

        header('Location: listar.php?msg=editado');
        exit;
    }

    // Em caso de erro, usa os dados do POST para repopular
    $produto = array_merge($produto, $dados);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produto | ERP Moda</title>
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
                <div class="topbar-title">Editar Produto</div>
                <div class="topbar-breadcrumb">Admin / Produtos / Editar</div>
            </div>
            <div class="ms-auto">
                <a href="listar.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Voltar
                </a>
            </div>
        </div>
        <main class="p-4 flex-grow-1 fade-in">
            <div class="page-header">
                <h2><i class="bi bi-pencil-square me-2 text-warning"></i>Editar Produto</h2>
                <p class="text-muted small mb-0">ID #<?= $id ?> — <?= htmlspecialchars($produto['nome']) ?></p>
            </div>

            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <ul class="mb-0"><?php foreach ($erros as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form method="POST" class="form-card">
                <div class="row g-3">
                    <div class="col-12 col-md-8">
                        <label for="nome" class="form-label">Nome <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nome" name="nome"
                               value="<?= htmlspecialchars($produto['nome']) ?>" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="codigo_barras" class="form-label">Código de Barras</label>
                        <input type="text" class="form-control" id="codigo_barras" name="codigo_barras"
                               value="<?= htmlspecialchars($produto['codigo_barras'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="categoria_id" class="form-label">Categoria</label>
                        <select class="form-select" id="categoria_id" name="categoria_id">
                            <option value="0">— Selecione —</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $produto['categoria_id'] == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="fornecedor_id" class="form-label">Fornecedor</label>
                        <select class="form-select" id="fornecedor_id" name="fornecedor_id">
                            <option value="0">— Selecione —</option>
                            <?php foreach ($fornecedores as $f): ?>
                                <option value="<?= $f['id'] ?>" <?= $produto['fornecedor_id'] == $f['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($f['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="tamanho" class="form-label">Tamanho</label>
                        <input type="text" class="form-control" id="tamanho" name="tamanho"
                               value="<?= htmlspecialchars($produto['tamanho'] ?? '') ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="cor" class="form-label">Cor</label>
                        <input type="text" class="form-control" id="cor" name="cor"
                               value="<?= htmlspecialchars($produto['cor'] ?? '') ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="preco_custo" class="form-label">Preço de Custo</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" class="form-control" id="preco_custo" name="preco_custo"
                                   value="<?= number_format($produto['preco_custo'], 2, '.', '') ?>" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="preco_venda" class="form-label">Preço de Venda <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" class="form-control" id="preco_venda" name="preco_venda"
                                   value="<?= number_format($produto['preco_venda'], 2, '.', '') ?>" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="quantidade_estoque" class="form-label">Qtd. em Estoque</label>
                        <input type="number" class="form-control" id="quantidade_estoque" name="quantidade_estoque"
                               value="<?= (int)$produto['quantidade_estoque'] ?>" min="0">
                        <div class="form-text text-warning small">Alterações registram movimentação de estoque.</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="estoque_minimo" class="form-label">Estoque Mínimo</label>
                        <input type="number" class="form-control" id="estoque_minimo" name="estoque_minimo"
                               value="<?= (int)$produto['estoque_minimo'] ?>" min="0">
                    </div>
                    <div class="col-12">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"><?= htmlspecialchars($produto['descricao'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="ativo" name="ativo"
                                   <?= $produto['ativo'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ativo">Produto ativo</label>
                        </div>
                    </div>
                    <div class="col-12 d-flex gap-2 pt-2">
                        <button type="submit" class="btn btn-warning px-4">
                            <i class="bi bi-floppy me-1"></i>Atualizar Produto
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
