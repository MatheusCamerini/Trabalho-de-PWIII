// ============================================================
//  script.js — Validação, Interatividade e Busca em Tempo Real
// ============================================================
"use strict";

// ── Estado da aplicação ──────────────────────────────────────
const App = {
    pendingDeleteId: null,
    isEditing: false,
};

// ── Referências DOM ──────────────────────────────────────────
const $  = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

const modalOverlay   = $('#modal-overlay');
const modalBox       = $('#modal-box');
const modalTitle     = $('#modal-title');
const confirmOverlay = $('#confirm-overlay');
const itemForm       = $('#item-form');
const itemIdInput    = $('#item-id');
const formAction     = $('#form-action');
const searchInput    = $('#search-input');
const tbody          = $('#items-tbody');
const noResults      = $('#no-results');
const resultCount    = $('#result-count');
const btnLabel       = $('#btn-label');
const btnSpinner     = $('#btn-spinner');
const btnSubmit      = $('#btn-submit');

// ── Campos do formulário ─────────────────────────────────────
const fields = {
    nome:       { input: $('#f-nome'),       error: $('#err-nome')       },
    categoria:  { input: $('#f-categoria'),  error: $('#err-categoria')  },
    quantidade: { input: $('#f-quantidade'), error: $('#err-quantidade') },
    preco:      { input: $('#f-preco'),      error: $('#err-preco')      },
};

// ── Toast Notifications ──────────────────────────────────────
function showToast(message, type = 'success') {
    const container = $('#toast-container');
    const icons = { success: 'check-circle', error: 'x-circle', info: 'info' };
    const colors = {
        success: 'border-emerald-600/50 bg-emerald-950/90 text-emerald-300',
        error:   'border-red-600/50 bg-red-950/90 text-red-300',
        info:    'border-blue-600/50 bg-blue-950/90 text-blue-300',
    };

    const toast = document.createElement('div');
    toast.className = `
        pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl border
        shadow-2xl text-sm font-medium backdrop-blur-sm max-w-xs
        ${colors[type]} toast-enter
    `.replace(/\s+/g, ' ').trim();

    toast.innerHTML = `
        <i data-lucide="${icons[type]}" class="w-4 h-4 shrink-0"></i>
        <span>${message}</span>
    `;

    container.appendChild(toast);
    if (typeof lucide !== 'undefined') lucide.createIcons({ el: toast });

    // Forçar reflow para animar
    requestAnimationFrame(() => {
        requestAnimationFrame(() => toast.classList.add('toast-visible'));
    });

    setTimeout(() => {
        toast.classList.remove('toast-visible');
        toast.classList.add('toast-exit');
        toast.addEventListener('transitionend', () => toast.remove(), { once: true });
    }, 3500);
}

// ── Modal ────────────────────────────────────────────────────
function openModal(title, data = null) {
    App.isEditing = data !== null;
    modalTitle.textContent = title;
    btnLabel.textContent   = App.isEditing ? 'Atualizar Item' : 'Salvar Item';
    formAction.value       = App.isEditing ? 'update' : 'create';

    clearFormErrors();
    itemForm.reset();

    if (data) {
        itemIdInput.value              = data.id;
        fields.nome.input.value        = data.nome;
        fields.categoria.input.value   = data.categoria;
        fields.quantidade.input.value  = data.quantidade;
        fields.preco.input.value       = parseFloat(data.preco).toFixed(2);
    } else {
        itemIdInput.value = '';
    }

    modalOverlay.classList.remove('hidden');
    modalOverlay.classList.add('flex');
    setTimeout(() => fields.nome.input.focus(), 120);
}

function closeModal() {
    modalOverlay.classList.add('hidden');
    modalOverlay.classList.remove('flex');
    clearFormErrors();
    itemForm.reset();
}

$('#btn-new-item').addEventListener('click', () => openModal('Adicionar Novo Item'));
$('#modal-close').addEventListener('click', closeModal);
$('#btn-cancel').addEventListener('click', closeModal);

modalOverlay.addEventListener('click', (e) => {
    if (e.target === modalOverlay) closeModal();
});

// ── Confirmar Exclusão ───────────────────────────────────────
function openConfirm(id) {
    App.pendingDeleteId = id;
    confirmOverlay.classList.remove('hidden');
    confirmOverlay.classList.add('flex');
}

function closeConfirm() {
    App.pendingDeleteId = null;
    confirmOverlay.classList.add('hidden');
    confirmOverlay.classList.remove('flex');
}

$('#confirm-cancel').addEventListener('click', closeConfirm);

confirmOverlay.addEventListener('click', (e) => {
    if (e.target === confirmOverlay) closeConfirm();
});

$('#confirm-ok').addEventListener('click', async () => {
    if (!App.pendingDeleteId) return;
    await sendRequest({ action: 'delete', id: App.pendingDeleteId });
    closeConfirm();
});

