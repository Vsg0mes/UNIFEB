<?php
/**
 * usuario/perfil.php
 * Página de perfil do usuário logado — permite alterar nome e senha.
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
verificarAcesso();

if (!defined('BASE_URL')) define('BASE_URL', '../');

$pdo       = getDB();
$usuarioId = usuarioLogadoId();
$erros     = [];
$sucesso   = '';

// Carrega dados do usuário logado
$stmt = $pdo->prepare("SELECT id, nome, email, perfil, data_criacao FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $usuarioId]);
$usuario = $stmt->fetch();

// Processamento POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome        = trim($_POST['nome']        ?? '');
    $senhaAtual  = trim($_POST['senha_atual'] ?? '');
    $novaSenha   = trim($_POST['nova_senha']  ?? '');
    $confirmaSenha = trim($_POST['confirma_senha'] ?? '');

    if (empty($nome)) $erros[] = 'O nome é obrigatório.';

    // Troca de senha
    if (!empty($senhaAtual) || !empty($novaSenha)) {
        // Verifica senha atual
        $stmtSenha = $pdo->prepare("SELECT senha FROM usuarios WHERE id = :id");
        $stmtSenha->execute([':id' => $usuarioId]);
        $hashAtual = $stmtSenha->fetchColumn();

        if (!password_verify($senhaAtual, $hashAtual)) {
            $erros[] = 'Senha atual incorreta.';
        } elseif (strlen($novaSenha) < 6) {
            $erros[] = 'A nova senha deve ter no mínimo 6 caracteres.';
        } elseif ($novaSenha !== $confirmaSenha) {
            $erros[] = 'A confirmação da senha não confere.';
        }
    }

    if (empty($erros)) {
        if (!empty($novaSenha) && !empty($senhaAtual)) {
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = :nome, senha = :senha WHERE id = :id");
            $stmt->execute([':nome' => $nome, ':senha' => password_hash($novaSenha, PASSWORD_BCRYPT), ':id' => $usuarioId]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = :nome WHERE id = :id");
            $stmt->execute([':nome' => $nome, ':id' => $usuarioId]);
        }
        // Atualiza sessão
        $_SESSION['usuario_nome'] = $nome;
        $usuario['nome']          = $nome;
        $sucesso = 'Perfil atualizado com sucesso!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil | ERP Moda</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
<div id="sidebarOverlay" class="sidebar-overlay"></div>
<div class="app-wrapper">
    <?php
    if (isAdmin()) { require_once __DIR__ . '/../includes/sidebar_admin.php'; }
    else           { require_once __DIR__ . '/../includes/sidebar_user.php';  }
    ?>
    <div class="content-wrapper">
        <div class="topbar">
            <button class="btn btn-link text-dark d-lg-none me-2 p-0" id="sidebarToggleBtn"><i class="bi bi-list fs-4"></i></button>
            <div><div class="topbar-title">Meu Perfil</div></div>
        </div>
        <main class="p-4 flex-grow-1 fade-in">
            <div class="page-header"><h2><i class="bi bi-person-circle me-2 text-primary-custom"></i>Meu Perfil</h2></div>

            <?php if ($sucesso): ?>
                <div class="alert alert-success alert-auto-close alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($sucesso) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($erros as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Card de info do usuário -->
                <div class="col-12 col-md-4">
                    <div class="form-card text-center">
                        <div class="avatar-circle mx-auto mb-3"
                             style="width:80px;height:80px;font-size:2rem;<?= isAdmin() ? '' : 'background:linear-gradient(135deg,#06b6d4,#6366f1);' ?>">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <h5 class="fw-bold"><?= htmlspecialchars($usuario['nome']) ?></h5>
                        <p class="text-muted small mb-1"><?= htmlspecialchars($usuario['email']) ?></p>
                        <?= $usuario['perfil'] === 'admin'
                            ? '<span class="badge bg-warning text-dark">Administrador</span>'
                            : '<span class="badge bg-info text-dark">Vendedor</span>'
                        ?>
                        <hr>
                        <div class="text-muted small">
                            <i class="bi bi-calendar me-1"></i>
                            Membro desde <?= date('d/m/Y', strtotime($usuario['data_criacao'])) ?>
                        </div>
                    </div>
                </div>

                <!-- Formulário de edição -->
                <div class="col-12 col-md-8">
                    <div class="form-card">
                        <h6 class="fw-bold mb-3"><i class="bi bi-pencil-square me-2 text-primary-custom"></i>Editar Dados</h6>
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">E-mail</label>
                                    <input type="email" class="form-control" value="<?= htmlspecialchars($usuario['email']) ?>" readonly disabled>
                                    <div class="form-text">O e-mail não pode ser alterado aqui. Solicite ao administrador.</div>
                                </div>

                                <div class="col-12 mt-2">
                                    <h6 class="fw-semibold text-muted border-bottom pb-2 mb-3">
                                        <i class="bi bi-lock me-1"></i>Alterar Senha (opcional)
                                    </h6>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Senha Atual</label>
                                    <input type="password" class="form-control" name="senha_atual" autocomplete="current-password">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Nova Senha</label>
                                    <input type="password" class="form-control" name="nova_senha" autocomplete="new-password" placeholder="Mínimo 6 caracteres">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Confirmar Nova Senha</label>
                                    <input type="password" class="form-control" name="confirma_senha" autocomplete="new-password">
                                </div>
                                <div class="col-12 pt-2">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bi bi-floppy me-1"></i>Salvar Alterações
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
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
