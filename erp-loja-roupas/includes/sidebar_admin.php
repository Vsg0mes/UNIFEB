<?php
/**
 * includes/sidebar_admin.php
 * Menu lateral da área administrativa.
 * Requer que auth_check.php já tenha sido incluído.
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
?>
<!-- ========== SIDEBAR ADMIN ========== -->
<nav id="sidebar" class="sidebar d-flex flex-column">
    <!-- Logo / Brand -->
    <div class="sidebar-brand">
        <a href="<?= BASE_URL ?>admin/dashboard.php" class="d-flex align-items-center text-decoration-none">
            <i class="bi bi-shop-window fs-4 me-2 text-warning"></i>
            <span class="fw-bold text-white fs-5">ERP <span class="text-warning">Moda</span></span>
        </a>
        <!-- Botão para colapsar sidebar (mobile) -->
        <button class="btn btn-link text-white d-lg-none ms-auto sidebar-toggle-btn" id="sidebarCollapseBtn">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <!-- Perfil do usuário -->
    <div class="sidebar-user-info px-3 py-2">
        <div class="d-flex align-items-center gap-2">
            <div class="avatar-circle"><i class="bi bi-person-fill"></i></div>
            <div>
                <div class="text-white fw-semibold small"><?= usuarioLogadoNome() ?></div>
                <span class="badge bg-warning text-dark" style="font-size:.65rem;">Administrador</span>
            </div>
        </div>
    </div>

    <hr class="sidebar-divider">

    <!-- Menu de navegação -->
    <ul class="nav flex-column sidebar-nav px-2 flex-grow-1">

        <!-- Dashboard -->
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/dashboard.php"
               class="nav-link sidebar-link <?= $currentPage === 'dashboard.php' && $currentDir === 'admin' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>

        <!-- PDV -->
        <li class="nav-item">
            <a href="<?= BASE_URL ?>usuario/pdv.php" class="nav-link sidebar-link">
                <i class="bi bi-cash-register me-2"></i> PDV / Caixa
            </a>
        </li>

        <!-- Vendas -->
        <li class="nav-item">
            <a class="nav-link sidebar-link <?= $currentDir === 'vendas' ? 'active' : '' ?>"
               data-bs-toggle="collapse" href="#collapseVendas" role="button">
                <i class="bi bi-cart3 me-2"></i> Vendas
                <i class="bi bi-chevron-down ms-auto small"></i>
            </a>
            <div class="collapse <?= $currentDir === 'vendas' ? 'show' : '' ?>" id="collapseVendas">
                <ul class="nav flex-column ms-3">
                    <li><a href="<?= BASE_URL ?>usuario/vendas/historico.php" class="nav-link sidebar-link-sub">
                        <i class="bi bi-clock-history me-2"></i> Histórico
                    </a></li>
                </ul>
            </div>
        </li>

        <li class="sidebar-section-label">CADASTROS</li>

        <!-- Produtos -->
        <li class="nav-item">
            <a class="nav-link sidebar-link <?= $currentDir === 'produtos' ? 'active' : '' ?>"
               data-bs-toggle="collapse" href="#collapseProdutos" role="button">
                <i class="bi bi-bag me-2"></i> Produtos
                <i class="bi bi-chevron-down ms-auto small"></i>
            </a>
            <div class="collapse <?= $currentDir === 'produtos' ? 'show' : '' ?>" id="collapseProdutos">
                <ul class="nav flex-column ms-3">
                    <li><a href="<?= BASE_URL ?>admin/produtos/listar.php" class="nav-link sidebar-link-sub">
                        <i class="bi bi-list-ul me-2"></i> Listar
                    </a></li>
                    <li><a href="<?= BASE_URL ?>admin/produtos/cadastrar.php" class="nav-link sidebar-link-sub">
                        <i class="bi bi-plus-circle me-2"></i> Novo Produto
                    </a></li>
                </ul>
            </div>
        </li>

        <!-- Categorias -->
        <li class="nav-item">
            <a class="nav-link sidebar-link <?= $currentDir === 'categorias' ? 'active' : '' ?>"
               data-bs-toggle="collapse" href="#collapseCat" role="button">
                <i class="bi bi-tag me-2"></i> Categorias
                <i class="bi bi-chevron-down ms-auto small"></i>
            </a>
            <div class="collapse <?= $currentDir === 'categorias' ? 'show' : '' ?>" id="collapseCat">
                <ul class="nav flex-column ms-3">
                    <li><a href="<?= BASE_URL ?>admin/categorias/listar.php" class="nav-link sidebar-link-sub">
                        <i class="bi bi-list-ul me-2"></i> Listar
                    </a></li>
                    <li><a href="<?= BASE_URL ?>admin/categorias/cadastrar.php" class="nav-link sidebar-link-sub">
                        <i class="bi bi-plus-circle me-2"></i> Nova Categoria
                    </a></li>
                </ul>
            </div>
        </li>

        <!-- Fornecedores -->
        <li class="nav-item">
            <a class="nav-link sidebar-link <?= $currentDir === 'fornecedores' ? 'active' : '' ?>"
               data-bs-toggle="collapse" href="#collapseForns" role="button">
                <i class="bi bi-truck me-2"></i> Fornecedores
                <i class="bi bi-chevron-down ms-auto small"></i>
            </a>
            <div class="collapse <?= $currentDir === 'fornecedores' ? 'show' : '' ?>" id="collapseForns">
                <ul class="nav flex-column ms-3">
                    <li><a href="<?= BASE_URL ?>admin/fornecedores/listar.php" class="nav-link sidebar-link-sub">
                        <i class="bi bi-list-ul me-2"></i> Listar
                    </a></li>
                    <li><a href="<?= BASE_URL ?>admin/fornecedores/cadastrar.php" class="nav-link sidebar-link-sub">
                        <i class="bi bi-plus-circle me-2"></i> Novo Fornecedor
                    </a></li>
                </ul>
            </div>
        </li>

        <!-- Clientes -->
        <li class="nav-item">
            <a class="nav-link sidebar-link <?= $currentDir === 'clientes' ? 'active' : '' ?>"
               data-bs-toggle="collapse" href="#collapseClientes" role="button">
                <i class="bi bi-people me-2"></i> Clientes
                <i class="bi bi-chevron-down ms-auto small"></i>
            </a>
            <div class="collapse <?= $currentDir === 'clientes' ? 'show' : '' ?>" id="collapseClientes">
                <ul class="nav flex-column ms-3">
                    <li><a href="<?= BASE_URL ?>admin/clientes/listar.php" class="nav-link sidebar-link-sub">
                        <i class="bi bi-list-ul me-2"></i> Listar
                    </a></li>
                    <li><a href="<?= BASE_URL ?>admin/clientes/cadastrar.php" class="nav-link sidebar-link-sub">
                        <i class="bi bi-plus-circle me-2"></i> Novo Cliente
                    </a></li>
                </ul>
            </div>
        </li>

        <!-- Funcionários -->
        <li class="nav-item">
            <a class="nav-link sidebar-link <?= $currentDir === 'funcionarios' ? 'active' : '' ?>"
               data-bs-toggle="collapse" href="#collapseFunc" role="button">
                <i class="bi bi-person-badge me-2"></i> Funcionários
                <i class="bi bi-chevron-down ms-auto small"></i>
            </a>
            <div class="collapse <?= $currentDir === 'funcionarios' ? 'show' : '' ?>" id="collapseFunc">
                <ul class="nav flex-column ms-3">
                    <li><a href="<?= BASE_URL ?>admin/funcionarios/listar.php" class="nav-link sidebar-link-sub">
                        <i class="bi bi-list-ul me-2"></i> Listar
                    </a></li>
                    <li><a href="<?= BASE_URL ?>admin/funcionarios/cadastrar.php" class="nav-link sidebar-link-sub">
                        <i class="bi bi-plus-circle me-2"></i> Novo Funcionário
                    </a></li>
                </ul>
            </div>
        </li>

        <li class="sidebar-section-label">OPERAÇÕES</li>

        <!-- Estoque -->
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/estoque/movimentacoes.php"
               class="nav-link sidebar-link <?= $currentDir === 'estoque' ? 'active' : '' ?>">
                <i class="bi bi-boxes me-2"></i> Estoque
            </a>
        </li>

        <!-- Relatórios -->
        <li class="nav-item">
            <a class="nav-link sidebar-link <?= $currentDir === 'relatorios' ? 'active' : '' ?>"
               data-bs-toggle="collapse" href="#collapseRel" role="button">
                <i class="bi bi-bar-chart-line me-2"></i> Relatórios
                <i class="bi bi-chevron-down ms-auto small"></i>
            </a>
            <div class="collapse <?= $currentDir === 'relatorios' ? 'show' : '' ?>" id="collapseRel">
                <ul class="nav flex-column ms-3">
                    <li><a href="<?= BASE_URL ?>admin/relatorios/vendas.php" class="nav-link sidebar-link-sub">
                        <i class="bi bi-graph-up me-2"></i> Vendas
                    </a></li>
                    <li><a href="<?= BASE_URL ?>admin/relatorios/estoque.php" class="nav-link sidebar-link-sub">
                        <i class="bi bi-clipboard-data me-2"></i> Estoque
                    </a></li>
                </ul>
            </div>
        </li>

        <!-- Configurações -->
        <li class="nav-item">
            <a href="<?= BASE_URL ?>admin/configuracoes.php"
               class="nav-link sidebar-link <?= $currentPage === 'configuracoes.php' ? 'active' : '' ?>">
                <i class="bi bi-gear me-2"></i> Configurações
            </a>
        </li>
    </ul>

    <hr class="sidebar-divider">

    <!-- Logout -->
    <div class="px-3 pb-3">
        <a href="<?= BASE_URL ?>auth/logout.php"
           class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-box-arrow-right"></i> Sair do Sistema
        </a>
    </div>
</nav>
<!-- ========== FIM SIDEBAR ADMIN ========== -->
