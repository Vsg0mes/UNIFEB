<?php
/**
 * admin/fornecedores/cadastrar.php
 * Formulário de cadastro e edição de fornecedor.
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
verificarAcesso('admin');

if (!defined('BASE_URL')) define('BASE_URL', '../../');

$pdo    = getDB();
$erros  = [];
$editando = false;
$dados  = ['ativo' => true];

// Modo edição
$idEditar = (int)($_GET['editar'] ?? 0);
if ($idEditar > 0) {
    $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id = :id");
    $stmt->execute([':id' => $idEditar]);
    $dados    = $stmt->fetch() ?: [];
    $editando = !empty($dados);
}

// Processamento POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPost = (int)($_POST['id'] ?? 0);
    $dados  = [
        'nome'     => trim($_POST['nome']     ?? ''),
        'cnpj'     => trim($_POST['cnpj']     ?? ''),
        'telefone' => trim($_POST['telefone'] ?? ''),
        'email'    => trim($_POST['email']    ?? ''),
        'endereco' => trim($_POST['endereco'] ?? ''),
        'ativo'    => isset($_POST['ativo']),
    ];

    if (empty($dados['nome'])) $erros[] = 'O nome do fornecedor é obrigatório.';

    // Verifica CNPJ duplicado
    if (!empty($dados['cnpj'])) {
        $stmtDup = $pdo->prepare("SELECT id FROM fornecedores WHERE cnpj = :cnpj AND id != :id LIMIT 1");
        $stmtDup->execute([':cnpj' => $dados['cnpj'], ':id' => $idPost]);
        if ($stmtDup->fetch()) $erros[] = 'CNPJ já cadastrado.';
    }

    if (empty($erros)) {
        if ($idPost > 0) {
            // Atualização
            $stmt = $pdo->prepare("UPDATE fornecedores SET nome=:nome,cnpj=:cnpj,telefone=:tel,email=:email,endereco=:end,ativo=:ativo WHERE id=:id");
            $stmt->execute([':nome'=>$dados['nome'],':cnpj'=>$dados['cnpj']?:null,':tel'=>$dados['telefone']?:null,
                            ':email'=>$dados['email']?:null,':end'=>$dados['endereco']?:null,':ativo'=>$dados['ativo']?'true':'false',':id'=>$idPost]);
            header('Location: listar.php?msg=editado');
        } else {
            // Inserção
            $stmt = $pdo->prepare("INSERT INTO fornecedores (nome,cnpj,telefone,email,endereco,ativo) VALUES (:nome,:cnpj,:tel,:email,:end,:ativo)");
            $stmt->execute([':nome'=>$dados['nome'],':cnpj'=>$dados['cnpj']?:null,':tel'=>$dados['telefone']?:null,
                            ':email'=>$dados['email']?:null,':end'=>$dados['endereco']?:null,':ativo'=>$dados['ativo']?'true':'false']);
            header('Location: listar.php?msg=criado');
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editando ? 'Editar' : 'Novo' ?> Fornecedor | ERP Moda</title>
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
            <div><div class="topbar-title"><?= $editando ? 'Editar' : 'Novo' ?> Fornecedor</div></div>
            <div class="ms-auto"><a href="listar.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Voltar</a></div>
        </div>
        <main class="p-4 flex-grow-1 fade-in">
            <div class="page-header"><h2><i class="bi bi-truck me-2 text-primary-custom"></i><?= $editando ? 'Editar' : 'Novo' ?> Fornecedor</h2></div>

            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger mb-3">
                    <?php foreach ($erros as $e): ?><div><i class="bi bi-x-circle me-1"></i><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="form-card">
                <?php if ($editando): ?><input type="hidden" name="id" value="<?= (int)$dados['id'] ?>"><?php endif; ?>
                <div class="row g-3">
                    <div class="col-12 col-md-8">
                        <label class="form-label">Nome <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nome" value="<?= htmlspecialchars($dados['nome'] ?? '') ?>" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">CNPJ</label>
                        <input type="text" class="form-control" name="cnpj" data-mask="cnpj"
                               value="<?= htmlspecialchars($dados['cnpj'] ?? '') ?>" placeholder="00.000.000/0000-00">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Telefone</label>
                        <input type="text" class="form-control" name="telefone" data-mask="telefone"
                               value="<?= htmlspecialchars($dados['telefone'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label">E-mail</label>
                        <input type="email" class="form-control" name="email"
                               value="<?= htmlspecialchars($dados['email'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Endereço</label>
                        <input type="text" class="form-control" name="endereco"
                               value="<?= htmlspecialchars($dados['endereco'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="ativo" name="ativo"
                                   <?= ($dados['ativo'] ?? true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ativo">Fornecedor ativo</label>
                        </div>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-floppy me-1"></i><?= $editando ? 'Atualizar' : 'Salvar' ?>
                        </button>
                        <a href="listar.php" class="btn btn-outline-secondary">Cancelar</a>
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
