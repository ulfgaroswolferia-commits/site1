<?php
$client = $view['client'] ?? [];
$orders = $view['orders'] ?? [];
$base   = $view['base'] ?? App::baseUrl();
$title  = $view['title'] ?? 'Historia Zamówień — Hurtownia Magdy';
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= Tools::h($base) ?>assets/css/b2b.css?v=<?= (int) @filemtime(BASE_PATH . '/assets/css/b2b.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; }
        
        /* Naprzemienne tło wierszy zamówień (zebra) oraz ciemniejszy szary na hover */
        table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }
        table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        table tbody tr {
            transition: background-color 0.15s ease-in-out;
        }
        table tbody tr:hover {
            background-color: #e2e8f0 !important;
        }
    </style>
</head>
<body class="min-h-full flex flex-col bg-slate-100 text-slate-800">

    <!-- Top Navigation Header -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand Logo Link -->
                <a href="<?= $base ?>b2b" class="flex items-center gap-3 group focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 rounded-xl shrink-0" title="Przejdź do startu zamówienia" aria-label="Hurtownia Magdy — przejdź do startu zamówienia">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 flex items-center justify-center text-white shadow-md shadow-emerald-600/20 group-hover:bg-emerald-700 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <div class="hidden sm:block">
                        <span class="text-lg font-black tracking-tight text-slate-900 block leading-tight group-hover:text-emerald-700 transition">HURTOWNIA MAGDY</span>
                        <span class="text-[11px] font-bold tracking-wider uppercase text-emerald-600">Platforma zamówień B2B</span>
                    </div>
                </a>

                <!-- Client Info & Actions -->
                <div class="flex items-center gap-2 sm:gap-4">
                    <a href="<?= $base ?>b2b" class="inline-flex items-center gap-1.5 min-h-[44px] sm:min-h-0 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-xs font-bold text-white shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Wróć do katalogu
                    </a>

                    <a href="<?= $base ?>b2b/logout" class="p-3 sm:p-2 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 transition" title="Wyloguj" aria-label="Wyloguj">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
        <div class="mb-6">
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Historia zamówień</h1>
            <p class="text-sm text-slate-500 mt-1">Zestawienie Twoich wcześniejszych zamówień w Hurtowni Magdy.</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-4">Numer zamówienia</th>
                            <th class="py-3.5 px-4">Data złożenia</th>
                            <th class="hidden sm:table-cell py-3.5 px-4 text-center">Pozycje</th>
                            <th class="py-3.5 px-4 text-right">Wartość</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Dokumenty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="6" class="py-12 px-4 text-center text-slate-500">
                                    Nie złożyłeś jeszcze żadnego zamówienia.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $o): 
                                $st = $o['status'] ?? 'new';
                                $badgeClass = match($st) {
                                    'new' => 'bg-sky-50 text-sky-700 border-sky-200',
                                    'processing' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-slate-50 text-slate-700 border-slate-200'
                                };
                                $statusLabel = match($st) {
                                    'new' => 'Nowe',
                                    'processing' => 'W kompletacji',
                                    'completed' => 'Zrealizowane',
                                    'cancelled' => 'Anulowane',
                                    default => $st
                                };
                            ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-4 px-4">
                                        <button type="button" 
                                            onclick="openClientOrderDetails(<?= (int)$o['id'] ?>)" 
                                            class="group font-black text-emerald-700 hover:text-emerald-800 transition flex items-center gap-1.5 min-h-[44px] sm:min-h-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 rounded cursor-pointer text-left"
                                            title="Kliknij, aby zobaczyć szczegóły zamówienia"
                                            aria-haspopup="dialog">
                                            <span class="group-hover:underline"><?= htmlspecialchars($o['order_number']) ?></span>
                                            <svg class="w-3.5 h-3.5 text-slate-500 group-hover:text-emerald-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </td>
                                    <td class="py-4 px-4 text-slate-500 text-xs">
                                        <?= htmlspecialchars($o['created_at']) ?>
                                    </td>
                                    <td class="hidden sm:table-cell py-4 px-4 text-center font-bold text-slate-700">
                                        <?= (int)$o['total_items'] ?>
                                    </td>
                                    <td class="py-4 px-4 text-right font-black text-slate-900 whitespace-nowrap">
                                        <?= Tools::h(Tools::money($o['total_amount'])) ?>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border <?= $badgeClass ?>">
                                            <?= Tools::h($statusLabel) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <a href="<?= $base ?>b2b/download?id=<?= (int)$o['id'] ?>"
                                            class="inline-flex items-center gap-1.5 min-h-[44px] sm:min-h-0 px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-100 hover:text-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 transition whitespace-nowrap">
                                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            Excel (.xlsx)
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Szczegółów Zamówienia Klienta -->
    <div id="clientOrderModal" role="dialog" aria-modal="true" aria-labelledby="modalOrderNumber" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl overflow-hidden border border-slate-200 flex flex-col max-h-[90vh]">
            <!-- Modal Header -->
            <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 id="modalOrderNumber" class="text-base font-black text-slate-900">Zamówienie</h3>
                            <span id="modalOrderStatusBadge"></span>
                        </div>
                        <p id="modalOrderDate" class="text-xs text-slate-500 mt-0.5"></p>
                    </div>
                </div>
                <button type="button" id="modalCloseBtn" onclick="closeClientOrderModal()" aria-label="Zamknij" class="text-slate-500 hover:text-slate-700 p-3 sm:p-2 rounded-xl hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Body (Dane dostawy + Tabela pozycji) -->
            <div class="p-6 overflow-y-auto space-y-4 flex-1">
                <!-- Info o dostawie i uwagach -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 bg-slate-50 rounded-2xl text-xs border border-slate-200/70">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Adres dostawy</span>
                        <span id="modalOrderAddress" class="font-semibold text-slate-800"></span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Uwagi do zamówienia</span>
                        <span id="modalOrderNotes" class="font-medium text-slate-600 italic"></span>
                    </div>
                </div>

                <!-- Tabela pozycji -->
                <div class="border border-slate-200 rounded-2xl overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider font-bold border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-3">Towar</th>
                                <th class="py-2.5 px-3 text-right">Ilość</th>
                                <th class="py-2.5 px-3 text-center">Rozbicie logistyczne</th>
                                <th class="py-2.5 px-3 text-right">Wartość</th>
                            </tr>
                        </thead>
                        <tbody id="modalOrderItemsBody" class="divide-y divide-slate-100">
                            <!-- Wiersze generowane dynamicznie przez JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-5 border-t border-slate-100 bg-slate-50/80 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-sm text-slate-700">
                    <span>Razem wartość zamówienia:</span>
                    <strong id="modalOrderTotal" class="text-lg font-black text-emerald-700 ml-1.5 whitespace-nowrap">0,00&nbsp;zł</strong>
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <a id="modalDownloadBtn" href="#" target="_blank" rel="noopener"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 min-h-[44px] sm:min-h-0 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Pobierz plik (.xlsx)
                    </a>
                    <button type="button" onclick="closeClientOrderModal()"
                        class="min-h-[44px] sm:min-h-0 px-4 py-2.5 bg-slate-200 hover:bg-slate-300 font-bold text-xs text-slate-700 rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 transition">
                        Zamknij
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Powiadomienia (toast) — zamiast alert() -->
    <div id="toastRegion" role="status" aria-live="polite" class="fixed z-[60] inset-x-4 bottom-4 sm:left-auto sm:right-6 sm:bottom-6 sm:w-96 flex flex-col gap-2 pointer-events-none"></div>

    <script>
        const BASE_URL = '<?= $base ?>';
        const LOGIN_URL = BASE_URL + 'b2b/login';

        // Kwota do WYŚWIETLENIA w formacie polskim (1 234,50 zł)
        const formatPLN = (v) => new Intl.NumberFormat('pl-PL', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(Number(v) || 0) + ' zł';

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            const div = document.createElement('div');
            div.textContent = String(str);
            return div.innerHTML;
        }

        // Toast: opts = { type: 'info'|'error', linkHref, linkText, duration }
        function showToast(message, opts) {
            const o = opts || {};
            const region = document.getElementById('toastRegion');
            if (!region) return;
            const toast = document.createElement('div');
            toast.className = 'pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-semibold text-white ' + (o.type === 'error' ? 'bg-rose-700' : 'bg-slate-800');
            const text = document.createElement('div');
            text.className = 'flex-1';
            text.textContent = message;
            if (o.linkHref) {
                const link = document.createElement('a');
                link.href = o.linkHref;
                link.className = 'block mt-1 underline font-extrabold focus:outline-none focus-visible:ring-2 focus-visible:ring-white rounded';
                link.textContent = o.linkText || o.linkHref;
                text.appendChild(link);
            }
            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'shrink-0 -m-1 p-1 rounded-lg text-white/80 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white';
            close.setAttribute('aria-label', 'Zamknij powiadomienie');
            close.textContent = '×';
            close.addEventListener('click', () => toast.remove());
            toast.append(text, close);
            region.appendChild(toast);
            const duration = (o.duration === undefined) ? (o.linkHref ? 0 : 6000) : o.duration;
            if (duration > 0) setTimeout(() => toast.remove(), duration);
        }

        // Odpowiedź fetch → JSON, z rozpoznaniem wygasłej sesji (401/403/419, przekierowanie, HTML zamiast JSON)
        async function readJsonResponse(res) {
            const contentType = (res.headers.get('content-type') || '').toLowerCase();
            if (res.status === 401 || res.status === 403 || res.status === 419 || res.redirected
                || (res.ok && contentType.indexOf('application/json') === -1)) {
                const err = new Error('Sesja wygasła — zaloguj się ponownie.');
                err.sessionExpired = true;
                throw err;
            }
            if (contentType.indexOf('application/json') === -1) {
                throw new Error('Błąd serwera (HTTP ' + res.status + '). Spróbuj ponownie za chwilę.');
            }
            return res.json();
        }

        const modal = document.getElementById('clientOrderModal');
        let lastFocusBeforeModal = null;

        function openClientOrderDetails(id) {
            lastFocusBeforeModal = document.activeElement;
            fetch(BASE_URL + 'b2b/orderdetails?id=' + id, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
                .then(readJsonResponse)
                .then(d => {
                    if (!d.ok) throw new Error(d.error || 'Nie udało się pobrać szczegółów zamówienia.');

                    document.getElementById('modalOrderNumber').textContent = d.order.order_number;
                    document.getElementById('modalOrderDate').textContent = 'Złożone: ' + (d.order.created_at || '—');
                    document.getElementById('modalOrderAddress').textContent = d.order.delivery_address_snapshot || 'Standardowy adres dostawy';
                    document.getElementById('modalOrderNotes').textContent = d.order.notes ? `"${d.order.notes}"` : 'Brak dodatkowych uwag';
                    document.getElementById('modalOrderTotal').textContent = formatPLN(d.order.total_amount);
                    document.getElementById('modalDownloadBtn').href = BASE_URL + 'b2b/download?id=' + d.order.id;

                    // Badge statusu
                    const st = d.order.status || 'new';
                    const statusBadges = {
                        'new': { label: 'Nowe', classes: 'bg-sky-50 text-sky-700 border-sky-200' },
                        'processing': { label: 'W kompletacji', classes: 'bg-amber-50 text-amber-700 border-amber-200' },
                        'completed': { label: 'Zrealizowane', classes: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
                        'cancelled': { label: 'Anulowane', classes: 'bg-rose-50 text-rose-700 border-rose-200' }
                    };
                    const badge = document.createElement('span');
                    const statusConfig = statusBadges[st];
                    badge.className = 'inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border ' +
                        (statusConfig ? statusConfig.classes : 'bg-slate-50 text-slate-700 border-slate-200');
                    badge.textContent = statusConfig ? statusConfig.label : String(st);
                    document.getElementById('modalOrderStatusBadge').replaceChildren(badge);

                    // Pozycje zamówienia
                    const tbody = document.getElementById('modalOrderItemsBody');
                    tbody.replaceChildren();
                    if (d.items && d.items.length > 0) {
                        d.items.forEach(it => {
                            const isCustom = Number(it.is_custom) === 1;
                            const tr = document.createElement('tr');
                            tr.className = isCustom ? 'bg-amber-50/40 hover:bg-amber-50/70 transition' : 'hover:bg-slate-50 transition';
                            const productName = document.createElement('td');
                            productName.className = 'py-2.5 px-3 font-bold text-slate-800';
                            if (isCustom) {
                                productName.innerHTML = `
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span>${escapeHtml(it.product_name)}</span>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">Spoza cennika</span>
                                    </div>
                                `;
                            } else {
                                productName.textContent = it.product_name;
                            }
                            const quantity = document.createElement('td');
                            quantity.className = 'py-2.5 px-3 text-right font-semibold ' + (isCustom ? 'text-amber-800' : 'text-emerald-700');
                            quantity.textContent = `${parseFloat(it.quantity)} ${it.unit}`;
                            const packageCell = document.createElement('td');
                            packageCell.className = 'py-2.5 px-3 text-center';
                            const packageSummary = document.createElement('span');
                            packageSummary.className = 'inline-block px-2 py-0.5 rounded font-medium text-[11px] ' + (isCustom ? 'bg-amber-100/70 text-amber-800' : 'bg-slate-100 text-slate-700');
                            packageSummary.textContent = it.package_summary || '—';
                            packageCell.appendChild(packageSummary);
                            const itemTotal = document.createElement('td');
                            itemTotal.className = 'py-2.5 px-3 text-right font-black text-slate-900 whitespace-nowrap';
                            itemTotal.textContent = isCustom && parseFloat(it.item_total) === 0 ? 'Do wyceny' : formatPLN(it.item_total);
                            tr.append(productName, quantity, packageCell, itemTotal);
                            tbody.appendChild(tr);
                        });
                    } else {
                        const emptyRow = document.createElement('tr');
                        const emptyCell = document.createElement('td');
                        emptyCell.colSpan = 4;
                        emptyCell.className = 'py-6 text-center text-slate-500';
                        emptyCell.textContent = 'Brak pozycji w zamówieniu.';
                        emptyRow.appendChild(emptyCell);
                        tbody.appendChild(emptyRow);
                    }

                    modal.classList.remove('hidden');
                    document.getElementById('modalCloseBtn').focus();
                })
                .catch(err => {
                    if (err && err.sessionExpired) {
                        showToast('Sesja wygasła — zaloguj się ponownie.', { type: 'error', linkHref: LOGIN_URL, linkText: 'Przejdź do logowania' });
                    } else {
                        showToast((err && err.message) ? err.message : 'Nie udało się pobrać szczegółów zamówienia.', { type: 'error' });
                    }
                });
        }

        function closeClientOrderModal() {
            modal.classList.add('hidden');
            if (lastFocusBeforeModal && typeof lastFocusBeforeModal.focus === 'function') lastFocusBeforeModal.focus();
        }

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeClientOrderModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeClientOrderModal();
        });
    </script>
</body>
</html>
