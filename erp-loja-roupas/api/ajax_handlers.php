<?php
/**
 * api/ajax_handlers.php
 * Endpoint central para requisições AJAX do front-end.
 * Retorna JSON. Protegido por verificação de sessão.
 *
 * Actions disponíveis:
 *   - buscar_produto : busca produtos por nome ou código de barras (PDV)
 *   - buscar_cliente : busca clientes por nome ou CPF
 */

// Inicia sessão e valida autenticação
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Retorna erro 401 se não autenticado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['erro' => true, 'mensagem' => 'Não autenticado.']);
    exit;
}

// Define headers para JSON
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// Carrega conexão PDO
require_once __DIR__ . '/../config/database.php';

$action = trim($_GET['action'] ?? '');
$pdo    = getDB();

// ============================================================
// DISPATCHER de actions
// ============================================================
switch ($action) {

    // ----------------------------------------------------------
    // Busca de produtos para o PDV
    // ----------------------------------------------------------
    case 'buscar_produto':
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            echo json_encode(['produtos' => []]);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT
                p.id, p.nome, p.codigo_barras, p.tamanho, p.cor,
                p.preco_venda, p.quantidade_estoque,
                c.nome AS categoria
            FROM produtos p
            LEFT JOIN categorias c ON c.id = p.categoria_id
            WHERE p.ativo = TRUE
              AND p.quantidade_estoque > 0
              AND (p.nome ILIKE :q OR p.codigo_barras ILIKE :q)
            ORDER BY p.nome ASC
            LIMIT 20
        ");
        $stmt->execute([':q' => "%{$q}%"]);
        $produtos = $stmt->fetchAll();

        // Formata campos numéricos
        foreach ($produtos as &$p) {
            $p['preco_venda']        = (float)$p['preco_venda'];
            $p['quantidade_estoque'] = (int)$p['quantidade_estoque'];
        }

        echo json_encode(['produtos' => $produtos]);
        break;

    // ----------------------------------------------------------
    // Busca de clientes (para autocomplete no PDV ou formulários)
    // ----------------------------------------------------------
    case 'buscar_cliente':
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            echo json_encode(['clientes' => []]);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT id, nome, cpf, telefone, email
            FROM clientes
            WHERE nome ILIKE :q OR cpf ILIKE :q
            ORDER BY nome ASC
            LIMIT 10
        ");
        $stmt->execute([':q' => "%{$q}%"]);
        $clientes = $stmt->fetchAll();

        echo json_encode(['clientes' => $clientes]);
        break;

    // ----------------------------------------------------------
    // Dados de um produto pelo ID (para modal de detalhes)
    // ----------------------------------------------------------
    case 'produto_detalhes':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['erro' => true, 'mensagem' => 'ID inválido.']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT p.*, c.nome AS categoria, f.nome AS fornecedor
            FROM produtos p
            LEFT JOIN categorias  c ON c.id = p.categoria_id
            LEFT JOIN fornecedores f ON f.id = p.fornecedor_id
            WHERE p.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $produto = $stmt->fetch();

        if (!$produto) {
            echo json_encode(['erro' => true, 'mensagem' => 'Produto não encontrado.']);
            exit;
        }

        $produto['preco_venda'] = (float)$produto['preco_venda'];
        $produto['preco_custo'] = (float)$produto['preco_custo'];
        echo json_encode(['produto' => $produto]);
        break;

    // ----------------------------------------------------------
    // Verificar estoque de um produto
    // ----------------------------------------------------------
    case 'verificar_estoque':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['erro' => true]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT quantidade_estoque, estoque_minimo FROM produtos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $estoque = $stmt->fetch();

        echo json_encode([
            'quantidade'       => (int)($estoque['quantidade_estoque'] ?? 0),
            'minimo'           => (int)($estoque['estoque_minimo']     ?? 0),
            'critico'          => ($estoque['quantidade_estoque'] ?? 0) <= ($estoque['estoque_minimo'] ?? 0),
        ]);
        break;

    // ----------------------------------------------------------
    // Action não reconhecida
    // ----------------------------------------------------------
    default:
        http_response_code(400);
        echo json_encode(['erro' => true, 'mensagem' => 'Action inválida: ' . htmlspecialchars($action)]);
        break;
}
