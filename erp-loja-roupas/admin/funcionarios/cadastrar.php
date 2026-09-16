<?php
/**
 * admin/funcionarios/cadastrar.php
 * Cadastro e edição de funcionários/usuários do sistema.
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
verificarAcesso('admin');

if (!defined('BASE_URL')) define('BASE_URL', '../../');

$pdo      = getDB();
$erros    = [];
$editando = false;
$dados    = ['perfil' => 'usuario', 'ativo' => true];

// Modo edição
$idEditar = (int)($_GET['editar'] ?? 0);
if ($idEditar > 0) {
    $stmt = $pdo->prepare("SELECT id, nome, email, perfil, ativo FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $idEditar]);
    $dados    = $stmt->fetch() ?: [];
    $editando = !empty($dados);
}

// Processamento POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPost = (int)($_POST['id'] ?? 0);
    $dados  = [
        'nome'   => trim($_POST['nome']   ?? ''),
        'email'  => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
        'perfil' => in_array($_POST['perfil'] ?? '', ['admin','usuario']) ? $_POST['perfil'] : 'usuario',
        'senha'  => trim($_POST['senha']  ?? ''),
        'ativo'  => isset($_POST['ativo']),
    ];

    if (empty($dados['nome']))             $erros[] = 'O nome é obrigatório.';
    if (empty($dados['email']))            $erros[] = 'O e-mail é obrigatório.';
    if (!$editando && empty($dados['senha'])) $erros[] = 'A senha é obrigatória.';
    if (!empty($dados['senha']) && strlen($dados['senha']) < 6) $erros[] = 'A senha deve ter no mínimo 6 caracteres.';

    // E-mail único
    $stmtDup = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :id LIMIT 1");
    $stmtDup->execute([':email' => $dados['email'], ':id' => $idPost]);
    if ($stmtDup->fetch()) $erros[] = 'E-mail já cadastrado.';

    if (empty($erros)) {
        if ($idPost > 0) {
            // Atualiza (senha opcional)
            if (!empty($dados['senha'])) {
                $stmt = $pdo->prepare("UPDATE usuarios SET nome=:nome,email=:email,perfil=:perfil,senha=:senha,ativo=:ativo WHERE id=:id");
                $stmt->execute([':nome'=>$dados['nome'],':email'=>$dados['email'],':perfil'=>$dados['perfil'],
                                ':senha'=>password_hash($dados['senha'], PASSWORD_BCRYPT),':ativo'=>$dados['ativo']?'true':'false',':id'=>$idPost]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nome=:nome,email=:email,perfil=:perfil,ativo=:ativo WHERE id=:id");
                $stmt->execute([':nome'=>$dados['nome'],':email'=>$dados['email'],':perfil'=>$dados['perfil'],
                                ':ativo'=>$dados['ativo']?'true':'false',':id'=>$idPost]);
            }
            header('Location: listar.php?msg=editado');
        } else {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome,email,senha,perfil,ativo) VALUES (:nome,:email,:senha,:perfil,:ativo)");
            $stmt->execute([':nome'=>$dados['nome'],':email'=>$dados['email'],
                            ':senha'=>password_hash($dados['senha'], PASSWORD_BCRYPT),
                            ':perfil'=>$dados['perfil'],':ativo'=>$dados['ativo']?'true':'false']);
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
    <title><?= $editando ? 'Editar' : 'Novo' ?> Funcionário | ERP Moda</title>
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
            <div><div class="topbar-title"><?= $editando ? 'Editar' : 'Novo' ?> Funcionário</div></div>
            <div class="ms-auto"><a href="listar.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Voltar</a></div>
        </div>
        <main class="p-4 flex-grow-1 fade-in">
            <div class="page-header"><h2><i class="bi bi-person-badge me-2 text-primary-custom"></i><?= $editando ? 'Editar' : 'Novo' ?> Funcionário</h2></div>

            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger mb-3">
                    <?php foreach ($erros as $e): ?><div><i class="bi bi-x-circle me-1"></i><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="form-card" autocomplete="off">
                <?php if ($editando): ?><input type="hidden" name="id" value="<?= (int)$dados['id'] ?>"><?php endif; ?>
                <div class="row g-3">
                    <div class="col-12 col-md-8">
                        <label class="form-label">Nome Completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nome" value="<?= htmlspecialchars($dados['nome'] ?? '') ?>" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Perfil de Acesso <span class="text-danger">*</span></label>
                        <select class="form-select" name="perfil">
                            <option value="usuario" <?= ($dados['perfil'] ?? '') === 'usuario' ? 'selected' : '' ?>>Vendedor (Usuário)</option>
                            <option value="admin"   <?= ($dados['perfil'] ?? '') === 'admin'   ? 'selected' : '' ?>>Administrador</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">E-mail <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($dados['email'] ?? '') ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">
                            Senha <?= $editando ? '(deixe em branco para manter)' : '<span class="text-danger">*</span>' ?>
                        </label>
                        <input type="password" class="form-control" name="senha" autocomplete="new-password"
                               placeholder="Mínimo 6 caracteres" <?= !$editando ? 'required' : '' ?>>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="ativo" name="ativo"
                                   <?= ($dados['ativo'] ?? true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="ativo">Usuário ativo (pode fazer login)</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-info d-flex align-items-center gap-2 py-2">
                            <i class="bi bi-info-circle-fill"></i>
                            <small>A senha padrão de demonstração é <strong>Admin@123</strong>. Altere-a após o primeiro login.</small>
                        </div>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-floppy me-1"></i><?= $editando ? 'Atualizar' : 'Criar Usuário' ?>
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
