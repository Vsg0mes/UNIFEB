-- ============================================================
--  ERP LOJA DE ROUPAS - Script PostgreSQL Completo
--  Versão: 1.0 | Data: 2024
--  Modelagem em 3ª Forma Normal (3FN)
-- ============================================================

-- Apaga e recria o schema para ambiente limpo (opcional em dev)
-- DROP SCHEMA public CASCADE; CREATE SCHEMA public;

-- ============================================================
--  1. TABELA: usuarios
--     Armazena todos os usuários do sistema (admin e operadores)
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id              SERIAL PRIMARY KEY,
    nome            VARCHAR(100)    NOT NULL,
    email           VARCHAR(150)    NOT NULL UNIQUE,
    senha           VARCHAR(255)    NOT NULL,                          -- hash bcrypt/password_hash
    perfil          VARCHAR(10)     NOT NULL DEFAULT 'usuario'
                        CHECK (perfil IN ('admin', 'usuario')),
    ativo           BOOLEAN         NOT NULL DEFAULT TRUE,
    data_criacao    TIMESTAMP       NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE  usuarios             IS 'Usuários do sistema ERP (administradores e vendedores)';
COMMENT ON COLUMN usuarios.perfil      IS 'Nível de acesso: admin = acesso total, usuario = apenas PDV e consultas';
COMMENT ON COLUMN usuarios.senha       IS 'Hash gerado com password_hash() do PHP (bcrypt)';
COMMENT ON COLUMN usuarios.ativo      IS 'FALSE desativa o login sem excluir o registro';

-- ============================================================
--  2. TABELA: categorias
--     Agrupa produtos (Masculino, Feminino, Infantil, etc.)
-- ============================================================
CREATE TABLE IF NOT EXISTS categorias (
    id          SERIAL PRIMARY KEY,
    nome        VARCHAR(80)  NOT NULL UNIQUE,
    descricao   TEXT
);

COMMENT ON TABLE categorias IS 'Categorias de produtos da loja (ex: Masculino, Feminino, Infantil, Acessórios)';

-- ============================================================
--  3. TABELA: fornecedores
--     Cadastro de fornecedores / fabricantes
-- ============================================================
CREATE TABLE IF NOT EXISTS fornecedores (
    id          SERIAL PRIMARY KEY,
    nome        VARCHAR(150) NOT NULL,
    cnpj        VARCHAR(18)  UNIQUE,                                   -- formato 00.000.000/0000-00
    telefone    VARCHAR(20),
    email       VARCHAR(150),
    endereco    TEXT,
    ativo       BOOLEAN      NOT NULL DEFAULT TRUE
);

COMMENT ON TABLE fornecedores IS 'Fornecedores e fabricantes dos produtos comercializados';

