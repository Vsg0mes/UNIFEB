<?php
/**
 * admin/funcionarios/listar.php
 * Listagem de usuários/funcionários do sistema.
 */

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';
verificarAcesso('admin');

if (!defined('BASE_URL')) define('BASE_URL', '../../');

$pdo = getDB();
$msg = $_GET['msg'] ?? '';

// Toggle ativo/inativo
if (isset($_GET['toggle']) && (int)$_GET['toggle'] !== usuarioLogadoId()) {
    $idToggle = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE usuarios SET ativo = NOT ativo WHERE id = :id")->execute([':id' => $idToggle]);
    header('Location: listar.php?msg=atualizado');
    exit;
}

// Exclusão (não permite excluir a si mesmo ou admin único)
if (isset($_GET['excluir'])) {
    $idExcluir = (int)$_GET['excluir'];
    if ($idExcluir === usuarioLogadoId()) {
        $msg = 'erro_self';
    } else {
        $pdo->prepare("DELETE FROM usuarios WHERE id = :id")->execute([':id' => $idExcluir]);
        $msg = 'excluido';
    }
}

$funcionarios = $pdo->query("
    SELECT u.*, COUNT(v.id) AS qtd_vendas
    FROM usuarios u
    LEFT JOIN vendas v ON v.usuario_id = u.id
    GROUP BY u.id
    ORDER BY u.perfil DESC, u.nome ASC
")->fetchAll();

$mensagens = [
    'criado'     => ['tipo'=>'success','texto'=>'Usuário cadastrado com sucesso!'],
    'editado'    => ['tipo'=>'success','texto'=>'Usuário atualizado com sucesso!'],
    'excluido'   => ['tipo'=>'success','texto'=>'Usuário excluído com sucesso!'],
    'atualizado' => ['tipo'=>'success','texto'=>'Status do usuário atualizado!'],
    'erro_self'  => ['tipo'=>'danger', 'texto'=>'Você não pode excluir sua própria conta.'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funcionários | ERP Moda</title>
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
            <div><div class="topbar-title">Funcionários / Usuários</div><div class="topbar-breadcrumb">Admin / Funcionários</div></div>
            <div class="ms-auto">
                <a href="cadastrar.php" class="btn btn-primary btn-sm fw-semibold">
                    <i class="bi bi-plus-circle me-1"></i>Novo Funcionário
                </a>
            </div>
        </div>
        <main class="p-4 flex-grow-1 fade-in">
            <div class="page-header"><h2><i class="bi bi-person-badge me-2 text-primary-custom"></i>Funcionários / Usuários</h2></div>

            <?php if ($msg && isset($mensagens[$msg])): ?>
                <div class="alert alert-<?= $mensagens[$msg]['tipo'] ?> alert-auto-close alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($mensagens[$msg]['texto']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="table-card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nome / E-mail</th>
                                <th class="text-center">Perfil</th>
                                <th class="text-center">Vendas</th>
                                <th>Cadastrado em</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($funcionarios as $f): ?>
                                <tr <?= !$f['ativo'] ? 'class="opacity-50"' : '' ?>>
                                    <td class="text-muted small"><?= (int)$f['id'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-circle <?= $f['perfil'] === 'admin' ? '' : 'avatar-circle-user' ?>" style="width:32px;height:32px;font-size:.85rem;">
                                                <i class="bi bi-person-fill"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold small"><?= htmlspecialchars($f['nome']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($f['email']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?= $f['perfil'] === 'admin'
                                            ? '<span class="badge bg-warning text-dark"><i class="bi bi-shield-fill-check me-1"></i>Admin</span>'
                                            : '<span class="badge bg-info text-dark"><i class="bi bi-person me-1"></i>Vendedor</span>'
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary bg-opacity-15 text-dark"><?= (int)$f['qtd_vendas'] ?></span>
                                    </td>
                                    <td class="text-muted small"><?= date('d/m/Y', strtotime($f['data_criacao'])) ?></td>
                                    <td class="text-center">
                                        <?= $f['ativo']
                                            ? '<span class="badge bg-success">Ativo</span>'
                                            : '<span class="badge bg-secondary">Inativo</span>'
                                        ?>
                                        <?php if ($f['id'] === usuarioLogadoId()): ?>
                                            <span class="badge bg-primary ms-1">Você</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="cadastrar.php?editar=<?= $f['id'] ?>"
                                               class="btn btn-warning btn-action" title="Editar" data-bs-toggle="tooltip">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if ($f['id'] !== usuarioLogadoId()): ?>
                                                <a href="listar.php?toggle=<?= $f['id'] ?>"
                                                   class="btn btn-<?= $f['ativo'] ? 'secondary' : 'success' ?> btn-action"
                                                   title="<?= $f['ativo'] ? 'Desativar' : 'Ativar' ?>" data-bs-toggle="tooltip">
                                                    <i class="bi bi-<?= $f['ativo'] ? 'person-dash' : 'person-check' ?>"></i>
                                                </a>
                                                <a href="listar.php?excluir=<?= $f['id'] ?>"
                                                   class="btn btn-danger btn-action" title="Excluir" data-bs-toggle="tooltip"
                                                   onclick="return confirmarExclusao('Excluir o usuário &quot;<?= addslashes($f['nome']) ?>&quot;?', this.href)">
                                                    <i class="bi bi-trash3"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
        <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmXKzlVzSwh/so7GXpTzm+O1oeN" crossorigin="anonymous"></script>
<script src="../../public/js/scripts.js"></script>
</body>
</html>
