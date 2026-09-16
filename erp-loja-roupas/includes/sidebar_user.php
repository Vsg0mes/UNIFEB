<?php
/**
 * includes/sidebar_user.php
 * Menu lateral simplificado para o perfil Usuário (vendedor/operador de caixa).
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
?>
<!-- ========== SIDEBAR USUÁRIO ========== -->
<nav id="sidebar" class="sidebar d-flex flex-column">
    <!-- Logo / Brand -->
    <div class="sidebar-brand">
        <a href="<?= BASE_URL ?>usuario/dashboard.php" class="d-flex align-items-center text-decoration-none">
            <i class="bi bi-shop-window fs-4 me-2 text-info"></i>
            <span class="fw-bold text-white fs-5">ERP <span class="text-info">Moda</span></span>
        </a>
        <button class="btn btn-link text-white d-lg-none ms-auto sidebar-toggle-btn" id="sidebarCollapseBtn">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <!-- Perfil do usuário -->
    <div class="sidebar-user-info px-3 py-2">
        <div class="d-flex align-items-center gap-2">
            <div class="avatar-circle avatar-circle-user"><i class="bi bi-person-fill"></i></div>
            <div>
                <div class="text-white fw-semibold small"><?= usuarioLogadoNome() ?></div>
                <span class="badge bg-info text-dark" style="font-size:.65rem;">Vendedor</span>
            </div>
        </div>
    </div>

    <hr class="sidebar-divider">

    <ul class="nav flex-column sidebar-nav px-2 flex-grow-1">

        <!-- Dashboard -->
        <li class="nav-item">
            <a href="<?= BASE_URL ?>usuario/dashboard.php"
               class="nav-link sidebar-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-house me-2"></i> Início
            </a>
        </li>

        <!-- PDV -->
        <li class="nav-item">
            <a href="<?= BASE_URL ?>usuario/pdv.php"
               class="nav-link sidebar-link <?= $currentPage === 'pdv.php' ? 'active' : '' ?>">
                <i class="bi bi-cash-register me-2"></i> PDV / Caixa
                <span class="badge bg-success ms-auto">VENDER</span>
            </a>
        </li>

        <li class="sidebar-section-label">MINHAS VENDAS</li>

        <!-- Nova Venda -->
        <li class="nav-item">
            <a href="<?= BASE_URL ?>usuario/vendas/nova_venda.php"
               class="nav-link sidebar-link <?= $currentPage === 'nova_venda.php' ? 'active' : '' ?>">
                <i class="bi bi-plus-circle me-2"></i> Nova Venda
            </a>
        </li>

        <!-- Histórico -->
        <li class="nav-item">
            <a href="<?= BASE_URL ?>usuario/vendas/historico.php"
               class="nav-link sidebar-link <?= $currentPage === 'historico.php' ? 'active' : '' ?>">
                <i class="bi bi-clock-history me-2"></i> Histórico de Vendas
            </a>
        </li>

        <li class="sidebar-section-label">MINHA CONTA</li>

        <!-- Perfil -->
        <li class="nav-item">
            <a href="<?= BASE_URL ?>usuario/perfil.php"
               class="nav-link sidebar-link <?= $currentPage === 'perfil.php' ? 'active' : '' ?>">
                <i class="bi bi-person-circle me-2"></i> Meu Perfil
            </a>
        </li>
    </ul>

    <hr class="sidebar-divider">

    <!-- Logout -->
    <div class="px-3 pb-3">
        <a href="<?= BASE_URL ?>auth/logout.php"
           class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-box-arrow-right"></i> Sair
        </a>
    </div>
</nav>
<!-- ========== FIM SIDEBAR USUÁRIO ========== -->