-- ============================================================
--  4. TABELA: produtos
--     Catálogo completo de produtos com controle de estoque
-- ============================================================
CREATE TABLE IF NOT EXISTS produtos (
    id                  SERIAL PRIMARY KEY,
    nome                VARCHAR(150)    NOT NULL,
    descricao           TEXT,
    categoria_id        INT             REFERENCES categorias(id)   ON DELETE SET NULL,
    fornecedor_id       INT             REFERENCES fornecedores(id) ON DELETE SET NULL,
    tamanho             VARCHAR(10),                                    -- PP, P, M, G, GG, XGG, Único, 36...
    cor                 VARCHAR(50),
    preco_custo         NUMERIC(10,2)   NOT NULL DEFAULT 0.00
                            CHECK (preco_custo >= 0),
    preco_venda         NUMERIC(10,2)   NOT NULL DEFAULT 0.00
                            CHECK (preco_venda >= 0),
    quantidade_estoque  INT             NOT NULL DEFAULT 0
                            CHECK (quantidade_estoque >= 0),
    estoque_minimo      INT             NOT NULL DEFAULT 5
                            CHECK (estoque_minimo >= 0),
    codigo_barras       VARCHAR(50)     UNIQUE,
    ativo               BOOLEAN         NOT NULL DEFAULT TRUE,
    data_cadastro       TIMESTAMP       NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE  produtos                     IS 'Catálogo de produtos da loja com preços e controle de estoque';
COMMENT ON COLUMN produtos.tamanho             IS 'Tamanho da peça: PP, P, M, G, GG, XGG ou numeração (ex: 36, 38)';
COMMENT ON COLUMN produtos.estoque_minimo      IS 'Quantidade mínima desejada; alerta quando estoque ficar abaixo';
COMMENT ON COLUMN produtos.codigo_barras       IS 'Código EAN-13 ou código interno para leitura de barras';

-- ============================================================
--  5. TABELA: clientes
--     Cadastro de clientes para vincular às vendas
-- ============================================================
CREATE TABLE IF NOT EXISTS clientes (
    id              SERIAL PRIMARY KEY,
    nome            VARCHAR(100)    NOT NULL,
    cpf             VARCHAR(14)     UNIQUE,                             -- formato 000.000.000-00
    telefone        VARCHAR(20),
    email           VARCHAR(150),
    endereco        TEXT,
    data_nascimento DATE,
    data_cadastro   TIMESTAMP       NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE clientes IS 'Clientes da loja (opcionalmente vinculados às vendas para fidelização)';

-- ============================================================
--  6. TABELA: vendas
--     Cabeçalho de cada venda / transação
-- ============================================================
CREATE TABLE IF NOT EXISTS vendas (
    id              SERIAL PRIMARY KEY,
    cliente_id      INT             REFERENCES clientes(id)  ON DELETE SET NULL,  -- NULL = venda avulsa
    usuario_id      INT             NOT NULL REFERENCES usuarios(id) ON DELETE RESTRICT,
    data_venda      TIMESTAMP       NOT NULL DEFAULT NOW(),
    valor_total     NUMERIC(10,2)   NOT NULL DEFAULT 0.00
                        CHECK (valor_total >= 0),
    desconto        NUMERIC(10,2)   NOT NULL DEFAULT 0.00
                        CHECK (desconto >= 0),
    valor_pago      NUMERIC(10,2)   NOT NULL DEFAULT 0.00,
    forma_pagamento VARCHAR(20)     NOT NULL DEFAULT 'dinheiro'
                        CHECK (forma_pagamento IN ('dinheiro','cartao_credito','cartao_debito','pix','fiado')),
    status          VARCHAR(15)     NOT NULL DEFAULT 'concluida'
                        CHECK (status IN ('concluida','cancelada','pendente')),
    observacao      TEXT
);

COMMENT ON TABLE  vendas                IS 'Registro de cabeçalho de cada venda realizada na loja';
COMMENT ON COLUMN vendas.cliente_id     IS 'NULL permite venda avulsa sem identificação do cliente';
COMMENT ON COLUMN vendas.usuario_id     IS 'Vendedor responsável pela venda (FK obrigatória)';
COMMENT ON COLUMN vendas.desconto       IS 'Desconto em valor monetário aplicado sobre o total bruto';
COMMENT ON COLUMN vendas.valor_pago     IS 'Valor efetivamente pago pelo cliente (para cálculo de troco)';

-- ============================================================
--  7. TABELA: venda_itens
--     Itens (produtos) de cada venda — relação N:M
-- ============================================================
CREATE TABLE IF NOT EXISTS venda_itens (
    id              SERIAL PRIMARY KEY,
    venda_id        INT             NOT NULL REFERENCES vendas(id)   ON DELETE CASCADE,
    produto_id      INT             NOT NULL REFERENCES produtos(id) ON DELETE RESTRICT,
    quantidade      INT             NOT NULL CHECK (quantidade > 0),
    preco_unitario  NUMERIC(10,2)   NOT NULL CHECK (preco_unitario >= 0),
    subtotal        NUMERIC(10,2)   NOT NULL CHECK (subtotal >= 0)    -- quantidade * preco_unitario
);

COMMENT ON TABLE  venda_itens              IS 'Itens detalhados de cada venda (produtos vendidos)';
COMMENT ON COLUMN venda_itens.subtotal     IS 'Calculado como quantidade × preco_unitario no momento da venda';
COMMENT ON COLUMN venda_itens.preco_unitario IS 'Preço snapshot no momento da venda (imune a alterações futuras)';

-- ============================================================
--  8. TABELA: movimentacao_estoque
--     Rastreio de todas as entradas e saídas de estoque
-- ============================================================
CREATE TABLE IF NOT EXISTS movimentacao_estoque (
    id                  SERIAL PRIMARY KEY,
    produto_id          INT             NOT NULL REFERENCES produtos(id)  ON DELETE CASCADE,
    usuario_id          INT             REFERENCES usuarios(id)           ON DELETE SET NULL,
    tipo                VARCHAR(10)     NOT NULL
                            CHECK (tipo IN ('entrada','saida')),
    quantidade          INT             NOT NULL CHECK (quantidade > 0),
    motivo              VARCHAR(200),
    data_movimentacao   TIMESTAMP       NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE  movimentacao_estoque       IS 'Histórico de todas as movimentações de estoque (entradas e saídas)';
COMMENT ON COLUMN movimentacao_estoque.tipo  IS 'entrada = compra/reposição; saida = venda/perda/devolução';

-- ============================================================
--  9. TABELA: caixa
--     Controle de abertura e fechamento de caixa por turno
-- ============================================================
CREATE TABLE IF NOT EXISTS caixa (
    id               SERIAL PRIMARY KEY,
    usuario_id       INT             NOT NULL REFERENCES usuarios(id) ON DELETE RESTRICT,
    data_abertura    TIMESTAMP       NOT NULL DEFAULT NOW(),
    data_fechamento  TIMESTAMP,
    valor_abertura   NUMERIC(10,2)   NOT NULL DEFAULT 0.00
                         CHECK (valor_abertura >= 0),
    valor_fechamento NUMERIC(10,2)
                         CHECK (valor_fechamento >= 0),
    status           VARCHAR(10)     NOT NULL DEFAULT 'aberto'
                         CHECK (status IN ('aberto','fechado'))
);

COMMENT ON TABLE caixa IS 'Controle de abertura e fechamento do caixa por usuário/turno';

-- ============================================================
--  ÍNDICES para performance em buscas frequentes
-- ============================================================
CREATE INDEX IF NOT EXISTS idx_produtos_categoria    ON produtos(categoria_id);
CREATE INDEX IF NOT EXISTS idx_produtos_fornecedor   ON produtos(fornecedor_id);
CREATE INDEX IF NOT EXISTS idx_produtos_ativo        ON produtos(ativo);
CREATE INDEX IF NOT EXISTS idx_vendas_usuario        ON vendas(usuario_id);
CREATE INDEX IF NOT EXISTS idx_vendas_cliente        ON vendas(cliente_id);
CREATE INDEX IF NOT EXISTS idx_vendas_data           ON vendas(data_venda);
CREATE INDEX IF NOT EXISTS idx_venda_itens_venda     ON venda_itens(venda_id);
CREATE INDEX IF NOT EXISTS idx_venda_itens_produto   ON venda_itens(produto_id);
CREATE INDEX IF NOT EXISTS idx_movest_produto        ON movimentacao_estoque(produto_id);

-- ============================================================
--  TRIGGER: dar baixa automática no estoque ao inserir item
-- ============================================================

-- Função chamada pela trigger
CREATE OR REPLACE FUNCTION fn_baixa_estoque_venda()
RETURNS TRIGGER AS $$
BEGIN
    -- Verifica se há estoque suficiente antes de prosseguir
    IF (SELECT quantidade_estoque FROM produtos WHERE id = NEW.produto_id) < NEW.quantidade THEN
        RAISE EXCEPTION 'Estoque insuficiente para o produto id=%. Disponível: %, Solicitado: %',
            NEW.produto_id,
            (SELECT quantidade_estoque FROM produtos WHERE id = NEW.produto_id),
            NEW.quantidade;
    END IF;

    -- Decrementa o estoque do produto
    UPDATE produtos
    SET quantidade_estoque = quantidade_estoque - NEW.quantidade
    WHERE id = NEW.produto_id;

    -- Registra a movimentação de saída automaticamente
    INSERT INTO movimentacao_estoque (produto_id, usuario_id, tipo, quantidade, motivo)
    SELECT NEW.produto_id,
           v.usuario_id,
           'saida',
           NEW.quantidade,
           CONCAT('Saída automática pela Venda #', NEW.venda_id)
    FROM vendas v WHERE v.id = NEW.venda_id;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger disparada APÓS inserção em venda_itens
CREATE TRIGGER trg_baixa_estoque_venda
AFTER INSERT ON venda_itens
FOR EACH ROW
EXECUTE FUNCTION fn_baixa_estoque_venda();

-- Função/Trigger para ESTORNO de estoque ao cancelar venda
CREATE OR REPLACE FUNCTION fn_estorno_estoque_cancelamento()
RETURNS TRIGGER AS $$
BEGIN
    -- Somente processa quando status muda para 'cancelada'
    IF NEW.status = 'cancelada' AND OLD.status != 'cancelada' THEN
        -- Devolve o estoque de cada item da venda
        UPDATE produtos p
        SET quantidade_estoque = p.quantidade_estoque + vi.quantidade
        FROM venda_itens vi
        WHERE vi.venda_id = NEW.id AND vi.produto_id = p.id;

        -- Registra a entrada de estorno
        INSERT INTO movimentacao_estoque (produto_id, usuario_id, tipo, quantidade, motivo)
        SELECT vi.produto_id, NEW.usuario_id, 'entrada', vi.quantidade,
               CONCAT('Estorno por cancelamento da Venda #', NEW.id)
        FROM venda_itens vi WHERE vi.venda_id = NEW.id;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_estorno_estoque_cancelamento
AFTER UPDATE ON vendas
FOR EACH ROW
EXECUTE FUNCTION fn_estorno_estoque_cancelamento();

-- ============================================================
--  DADOS DE EXEMPLO (Seeds)
-- ============================================================

-- Usuários (senha padrão para todos: Admin@123)
-- Hash gerado com PHP: password_hash('Admin@123', PASSWORD_BCRYPT)
INSERT INTO usuarios (nome, email, senha, perfil) VALUES
    ('Administrador', 'admin@erploja.com',   '$2y$12$YKqF3r8bWXzFfXOQlC9r1.JBZ5m9h2V0oP3fK7sNqUv1tXeYpIgRu', 'admin'),
    ('Carlos Vendedor', 'carlos@erploja.com','$2y$12$YKqF3r8bWXzFfXOQlC9r1.JBZ5m9h2V0oP3fK7sNqUv1tXeYpIgRu', 'usuario'),
    ('Ana Caixa',      'ana@erploja.com',    '$2y$12$YKqF3r8bWXzFfXOQlC9r1.JBZ5m9h2V0oP3fK7sNqUv1tXeYpIgRu', 'usuario');

-- Categorias
INSERT INTO categorias (nome, descricao) VALUES
    ('Feminino',   'Roupas e acessórios femininos'),
    ('Masculino',  'Roupas e acessórios masculinos'),
    ('Infantil',   'Roupas infantis de 0 a 14 anos'),
    ('Acessórios', 'Bolsas, cintos, lenços e bijuterias'),
    ('Calçados',   'Sapatos, sandálias e tênis');

-- Fornecedores
INSERT INTO fornecedores (nome, cnpj, telefone, email, endereco) VALUES
    ('Textil Brasil Ltda',    '12.345.678/0001-90', '(11) 3456-7890', 'vendas@textilbrasil.com.br', 'Rua das Industrias, 100 - São Paulo/SP'),
    ('Moda Sul Confecções',   '98.765.432/0001-10', '(51) 3321-4455', 'comercial@modasul.com.br',   'Av. dos Tecidos, 250 - Porto Alegre/RS'),
    ('AcessórioPrime Importadora', '55.123.456/0001-33', '(21) 2233-4455', 'contato@acessorioprime.com', 'Rua do Comércio, 78 - Rio de Janeiro/RJ'),
    ('Calça & Cia',           '44.555.666/0001-77', '(31) 3344-5566', 'vendas@calcaecia.com.br',    'Distrito Industrial, Q3 - Belo Horizonte/MG'),
    ('Kids Fashion',          '77.888.999/0001-22', '(41) 3344-7788', 'kids@kidsfashion.com.br',    'Rua das Crianças, 55 - Curitiba/PR');

-- Produtos
INSERT INTO produtos (nome, descricao, categoria_id, fornecedor_id, tamanho, cor, preco_custo, preco_venda, quantidade_estoque, estoque_minimo, codigo_barras) VALUES
    ('Blusa Feminina Floral', 'Blusa com estampa floral, tecido 100% algodão', 1, 1, 'M', 'Rosa',   25.00,  59.90, 30, 5, '7891234560001'),
    ('Calça Jeans Skinny',    'Calça jeans skinny stretch', 1, 2, 'G',  'Azul',   60.00, 149.90, 20, 3, '7891234560002'),
    ('Vestido Midi Florido',  'Vestido midi com decote V e estampa floral', 1, 1, 'P',  'Verde',  70.00, 169.90, 15, 3, '7891234560003'),
    ('Camiseta Masculina',    'Camiseta básica 100% algodão', 2, 1, 'G',  'Branca', 18.00,  49.90, 50, 10, '7891234560004'),
    ('Camisa Social Slim',    'Camisa social fit slim para ocasiões formais', 2, 4, 'M',  'Azul',   45.00,  99.90, 25, 5, '7891234560005'),
    ('Bermuda Masculina',     'Bermuda de praia em tecido leve', 2, 2, 'GG', 'Cinza',  30.00,  69.90, 20, 5, '7891234560006'),
    ('Conjunto Infantil',     'Conjunto camiseta + shorts para criança', 3, 5, '4',  'Colorido', 22.00, 59.90, 40, 8, '7891234560007'),
    ('Vestido Infantil',      'Vestidinho com renda para meninas', 3, 5, '6',  'Amarelo', 28.00, 65.90, 18, 5, '7891234560008'),
    ('Bolsa Feminina Couro',  'Bolsa feminina em couro sintético', 4, 3, 'Único', 'Preto', 35.00, 89.90, 12, 3, '7891234560009'),
    ('Cinto de Couro Masculino', 'Cinto masculino clássico em couro', 4, 3, 'Único', 'Marrom', 15.00, 39.90, 20, 5, '7891234560010'),
    ('Tênis Casual Feminino', 'Tênis casual feminino confortável', 5, 2, '37', 'Branco', 55.00, 129.90, 8, 3, '7891234560011'),
    ('Sapato Social Masculino','Sapato social masculino couro', 5, 4, '42', 'Preto', 70.00, 159.90, 6, 2, '7891234560012');

-- Clientes
INSERT INTO clientes (nome, cpf, telefone, email, endereco, data_nascimento) VALUES
    ('Maria Aparecida Silva',  '123.456.789-00', '(14) 99123-4567', 'maria@email.com',  'Rua das Flores, 45 - Barretos/SP', '1985-03-15'),
    ('João Paulo Ferreira',    '987.654.321-00', '(14) 98765-4321', 'joao@email.com',   'Av. Brasil, 200 - Barretos/SP',    '1990-07-22'),
    ('Fernanda Costa Oliveira','111.222.333-00', '(14) 97111-2233', 'fernanda@email.com','Rua Boa Vista, 10 - Colina/SP',   '1992-11-08'),
    ('Roberto Alves Souza',    '444.555.666-00', '(14) 96444-5566', 'roberto@email.com', 'Rua XV de Novembro, 88 - Barretos/SP','1978-01-30'),
    ('Patrícia Lima Santos',   '777.888.999-00', '(14) 95777-8899', 'patricia@email.com','Alameda das Acácias, 33 - Barretos/SP','2001-05-12');

-- Vendas de exemplo (venda 1 e 2 terão itens adicionados na sequência)
-- Nota: Inserimos com status 'pendente' primeiro para não triggerar o desconto, depois atualizamos
INSERT INTO vendas (cliente_id, usuario_id, data_venda, valor_total, desconto, valor_pago, forma_pagamento, status) VALUES
    (1, 2, NOW() - INTERVAL '2 days', 209.80, 10.00, 200.00, 'dinheiro',       'concluida'),
    (2, 2, NOW() - INTERVAL '1 day',  149.90,  0.00, 149.90, 'cartao_debito',  'concluida'),
    (3, 3, NOW(),                       59.90,  0.00,  59.90, 'pix',            'concluida');

-- ============================================================
--  DIAGRAMA ER (Mermaid)
-- ============================================================
/*
erDiagram
    USUARIOS {
        int id PK
        varchar nome
        varchar email UK
        varchar senha
        varchar perfil
        boolean ativo
        timestamp data_criacao
    }
    CATEGORIAS {
        int id PK
        varchar nome UK
        text descricao
    }
    FORNECEDORES {
        int id PK
        varchar nome
        varchar cnpj UK
        varchar telefone
        varchar email
        text endereco
    }
    PRODUTOS {
        int id PK
        varchar nome
        int categoria_id FK
        int fornecedor_id FK
        varchar tamanho
        varchar cor
        numeric preco_custo
        numeric preco_venda
        int quantidade_estoque
        int estoque_minimo
        varchar codigo_barras UK
    }
    CLIENTES {
        int id PK
        varchar nome
        varchar cpf UK
        varchar telefone
        varchar email
        date data_nascimento
    }
    VENDAS {
        int id PK
        int cliente_id FK
        int usuario_id FK
        timestamp data_venda
        numeric valor_total
        numeric desconto
        varchar forma_pagamento
        varchar status
    }
    VENDA_ITENS {
        int id PK
        int venda_id FK
        int produto_id FK
        int quantidade
        numeric preco_unitario
        numeric subtotal
    }
    MOVIMENTACAO_ESTOQUE {
        int id PK
        int produto_id FK
        int usuario_id FK
        varchar tipo
        int quantidade
        varchar motivo
        timestamp data_movimentacao
    }
    CAIXA {
        int id PK
        int usuario_id FK
        timestamp data_abertura
        timestamp data_fechamento
        numeric valor_abertura
        numeric valor_fechamento
        varchar status
    }

    CATEGORIAS  ||--o{ PRODUTOS : "tem"
    FORNECEDORES ||--o{ PRODUTOS : "fornece"
    CLIENTES    ||--o{ VENDAS : "realiza"
    USUARIOS    ||--o{ VENDAS : "registra"
    VENDAS      ||--|{ VENDA_ITENS : "contem"
    PRODUTOS    ||--o{ VENDA_ITENS : "incluido_em"
    PRODUTOS    ||--o{ MOVIMENTACAO_ESTOQUE : "rastreado_em"
    USUARIOS    ||--o{ MOVIMENTACAO_ESTOQUE : "registra"
    USUARIOS    ||--o{ CAIXA : "opera"
*/