// ── Delegação de eventos na tabela ───────────────────────────
tbody.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;

    const row    = btn.closest('.item-row');
    if (!row) return;

    const action = btn.dataset.action;
    const data   = row.dataset;

    if (action === 'edit') {
        openModal('Editar Item', {
            id:         data.id,
            nome:       data.nome,
            categoria:  data.categoria,
            quantidade: data.quantidade,
            preco:      data.preco,
        });
    }

    if (action === 'delete') {
        openConfirm(data.id);
    }
});

// ── Validação Client-Side ─────────────────────────────────────
function clearFormErrors() {
    Object.values(fields).forEach(({ input, error }) => {
        error.textContent = '';
        input.classList.remove('input-invalid');
    });
}

function validateForm() {
    clearFormErrors();
    let valid = true;

    const nome       = fields.nome.input.value.trim();
    const categoria  = fields.categoria.input.value.trim();
    const quantidade = fields.quantidade.input.value.trim();
    const preco      = fields.preco.input.value.trim();

    // Nome
    if (!nome) {
        setError('nome', 'Nome é obrigatório.');
        valid = false;
    } else if (nome.length < 2) {
        setError('nome', 'Nome deve ter ao menos 2 caracteres.');
        valid = false;
    } else if (nome.length > 120) {
        setError('nome', 'Nome muito longo (máx 120 caracteres).');
        valid = false;
    }

    // Categoria
    if (!categoria) {
        setError('categoria', 'Categoria é obrigatória.');
        valid = false;
    } else if (categoria.length < 2) {
        setError('categoria', 'Categoria deve ter ao menos 2 caracteres.');
        valid = false;
    }

    // Quantidade
    if (quantidade === '') {
        setError('quantidade', 'Quantidade é obrigatória.');
        valid = false;
    } else if (!/^\d+$/.test(quantidade) || parseInt(quantidade, 10) < 0) {
        setError('quantidade', 'Digite um número inteiro ≥ 0.');
        valid = false;
    }

    // Preço
    if (preco === '') {
        setError('preco', 'Preço é obrigatório.');
        valid = false;
    } else {
        const precoNum = parseFloat(preco.replace(',', '.'));
        if (isNaN(precoNum) || precoNum < 0) {
            setError('preco', 'Digite um valor numérico ≥ 0.');
            valid = false;
        }
    }

    return valid;
}

function setError(field, message) {
    fields[field].error.textContent = message;
    fields[field].input.classList.add('input-invalid');
}

// Limpa erro ao digitar
Object.values(fields).forEach(({ input, error }) => {
    input.addEventListener('input', () => {
        error.textContent = '';
        input.classList.remove('input-invalid');
    });
});

// ── Envio do Formulário ───────────────────────────────────────
itemForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validateForm()) return;

    const payload = {
        action:     formAction.value,
        id:         itemIdInput.value,
        nome:       fields.nome.input.value.trim(),
        categoria:  fields.categoria.input.value.trim(),
        quantidade: fields.quantidade.input.value.trim(),
        preco:      fields.preco.input.value.trim().replace(',', '.'),
    };

    await sendRequest(payload, true);
});

