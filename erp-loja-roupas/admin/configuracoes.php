<?php
/**
 * admin/configuracoes.php
 * Página de configurações gerais do sistema.
 * Permite o admin visualizar e atualizar dados básicos.
 */

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
verificarAcesso('admin');

if (!defined('BASE_URL')) define('BASE_URL', '../');

$pdo = getDB();
$msg = $_GET['msg'] ?? '';

// Estatísticas do banco de dados
$stats = [
    'usuarios'   => $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
    'produtos'   => $pdo->query("SELECT COUNT(*) FROM produtos WHERE ativo = TRUE")->fetchColumn(),
    'clientes'   => $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn(),
    'vendas'     => $pdo->query("SELECT COUNT(*) FROM vendas WHERE status = 'concluida'")->fetchColumn(),
    'categorias' => $pdo->query("SELECT COUNT(*) FROM categorias")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações | ERP Moda</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
<div id="sidebarOverlay" class="sidebar-overlay"></div>
<div class="app-wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <div class="content-wrapper">
        <div class="topbar">
            <button class="btn btn-link text-dark d-lg-none me-2 p-0" id="sidebarToggleBtn"><i class="bi bi-list fs-4"></i></button>
            <div><div class="topbar-title">Configurações</div><div class="topbar-breadcrumb">Admin / Configurações</div></div>
        </div>
        <main class="p-4 flex-grow-1 fade-in">
            <div class="page-header"><h2><i class="bi bi-gear me-2 text-primary-custom"></i>Configurações do Sistema</h2></div>

            <div class="row g-4">
                <!-- Estatísticas do banco -->
                <div class="col-12 col-md-6">
                    <div class="form-card">
                        <h6 class="fw-bold mb-3"><i class="bi bi-database me-2 text-primary-custom"></i>Estatísticas do Banco de Dados</h6>
                        <div class="list-group list-group-flush">
                            <?php foreach ([
                                'Usuários'   => $stats['usuarios'],
                                'Produtos'   => $stats['produtos'],
                                'Clientes'   => $stats['clientes'],
                                'Vendas'     => $stats['vendas'],
                                'Categorias' => $stats['categorias'],
                            ] as $label => $valor): ?>
                                <div class="list-group-item d-flex justify-content-between px-0">
                                    <span class="text-muted small"><?= $label ?></span>
                                    <span class="fw-bold"><?= number_format($valor) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Informações do sistema -->
                <div class="col-12 col-md-6">
                    <div class="form-card">
                        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary-custom"></i>Informações do Sistema</h6>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted small">Versão do PHP</span>
                                <span class="fw-bold"><?= PHP_VERSION ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted small">Banco de Dados</span>
                                <span class="fw-bold">PostgreSQL</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted small">Data/Hora do Servidor</span>
                                <span class="fw-bold"><?= date('d/m/Y H:i:s') ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted small">Usuário Logado</span>
                                <span class="fw-bold"><?= usuarioLogadoNome() ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Links rápidos para gestão -->
                <div class="col-12">
                    <div class="form-card">
                        <h6 class="fw-bold mb-3"><i class="bi bi-lightning me-2 text-warning"></i>Ações Rápidas</h6>
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <a href="funcionarios/cadastrar.php" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-person-plus d-block fs-4 mb-1"></i>
                                    <small>Novo Usuário</small>
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <a href="produtos/cadastrar.php" class="btn btn-outline-success w-100">
                                    <i class="bi bi-bag-plus d-block fs-4 mb-1"></i>
                                    <small>Novo Produto</small>
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <a href="estoque/movimentacoes.php" class="btn btn-outline-warning w-100">
                                    <i class="bi bi-boxes d-block fs-4 mb-1"></i>
                                    <small>Estoque</small>
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <a href="relatorios/vendas.php" class="btn btn-outline-info w-100">
                                    <i class="bi bi-bar-chart-line d-block fs-4 mb-1"></i>
                                    <small>Relatórios</small>
                                </a>
                            </div>
                        </div>
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
