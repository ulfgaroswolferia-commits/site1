<?php
$client     = $view['client'] ?? [];
$products   = $view['products'] ?? [];
$categories = $view['categories'] ?? [];
$csrfToken  = $view['csrfToken'] ?? Tools::csrfToken();
$base       = $view['base'] ?? App::baseUrl();
$title      = $view['title'] ?? 'Katalog Zamówień B2B — Hurtownia Magdy';
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= Tools::h($base) ?>assets/css/b2b.css?v=<?= (int) @filemtime(BASE_PATH . '/assets/css/b2b.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        // Ochrona przed wyciekiem tokenu: natychmiastowe usunięcie parametru token z adresu i historii
        if (window.history && window.history.replaceState && window.location.search.includes('token=')) {
            const url = new URL(window.location.href);
            url.searchParams.delete('token');
            const cleanUrl = url.pathname + (url.searchParams.toString() ? '?' + url.searchParams.toString() : '') + url.hash;
            window.history.replaceState({}, document.title, cleanUrl);
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; }
        /* Chrome, Safari, Edge, Opera */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        /* Firefox */
        input[type=number] {
            -moz-appearance: textfield;
        }

        /* Naprzemienne tło wierszy asortymentu (zebra) oraz ciemniejszy szary na hover */
        #catalogTable tbody tr.product-row:nth-child(odd),
        #catalogTable tbody tr.product-row.prod-row-odd {
            background-color: #ffffff;
        }
        #catalogTable tbody tr.product-row:nth-child(even),
        #catalogTable tbody tr.product-row.prod-row-even {
            background-color: #f8fafc;
        }
        #catalogTable tbody tr.product-row {
            transition: background-color 0.15s ease-in-out;
        }
        #catalogTable tbody tr.product-row:hover,
        #catalogTable tbody tr.product-row.prod-row-odd:hover,
        #catalogTable tbody tr.product-row.prod-row-even:hover {
            background-color: #e2e8f0 !important;
        }

        /* Telefon (< sm): każdy wiersz asortymentu jako karta. Ten sam markup co tabela,
           więc selektory JS (.product-row, .input-qty, .btn-step-*, data-*) działają bez zmian.
           Wyświetlanie ustawione tutaj, a nie klasami Tailwind, żeby przełączana przez JS
           klasa .hidden (filtry, wyszukiwarka) zawsze wygrywała. */
        @media (max-width: 639.98px) {
            #catalogTable,
            #catalogTable tbody { display: block; width: 100%; }
            #catalogTable tbody tr.product-row {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                grid-template-areas:
                    "name name"
                    "pkg price"
                    "qty qty"
                    "summary total";
                column-gap: 0.75rem;
                row-gap: 0.625rem;
                padding: 1rem;
                align-items: center;
            }
            #catalogTable tbody tr.product-row.hidden { display: none; }
            #catalogTable tbody tr.product-row > td { display: block; padding: 0; }
            #catalogTable tbody tr.product-row > td.cell-lp { display: none; }
            #catalogTable tbody tr.product-row > td.cell-name { grid-area: name; }
            #catalogTable tbody tr.product-row > td.cell-pkg { grid-area: pkg; }
            #catalogTable tbody tr.product-row > td.cell-price { grid-area: price; }
            #catalogTable tbody tr.product-row > td.cell-qty { grid-area: qty; }
            #catalogTable tbody tr.product-row > td.cell-summary { grid-area: summary; align-self: start; }
            #catalogTable tbody tr.product-row > td.cell-total { grid-area: total; align-self: start; }
            #catalogTable tbody tr:not(.product-row),
            #catalogTable tbody tr:not(.product-row) > td { display: block; }
        }
    </style>
