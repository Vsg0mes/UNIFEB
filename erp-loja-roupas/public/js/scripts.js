/**
 * public/js/scripts.js
 * Scripts JavaScript globais do ERP Loja de Roupas
 * Não depende de jQuery — JS puro (ES6+)
 */

'use strict';

/* ============================================================
   SIDEBAR — Toggle mobile
   ============================================================ */
(function initSidebar() {
    const sidebar          = document.getElementById('sidebar');
    const toggleBtn        = document.getElementById('sidebarToggleBtn');   // Botão na topbar
    const collapseBtn      = document.getElementById('sidebarCollapseBtn'); // Botão "X" dentro da sidebar
    const overlay          = document.getElementById('sidebarOverlay');

    function openSidebar() {
        sidebar?.classList.add('show');
        overlay?.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar?.classList.remove('show');
        overlay?.classList.remove('show');
        document.body.style.overflow = '';
    }

    toggleBtn?.addEventListener('click', openSidebar);
    collapseBtn?.addEventListener('click', closeSidebar);
    overlay?.addEventListener('click', closeSidebar);

    // Fecha ao redimensionar para desktop
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992) closeSidebar();
    });
})();

/* ============================================================
   ALERTAS — auto-fechar após 5 segundos
   ============================================================ */
(function initAutoAlerts() {
    document.querySelectorAll('.alert-auto-close').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert?.close();
        }, 5000);
    });
})();

/* ============================================================
   CONFIRMAÇÃO DE EXCLUSÃO
   ============================================================ */
function confirmarExclusao(mensagem, url) {
    mensagem = mensagem || 'Tem certeza que deseja excluir este registro? Esta ação não pode ser desfeita.';
    if (confirm(mensagem)) {
        window.location.href = url;
    }
    return false;
}

/* ============================================================
   TOAST NOTIFICATION
   ============================================================ */