// ── Requisição AJAX ───────────────────────────────────────────
async function sendRequest(payload, closeOnSuccess = false) {
    setLoading(true);

    const formData = new URLSearchParams(payload);

    try {
        const response = await fetch('process.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    formData.toString(),
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const result = await response.json();

        if (result.success) {
            showToast(result.message, 'success');
            if (closeOnSuccess) closeModal();
            handleDOMUpdate(payload.action, result.data);
            updateKPIs();
        } else {
            showToast(result.message || 'Ocorreu um erro.', 'error');
        }

    } catch (err) {
        console.error(err);
        showToast('Falha na comunicação com o servidor.', 'error');
    } finally {
        setLoading(false);
    }
}

function setLoading(state) {
    btnSubmit.disabled = state;
    btnLabel.classList.toggle('opacity-50', state);
    btnSpinner.classList.toggle('hidden', !state);
}

// ── Atualização Dinâmica do DOM ───────────────────────────────
function handleDOMUpdate(action, data) {
    const emptyRow = $('#empty-row');

    if (action === 'create' && data) {
        if (emptyRow) emptyRow.remove();
        const tr = buildRow(data);
        tbody.insertAdjacentElement('afterbegin', tr);
        tr.classList.add('row-highlight');
        setTimeout(() => tr.classList.remove('row-highlight'), 2000);
        if (typeof lucide !== 'undefined') lucide.createIcons({ el: tr });
    }

    if (action === 'update' && data) {
        const existing = $(`[data-id="${data.id}"]`, tbody);
        if (existing) {
            const newRow = buildRow(data);
            existing.replaceWith(newRow);
            newRow.classList.add('row-highlight');
            setTimeout(() => newRow.classList.remove('row-highlight'), 2000);
            if (typeof lucide !== 'undefined') lucide.createIcons({ el: newRow });
        }
    }

    if (action === 'delete' && data) {
        const id = data[':id'] || data.id;
        const row = $(`[data-id="${id}"]`, tbody);
        if (row) {
            row.classList.add('row-remove');
            row.addEventListener('animationend', () => {
                row.remove();
                if ($$('.item-row', tbody).length === 0) showEmptyState();
            }, { once: true });
        }
    }

    filterItems(searchInput.value);
}

function buildRow(item) {
    const preco     = parseFloat(item.preco);
    const quantidade = parseInt(item.quantidade, 10);
    const total     = preco * quantidade;
    const qCls      = quantidade === 0 ? 'text-red-400' : quantidade < 5 ? 'text-amber-400' : 'text-emerald-400';

    const tr = document.createElement('tr');
    tr.className = 'item-row border-b border-slate-800/40 hover:bg-slate-800/30 transition-colors group';
    tr.dataset.id         = item.id;
    tr.dataset.nome       = item.nome;
    tr.dataset.categoria  = item.categoria;
    tr.dataset.quantidade = item.quantidade;
    tr.dataset.preco      = item.preco;

    tr.innerHTML = `
        <td class="px-5 py-4 font-mono text-xs text-slate-600">#${item.id}</td>
        <td class="px-5 py-4">
            <span class="font-medium text-slate-100 item-nome">${escapeHtml(item.nome)}</span>
        </td>
        <td class="px-5 py-4 hidden sm:table-cell">
            <span class="category-badge item-categoria">${escapeHtml(item.categoria)}</span>
        </td>
        <td class="px-5 py-4 text-center hidden md:table-cell">
            <span class="font-mono font-medium ${qCls} item-quantidade">${quantidade}</span>
        </td>
        <td class="px-5 py-4 text-right font-mono text-slate-300">
            R$&nbsp;<span class="item-preco">${preco.toFixed(2).replace('.', ',')}</span>
        </td>
        <td class="px-5 py-4 text-right font-mono text-slate-400 hidden lg:table-cell">
            R$&nbsp;${total.toFixed(2).replace('.', ',')}
        </td>
        <td class="px-5 py-4">
            <div class="flex items-center justify-center gap-2">
                <button class="btn-icon-edit" title="Editar" data-action="edit">
                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                </button>
                <button class="btn-icon-delete" title="Excluir" data-action="delete">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </td>
    `;
    return tr;
}

function showEmptyState() {
    const tr = document.createElement('tr');
    tr.id = 'empty-row';
    tr.innerHTML = `
        <td colspan="7" class="px-5 py-16 text-center text-slate-500">
            <div class="flex flex-col items-center gap-3">
                <div class="w-16 h-16 rounded-2xl bg-slate-800/60 flex items-center justify-center">
                    <i data-lucide="package-open" class="w-8 h-8 text-slate-600"></i>
                </div>
                <div>
                    <p class="font-medium text-slate-400">Nenhum item cadastrado</p>
                    <p class="text-xs mt-1">Clique em "Novo Item" para começar</p>
                </div>
            </div>
        </td>
    `;
    tbody.appendChild(tr);
    if (typeof lucide !== 'undefined') lucide.createIcons({ el: tr });
}

// ── Busca em Tempo Real ───────────────────────────────────────
function filterItems(query) {
    const term = query.toLowerCase().trim();
    const rows = $$('.item-row', tbody);
    let visibleCount = 0;

    rows.forEach(row => {
        const nome       = (row.dataset.nome      || '').toLowerCase();
        const categoria  = (row.dataset.categoria || '').toLowerCase();
        const match      = !term || nome.includes(term) || categoria.includes(term);
        row.classList.toggle('hidden', !match);
        if (match) visibleCount++;
    });

    const hasRows = rows.length > 0;
    noResults.classList.toggle('hidden', !hasRows || visibleCount > 0);
    resultCount.textContent = visibleCount;
}

let searchDebounce;
searchInput.addEventListener('input', (e) => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => filterItems(e.target.value), 160);
});

// ── Atualizar KPIs ─────────────────────────────────────────────
function updateKPIs() {
    const rows = $$('.item-row', tbody);
    let totalItens = rows.length;
    let totalQtd   = 0;
    let totalValor = 0;
    const cats     = new Set();

    rows.forEach(row => {
        const qty   = parseInt(row.dataset.quantidade, 10) || 0;
        const price = parseFloat(row.dataset.preco) || 0;
        totalQtd   += qty;
        totalValor += qty * price;
        if (row.dataset.categoria) cats.add(row.dataset.categoria);
    });

    $('#kpi-total').textContent = totalItens;
    $('#kpi-qtd').textContent   = totalQtd.toLocaleString('pt-BR');
    $('#kpi-valor').textContent = totalValor.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
    $('#kpi-cats').textContent  = cats.size;
}

// ── Helpers ────────────────────────────────────────────────────
function escapeHtml(text) {
    const map = { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":"&#039;" };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}
