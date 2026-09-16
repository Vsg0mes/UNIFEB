# 🛍️ ERP Loja de Roupas

Sistema ERP completo para varejo de moda desenvolvido em **PHP puro** (sem frameworks), **PostgreSQL** e **Bootstrap 5**.

---

## 📋 Sumário

- [Funcionalidades](#-funcionalidades)
- [Stack Tecnológico](#-stack-tecnológico)
- [Estrutura de Diretórios](#-estrutura-de-diretórios)
- [Requisitos](#-requisitos)
- [Instalação](#-instalação-passo-a-passo)
- [Credenciais Padrão](#-credenciais-padrão)
- [Diagrama ER](#-diagrama-er)
- [Módulos do Sistema](#-módulos-do-sistema)
- [Segurança](#-segurança)

---

## ✅ Funcionalidades

### Área Administrativa (admin)
- 📊 **Dashboard** com KPIs em tempo real (vendas do dia, faturamento mensal, estoque crítico, clientes)
- 📈 **Gráfico Chart.js** de faturamento dos últimos 7 dias
- 🛍️ **CRUD completo** de Produtos (com controle de estoque, tamanho, cor, código de barras)
- 🏷️ **CRUD de Categorias** (Feminino, Masculino, Infantil, Acessórios, etc.)
- 🚚 **CRUD de Fornecedores** (com CNPJ, contato e produtos vinculados)
- 👥 **CRUD de Clientes** (com CPF, histórico de compras e total gasto)
- 👤 **CRUD de Funcionários/Usuários** (com controle de perfil admin/vendedor)
- 📦 **Controle de Estoque** (movimentações manuais de entrada e saída, histórico completo)
- 📄 **Relatório de Vendas** (por período, vendedor, forma de pagamento + gráfico de pizza)
- 📄 **Relatório de Estoque** (posição atual, valor de custo vs venda, produtos críticos)
- ⚙️ **Configurações** com estatísticas do banco de dados

### Área do Vendedor (usuario)
- 🏠 **Dashboard** personalizado com saudação por hora e métricas individuais
- 💳 **PDV (Ponto de Venda)** completo:
  - Busca de produto por nome ou código de barras (AJAX com debounce)
  - Carrinho dinâmico (adicionar, remover, alterar quantidade)
  - Cálculo automático de total, desconto e troco
  - Seleção de cliente (opcional) e observação
  - Múltiplas formas de pagamento: Dinheiro, Cartão Crédito/Débito, PIX, Fiado
  - Modal de confirmação com troco destacado
- 📋 **Histórico de Vendas** com filtros por período e status
- 👤 **Perfil** com edição de nome e troca de senha

---

## 🛠️ Stack Tecnológico

| Componente | Tecnologia |
|-----------|-----------|
| Backend | PHP 8.0+ puro (sem frameworks) |
| Banco de Dados | PostgreSQL 14+ |
| PDO | Prepared Statements (proteção SQL Injection) |
| Frontend | Bootstrap 5.3 (via CDN) |
| Ícones | Bootstrap Icons 1.11 |
| Gráficos | Chart.js 4.4 |
| Fontes | Google Fonts — Inter |
| Autenticação | PHP Sessions |

---

## 📁 Estrutura de Diretórios

```
erp-loja-roupas/
│
├── config/
│   └── database.php          # Conexão PDO com PostgreSQL (Singleton)
│
├── includes/
│   ├── header.php            # Cabeçalho HTML global
│   ├── footer.php            # Rodapé HTML global
│   ├── sidebar_admin.php     # Menu lateral do admin
│   ├── sidebar_user.php      # Menu lateral do vendedor
│   └── auth_check.php        # Middleware de autenticação
│
├── public/
│   ├── css/style.css         # Estilos customizados + design system
│   └── js/scripts.js         # JavaScript global (PDV, máscaras, toasts)
│
├── auth/
│   ├── login.php             # Tela de login
│   ├── logout.php            # Logout seguro
│   └── register.php          # Redireciona para cadastro de funcionários
│
├── admin/
│   ├── dashboard.php         # Painel administrativo
│   ├── produtos/             # CRUD completo de produtos
│   ├── categorias/           # CRUD de categorias
│   ├── fornecedores/         # CRUD de fornecedores
│   ├── clientes/             # CRUD de clientes
│   ├── funcionarios/         # CRUD de usuários/funcionários
│   ├── estoque/              # Movimentações de estoque
│   ├── relatorios/           # Relatórios de vendas e estoque
│   └── configuracoes.php     # Configurações do sistema
│
├── usuario/
│   ├── dashboard.php         # Painel do vendedor
│   ├── pdv.php               # Ponto de Venda (caixa)
│   ├── vendas/               # Histórico e nova venda
│   └── perfil.php            # Edição do perfil
│
├── api/
│   └── ajax_handlers.php     # Endpoint AJAX (busca produtos, clientes)
│
├── database/
│   └── schema.sql            # Script SQL completo (DDL + seeds + triggers)
│
├── index.php                 # Ponto de entrada (redireciona conforme sessão)
├── .env.example              # Exemplo de variáveis de ambiente
└── README.md                 # Este arquivo
```

---

## 📌 Requisitos

- **PHP** 8.0 ou superior
- **PostgreSQL** 14 ou superior
- **Extensão PDO_PGSQL** habilitada no PHP
- Servidor web: **Apache** (XAMPP) ou **Nginx**
- PHP `session`, `password_hash`, `json_encode` (padrão)

---

## 🚀 Instalação — Passo a Passo

### 1. Instalar dependências

#### Opção A: XAMPP + PostgreSQL (Windows)

1. Baixe e instale o [XAMPP](https://www.apachefriends.org/)
2. Baixe e instale o [PostgreSQL](https://www.postgresql.org/download/windows/)
3. Instale a extensão `pdo_pgsql` no PHP:
   - Abra `C:\xampp\php\php.ini`
   - Descomente (remova o `;`) das linhas:
     ```ini
     extension=pdo_pgsql
     extension=pgsql
     ```
   - Reinicie o Apache no XAMPP Control Panel

#### Opção B: Linux (Ubuntu/Debian)

```bash
sudo apt update
sudo apt install php8.2 php8.2-pgsql php8.2-pdo php8.2-mbstring postgresql apache2
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

### 2. Configurar o Banco de Dados

```bash
# Acesse o PostgreSQL
psql -U postgres

# Crie o banco de dados
CREATE DATABASE erp_loja_roupas;

# Conecte ao banco
\c erp_loja_roupas

# Execute o script SQL
\i /caminho/para/erp-loja-roupas/database/schema.sql
```

**Ou via pgAdmin:**
1. Abra o pgAdmin
2. Crie um banco chamado `erp_loja_roupas`
3. Abra o Query Tool
4. Copie e cole o conteúdo de `database/schema.sql`
5. Execute (F5)

---

### 3. Configurar as variáveis de ambiente

```bash
# Copie o arquivo de exemplo
cp .env.example .env
```

Edite o arquivo `.env`:

```env
DB_HOST=localhost
DB_PORT=5432
DB_NAME=erp_loja_roupas
DB_USER=postgres
DB_PASS=sua_senha_aqui
```

---

### 4. Colocar o projeto no servidor web

**XAMPP (Windows):**

```
Copie a pasta erp-loja-roupas para: C:\xampp\htdocs\
Acesse: http://localhost/erp-loja-roupas/
```

**Apache/Nginx (Linux):**

```bash
sudo cp -r erp-loja-roupas /var/www/html/
sudo chown -R www-data:www-data /var/www/html/erp-loja-roupas
```

Acesse: `http://localhost/erp-loja-roupas/`

---

### 5. Acessar o sistema

Abra o navegador e acesse:

```
http://localhost/erp-loja-roupas/
```

---

## 🔑 Credenciais Padrão

> **⚠️ Importante:** Altere as senhas imediatamente após o primeiro acesso!

| Usuário | E-mail | Senha | Perfil |
|---------|--------|-------|--------|
| Administrador | admin@erploja.com | Admin@123 | Admin |
| Carlos Vendedor | carlos@erploja.com | Admin@123 | Vendedor |
| Ana Caixa | ana@erploja.com | Admin@123 | Vendedor |

---

## 🗄️ Diagrama ER

```
CATEGORIAS ──────────┐
                     │ (1:N)
FORNECEDORES ────────┤
                     │ (1:N)
                     ▼
                  PRODUTOS ──────────────┐
                     │                  │ (1:N)
                     │ (1:N)            ▼
                     │          MOVIMENTACAO_ESTOQUE
                     │                  ▲
CLIENTES ────────────┐                  │
            (1:N)    │                  │
                     ▼                  │
USUARIOS ──────────► VENDAS ──────► VENDA_ITENS
  │                                     │
  │ (1:N)                               │ (N:1)
  ▼                                     │
CAIXA                              PRODUTOS ◄──
```

---

## 📦 Módulos do Sistema

### Trigger de Baixa de Estoque

O banco possui uma **trigger PostgreSQL** que:
- **Dispara automaticamente** ao inserir um item em `venda_itens`
- **Decrementa** `quantidade_estoque` em `produtos`
- **Registra** automaticamente em `movimentacao_estoque`
- **Lança exceção** se o estoque for insuficiente (protege integridade)
- **Estorna** o estoque quando uma venda é cancelada (segunda trigger)

### Segurança Implementada

- ✅ **Prepared Statements PDO** em 100% das queries
- ✅ **`htmlspecialchars()`** em todos os outputs HTML
- ✅ **`password_hash()` / `password_verify()`** para senhas (bcrypt)
- ✅ **`session_regenerate_id()`** após login (previne session fixation)
- ✅ **Controle de acesso por perfil** (`verificarAcesso('admin')`)
- ✅ **Proteção CSRF** via validação de sessão no AJAX
- ✅ **Sanitização de inputs** com `filter_input()` e `trim()`
- ✅ **Exclusão lógica** de produtos com histórico de vendas
- ✅ **PDO::ATTR_EMULATE_PREPARES = false** (prepared statements reais)

---

## 🔧 Solução de Problemas

### "Call to undefined function `pg_connect()`"
- Habilite `extension=pdo_pgsql` no `php.ini`
- Reinicie o servidor web

### Erro de conexão com o banco
- Verifique se o PostgreSQL está rodando
- Confirme as credenciais no arquivo `.env`
- Verifique se o banco `erp_loja_roupas` foi criado

### Senha do admin não funciona
Execute no psql para resetar a senha para `Admin@123`:
```sql
UPDATE usuarios
SET senha = '$2y$12$YKqF3r8bWXzFfXOQlC9r1.JBZ5m9h2V0oP3fK7sNqUv1tXeYpIgRu'
WHERE email = 'admin@erploja.com';
```
> Ou gere um novo hash via PHP: `echo password_hash('Admin@123', PASSWORD_BCRYPT);`

### Erro de permissão no Linux
```bash
sudo chmod -R 755 /var/www/html/erp-loja-roupas
sudo chown -R www-data:www-data /var/www/html/erp-loja-roupas
```

---

## 📝 Licença

Este projeto é de uso educacional. Sinta-se livre para adaptar conforme suas necessidades.

---

*Desenvolvido com PHP puro + PostgreSQL + Bootstrap 5* 🚀