</head>
<body class="min-h-full flex flex-col bg-slate-100 text-slate-800 pb-40 sm:pb-28">

    <?php if (!empty($view['isAdmin'])): ?>
    <!-- Pasek informacyjny trybu podglądu dla administratora hurtowni -->
    <div class="bg-amber-500 text-slate-950 px-4 py-2 text-xs font-bold shadow-sm relative z-50 border-b border-amber-600/40">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-slate-900 text-amber-400 text-xs">👁️</span>
                <span><strong>TRYB PODGLĄDU SKLEPU B2B:</strong> Jesteś zalogowany jako Hurtownik (Admin). Klienci widzą poniższy cennik i asortyment.</span>
            </div>
            <a href="<?= $base ?>b2b/admin" class="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-900 text-white hover:bg-slate-800 rounded-lg text-xs font-bold transition shadow-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Wróć do Panelu Hurtownika
            </a>
        </div>
    </div>
    <?php endif; ?>

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
                <div class="flex items-center gap-2 sm:gap-6 min-w-0">
                    <div class="text-right min-w-0">
                        <div class="flex items-center justify-end gap-1.5">
                            <span class="inline-block w-2 h-2 shrink-0 rounded-full <?= !empty($view['isAdmin']) ? 'bg-amber-500' : 'bg-emerald-500 animate-pulse' ?>"></span>
                            <span class="text-xs font-black text-slate-900 uppercase tracking-wide truncate">
                                <?= htmlspecialchars($client['company_name'] ?? 'Odbiorca B2B') ?>
                            </span>
                        </div>
                        <div class="text-[11px] text-slate-500 truncate max-w-[160px] sm:max-w-xs">
                            <?= htmlspecialchars($client['delivery_address'] ?? 'Dostawa hurtowa') ?>
                        </div>
                    </div>

                    <?php if (!empty($view['isAdmin'])): ?>
                    <a href="<?= $base ?>b2b/admin" class="inline-flex items-center justify-center gap-1.5 min-w-[44px] min-h-[44px] sm:min-w-0 sm:min-h-0 px-3 py-1.5 rounded-lg bg-amber-100 border border-amber-300 text-xs font-bold text-amber-900 hover:bg-amber-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 transition shrink-0" title="Wróć do panelu hurtownika" aria-label="Panel Hurtownika">
                        <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span class="hidden sm:inline">Panel Hurtownika</span>
                    </a>
                    <a href="<?= $base ?>home/logout" class="p-3 sm:p-2 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 transition shrink-0" title="Wyloguj administratora" aria-label="Wyloguj administratora">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </a>
                    <?php else: ?>
                    <a href="<?= $base ?>b2b/history" class="inline-flex items-center justify-center gap-1.5 w-11 h-11 sm:w-auto sm:h-auto sm:px-3 sm:py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 transition shrink-0" title="Historia zamówień" aria-label="Historia zamówień">
                        <svg class="w-5 h-5 sm:w-3.5 sm:h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="hidden sm:inline">Historia</span>
                    </a>

                    <a href="<?= $base ?>b2b/logout" class="p-3 sm:p-2 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 transition shrink-0" title="Wyloguj" aria-label="Wyloguj">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full flex-1">
        
        <!-- Controls Bar: Live Search & Category Filter Pills -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-slate-200 mb-6 space-y-4">
            <div class="flex flex-col sm:flex-row gap-4 justify-between items-stretch sm:items-center">
                <!-- Search -->
                <div class="relative flex-1 max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" id="searchInput" placeholder="Szukaj towaru (np. mango, ziemniak, pomidor)..." aria-label="Szukaj towaru"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-base sm:text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition">
                </div>

                <!-- Fast Filter Toggle (Tylko zamawiane) -->
                <div class="flex flex-wrap items-center justify-between sm:justify-start gap-3">
                    <label class="flex items-center gap-2 min-h-[44px] sm:min-h-0 text-xs font-bold text-slate-700 cursor-pointer select-none">
                        <input type="checkbox" id="filterOrderedOnly" class="w-5 h-5 sm:w-4 sm:h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500">
                        <span>Pokaż tylko wybrane pozycje (<span id="orderedCountLabel">0</span>)</span>
                    </label>
                    <button type="button" id="clearQuantitiesBtn" class="text-xs text-rose-600 hover:text-rose-700 font-semibold px-3 py-2.5 sm:px-2 sm:py-1 rounded hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 transition">
                        Wyczyść koszyk
                    </button>
                    <!-- Potwierdzenie czyszczenia koszyka w miejscu (zamiast confirm()) -->
                    <div id="clearConfirmBox" class="hidden flex items-center gap-2 text-xs" role="group" aria-labelledby="clearConfirmText">
                        <span id="clearConfirmText" class="font-bold text-rose-700">Wyczyścić cały koszyk?</span>
                        <button type="button" id="clearConfirmYes" class="px-3 py-2.5 sm:py-1 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2 transition">Tak, wyczyść</button>
                        <button type="button" id="clearConfirmNo" class="px-3 py-2.5 sm:py-1 rounded-lg border border-slate-300 text-slate-700 font-bold hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 transition">Anuluj</button>
                    </div>
                </div>
            </div>

            <!-- Categories pills -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none text-xs font-semibold">
                <button type="button" class="category-pill active shrink-0 whitespace-nowrap px-3.5 py-2.5 sm:py-1.5 rounded-xl bg-emerald-600 text-white shadow-sm transition" data-category="ALL">
                    Wszystkie towary
                </button>
                <?php foreach (($categories ?? []) as $cat): ?>
                    <button type="button" class="category-pill shrink-0 whitespace-nowrap px-3.5 py-2.5 sm:py-1.5 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 transition" data-category="<?= htmlspecialchars($cat) ?>">
                        <?= htmlspecialchars($cat) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Product Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm" id="catalogTable">
                    <thead class="hidden sm:table-header-group bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4 w-12 text-center">Lp.</th>
                            <th class="py-3 px-4">Towar</th>
                            <th class="py-3 px-3 w-44 whitespace-nowrap">Opakowanie</th>
                            <th class="py-3 px-3 text-right w-28 whitespace-nowrap">Cena</th>
                            <th class="py-3 px-4 text-center w-48 whitespace-nowrap">Ilość do zamówienia</th>
                            <th class="py-3 px-3 w-44 whitespace-nowrap">Rozbicie logistyczne</th>
                            <th class="py-3 px-4 text-right w-32 whitespace-nowrap">Wartość</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="tableBody">
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="7" class="py-12 px-4 text-center text-slate-500">
                                    Brak dostępnych produktów w ofercie na dziś.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $lp = 1; foreach ($products as $p): 
                                $pkgSize = (float)($p['package_size'] ?? 1.0);
                                $pkgUnit = htmlspecialchars($p['package_unit'] ?? 'op.');
                                $unit = htmlspecialchars($p['unit'] ?? 'kg');
                                $price = (float)($p['price'] ?? 0);
                            ?>
                                <tr class="product-row transition-colors group"
                                    data-id="<?= (int)$p['id'] ?>"
                                    data-name="<?= htmlspecialchars($p['name']) ?>"
                                    data-category="<?= htmlspecialchars($p['category'] ?? 'Warzywa') ?>"
                                    data-unit="<?= $unit ?>"
                                    data-price="<?= $price ?>"
                                    data-pkg-size="<?= $pkgSize ?>"
                                    data-pkg-unit="<?= $pkgUnit ?>">
                                    
                                    <!-- Lp (ukryta w widoku kart na telefonie) -->
                                    <td class="cell-lp py-3.5 px-4 text-center text-xs font-semibold text-slate-500">
                                        <?= $lp++ ?>
                                    </td>

                                    <!-- Nazwa towaru -->
                                    <td class="cell-name py-3.5 px-4">
                                        <div class="font-bold text-base sm:text-sm text-slate-900 group-hover:text-emerald-700 transition">
                                            <?= htmlspecialchars($p['name']) ?>
                                        </div>
                                        <div class="text-[11px] text-slate-500">
                                            <?= htmlspecialchars($p['category'] ?? 'Świeże') ?>
                                        </div>
                                    </td>

                                    <!-- Opakowanie i asystent -->
                                    <td class="cell-pkg py-3.5 px-3 sm:whitespace-nowrap">
                                        <?php if ($pkgSize > 1.0): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-amber-50 border border-amber-200 text-amber-800 text-[11px] font-bold sm:whitespace-nowrap">
                                                <?= $pkgUnit ?> (<?= $pkgSize ?> <?= $unit ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-500 text-xs sm:whitespace-nowrap">luzem (<?= $unit ?>)</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Cena -->
                                    <td class="cell-price py-3.5 px-3 text-right whitespace-nowrap">
                                        <div class="font-bold text-slate-800">
                                            <?= Tools::h(Tools::money($price, false)) ?>&nbsp;<span class="text-xs font-normal text-slate-500">zł</span>
                                        </div>
                                        <div class="text-[10px] text-slate-500">za <?= $unit ?></div>
                                    </td>

                                    <!-- Pole ilości z klawiaturą i asystentem zaokrąglenia -->
                                    <td class="cell-qty py-3.5 px-4">
                                        <div class="flex items-center justify-center gap-2 sm:gap-1.5">
                                            <button type="button" aria-label="Zmniejsz ilość: <?= htmlspecialchars($p['name']) ?>" class="btn-step-down w-11 h-11 sm:w-8 sm:h-8 shrink-0 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-lg sm:text-base font-bold flex items-center justify-center active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 transition">
                                                -
                                            </button>
                                            <div class="relative flex-1 sm:flex-none sm:w-24">
                                                <input type="number" inputmode="decimal" step="<?= $unit === 'szt.' ? '1' : '0.5' ?>" min="0" value="" placeholder="0"
                                                    aria-label="Ilość: <?= htmlspecialchars($p['name']) ?>"
                                                    class="input-qty w-full h-11 sm:h-auto text-center py-1.5 px-2 rounded-lg border border-slate-300 font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-base sm:text-sm transition">
                                                <span class="absolute inset-y-0 right-2 flex items-center text-[10px] font-bold text-slate-500 pointer-events-none" aria-hidden="true"><?= $unit ?></span>
                                            </div>
                                            <button type="button" aria-label="Zwiększ ilość: <?= htmlspecialchars($p['name']) ?>" class="btn-step-up w-11 h-11 sm:w-8 sm:h-8 shrink-0 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-lg sm:text-base font-bold flex items-center justify-center active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 transition">
                                                +
                                            </button>
                                        </div>
                                        <!-- Przycisk asystenta optymalizacji klatki/worka -->
                                        <div class="optimizer-hint mt-1.5 sm:mt-1 text-center hidden">
                                            <button type="button" class="btn-round-up text-xs sm:text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg sm:rounded px-3 py-2.5 sm:px-2 sm:py-0.5 w-full sm:w-auto hover:bg-emerald-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 transition">
                                                Zaokrąglij do pełnej skrzynki
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Rozbicie logistyczne -->
                                    <td class="cell-summary py-3.5 px-3 sm:whitespace-nowrap">
                                        <span class="sm:hidden block text-[11px] font-bold uppercase tracking-wider text-slate-500">Opakowania</span>
                                        <span class="package-summary-label text-xs font-medium text-slate-600 sm:whitespace-nowrap">-</span>
                                    </td>

                                    <!-- Wartość -->
                                    <td class="cell-total py-3.5 px-4 text-right">
                                        <span class="sm:hidden block text-[11px] font-bold uppercase tracking-wider text-slate-500">Wartość</span>
                                        <span class="item-total-label font-bold text-slate-900 text-sm whitespace-nowrap">0,00&nbsp;zł</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sekcja: Dodaj produkt spoza cennika (na zapytanie) -->
        <div id="customProductSection" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mt-6 transition-all">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center shrink-0 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                            Dodaj produkt spoza cennika
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-amber-100/80 text-amber-800 border border-amber-200">Na zapytanie</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Potrzebujesz towaru, którego nie ma w ofercie hurtownika na dziś? Dopisz go do swojego zamówienia.</p>
                    </div>
                </div>

                <!-- Zastrzeżenie prawne / informacja o braku gwarancji -->
                <div class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-amber-50/90 border border-amber-200 text-amber-900 text-xs font-semibold shadow-sm">
                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Zamówienie produktów spoza cennika nie gwarantuje ich dostawy</span>
                </div>
            </div>

            <!-- Formularz dodawania pozycji -->
            <form id="customProductForm" class="mt-4 grid grid-cols-1 sm:grid-cols-12 gap-3 items-start" novalidate>
                <div class="sm:col-span-5">
                    <label for="customProdName" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Nazwa towaru <span class="text-rose-500" aria-hidden="true">*</span>
                    </label>
                    <input type="text" id="customProdName" placeholder="np. Koper włoski, Awokado Hass, Kurki świeże..." maxlength="200" aria-required="true"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 aria-[invalid=true]:border-rose-500 text-base sm:text-sm font-medium text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition">
                    <p id="customProdNameError" class="hidden mt-1 text-xs font-semibold text-rose-600"></p>
                </div>

                <div class="sm:col-span-2">
                    <label for="customProdQty" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Ilość <span class="text-rose-500" aria-hidden="true">*</span>
                    </label>
                    <input type="text" id="customProdQty" placeholder="np. 4,5 lub 5" inputmode="decimal" aria-required="true" autocomplete="off"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 aria-[invalid=true]:border-rose-500 text-base sm:text-sm font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition">
                    <p id="customProdQtyError" class="hidden mt-1 text-xs font-semibold text-rose-600"></p>
                </div>

                <div class="sm:col-span-2">
                    <label for="customProdUnit" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Jednostka
                    </label>
                    <select id="customProdUnit"
                        class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-base sm:text-sm font-semibold text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition">
                        <option value="kg">kg</option>
                        <option value="szt.">szt.</option>
                        <option value="op.">op.</option>
                        <option value="pęczek">pęczek</option>
                        <option value="skrzynka">skrzynka</option>
                        <option value="karton">karton</option>
                        <option value="worek">worek</option>
                    </select>
                </div>

                <div class="sm:col-span-3">
                    <span class="hidden sm:block text-xs font-bold uppercase tracking-wider mb-1.5 invisible" aria-hidden="true">&nbsp;</span>
                    <button type="submit" id="btnAddCustomProduct"
                        class="w-full min-h-[44px] py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-600 active:scale-95 text-white font-extrabold text-sm shadow-md shadow-amber-500/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition flex items-center justify-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Dodaj do zamówienia</span>
                    </button>
                </div>
            </form>

            <!-- Tabela pozycji spoza cennika (lista zamówienia) na dole -->
            <div id="customProductsContainer" class="mt-5 hidden">
                <div class="flex flex-wrap items-center justify-between gap-1 mb-2">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-amber-900 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        Pozycje spoza cennika na liście zamówienia (<span id="customItemsCount">0</span>):
                    </span>
                    <span class="text-[11px] text-slate-500">Wycena indywidualna przez hurtownię</span>
                </div>
                <div class="border border-amber-200/80 rounded-xl overflow-x-auto bg-amber-50/20">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-amber-100/60 text-amber-950 font-bold border-b border-amber-200/70">
                            <tr>
                                <th class="hidden sm:table-cell py-2.5 px-4 w-12 text-center">#</th>
                                <th class="py-2.5 px-3 sm:px-4">Nazwa towaru</th>
                                <th class="py-2.5 px-3 sm:px-4 text-center sm:w-32">Ilość</th>
                                <th class="hidden sm:table-cell py-2.5 px-4 w-44">Status dostawy</th>
                                <th class="hidden sm:table-cell py-2.5 px-4 text-right w-32">Szacowana cena</th>
                                <th class="py-2.5 px-3 sm:px-4 text-center sm:w-16">Usuń</th>
                            </tr>
                        </thead>
                        <tbody id="customItemsTableBody" class="divide-y divide-amber-100/80">
                            <!-- Dynamicznie dodawane wiersze -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Floating Bottom Bar (Pływające podsumowanie koszyka) -->
    <div id="floatingCart" class="fixed bottom-0 inset-x-0 bg-slate-900/95 backdrop-blur text-white py-3.5 px-4 sm:px-8 border-t border-slate-800 shadow-2xl z-40 transition-transform duration-300 translate-y-0">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4">
            <div class="flex items-center gap-4 sm:gap-6 text-sm">
                <div>
                    <span class="text-xs text-slate-400 block uppercase font-bold tracking-wider">Wybrane pozycje:</span>
                    <span id="cartItemsCount" class="font-extrabold text-emerald-400 text-lg">0 pozycji</span>
                </div>
                <div class="h-8 w-px bg-slate-700"></div>
                <div>
                    <span class="text-xs text-slate-400 block uppercase font-bold tracking-wider">Łączna wartość:</span>
                    <span id="cartTotalSum" class="font-black text-white text-xl whitespace-nowrap">0,00&nbsp;zł</span>
                </div>
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                <button type="button" id="btnOpenReview" disabled
                    class="w-full sm:w-auto min-h-[44px] px-8 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 disabled:opacity-40 disabled:cursor-not-allowed font-extrabold text-white text-sm shadow-lg shadow-emerald-500/20 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900 transition flex items-center justify-center gap-2">
                    <span>Złóż zamówienie</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Review & Checkout Modal -->
    <div id="checkoutModal" role="dialog" aria-modal="true" aria-label="Podsumowanie zamówienia" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-slate-100 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            <!-- Header -->
            <div class="px-6 py-5 bg-gradient-to-r from-emerald-600 to-teal-700 text-white flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-black tracking-tight">Podsumowanie zamówienia B2B</h3>
                    <p class="text-xs text-emerald-100 mt-0.5">Sprawdź specyfikację przed wysłaniem</p>
                </div>
                <button type="button" id="btnCloseModal" aria-label="Zamknij" class="text-white/80 hover:text-white p-2 rounded-lg hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 overflow-y-auto space-y-5 flex-1">
                <!-- Client Details Snapshot -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 text-xs grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <span class="text-slate-500 font-semibold block">Odbiorca:</span>
                        <strong class="text-slate-900 text-sm"><?= htmlspecialchars($client['company_name'] ?? '') ?></strong>
                    </div>
                    <div>
                        <span class="text-slate-500 font-semibold block">Adres dostawy:</span>
                        <span class="text-slate-700"><?= htmlspecialchars($client['delivery_address'] ?? '') ?></span>
                    </div>
                    <div>
                        <span class="text-slate-500 font-semibold block">Telefon kontaktowy:</span>
                        <span class="text-slate-700"><?= htmlspecialchars($client['phone'] ?? '-') ?></span>
                    </div>
                    <div>
                        <span class="text-slate-500 font-semibold block">Data złożenia:</span>
                        <span class="text-slate-700"><?= date('d.m.Y H:i') ?></span>
                    </div>
                </div>

                <!-- Delivery schedule & cutoff selector -->
                <?php $sched = $view['deliverySchedule'] ?? null; ?>
                <?php if (!empty($sched['options'])): ?>
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Termin realizacji dostawy:</h4>
                    <?php if (!empty($sched['is_cutoff_passed'])): ?>
                        <div class="mb-3 p-3 rounded-xl bg-amber-50 border border-amber-200 flex items-start gap-2.5 text-xs text-amber-900 shadow-sm">
                            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div>
                                <span class="font-bold">Zamówienia na jutrzejszy poranek zostały zamknięte o <?= htmlspecialchars($sched['cutoff_time']) ?>.</span>
                                <span class="block text-amber-700 mt-0.5">Najbliższy dostępny termin realizacji zamówienia na rampie to <strong><?= htmlspecialchars($sched['options'][0]['short_label'] ?? '') ?></strong>.</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="deliveryDateOptions">
                        <?php foreach ($sched['options'] as $idx => $opt): ?>
                            <label class="delivery-date-card relative flex flex-col p-3 rounded-xl border-2 cursor-pointer transition select-none <?= $opt['is_default'] ? 'border-emerald-600 bg-emerald-50/60 shadow-sm' : 'border-slate-200 hover:border-slate-300 bg-white' ?>">
                                <input type="radio" name="modal_delivery_date" value="<?= htmlspecialchars($opt['date']) ?>" <?= $opt['is_default'] ? 'checked' : '' ?> class="sr-only input-delivery-date">
                                <span class="text-xs font-black <?= $opt['is_default'] ? 'text-emerald-900' : 'text-slate-800' ?>"><?= htmlspecialchars($opt['short_label']) ?></span>
                                <span class="text-[11px] <?= $opt['is_default'] ? 'text-emerald-700 font-semibold' : 'text-slate-500' ?> mt-0.5"><?= htmlspecialchars($opt['sub_label']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Ostrzeżenie w modalu gdy występują pozycje spoza cennika -->
                <div id="modalOffCatalogNotice" class="hidden p-3.5 rounded-2xl bg-amber-50 border border-amber-200/90 flex items-start gap-2.5 text-xs text-amber-900 shadow-sm">
                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <span class="font-bold">Uwaga: Zamówienie produktów spoza cennika nie gwarantuje ich dostawy.</span>
                        <span class="block text-amber-800 mt-0.5">Dostępność towaru oraz ostateczną cenę hurtownia potwierdzi podczas kompletacji na rampie.</span>
                    </div>
                </div>

                <!-- Products breakdown list -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Specyfikacja pozycji:</h4>
                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-100 text-slate-600 font-bold border-b border-slate-200">
                                <tr>
                                    <th class="py-2.5 px-3">Towar</th>
                                    <th class="py-2.5 px-3 text-center">Ilość</th>
                                    <th class="py-2.5 px-3">Opakowania</th>
                                    <th class="py-2.5 px-3 text-right">Wartość</th>
                                </tr>
                            </thead>
                            <tbody id="modalItemsBody" class="divide-y divide-slate-100">
                                <!-- Dynamic items -->
                            </tbody>
                            <tfoot class="bg-slate-50 font-black text-slate-900 border-t border-slate-200">
                                <tr>
                                    <td colspan="3" class="py-3 px-3 text-right text-xs uppercase">Łącznie do zapłaty:</td>
                                    <td id="modalTotalSum" class="py-3 px-3 text-right text-emerald-600 text-sm font-extrabold whitespace-nowrap">0,00&nbsp;zł</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Delivery notes -->
                <div>
                    <label for="orderNotes" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">
                        Uwagi dla hurtowni i kierowcy (opcjonalnie):
                    </label>
                    <textarea id="orderNotes" rows="2"
                        class="w-full p-3 rounded-xl border border-slate-200 text-base sm:text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
                        placeholder="np. prosimy o dostawę przed 6:30 rano; skrzynki na wymianę"></textarea>
                </div>
            </div>

            <!-- Komunikat błędu wysyłki zamówienia (zamiast alert()) -->
            <div id="checkoutError" role="alert" class="hidden mx-6 mb-3 p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700"></div>

            <!-- Footer -->
            <div class="px-4 sm:px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2 sm:gap-3">
                <button type="button" id="btnCancelModal" class="min-h-[44px] sm:min-h-0 px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-600 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 transition">
                    Wróć do edycji
                </button>
                <button type="button" id="btnConfirmOrder"
                    class="min-h-[44px] sm:min-h-0 px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow-lg shadow-emerald-600/30 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 transition flex items-center justify-center gap-2">
                    <span id="confirmBtnText">Zatwierdź i wyślij zamówienie</span>
                    <svg id="confirmSpinner" class="w-4 h-4 animate-spin hidden" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" role="dialog" aria-modal="true" aria-labelledby="successModalTitle" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 text-center shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95 duration-200">
            <div class="w-16 h-16 rounded-3xl bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 id="successModalTitle" class="text-xl font-black text-slate-900">Zamówienie przyjęte!</h3>
            <p class="text-xs text-slate-500 mt-1">Twoje zamówienie zostało przekazane do działu kompletacji hurtowni.</p>

            <div class="my-6 p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <div class="text-[11px] uppercase font-bold text-slate-500">Numer zamówienia</div>
                <div id="successOrderNumber" class="text-lg font-black text-emerald-600 mt-0.5">ZAM/B2B/...</div>
            </div>

            <div class="space-y-3">
                <a id="downloadPackingSheetBtn" href="#" target="_blank" rel="noopener"
                    class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow-md shadow-emerald-600/20 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Pobierz specyfikację (Excel .xlsx)
                </a>
                <button type="button" id="btnNewOrder" class="w-full min-h-[44px] py-2.5 px-4 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 transition">
                    Złóż kolejne zamówienie
                </button>
                <a href="<?= $base ?>b2b/logout" id="btnLogoutAfterOrder"
                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border border-rose-200 bg-rose-50/50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition">
                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    To wszystko, wyloguj
                </a>
            </div>
        </div>
    </div>

    <!-- Powiadomienia (toast) — zamiast alert() -->
    <div id="toastRegion" role="status" aria-live="polite" class="fixed z-[60] inset-x-4 bottom-44 sm:bottom-28 sm:left-auto sm:right-6 sm:w-96 flex flex-col gap-2 pointer-events-none"></div>

    <!-- CSRF & JavaScript Controller -->
    <script>
        const CSRF_TOKEN = '<?= $csrfToken ?>';
        const BASE_URL   = '<?= $base ?>';
        const LOGIN_URL  = BASE_URL + 'b2b/login';
        // Szkic koszyka w localStorage — klucz osobny dla każdego klienta (i dla podglądu admina)
        const CART_DRAFT_KEY = 'b2b_cart_draft_' + '<?= !empty($view['isAdmin']) ? 'admin_' : '' ?>' + '<?= (int) ($client['id'] ?? 0) ?>';
        const CART_DRAFT_MAX_AGE_MS = 7 * 24 * 60 * 60 * 1000;

        // Kwota do WYŚWIETLENIA w formacie polskim (1 234,50 zł). Nie używać do wartości wysyłanych na serwer.
        const formatPLN = (v) => new Intl.NumberFormat('pl-PL', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(Number(v) || 0) + ' zł';

        // Toast: krótki komunikat w rogu ekranu. opts: { type: 'info'|'success'|'error', linkHref, linkText, duration }
        function showToast(message, opts) {
            const o = opts || {};
            const region = document.getElementById('toastRegion');
            if (!region) return;
            const colors = {
                success: 'bg-emerald-700 text-white',
                error: 'bg-rose-700 text-white',
                info: 'bg-slate-800 text-white'
            };
            const toast = document.createElement('div');
            toast.className = 'pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-semibold ' + (colors[o.type] || colors.info);
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
            const duration = (o.duration === undefined) ? (o.linkHref ? 0 : 5000) : o.duration;
            if (duration > 0) setTimeout(() => toast.remove(), duration);
        }

        // Odpowiedź fetch → JSON, z rozpoznaniem wygasłej sesji (401/403/419, przekierowanie na logowanie, HTML zamiast JSON).
        function sessionExpiredError() {
            const err = new Error('Sesja wygasła — zaloguj się ponownie.');
            err.sessionExpired = true;
            return err;
        }
        async function readJsonResponse(res) {
            const contentType = (res.headers.get('content-type') || '').toLowerCase();
            if (res.status === 401 || res.status === 403 || res.status === 419 || res.redirected) {
                throw sessionExpiredError();
            }
            if (contentType.indexOf('application/json') === -1) {
                if (res.ok) throw sessionExpiredError();
                const httpErr = new Error('Błąd serwera (HTTP ' + res.status + '). Spróbuj ponownie za chwilę.');
                httpErr.httpError = true;
                throw httpErr;
            }
            return res.json();
        }
        function showSessionExpired() {
            showToast('Sesja wygasła — zaloguj się ponownie. Twój koszyk zostanie przywrócony po zalogowaniu.', {
                type: 'error',
                linkHref: LOGIN_URL,
                linkText: 'Przejdź do logowania'
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const rows = Array.from(document.querySelectorAll('.product-row'));
            const searchInput = document.getElementById('searchInput');
            const categoryPills = document.querySelectorAll('.category-pill');
            const filterOrderedOnly = document.getElementById('filterOrderedOnly');
            const clearQuantitiesBtn = document.getElementById('clearQuantitiesBtn');

            const cartItemsCount = document.getElementById('cartItemsCount');
            const cartTotalSum = document.getElementById('cartTotalSum');
            const orderedCountLabel = document.getElementById('orderedCountLabel');
            const btnOpenReview = document.getElementById('btnOpenReview');

            // Modal elements
            const checkoutModal = document.getElementById('checkoutModal');
            const btnCloseModal = document.getElementById('btnCloseModal');
            const btnCancelModal = document.getElementById('btnCancelModal');
            const btnConfirmOrder = document.getElementById('btnConfirmOrder');
            const modalItemsBody = document.getElementById('modalItemsBody');
            const modalTotalSum = document.getElementById('modalTotalSum');
            const orderNotes = document.getElementById('orderNotes');
            const confirmBtnText = document.getElementById('confirmBtnText');
            const confirmSpinner = document.getElementById('confirmSpinner');

            // Success Modal
            const successModal = document.getElementById('successModal');
            const successOrderNumber = document.getElementById('successOrderNumber');
            const downloadPackingSheetBtn = document.getElementById('downloadPackingSheetBtn');
            const btnNewOrder = document.getElementById('btnNewOrder');

            // Elementy formularza i tabeli pozycji spoza cennika
            const customProductForm = document.getElementById('customProductForm');
            const customProdName = document.getElementById('customProdName');
            const customProdQty = document.getElementById('customProdQty');
            const customProdUnit = document.getElementById('customProdUnit');
            const customProductsContainer = document.getElementById('customProductsContainer');
            const customItemsTableBody = document.getElementById('customItemsTableBody');
            const customItemsCount = document.getElementById('customItemsCount');
            const modalOffCatalogNotice = document.getElementById('modalOffCatalogNotice');
            const customProdNameError = document.getElementById('customProdNameError');
            const customProdQtyError = document.getElementById('customProdQtyError');
            const checkoutError = document.getElementById('checkoutError');
            const clearConfirmBox = document.getElementById('clearConfirmBox');
            const clearConfirmYes = document.getElementById('clearConfirmYes');
            const clearConfirmNo = document.getElementById('clearConfirmNo');
            let customItems = [];
            let isSubmittingOrder = false;
            let orderCompleted = false;
            let skipUnloadWarning = false;
            let lastFocusBeforeModal = null;

            function escapeHtml(str) {
                if (!str) return '';
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }

            let currentCategory = 'ALL';

            // Odmiana jednostek i opakowań przez przypadki w JS
            function inflectPolishJs(number, unit) {
                if (!unit) return '';
                const u = unit.trim().toLowerCase();
                const formsMap = {
                    'klatka': ['klatka', 'klatki', 'klatek'],
                    'klatki': ['klatka', 'klatki', 'klatek'],
                    'klatek': ['klatka', 'klatki', 'klatek'],
                    'worek': ['worek', 'worki', 'worków'],
                    'worki': ['worek', 'worki', 'worków'],
                    'worków': ['worek', 'worki', 'worków'],
                    'skrzynka': ['skrzynka', 'skrzynki', 'skrzynek'],
                    'skrzynki': ['skrzynka', 'skrzynki', 'skrzynek'],
                    'skrzynek': ['skrzynka', 'skrzynki', 'skrzynek'],
                    'karton': ['karton', 'kartony', 'kartonów'],
                    'kartony': ['karton', 'kartony', 'kartonów'],
                    'kartonów': ['karton', 'kartony', 'kartonów'],
                    'pudełko': ['pudełko', 'pudełka', 'pudełek'],
                    'pudełka': ['pudełko', 'pudełka', 'pudełek'],
                    'pudełek': ['pudełko', 'pudełka', 'pudełek'],
                    'pęczek': ['pęczek', 'pęczki', 'pęczków'],
                    'pęczki': ['pęczek', 'pęczki', 'pęczków'],
                    'pęczków': ['pęczek', 'pęczki', 'pęczków'],
                    'zgrzewka': ['zgrzewka', 'zgrzewki', 'zgrzewek'],
                    'zgrzewki': ['zgrzewka', 'zgrzewki', 'zgrzewek'],
                    'zgrzewek': ['zgrzewka', 'zgrzewki', 'zgrzewek'],
                    'paleta': ['paleta', 'palety', 'palet'],
                    'palety': ['paleta', 'palety', 'palet'],
                    'palet': ['paleta', 'palety', 'palet'],
                    'paczka': ['paczka', 'paczki', 'paczek'],
                    'paczki': ['paczka', 'paczki', 'paczek'],
                    'paczek': ['paczka', 'paczki', 'paczek'],
                    'wytłaczanka': ['wytłaczanka', 'wytłaczanki', 'wytłaczanek'],
                    'wytłaczanki': ['wytłaczanka', 'wytłaczanki', 'wytłaczanek'],
                    'wytłaczanek': ['wytłaczanka', 'wytłaczanki', 'wytłaczanek'],
                    'koszyk': ['koszyk', 'koszyki', 'koszyków'],
                    'koszyki': ['koszyk', 'koszyki', 'koszyków'],
                    'koszyków': ['koszyk', 'koszyki', 'koszyków'],
                    'wiązka': ['wiązka', 'wiązki', 'wiązek'],
                    'wiązki': ['wiązka', 'wiązki', 'wiązek'],
                    'wiązek': ['wiązka', 'wiązki', 'wiązek'],
                    'opakowanie': ['opakowanie', 'opakowania', 'opakowań'],
                    'opakowania': ['opakowanie', 'opakowania', 'opakowań'],
                    'opakowań': ['opakowanie', 'opakowania', 'opakowań'],
                    'sztuka': ['sztuka', 'sztuki', 'sztuk'],
                    'sztuki': ['sztuka', 'sztuki', 'sztuk'],
                    'sztuk': ['sztuka', 'sztuki', 'sztuk'],
                    'szt': ['szt.', 'szt.', 'szt.'],
                    'szt.': ['szt.', 'szt.', 'szt.'],
                    'op': ['op.', 'op.', 'op.'],
                    'op.': ['op.', 'op.', 'op.'],
                    'kg': ['kg', 'kg', 'kg'],
                    'g': ['g', 'g', 'g'],
                    'l': ['l', 'l', 'l'],
                    'litr': ['litr', 'litry', 'litrów'],
                    'litry': ['litr', 'litry', 'litrów'],
                    'litrów': ['litr', 'litry', 'litrów']
                };

                let forms = formsMap[u];
                if (!forms) {
                    if (u.endsWith('.') || u.length <= 3) return unit;
                    if (u.endsWith('ka')) {
                        const stem = u.slice(0, -2);
                        forms = [u, stem + 'ki', stem + 'ek'];
                    } else {
                        return unit;
                    }
                }

                const isInt = Math.floor(number) === number;
                if (isInt) {
                    const abs = Math.abs(number);
                    if (abs === 1) return forms[0];
                    const mod10 = abs % 10;
                    const mod100 = abs % 100;
                    if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) {
                        return forms[1];
                    }
                    return forms[2];
                }
                return forms[1];
            }

            // Rozbicie logistyczne w JS z poprawną gramatyką
            function computePackageSummary(qty, pkgSize, pkgUnit, unit) {
                if (pkgSize <= 1.0) {
                    return qty > 0 ? `${qty} ${inflectPolishJs(qty, unit)}` : '-';
                }
                const fullBoxes = Math.floor(qty / pkgSize);
                const remainder = Math.round((qty % pkgSize) * 100) / 100;

                const boxInflected = inflectPolishJs(fullBoxes, pkgUnit);
                const unitInflected = inflectPolishJs(remainder, unit);

                if (fullBoxes > 0 && remainder > 0) {
                    return `${fullBoxes} ${boxInflected} + ${remainder} ${unitInflected}`;
                } else if (fullBoxes > 0) {
                    return `${fullBoxes} ${boxInflected}`;
                } else if (remainder > 0) {
                    return `${remainder} ${unitInflected}`;
                }
                return '-';
            }

            // Aktualizacja wiersza
            function updateRow(row) {
                const input = row.querySelector('.input-qty');
                const price = parseFloat(row.dataset.price) || 0;
                const pkgSize = parseFloat(row.dataset.pkgSize) || 1.0;
                const pkgUnit = row.dataset.pkgUnit || 'op.';
                const unit = row.dataset.unit || 'kg';

                let qty = parseFloat(input.value) || 0;
                if (qty < 0) { qty = 0; input.value = 0; }

                const total = Math.round(qty * price * 100) / 100;

                // Labels
                const summaryLabel = row.querySelector('.package-summary-label');
                const totalLabel = row.querySelector('.item-total-label');
                const optimizerHint = row.querySelector('.optimizer-hint');

                summaryLabel.textContent = computePackageSummary(qty, pkgSize, pkgUnit, unit);
                totalLabel.textContent = formatPLN(total);

                // Asystent zaokrąglenia (Box Optimizer - min. 65% napełnienia opakowania)
                if (pkgSize > 1.0 && qty > 0) {
                    const remainder = Math.round((qty % pkgSize) * 1000) / 1000;
                    const fillRatio = remainder / pkgSize;
                    if (remainder > 0.001 && fillRatio >= 0.6499) {
                        const nextFullQty = Math.ceil(qty / pkgSize) * pkgSize;
                        const btnRound = optimizerHint.querySelector('.btn-round-up');
                        const ceilBoxes = Math.ceil(qty / pkgSize);
                        btnRound.textContent = `Zaokrąglij do ${nextFullQty} ${unit} (${ceilBoxes} ${inflectPolishJs(ceilBoxes, pkgUnit)})`;
                        btnRound.dataset.targetQty = nextFullQty;
                        optimizerHint.classList.remove('hidden');
                    } else {
                        optimizerHint.classList.add('hidden');
                    }
                } else {
                    optimizerHint.classList.add('hidden');
                }

                updateCartTotals();
            }

            // Pomocnik parsowania ilości: obsługuje liczby całkowite, ułamki dziesiętne z kropką i przecinkiem (np. 4,5),
            // ułamki zwykłe (np. 1/2), a także opcjonalną jednostkę wpisaną przez klienta w polu ilości (np. "4,5 kg").
            function parseQuantityInput(val) {
                if (typeof val === 'number') {
                    return (Number.isFinite(val) && val > 0 && val < 100000) ? Math.round(val * 1000) / 1000 : null;
                }
                if (!val || typeof val !== 'string') return null;
                let s = val.trim().replace(/\s+/g, ' ').replace(',', '.');
                // Opcjonalne usunięcie dopisku jednostki na końcu, np. "4,5 kg" -> "4.5"
                s = s.replace(/\s*(kg|szt\.?|op\.?|pęczek|skrzynka|karton|worek|g|l|litr)\s*$/i, '').trim();
                // Obsługa ułamków zwykłych, np. "1/2" -> 0.5
                if (/^\d+\/\d+$/.test(s)) {
                    const parts = s.split('/');
                    const den = parseFloat(parts[1]);
                    if (den > 0) {
                        const res = parseFloat(parts[0]) / den;
                        return (Number.isFinite(res) && res > 0 && res < 100000) ? Math.round(res * 1000) / 1000 : null;
                    }
                    return null;
                }
                if (!/^\d+(\.\d+)?$/.test(s) && !/^\.\d+$/.test(s)) {
                    return null;
                }
                const num = parseFloat(s);
                if (!Number.isFinite(num) || num <= 0 || num >= 100000) {
                    return null;
                }
                return Math.round(num * 1000) / 1000;
            }

            // Formatowanie ilości do wyświetlenia z polskim przecinkiem (np. 4,5 kg)
            function formatQty(val) {
                if (typeof val !== 'number' || !Number.isFinite(val)) return String(val || '');
                return String(val).replace('.', ',');
            }

            // Renderowanie tabeli pozycji spoza cennika (lista zamówienia)
            function renderCustomItemsTable() {
                if (!customProductsContainer || !customItemsTableBody) return;

                if (customItems.length === 0) {
                    customProductsContainer.classList.add('hidden');
                    customItemsTableBody.innerHTML = '';
                    if (customItemsCount) customItemsCount.textContent = '0';
                    return;
                }

                customProductsContainer.classList.remove('hidden');
                if (customItemsCount) customItemsCount.textContent = customItems.length;
                customItemsTableBody.innerHTML = '';

                customItems.forEach((c, idx) => {
                    const tr = document.createElement('tr');
                    tr.className = 'bg-amber-50/40 hover:bg-amber-50/70 transition-colors group';

                    const tdLp = document.createElement('td');
                    tdLp.className = 'hidden sm:table-cell py-3 px-4 text-center font-bold text-amber-700 text-xs';
                    tdLp.textContent = idx + 1;

                    const tdName = document.createElement('td');
                    tdName.className = 'py-3 px-3 sm:px-4 font-bold text-slate-900';
                    tdName.innerHTML = `
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm">${escapeHtml(c.name)}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">Spoza cennika</span>
                        </div>
                    `;

                    const tdQty = document.createElement('td');
                    tdQty.className = 'py-3 px-3 sm:px-4 text-center font-black text-amber-900 text-xs whitespace-nowrap';
                    tdQty.textContent = `${formatQty(c.qty)} ${c.unit}`;

                    const tdStatus = document.createElement('td');
                    tdStatus.className = 'hidden sm:table-cell py-3 px-4 text-xs font-semibold text-amber-800';
                    tdStatus.innerHTML = `
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            <span>Do potwierdzenia na rampie</span>
                        </span>
                    `;

                    const tdPrice = document.createElement('td');
                    tdPrice.className = 'hidden sm:table-cell py-3 px-4 text-right text-xs text-slate-500 italic font-medium';
                    tdPrice.textContent = 'Do ustalenia (0 zł)';

                    const tdAction = document.createElement('td');
                    tdAction.className = 'py-2 sm:py-3 px-2 sm:px-4 text-center';
                    const btnRemove = document.createElement('button');
                    btnRemove.type = 'button';
                    btnRemove.className = 'p-3 sm:p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 transition active:scale-95 cursor-pointer';
                    btnRemove.title = 'Usuń tę pozycję z listy';
                    btnRemove.setAttribute('aria-label', 'Usuń z listy: ' + c.name);
                    btnRemove.innerHTML = `
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    `;
                    btnRemove.addEventListener('click', () => {
                        customItems.splice(idx, 1);
                        renderCustomItemsTable();
                        updateCartTotals();
                    });
                    tdAction.appendChild(btnRemove);

                    tr.append(tdLp, tdName, tdQty, tdStatus, tdPrice, tdAction);
                    customItemsTableBody.appendChild(tr);
                });
            }

            // Walidacja pól formularza: komunikat pod polem + aria-invalid / aria-describedby
            function setFieldError(input, errorEl, message) {
                if (!input || !errorEl) return;
                if (message) {
                    errorEl.textContent = message;
                    errorEl.classList.remove('hidden');
                    input.setAttribute('aria-invalid', 'true');
                    input.setAttribute('aria-describedby', errorEl.id);
                } else {
                    errorEl.textContent = '';
                    errorEl.classList.add('hidden');
                    input.removeAttribute('aria-invalid');
                    input.removeAttribute('aria-describedby');
                }
            }
            if (customProdName) customProdName.addEventListener('input', () => setFieldError(customProdName, customProdNameError, ''));
            if (customProdQty) customProdQty.addEventListener('input', () => setFieldError(customProdQty, customProdQtyError, ''));

            // Obsługa formularza dodawania produktu spoza cennika
            if (customProductForm) {
                customProductForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const name = customProdName ? customProdName.value.trim() : '';
                    const rawQty = customProdQty ? customProdQty.value : '';

                    // Jeśli użytkownik wpisał jednostkę bezpośrednio w polu ilości (np. "4,5 kg"), zsynchronizuj selektor jednostki
                    const unitMatch = String(rawQty).trim().match(/\s*(kg|szt\.?|op\.?|pęczek|skrzynka|karton|worek)\s*$/i);
                    if (unitMatch && customProdUnit) {
                        const rawU = unitMatch[1].toLowerCase();
                        let normU = rawU;
                        if (normU.startsWith('szt')) normU = 'szt.';
                        else if (normU.startsWith('op')) normU = 'op.';
                        for (let opt of customProdUnit.options) {
                            if (opt.value === normU) {
                                customProdUnit.value = normU;
                                break;
                            }
                        }
                    }

                    const qty = parseQuantityInput(rawQty);
                    const unit = (customProdUnit ? customProdUnit.value.trim() : 'kg') || 'kg';

                    const nameError = name ? '' : 'Podaj nazwę produktu spoza cennika.';
                    const qtyError = (qty === null) ? 'Podaj prawidłową ilość (np. 5 lub 4,5).' : '';
                    setFieldError(customProdName, customProdNameError, nameError);
                    setFieldError(customProdQty, customProdQtyError, qtyError);

                    if (nameError) {
                        if (customProdName) customProdName.focus();
                        return;
                    }

                    if (qtyError) {
                        if (customProdQty) customProdQty.focus();
                        return;
                    }

                    customItems.push({
                        name: name,
                        qty: qty,
                        unit: unit
                    });

                    if (customProdName) customProdName.value = '';
                    if (customProdQty) customProdQty.value = '';
                    renderCustomItemsTable();
                    updateCartTotals();

                    if (customProductsContainer) {
                        customProductsContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                });
            }

            // Suma koszyka uwzględniająca pozycje z cennika oraz spoza cennika
            function updateCartTotals() {
                let totalAmount = 0.0;
                let orderedCount = 0;

                rows.forEach(row => {
                    const input = row.querySelector('.input-qty');
                    const qty = parseFloat(input.value) || 0;
                    if (qty > 0) {
                        const price = parseFloat(row.dataset.price) || 0;
                        totalAmount += (qty * price);
                        orderedCount++;
                    }
                });

                const totalItemsCount = orderedCount + customItems.length;

                if (customItems.length > 0 && orderedCount > 0) {
                    cartItemsCount.textContent = `${totalItemsCount} (${orderedCount} z cennika + ${customItems.length} spoza)`;
                } else if (customItems.length > 0 && orderedCount === 0) {
                    cartItemsCount.textContent = `${customItems.length} ${customItems.length === 1 ? 'pozycja spoza cennika' : 'pozycje spoza cennika'}`;
                } else {
                    cartItemsCount.textContent = `${orderedCount} ${orderedCount === 1 ? 'pozycja' : ([2, 3, 4].includes(orderedCount % 10) && ![12, 13, 14].includes(orderedCount % 100) ? 'pozycje' : 'pozycji')}`;
                }

                orderedCountLabel.textContent = orderedCount;
                cartTotalSum.textContent = formatPLN(totalAmount);

                btnOpenReview.disabled = (totalItemsCount === 0);
                scheduleDraftSave();
            }

            // ---- Szkic koszyka w localStorage (odświeżenie / cofnięcie strony nie gubi listy) ----
            let draftSaveTimer = null;
            let draftRestoring = false;

            function cartHasItems() {
                if (customItems.length > 0) return true;
                return rows.some(r => (parseFloat(r.querySelector('.input-qty').value) || 0) > 0);
            }

            function saveDraftNow() {
                if (draftSaveTimer) { clearTimeout(draftSaveTimer); draftSaveTimer = null; }
                if (draftRestoring || orderCompleted) return;
                try {
                    if (!cartHasItems()) {
                        window.localStorage.removeItem(CART_DRAFT_KEY);
                        return;
                    }
                    const qty = {};
                    rows.forEach(r => {
                        const v = parseFloat(r.querySelector('.input-qty').value) || 0;
                        if (v > 0) qty[r.dataset.id] = v;
                    });
                    const draft = {
                        v: 1,
                        savedAt: Date.now(),
                        qty: qty,
                        custom: customItems.map(c => ({ name: c.name, qty: c.qty, unit: c.unit })),
                        notes: orderNotes ? orderNotes.value : ''
                    };
                    window.localStorage.setItem(CART_DRAFT_KEY, JSON.stringify(draft));
                } catch (e) {
                    // Brak dostępu do localStorage (tryb prywatny, blokada) — koszyk działa bez szkicu
                }
            }

            function scheduleDraftSave() {
                if (draftRestoring) return;
                if (draftSaveTimer) clearTimeout(draftSaveTimer);
                draftSaveTimer = setTimeout(saveDraftNow, 300);
            }

            function clearDraft() {
                if (draftSaveTimer) { clearTimeout(draftSaveTimer); draftSaveTimer = null; }
                try {
                    window.localStorage.removeItem(CART_DRAFT_KEY);
                } catch (e) {
                    // ignorujemy — brak dostępu do localStorage
                }
            }

            function restoreDraft() {
                let draft = null;
                try {
                    const raw = window.localStorage.getItem(CART_DRAFT_KEY);
                    if (!raw) return;
                    draft = JSON.parse(raw);
                } catch (e) {
                    return;
                }
                if (!draft || typeof draft !== 'object') return;
                if (typeof draft.savedAt === 'number' && (Date.now() - draft.savedAt) > CART_DRAFT_MAX_AGE_MS) {
                    clearDraft();
                    return;
                }

                const allowedUnits = customProdUnit ? Array.from(customProdUnit.options).map(o => o.value) : ['kg'];
                let restoredCount = 0;
                draftRestoring = true;
                try {
                    const qtyMap = (draft.qty && typeof draft.qty === 'object') ? draft.qty : {};
                    rows.forEach(r => {
                        const v = Number(qtyMap[r.dataset.id]);
                        if (Number.isFinite(v) && v > 0 && v < 100000) {
                            r.querySelector('.input-qty').value = String(v);
                            updateRow(r);
                            restoredCount++;
                        }
                    });

                    if (Array.isArray(draft.custom)) {
                        draft.custom.forEach(c => {
                            if (!c || typeof c.name !== 'string') return;
                            const name = c.name.trim().slice(0, 200);
                            const q = parseQuantityInput(c.qty);
                            if (!name || q === null) return;
                            const unit = allowedUnits.indexOf(c.unit) !== -1 ? c.unit : 'kg';
                            customItems.push({ name: name, qty: q, unit: unit });
                            restoredCount++;
                        });
                        renderCustomItemsTable();
                    }

                    if (orderNotes && typeof draft.notes === 'string' && !orderNotes.value) {
                        orderNotes.value = draft.notes.slice(0, 2000);
                    }
                } finally {
                    draftRestoring = false;
                }

                updateCartTotals();
                filterRows();
                if (restoredCount > 0) {
                    showToast('Przywrócono niezapisaną listę zamówienia (' + restoredCount + ' poz.).', { type: 'info' });
                } else {
                    clearDraft();
                }
            }

            // Filtrowanie widoczności wierszy z zachowaniem naprzemiennego tła
            function filterRows() {
                const searchVal = searchInput.value.toLowerCase().trim();
                const onlyOrdered = filterOrderedOnly.checked;
                let visibleIdx = 0;

                rows.forEach(row => {
                    const name = row.dataset.name.toLowerCase();
                    const category = row.dataset.category;
                    const input = row.querySelector('.input-qty');
                    const qty = parseFloat(input.value) || 0;

                    const matchesSearch = name.includes(searchVal);
                    const matchesCategory = (currentCategory === 'ALL' || category === currentCategory);
                    const matchesOrdered = !onlyOrdered || (qty > 0);

                    if (matchesSearch && matchesCategory && matchesOrdered) {
                        row.classList.remove('hidden', 'prod-row-even', 'prod-row-odd');
                        if (visibleIdx % 2 === 1) {
                            row.classList.add('prod-row-even');
                        } else {
                            row.classList.add('prod-row-odd');
                        }
                        visibleIdx++;
                    } else {
                        row.classList.add('hidden');
                    }
                });
            }

            // Event Listeners dla każdego wiersza
            rows.forEach((row, index) => {
                const input = row.querySelector('.input-qty');
                const btnDown = row.querySelector('.btn-step-down');
                const btnUp = row.querySelector('.btn-step-up');
                const btnRound = row.querySelector('.btn-round-up');
                const unit = row.dataset.unit || 'kg';
                // Krok przycisków +/- zgodny z atrybutem step pola: 1 dla 'szt.', 0.5 dla pozostałych jednostek
                const step = (unit === 'szt.' ? 1 : 0.5);
                const roundQty = (v) => Math.round(v * 1000) / 1000;

                input.addEventListener('input', () => updateRow(row));

                // Klawiatura: Enter przechodzi do następnego wiersza
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const nextRow = rows[index + 1];
                        if (nextRow) {
                            const nextInput = nextRow.querySelector('.input-qty');
                            nextInput.focus();
                            nextInput.select();
                        }
                    }
                });

                btnDown.addEventListener('click', () => {
                    let val = roundQty((parseFloat(input.value) || 0) - step);
                    if (val < 0) val = 0;
                    input.value = val === 0 ? '' : val;
                    updateRow(row);
                });

                btnUp.addEventListener('click', () => {
                    let val = roundQty((parseFloat(input.value) || 0) + step);
                    input.value = val;
                    updateRow(row);
                });

                if (btnRound) {
                    btnRound.addEventListener('click', () => {
                        const target = parseFloat(btnRound.dataset.targetQty);
                        if (target) {
                            input.value = target;
                            updateRow(row);
                        }
                    });
                }
            });

            // Wyszukiwarka i filtry
            searchInput.addEventListener('input', filterRows);
            filterOrderedOnly.addEventListener('change', filterRows);

            categoryPills.forEach(pill => {
                pill.addEventListener('click', () => {
                    categoryPills.forEach(p => {
                        p.classList.remove('active', 'bg-emerald-600', 'text-white');
                        p.classList.add('bg-slate-100', 'text-slate-600');
                    });
                    pill.classList.add('active', 'bg-emerald-600', 'text-white');
                    pill.classList.remove('bg-slate-100', 'text-slate-600');

                    currentCategory = pill.dataset.category;
                    filterRows();
                });
            });

            // Czyszczenie koszyka z potwierdzeniem w miejscu (zamiast confirm())
            function hideClearConfirm(restoreFocus) {
                clearConfirmBox.classList.add('hidden');
                clearQuantitiesBtn.classList.remove('hidden');
                if (restoreFocus) clearQuantitiesBtn.focus();
            }

            clearQuantitiesBtn.addEventListener('click', () => {
                if (!cartHasItems()) {
                    showToast('Koszyk jest już pusty.', { type: 'info', duration: 2500 });
                    return;
                }
                clearQuantitiesBtn.classList.add('hidden');
                clearConfirmBox.classList.remove('hidden');
                clearConfirmNo.focus();
            });

            clearConfirmNo.addEventListener('click', () => hideClearConfirm(true));
            clearConfirmBox.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') hideClearConfirm(true);
            });

            clearConfirmYes.addEventListener('click', () => {
                rows.forEach(row => {
                    row.querySelector('.input-qty').value = '';
                    updateRow(row);
                });
                customItems = [];
                if (orderNotes) orderNotes.value = '';
                renderCustomItemsTable();
                updateCartTotals();
                filterRows();
                clearDraft();
                hideClearConfirm(true);
                showToast('Koszyk został wyczyszczony.', { type: 'success', duration: 3000 });
            });

            // Obsługa Modala podsumowania
            function getOrderedItems() {
                const items = [];
                rows.forEach(row => {
                    const input = row.querySelector('.input-qty');
                    const qty = parseFloat(input.value) || 0;
                    if (qty > 0) {
                        items.push({
                            product_id: parseInt(row.dataset.id),
                            is_custom: 0,
                            product_name: row.dataset.name,
                            price: parseFloat(row.dataset.price),
                            quantity: qty,
                            unit: row.dataset.unit,
                            package_size: parseFloat(row.dataset.pkgSize),
                            package_unit: row.dataset.pkgUnit,
                            package_summary: computePackageSummary(qty, parseFloat(row.dataset.pkgSize), row.dataset.pkgUnit, row.dataset.unit),
                            item_total: Math.round(qty * parseFloat(row.dataset.price) * 100) / 100
                        });
                    }
                });

                customItems.forEach(c => {
                    items.push({
                        product_id: null,
                        is_custom: 1,
                        product_name: c.name,
                        price: 0.00,
                        quantity: c.qty,
                        unit: c.unit,
                        package_size: 1.0,
                        package_unit: c.unit,
                        package_summary: 'Produkt spoza cennika (do potwierdzenia)',
                        item_total: 0.00
                    });
                });

                return items;
            }

            btnOpenReview.addEventListener('click', () => {
                const items = getOrderedItems();
                if (items.length === 0) return;

                modalItemsBody.innerHTML = '';
                let total = 0;
                let hasCustomItems = false;

                items.forEach(it => {
                    total += it.item_total;
                    const tr = document.createElement('tr');

                    if (it.is_custom) {
                        hasCustomItems = true;
                        tr.className = 'bg-amber-50/40 hover:bg-amber-50/70 transition-colors';

                        const productName = document.createElement('td');
                        productName.className = 'py-2.5 px-3 font-semibold text-slate-900';
                        productName.innerHTML = `
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span>${escapeHtml(it.product_name)}</span>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">Spoza cennika</span>
                            </div>
                        `;

                        const quantity = document.createElement('td');
                        quantity.className = 'py-2.5 px-3 text-center font-bold text-amber-800';
                        quantity.textContent = `${formatQty(it.quantity)} ${it.unit}`;

                        const packageSummary = document.createElement('td');
                        packageSummary.className = 'py-2.5 px-3 text-amber-700 italic text-[11px]';
                        packageSummary.textContent = 'Do potwierdzenia na rampie';

                        const itemTotal = document.createElement('td');
                        itemTotal.className = 'py-2.5 px-3 text-right text-xs font-semibold text-slate-500 italic';
                        itemTotal.textContent = 'Do wyceny';

                        tr.append(productName, quantity, packageSummary, itemTotal);
                    } else {
                        tr.className = 'hover:bg-slate-50 transition-colors';

                        const productName = document.createElement('td');
                        productName.className = 'py-2.5 px-3 font-semibold text-slate-900';
                        productName.textContent = it.product_name;

                        const quantity = document.createElement('td');
                        quantity.className = 'py-2.5 px-3 text-center font-bold text-emerald-700';
                        quantity.textContent = `${formatQty(it.quantity)} ${it.unit}`;

                        const packageSummary = document.createElement('td');
                        packageSummary.className = 'py-2.5 px-3 text-slate-500';
                        packageSummary.textContent = it.package_summary;

                        const itemTotal = document.createElement('td');
                        itemTotal.className = 'py-2.5 px-3 text-right font-bold text-slate-800 whitespace-nowrap';
                        itemTotal.textContent = formatPLN(it.item_total);

                        tr.append(productName, quantity, packageSummary, itemTotal);
                    }

                    modalItemsBody.appendChild(tr);
                });

                if (modalOffCatalogNotice) {
                    if (hasCustomItems) {
                        modalOffCatalogNotice.classList.remove('hidden');
                    } else {
                        modalOffCatalogNotice.classList.add('hidden');
                    }
                }

                modalTotalSum.textContent = formatPLN(total);
                hideCheckoutError();
                lastFocusBeforeModal = document.activeElement;
                checkoutModal.classList.remove('hidden');
                btnCloseModal.focus();
            });

            function closeModal() {
                checkoutModal.classList.add('hidden');
                if (lastFocusBeforeModal && typeof lastFocusBeforeModal.focus === 'function') lastFocusBeforeModal.focus();
            }

            function hideCheckoutError() {
                if (!checkoutError) return;
                checkoutError.classList.add('hidden');
                checkoutError.replaceChildren();
            }

            function showCheckoutError(message, withLoginLink) {
                if (!checkoutError) return;
                checkoutError.replaceChildren();
                const text = document.createElement('span');
                text.textContent = message;
                checkoutError.appendChild(text);
                if (withLoginLink) {
                    const link = document.createElement('a');
                    link.href = LOGIN_URL;
                    link.className = 'ml-1 underline font-extrabold text-rose-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 rounded';
                    link.textContent = 'Zaloguj się';
                    checkoutError.appendChild(link);
                }
                checkoutError.classList.remove('hidden');
            }

            btnCloseModal.addEventListener('click', closeModal);
            btnCancelModal.addEventListener('click', closeModal);
            // Zamknięcie kliknięciem w tło i klawiszem Esc (nie w trakcie wysyłania zamówienia)
            checkoutModal.addEventListener('click', (e) => {
                if (e.target === checkoutModal && !btnConfirmOrder.disabled) closeModal();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !checkoutModal.classList.contains('hidden') && !btnConfirmOrder.disabled) closeModal();
            });
            // Przełączanie kafelków daty dostawy
            const deliveryCards = document.querySelectorAll('.delivery-date-card');
            deliveryCards.forEach(card => {
                card.addEventListener('click', () => {
                    deliveryCards.forEach(c => {
                        c.classList.remove('border-emerald-600', 'bg-emerald-50/60', 'shadow-sm');
                        c.classList.add('border-slate-200', 'bg-white');
                        const title = c.querySelector('span:first-of-type');
                        if (title) { title.classList.remove('text-emerald-900'); title.classList.add('text-slate-800'); }
                        const sub = c.querySelector('span:last-of-type');
                        if (sub) { sub.classList.remove('text-emerald-700', 'font-semibold'); sub.classList.add('text-slate-500'); }
                    });
                    card.classList.add('border-emerald-600', 'bg-emerald-50/60', 'shadow-sm');
                    card.classList.remove('border-slate-200', 'bg-white');
                    const title = card.querySelector('span:first-of-type');
                    if (title) { title.classList.add('text-emerald-900'); title.classList.remove('text-slate-800'); }
                    const sub = card.querySelector('span:last-of-type');
                    if (sub) { sub.classList.add('text-emerald-700', 'font-semibold'); sub.classList.remove('text-slate-500'); }
                    const radio = card.querySelector('input[type="radio"]');
                    if (radio) radio.checked = true;
                });
            });

            let currentIdempotencyKey = null;
            function getIdempotencyKey() {
                if (!currentIdempotencyKey) {
                    currentIdempotencyKey = 'b2b_' + Date.now() + '_' + Math.random().toString(36).substring(2, 10);
                }
                return currentIdempotencyKey;
            }

            // Złożenie zamówienia AJAX
            btnConfirmOrder.addEventListener('click', async () => {
                const items = getOrderedItems();
                if (items.length === 0) return;

                btnConfirmOrder.disabled = true;
                isSubmittingOrder = true;
                hideCheckoutError();
                confirmBtnText.textContent = 'Wysyłanie zamówienia...';
                confirmSpinner.classList.remove('hidden');

                try {
                    const formData = new FormData();
                    formData.append('items', JSON.stringify(items));
                    formData.append('notes', orderNotes.value.trim());
                    formData.append('_csrf', CSRF_TOKEN);
                    formData.append('idempotency_key', getIdempotencyKey());

                    const checkedDelivery = document.querySelector('input[name="modal_delivery_date"]:checked');
                    if (checkedDelivery && checkedDelivery.value) {
                        formData.append('delivery_date', checkedDelivery.value);
                    }

                    // Satysfakcjonujące feedback UX dla Zamawiającego — min. 1 sekunda animacji wysyłania
                    const minDelayPromise = new Promise(resolve => setTimeout(resolve, 1000));

                    const fetchPromise = fetch(`${BASE_URL}b2b/saveorder`, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    }).then(async res => {
                        const data = await readJsonResponse(res);
                        return { resOk: res.ok, data };
                    });

                    const [_, result] = await Promise.all([minDelayPromise, fetchPromise]);
                    const { resOk, data } = result;

                    if (resOk && data.ok) {
                        currentIdempotencyKey = null; // Sukces: zresetuj token na kolejne zamówienie
                        orderCompleted = true;
                        clearDraft();
                        checkoutModal.classList.add('hidden');
                        successOrderNumber.textContent = data.order_number;
                        downloadPackingSheetBtn.href = `${BASE_URL}b2b/download?id=${data.order_id}`;
                        successModal.classList.remove('hidden');
                        btnNewOrder.focus();
                    } else {
                        showCheckoutError(data.error || 'Wystąpił błąd podczas zapisywania zamówienia.', false);
                    }
                } catch (err) {
                    if (err && err.sessionExpired) {
                        saveDraftNow();
                        // Szkic jest zapisany — przejście do logowania nie wymaga ostrzeżenia beforeunload
                        skipUnloadWarning = true;
                        showCheckoutError('Sesja wygasła — zaloguj się ponownie. Koszyk zostanie przywrócony po zalogowaniu.', true);
                        showSessionExpired();
                    } else if (err && err.httpError) {
                        showCheckoutError(err.message, false);
                    } else {
                        showCheckoutError('Błąd sieciowy: ' + (err && err.message ? err.message : 'brak połączenia') + '. Sprawdź internet i spróbuj ponownie.', false);
                    }
                } finally {
                    isSubmittingOrder = false;
                    btnConfirmOrder.disabled = false;
                    confirmBtnText.textContent = 'Zatwierdź i wyślij zamówienie';
                    confirmSpinner.classList.add('hidden');
                }
            });

            btnNewOrder.addEventListener('click', () => {
                currentIdempotencyKey = null;
                successModal.classList.add('hidden');
                rows.forEach(row => {
                    row.querySelector('.input-qty').value = '';
                    updateRow(row);
                });
                customItems = [];
                if (orderNotes) orderNotes.value = '';
                renderCustomItemsTable();
                orderCompleted = false;
                updateCartTotals();
                filterRows();
                clearDraft();
                window.scrollTo({ top: 0, behavior: 'smooth' });
                searchInput.focus({ preventScroll: true });
            });

            // Esc w oknie sukcesu = „Złóż kolejne zamówienie” (zamówienie jest już zapisane)
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !successModal.classList.contains('hidden')) btnNewOrder.click();
            });

            // Uwagi do zamówienia również trafiają do szkicu
            if (orderNotes) orderNotes.addEventListener('input', scheduleDraftSave);

            // Ostrzeżenie przed opuszczeniem strony z niewysłanym koszykiem
            window.addEventListener('beforeunload', (e) => {
                if (isSubmittingOrder || orderCompleted || skipUnloadWarning) return;
                if (!cartHasItems()) return;
                saveDraftNow();
                e.preventDefault();
                e.returnValue = '';
            });
            window.addEventListener('pagehide', () => {
                if (!orderCompleted) saveDraftNow();
            });

            restoreDraft();
        });
    </script>
</body>
</html>
