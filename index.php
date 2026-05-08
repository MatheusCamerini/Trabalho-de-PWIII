<?php
// ============================================================
//  index.php — Interface Principal + Listagem PHP
// ============================================================
require_once 'config.php';

// Busca todos os itens ordenados por criação (mais recente primeiro)
try {
    $db   = getDB();
    $stmt = $db->query('SELECT * FROM itens ORDER BY criado_em DESC');
    $itens = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $itens = [];
    $dbError = true;
}

$totalItens    = count($itens);
$totalQtd      = array_sum(array_column($itens, 'quantidade'));
$totalValor    = array_sum(array_map(
    fn($i) => $i['preco'] * $i['quantidade'], $itens
));
$categorias    = count(array_unique(array_column($itens, 'categoria')));
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Inventário · Dashboard</title>

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        slate: {
                            850: '#172033',
                            950: '#0b1220',
                        },
                        amber: { DEFAULT: '#f59e0b' }
                    },
                    fontFamily: {
                        sans: ['"DM Sans"', 'sans-serif'],
                        mono: ['"DM Mono"', 'monospace'],
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet" />

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css" />
</head>

<body class="bg-slate-950 text-slate-100 font-sans min-h-full antialiased">

<!-- ── TOAST CONTAINER ─────────────────────────────────────── -->
<div id="toast-container" class="fixed top-5 right-5 z-50 flex flex-col gap-3 pointer-events-none"></div>

<!-- ── MODAL OVERLAY ────────────────────────────────────────── -->
<div id="modal-overlay" class="fixed inset-0 z-40 bg-black/70 backdrop-blur-sm hidden items-center justify-center">
    <div id="modal-box" class="relative bg-slate-900 border border-slate-700/60 rounded-2xl shadow-2xl w-full max-w-md mx-4 p-7 modal-animate">
        <!-- Close -->
        <button id="modal-close" class="absolute top-4 right-4 text-slate-500 hover:text-slate-200 transition-colors">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>

        <h2 id="modal-title" class="text-xl font-semibold text-white mb-6"></h2>

        <form id="item-form" novalidate>
            <input type="hidden" id="item-id" name="id" value="" />
            <input type="hidden" id="form-action" name="action" value="create" />

            <div class="space-y-4">
                <!-- Nome -->
                <div class="field-group">
                    <label for="f-nome" class="field-label">Nome do Item</label>
                    <div class="input-icon-wrap">
                        <i data-lucide="package" class="input-icon"></i>
                        <input type="text" id="f-nome" name="nome" maxlength="120"
                               placeholder="Ex: Monitor Ultra Wide"
                               class="field-input" autocomplete="off" />
                    </div>
                    <p class="field-error" id="err-nome"></p>
                </div>

                <!-- Categoria -->
                <div class="field-group">
                    <label for="f-categoria" class="field-label">Categoria</label>
                    <div class="input-icon-wrap">
                        <i data-lucide="tag" class="input-icon"></i>
                        <input type="text" id="f-categoria" name="categoria" maxlength="80"
                               placeholder="Ex: Monitores"
                               class="field-input" autocomplete="off" />
                    </div>
                    <p class="field-error" id="err-categoria"></p>
                </div>

                <!-- Quantidade + Preço -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="field-group">
                        <label for="f-quantidade" class="field-label">Quantidade</label>
                        <div class="input-icon-wrap">
                            <i data-lucide="hash" class="input-icon"></i>
                            <input type="number" id="f-quantidade" name="quantidade" min="0"
                                   placeholder="0"
                                   class="field-input" />
                        </div>
                        <p class="field-error" id="err-quantidade"></p>
                    </div>

                    <div class="field-group">
                        <label for="f-preco" class="field-label">Preço (R$)</label>
                        <div class="input-icon-wrap">
                            <i data-lucide="dollar-sign" class="input-icon"></i>
                            <input type="number" id="f-preco" name="preco" min="0" step="0.01"
                                   placeholder="0,00"
                                   class="field-input" />
                        </div>
                        <p class="field-error" id="err-preco"></p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-3 mt-7">
                <button type="button" id="btn-cancel" class="btn-ghost flex-1">Cancelar</button>
                <button type="submit" id="btn-submit" class="btn-primary flex-1">
                    <span id="btn-label">Salvar Item</span>
                    <span id="btn-spinner" class="hidden ml-2">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin inline"></i>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── CONFIRM DIALOG ────────────────────────────────────────── -->
<div id="confirm-overlay" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden items-center justify-center">
    <div class="bg-slate-900 border border-red-900/50 rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-7 modal-animate text-center">
        <div class="w-14 h-14 rounded-full bg-red-950/60 border border-red-800/50 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="trash-2" class="w-7 h-7 text-red-400"></i>
        </div>
        <h3 class="text-lg font-semibold text-white mb-2">Excluir item?</h3>
        <p class="text-slate-400 text-sm mb-7">
            Esta ação é permanente e <strong class="text-slate-300">não pode ser desfeita</strong>.
        </p>
        <div class="flex gap-3">
            <button id="confirm-cancel" class="btn-ghost flex-1">Cancelar</button>
            <button id="confirm-ok" class="btn-danger flex-1">Sim, excluir</button>
        </div>
    </div>
</div>

<!-- ── SIDEBAR ───────────────────────────────────────────────── -->
<div class="flex min-h-screen">
    <aside class="sidebar w-64 shrink-0 hidden lg:flex flex-col border-r border-slate-800/70 px-5 py-7">
        <!-- Logo -->
        <div class="flex items-center gap-3 mb-10">
            <div class="w-9 h-9 rounded-lg bg-amber-400 flex items-center justify-center">
                <i data-lucide="boxes" class="w-5 h-5 text-slate-950"></i>
            </div>
            <span class="text-lg font-bold tracking-tight text-white">Inventário</span>
        </div>

        <!-- Nav -->
        <nav class="space-y-1">
            <a href="#" class="nav-item active">
                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                Dashboard
            </a>
        </nav>

        <div class="mt-auto pt-6 border-t border-slate-800/60">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-sm font-bold text-slate-950">A</div>
                <div>
                    <p class="text-sm font-medium text-slate-200">Admin</p>
                    <p class="text-xs text-slate-500">Gerenciador</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- ── MAIN CONTENT ─────────────────────────────────────── -->
    <main class="flex-1 overflow-x-hidden">

        <!-- Top bar -->
        <header class="sticky top-0 z-30 bg-slate-950/90 backdrop-blur border-b border-slate-800/60 px-6 py-4 flex items-center justify-between gap-4">
            <div>
                <h1 class="text-lg font-semibold text-white">Controle de Estoque</h1>
                <p class="text-xs text-slate-500 mt-0.5">Gerencie todos os seus itens em um só lugar</p>
            </div>

            <button id="btn-new-item" class="btn-primary gap-2 shrink-0">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Novo Item
            </button>
        </header>

        <div class="p-6 space-y-7">

            <!-- ── KPI CARDS ──────────────────────────────────── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

                <div class="kpi-card">
                    <div class="kpi-icon bg-blue-950/60 border-blue-800/40">
                        <i data-lucide="package-2" class="w-5 h-5 text-blue-400"></i>
                    </div>
                    <div>
                        <p class="kpi-label">Total de Itens</p>
                        <p class="kpi-value" id="kpi-total"><?= $totalItens ?></p>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon bg-emerald-950/60 border-emerald-800/40">
                        <i data-lucide="archive" class="w-5 h-5 text-emerald-400"></i>
                    </div>
                    <div>
                        <p class="kpi-label">Unidades em Estoque</p>
                        <p class="kpi-value" id="kpi-qtd"><?= number_format($totalQtd, 0, ',', '.') ?></p>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon bg-amber-950/60 border-amber-800/40">
                        <i data-lucide="circle-dollar-sign" class="w-5 h-5 text-amber-400"></i>
                    </div>
                    <div>
                        <p class="kpi-label">Valor Total (R$)</p>
                        <p class="kpi-value font-mono" id="kpi-valor"><?= number_format($totalValor, 2, ',', '.') ?></p>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon bg-purple-950/60 border-purple-800/40">
                        <i data-lucide="tag" class="w-5 h-5 text-purple-400"></i>
                    </div>
                    <div>
                        <p class="kpi-label">Categorias</p>
                        <p class="kpi-value" id="kpi-cats"><?= $categorias ?></p>
                    </div>
                </div>

            </div>

            <!-- ── SEARCH + TABLE ─────────────────────────────── -->
            <div class="bg-slate-900/60 border border-slate-800/60 rounded-2xl overflow-hidden">

                <!-- Toolbar -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 px-5 py-4 border-b border-slate-800/60">
                    <div class="relative flex-1">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 pointer-events-none"></i>
                        <input type="search" id="search-input"
                               placeholder="Buscar por nome, categoria…"
                               class="w-full bg-slate-800/60 border border-slate-700/50 rounded-lg pl-9 pr-4 py-2.5 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-400/40 focus:border-amber-400/50 transition" />
                    </div>
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <span id="result-count"><?= $totalItens ?></span> registro(s) encontrado(s)
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-800/60 text-xs uppercase tracking-wider text-slate-500">
                                <th class="px-5 py-3 text-left font-medium">#</th>
                                <th class="px-5 py-3 text-left font-medium">Nome</th>
                                <th class="px-5 py-3 text-left font-medium hidden sm:table-cell">Categoria</th>
                                <th class="px-5 py-3 text-center font-medium hidden md:table-cell">Qtd</th>
                                <th class="px-5 py-3 text-right font-medium">Preço</th>
                                <th class="px-5 py-3 text-right font-medium hidden lg:table-cell">Total</th>
                                <th class="px-5 py-3 text-center font-medium">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="items-tbody">
                        <?php if (isset($dbError)): ?>
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-red-400">
                                    <i data-lucide="alert-triangle" class="inline w-5 h-5 mb-1"></i>
                                    Não foi possível conectar ao banco de dados.
                                </td>
                            </tr>
                        <?php elseif (empty($itens)): ?>
                            <tr id="empty-row">
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
                            </tr>
                        <?php else: ?>
                            <?php foreach ($itens as $item): ?>
                            <tr class="item-row border-b border-slate-800/40 hover:bg-slate-800/30 transition-colors group"
                                data-id="<?= $item['id'] ?>"
                                data-nome="<?= htmlspecialchars($item['nome'], ENT_QUOTES) ?>"
                                data-categoria="<?= htmlspecialchars($item['categoria'], ENT_QUOTES) ?>"
                                data-quantidade="<?= $item['quantidade'] ?>"
                                data-preco="<?= $item['preco'] ?>">

                                <td class="px-5 py-4 font-mono text-xs text-slate-600">#<?= $item['id'] ?></td>

                                <td class="px-5 py-4">
                                    <span class="font-medium text-slate-100 item-nome"><?= htmlspecialchars($item['nome']) ?></span>
                                </td>

                                <td class="px-5 py-4 hidden sm:table-cell">
                                    <span class="category-badge item-categoria"><?= htmlspecialchars($item['categoria']) ?></span>
                                </td>

                                <td class="px-5 py-4 text-center hidden md:table-cell">
                                    <?php
                                        $q = (int) $item['quantidade'];
                                        $cls = $q === 0 ? 'text-red-400' : ($q < 5 ? 'text-amber-400' : 'text-emerald-400');
                                    ?>
                                    <span class="font-mono font-medium <?= $cls ?> item-quantidade"><?= $q ?></span>
                                </td>

                                <td class="px-5 py-4 text-right font-mono text-slate-300">
                                    R$&nbsp;<span class="item-preco"><?= number_format((float)$item['preco'], 2, ',', '.') ?></span>
                                </td>

                                <td class="px-5 py-4 text-right font-mono text-slate-400 hidden lg:table-cell">
                                    R$&nbsp;<?= number_format($item['preco'] * $item['quantidade'], 2, ',', '.') ?>
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
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- No search results -->
                <div id="no-results" class="hidden py-14 text-center text-slate-500">
                    <i data-lucide="search-x" class="inline w-8 h-8 mb-3 text-slate-600 block mx-auto"></i>
                    <p class="font-medium text-slate-400">Nenhum resultado encontrado</p>
                    <p class="text-xs mt-1">Tente outros termos de busca</p>
                </div>

            </div>
        </div><!-- /p-6 -->
    </main>
</div>

<script src="script.js"></script>
<script>
    // Inicializa ícones Lucide após o DOM
    if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</body>
</html>
