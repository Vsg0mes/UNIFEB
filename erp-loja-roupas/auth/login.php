<?php
/**
 * auth/login.php
 * Tela de autenticação do sistema ERP.
 * Processa o login e cria a sessão com os dados do usuário.
 */

// Inicia sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se já está logado, redireciona para o dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: ../admin/dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Define URL base para os includes
if (!defined('BASE_URL')) {
    define('BASE_URL', '../');
}

$erro    = '';
$sucesso = '';

// -----------------------------------------------------------
// Mensagens de redirecionamento via query string
// -----------------------------------------------------------
$mensagensErro = [
    'sessao_expirada'  => 'Sua sessão expirou. Faça login novamente.',
    'acesso_negado'    => 'Acesso negado. Você não tem permissão para esta área.',
    'conta_desativada' => 'Sua conta foi desativada. Contate o administrador.',
];
if (!empty($_GET['erro']) && isset($mensagensErro[$_GET['erro']])) {
    $erro = $mensagensErro[$_GET['erro']];
}
if (!empty($_GET['sucesso']) && $_GET['sucesso'] === 'logout') {
    $sucesso = 'Logout realizado com sucesso. Até logo!';
}

// -----------------------------------------------------------
// Processamento do formulário de login (POST)
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitiza inputs
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = trim($_POST['senha'] ?? '');

    if (empty($email) || empty($senha)) {
        $erro = 'Preencha o e-mail e a senha.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare(
            'SELECT id, nome, email, senha, perfil, ativo FROM usuarios WHERE email = :email LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        if ($usuario && $usuario['ativo'] && password_verify($senha, $usuario['senha'])) {
            // Regenera ID de sessão para prevenir session fixation
            session_regenerate_id(true);

            // Armazena dados do usuário na sessão
            $_SESSION['usuario_id']     = $usuario['id'];
            $_SESSION['usuario_nome']   = $usuario['nome'];
            $_SESSION['usuario_email']  = $usuario['email'];
            $_SESSION['usuario_perfil'] = $usuario['perfil'];
            $_SESSION['usuario_ativo']  = $usuario['ativo'];
            $_SESSION['login_time']     = time();

            // Redireciona conforme o perfil
            if ($usuario['perfil'] === 'admin') {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: ../usuario/dashboard.php');
            }
            exit;

        } elseif ($usuario && !$usuario['ativo']) {
            $erro = 'Sua conta está desativada. Contate o administrador.';
        } else {
            $erro = 'E-mail ou senha inválidos.';
        }
    }
}

// Define variáveis para o layout de login (sem sidebar)
$pageTitle = 'Login';
$bodyClass = 'login-body';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | ERP Moda</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body class="login-body">

<div class="login-wrapper">
    <div class="login-card shadow-lg">

        <!-- Logo -->
        <div class="login-logo text-center mb-4">
            <div class="login-icon-wrap mb-3">
                <i class="bi bi-shop-window"></i>
            </div>
            <h1 class="h3 fw-bold text-white mb-1">ERP <span class="text-warning">Moda</span></h1>
            <p class="text-white-50 small mb-0">Sistema de Gestão — Loja de Roupas</p>
        </div>

        <!-- Alertas -->
        <?php if ($erro): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" id="alertaErro">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($erro) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($sucesso) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formulário de Login -->
        <form method="POST" action="" id="formLogin" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label text-white-50 small fw-semibold">E-MAIL</label>
                <div class="input-group">
                    <span class="input-group-text login-input-icon">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email"
                           class="form-control login-input"
                           id="email"
                           name="email"
                           placeholder="seu@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required
                           autocomplete="email">
                </div>
            </div>

            <div class="mb-4">
                <label for="senha" class="form-label text-white-50 small fw-semibold">SENHA</label>
                <div class="input-group">
                    <span class="input-group-text login-input-icon">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password"
                           class="form-control login-input"
                           id="senha"
                           name="senha"
                           placeholder="••••••••"
                           required
                           autocomplete="current-password">
                    <button class="btn login-input-icon" type="button" id="toggleSenha" title="Mostrar/Ocultar senha">
                        <i class="bi bi-eye" id="iconeSenha"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold login-btn" id="btnLogin">
                <i class="bi bi-box-arrow-in-right me-2"></i> Entrar no Sistema
            </button>
        </form>

        <!-- Credenciais de demonstração -->
        <div class="login-demo-box mt-4">
            <p class="text-white-50 small text-center mb-2"><i class="bi bi-info-circle me-1"></i>Credenciais de demo:</p>
            <div class="row g-2">
                <div class="col-6">
                    <div class="demo-cred" onclick="preencherLogin('admin@erploja.com','Admin@123')">
                        <i class="bi bi-shield-fill-check text-warning me-1"></i>
                        <span class="small text-white">Admin</span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="demo-cred" onclick="preencherLogin('carlos@erploja.com','Admin@123')">
                        <i class="bi bi-person-fill text-info me-1"></i>
                        <span class="small text-white">Vendedor</span>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /login-card -->
</div><!-- /login-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmXKzlVzSwh/so7GXpTzm+O1oeN"
        crossorigin="anonymous"></script>
<script src="../public/js/scripts.js"></script>
<script>
    // Alterna visibilidade da senha
    document.getElementById('toggleSenha')?.addEventListener('click', () => {
        const input = document.getElementById('senha');
        const icone = document.getElementById('iconeSenha');
        if (input.type === 'password') {
            input.type = 'text';
            icone.className = 'bi bi-eye-slash';
        } else {
            input.type = 'password';
            icone.className = 'bi bi-eye';
        }
    });

    // Preenche os campos de login com as credenciais de demo
    function preencherLogin(email, senha) {
        document.getElementById('email').value = email;
        document.getElementById('senha').value = senha;
        document.getElementById('formLogin').classList.add('was-validated');
    }
</script>
</body>
</html>
