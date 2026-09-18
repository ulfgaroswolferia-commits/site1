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
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; }
    </style>
</head>
<body class="min-h-full flex flex-col bg-slate-100 text-slate-800">

    <!-- Top Navigation Header -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand Logo Link -->
                <a href="<?= $base ?>b2b" class="flex items-center gap-3 group focus:outline-none" title="Przejdź do startu zamówienia">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 flex items-center justify-center text-white shadow-md shadow-emerald-600/20 group-hover:bg-emerald-700 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-lg font-black tracking-tight text-slate-900 block leading-tight group-hover:text-emerald-700 transition">HURTOWNIA MAGDY</span>
                        <span class="text-[11px] font-bold tracking-wider uppercase text-emerald-600">Platforma zamówień B2B</span>
                    </div>
                </a>

                <!-- Client Info & Actions -->
                <div class="flex items-center gap-4">
                    <a href="<?= $base ?>b2b" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-xs font-bold text-white shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Wróć do katalogu
                    </a>

                    <a href="<?= $base ?>b2b/logout" class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Wyloguj">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            <th class="py-3.5 px-4 text-center">Pozycje</th>
                            <th class="py-3.5 px-4 text-right">Wartość</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Dokumenty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400">
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
                                            class="group font-black text-emerald-600 hover:text-emerald-800 transition flex items-center gap-1.5 focus:outline-none cursor-pointer text-left"
                                            title="Kliknij, aby zobaczyć szczegóły zamówienia">
                                            <span class="group-hover:underline"><?= htmlspecialchars($o['order_number']) ?></span>
                                            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-emerald-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </td>
                                    <td class="py-4 px-4 text-slate-500 text-xs">
                                        <?= htmlspecialchars($o['created_at']) ?>
                                    </td>
                                    <td class="py-4 px-4 text-center font-bold text-slate-700">
                                        <?= (int)$o['total_items'] ?>
                                    </td>
                                    <td class="py-4 px-4 text-right font-black text-slate-900">
                                        <?= number_format((float)$o['total_amount'], 2, '.', ' ') ?> zł
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border <?= $badgeClass ?>">
                                            <?= $statusLabel ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <a href="<?= $base ?>b2b/download?id=<?= (int)$o['id'] ?>"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-100 hover:text-emerald-700 transition">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
    <div id="clientOrderModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl overflow-hidden border border-slate-200 flex flex-col max-h-[90vh]">
            <!-- Modal Header -->
            <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center shadow-xs">
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
                <button type="button" onclick="closeClientOrderModal()" class="text-slate-400 hover:text-slate-700 p-2 rounded-xl hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Body (Dane dostawy + Tabela pozycji) -->
            <div class="p-6 overflow-y-auto space-y-4 flex-1">
                <!-- Info o dostawie i uwagach -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 bg-slate-50 rounded-2xl text-xs border border-slate-200/70">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Adres dostawy</span>
                        <span id="modalOrderAddress" class="font-semibold text-slate-800"></span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Uwagi do zamówienia</span>
                        <span id="modalOrderNotes" class="font-medium text-slate-600 italic"></span>
                    </div>
                </div>

                <!-- Tabela pozycji -->
                <div class="border border-slate-200 rounded-2xl overflow-hidden">
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
                    <strong id="modalOrderTotal" class="text-lg font-black text-emerald-700 ml-1.5">0.00 zł</strong>
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <a id="modalDownloadBtn" href="#" target="_blank"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Pobierz plik (.xlsx)
                    </a>
                    <button type="button" onclick="closeClientOrderModal()"
                        class="px-4 py-2.5 bg-slate-200 hover:bg-slate-300 font-bold text-xs text-slate-700 rounded-xl transition">
                        Zamknij
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = '<?= $base ?>';
        const modal = document.getElementById('clientOrderModal');

        function openClientOrderDetails(id) {
            fetch(BASE_URL + 'b2b/orderdetails?id=' + id)
                .then(r => r.json())
                .then(d => {
                    if (!d.ok) throw new Error(d.error || 'Nie udało się pobrać szczegółów zamówienia.');

                    document.getElementById('modalOrderNumber').textContent = d.order.order_number;
                    document.getElementById('modalOrderDate').textContent = 'Złożone: ' + (d.order.created_at || '—');
                    document.getElementById('modalOrderAddress').textContent = d.order.delivery_address_snapshot || 'Standardowy adres dostawy';
                    document.getElementById('modalOrderNotes').textContent = d.order.notes ? `"${d.order.notes}"` : 'Brak dodatkowych uwag';
                    document.getElementById('modalOrderTotal').textContent = parseFloat(d.order.total_amount).toFixed(2) + ' zł';
                    document.getElementById('modalDownloadBtn').href = BASE_URL + 'b2b/download?id=' + d.order.id;

                    // Badge statusu
                    const st = d.order.status || 'new';
                    const statusBadges = {
                        'new': '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border bg-sky-50 text-sky-700 border-sky-200">Nowe</span>',
                        'processing': '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border bg-amber-50 text-amber-700 border-amber-200">W kompletacji</span>',
                        'completed': '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border bg-emerald-50 text-emerald-700 border-emerald-200">Zrealizowane</span>',
                        'cancelled': '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border bg-rose-50 text-rose-700 border-rose-200">Anulowane</span>'
                    };
                    document.getElementById('modalOrderStatusBadge').innerHTML = statusBadges[st] || `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border bg-slate-50 text-slate-700 border-slate-200">${st}</span>`;

                    // Pozycje zamówienia
                    const tbody = document.getElementById('modalOrderItemsBody');
                    tbody.innerHTML = '';
                    if (d.items && d.items.length > 0) {
                        d.items.forEach(it => {
                            const tr = document.createElement('tr');
                            tr.className = 'hover:bg-slate-50 transition';
                            tr.innerHTML = `
                                <td class="py-2.5 px-3 font-bold text-slate-800">${it.product_name}</td>
                                <td class="py-2.5 px-3 text-right font-semibold text-emerald-700">${parseFloat(it.quantity)} ${it.unit}</td>
                                <td class="py-2.5 px-3 text-center">
                                    <span class="inline-block px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-medium text-[11px]">
                                        ${it.package_summary || '—'}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-right font-black text-slate-900">${parseFloat(it.item_total).toFixed(2)} zł</td>
                            `;
                            tbody.appendChild(tr);
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="4" class="py-6 text-center text-slate-400">Brak pozycji w zamówieniu.</td></tr>';
                    }

                    modal.classList.remove('hidden');
                })
                .catch(err => alert(err.message));
        }

        function closeClientOrderModal() {
            modal.classList.add('hidden');
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