function mostrarToast(mensagem, tipo = 'success', duracao = 4000) {
    // Cria container se não existir
    let container = document.getElementById('toastContainerGlobal');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainerGlobal';
        container.className = 'toast-container-custom';
        document.body.appendChild(container);
    }

    const icones = {
        success: 'bi-check-circle-fill',
        danger:  'bi-exclamation-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info:    'bi-info-circle-fill',
    };

    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-bg-${tipo} border-0 show fade-in`;
    toastEl.setAttribute('role', 'alert');
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i class="bi ${icones[tipo] || 'bi-info-circle-fill'}"></i>
                ${mensagem}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

    container.appendChild(toastEl);

    // Auto-remover após duração
    setTimeout(() => {
        toastEl.classList.remove('show');
        toastEl.addEventListener('transitionend', () => toastEl.remove());
    }, duracao);
}

/* ============================================================
   FORMATAÇÃO DE MOEDA (BRL)
   ============================================================ */
function formatarMoeda(valor) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(valor || 0);
}

/* ============================================================
   MÁSCARA — CPF
   ============================================================ */
function aplicarMascaraCPF(input) {
    input.addEventListener('input', function() {
        let v = this.value.replace(/\D/g, '').substring(0, 11);
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        this.value = v;
    });
}

/* ============================================================
   MÁSCARA — CNPJ
   ============================================================ */
function aplicarMascaraCNPJ(input) {
    input.addEventListener('input', function() {
        let v = this.value.replace(/\D/g, '').substring(0, 14);
        v = v.replace(/(\d{2})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1/$2');
        v = v.replace(/(\d{4})(\d{1,2})$/, '$1-$2');
        this.value = v;
    });
}

/* ============================================================
   MÁSCARA — Telefone
   ============================================================ */
function aplicarMascaraTelefone(input) {
    input.addEventListener('input', function() {
        let v = this.value.replace(/\D/g, '').substring(0, 11);
        if (v.length <= 10) {
            v = v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
        } else {
            v = v.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
        }
        this.value = v.trim().replace(/-$/, '');
    });
}

/* ============================================================
   INICIALIZA MÁSCARAS NAS PÁGINAS
   ============================================================ */
document.addEventListener('DOMContentLoaded', function() {
    // CPF
    document.querySelectorAll('input[data-mask="cpf"]').forEach(aplicarMascaraCPF);
    // CNPJ
    document.querySelectorAll('input[data-mask="cnpj"]').forEach(aplicarMascaraCNPJ);
    // Telefone
    document.querySelectorAll('input[data-mask="telefone"]').forEach(aplicarMascaraTelefone);

    // Ativa tooltips do Bootstrap
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });

    // Ativa popovers do Bootstrap
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
        new bootstrap.Popover(el);
    });
});

/* ============================================================
   PDV — Gerenciamento do Carrinho (usado em pdv.php)
   ============================================================ */
const PDV = (function() {
    let carrinho = []; // Array de itens do carrinho

    /**
     * Adiciona um produto ao carrinho.
     * @param {object} produto - { id, nome, preco_venda, quantidade_estoque, codigo_barras }
     */
    function adicionarItem(produto) {
        const existente = carrinho.find(item => item.id === produto.id);
        if (existente) {
            if (existente.quantidade < produto.quantidade_estoque) {
                existente.quantidade++;
                existente.subtotal = existente.quantidade * existente.preco_unitario;
            } else {
                mostrarToast('Estoque insuficiente para este produto!', 'warning');
                return;
            }
        } else {
            if (produto.quantidade_estoque < 1) {
                mostrarToast('Produto sem estoque disponível!', 'danger');
                return;
            }
            carrinho.push({
                id:                  produto.id,
                nome:                produto.nome,
                preco_unitario:      parseFloat(produto.preco_venda),
                quantidade:          1,
                subtotal:            parseFloat(produto.preco_venda),
                quantidade_estoque:  produto.quantidade_estoque,
                codigo_barras:       produto.codigo_barras || '',
            });
        }
        renderizarCarrinho();
        mostrarToast(`${produto.nome} adicionado ao carrinho!`, 'success', 2000);
    }

    /**
     * Remove um item do carrinho pelo índice.
     */
    function removerItem(index) {
        carrinho.splice(index, 1);
        renderizarCarrinho();
    }

    /**
     * Altera a quantidade de um item.
     */
    function alterarQuantidade(index, delta) {
        const item = carrinho[index];
        if (!item) return;
        const novaQtd = item.quantidade + delta;
        if (novaQtd <= 0) {
            removerItem(index);
            return;
        }
        if (novaQtd > item.quantidade_estoque) {
            mostrarToast('Limite de estoque atingido!', 'warning');
            return;
        }
        item.quantidade = novaQtd;
        item.subtotal   = novaQtd * item.preco_unitario;
        renderizarCarrinho();
    }

    /**
     * Limpa o carrinho.
     */
    function limpar() {
        carrinho = [];
        renderizarCarrinho();
    }

    /**
     * Calcula o total bruto do carrinho.
     */
    function calcularTotal() {
        return carrinho.reduce((acc, item) => acc + item.subtotal, 0);
    }

    /**
     * Renderiza os itens na tabela do PDV.
     */
    function renderizarCarrinho() {
        const tbody         = document.getElementById('carrinhoBody');
        const totalEl       = document.getElementById('totalBruto');
        const descontoEl    = document.getElementById('inputDesconto');
        const totalLiqEl    = document.getElementById('totalLiquido');
        const trocoEl       = document.getElementById('valorTroco');
        const valorPagoEl   = document.getElementById('inputValorPago');
        const hiddenEl      = document.getElementById('carrinhoJSON');
        const btnFinalizar  = document.getElementById('btnFinalizarVenda');

        if (!tbody) return;

        // Renderiza linhas da tabela
        tbody.innerHTML = '';
        if (carrinho.length === 0) {
            tbody.innerHTML = `
                <tr id="trCarrinhoVazio">
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="bi bi-cart3 fs-2 d-block mb-2"></i>
                        Nenhum produto adicionado
                    </td>
                </tr>`;
        } else {
            carrinho.forEach((item, idx) => {
                const tr = document.createElement('tr');
                tr.className = 'fade-in';
                tr.innerHTML = `
                    <td>
                        <div class="fw-semibold small">${item.nome}</div>
                        <small class="text-muted">${item.codigo_barras}</small>
                    </td>
                    <td class="text-center">
                        <div class="input-group input-group-sm" style="width:110px;margin:auto">
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="PDV.alterarQuantidade(${idx}, -1)">-</button>
                            <input type="text" class="form-control text-center" value="${item.quantidade}" readonly>
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="PDV.alterarQuantidade(${idx}, 1)">+</button>
                        </div>
                    </td>
                    <td class="text-end">${formatarMoeda(item.preco_unitario)}</td>
                    <td class="text-end fw-semibold">${formatarMoeda(item.subtotal)}</td>
                    <td class="text-center">
                        <button class="btn btn-danger btn-action" type="button"
                                onclick="PDV.removerItem(${idx})"
                                data-bs-toggle="tooltip" title="Remover item">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </td>`;
                tbody.appendChild(tr);
            });
        }

        // Atualiza totais
        const totalBruto  = calcularTotal();
        const desconto    = parseFloat(descontoEl?.value || 0) || 0;
        const totalLiq    = Math.max(totalBruto - desconto, 0);
        const valorPago   = parseFloat(valorPagoEl?.value || 0) || 0;
        const troco       = Math.max(valorPago - totalLiq, 0);

        if (totalEl)     totalEl.textContent    = formatarMoeda(totalBruto);
        if (totalLiqEl)  totalLiqEl.textContent = formatarMoeda(totalLiq);
        if (trocoEl)     trocoEl.textContent     = formatarMoeda(troco);

        // Serializa o carrinho para envio ao PHP
        if (hiddenEl) hiddenEl.value = JSON.stringify(carrinho);

        // Habilita/desabilita botão de finalizar
        if (btnFinalizar) btnFinalizar.disabled = carrinho.length === 0;

        // Re-inicializa tooltips nos novos elementos
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el, { trigger: 'hover' });
        });
    }

    // API pública do módulo PDV
    return {
        adicionarItem,
        removerItem,
        alterarQuantidade,
        limpar,
        calcularTotal,
        renderizarCarrinho,
        getCarrinho: () => carrinho,
    };
})();

/* ============================================================
   PDV — Busca de produto via AJAX
   ============================================================ */
let searchTimeout = null;

function buscarProdutoPDV(query) {
    clearTimeout(searchTimeout);
    const resultados = document.getElementById('resultadosBusca');

    if (query.length < 2) {
        if (resultados) resultados.innerHTML = '';
        return;
    }

    // Debounce: só busca após 350ms sem digitar
    searchTimeout = setTimeout(() => {
        if (resultados) {
            resultados.innerHTML = '<div class="p-3 text-center text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Buscando...</div>';
        }

        fetch(`../../api/ajax_handlers.php?action=buscar_produto&q=${encodeURIComponent(query)}`)
            .then(r => r.json())
            .then(data => {
                if (!resultados) return;
                if (!data.produtos || data.produtos.length === 0) {
                    resultados.innerHTML = '<div class="p-3 text-center text-muted small">Nenhum produto encontrado.</div>';
                    return;
                }
                resultados.innerHTML = data.produtos.map(p => `
                    <div class="produto-resultado-item" onclick='PDV.adicionarItem(${JSON.stringify(p)})'>
                        <div class="produto-badge"><i class="bi bi-bag"></i></div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">${p.nome}</div>
                            <small class="text-muted">${p.codigo_barras || ''} | Tam: ${p.tamanho || '-'} | Cor: ${p.cor || '-'}</small>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-success">${formatarMoeda(p.preco_venda)}</div>
                            <small class="text-muted">Estq: ${p.quantidade_estoque}</small>
                        </div>
                    </div>
                `).join('');
            })
            .catch(() => {
                if (resultados) resultados.innerHTML = '<div class="p-3 text-center text-danger small">Erro ao buscar produto.</div>';
            });
    }, 350);
}

/* ============================================================
   PDV — Atualiza troco ao mudar valor pago ou desconto
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
    ['inputValorPago', 'inputDesconto'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', () => {
            PDV.renderizarCarrinho();
        });
    });

    // Renderiza carrinho inicial (vazio)
    PDV.renderizarCarrinho();
});
