<?php
/**
 * includes/header.php
 * Cabeçalho HTML compartilhado por todas as páginas do sistema.
 * As variáveis $pageTitle e $pageDescription devem ser definidas
 * antes de incluir este arquivo.
 */

// Garante que a sessão está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define a URL base se ainda não definida
if (!defined('BASE_URL')) {
    $depth = substr_count(str_replace('\\', '/', __DIR__), '/') - substr_count(str_replace('\\','/',realpath($_SERVER['DOCUMENT_ROOT'])),'/');
    define('BASE_URL', str_repeat('../', $depth));
}

$pageTitle       = $pageTitle       ?? 'ERP Loja de Roupas';
$pageDescription = $pageDescription ?? 'Sistema ERP para gerenciamento de loja de roupas';
$bodyClass       = $bodyClass       ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="robots" content="noindex, nofollow"><!-- Sistema interno -->
    <title><?= htmlspecialchars($pageTitle) ?> | ERP Moda</title>

    <!-- Bootstrap 5 CSS via CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS customizado -->
    <link rel="stylesheet" href="<?= BASE_URL ?>public/css/style.css">
</head>
<body class="<?= htmlspecialchars($bodyClass) ?>">
