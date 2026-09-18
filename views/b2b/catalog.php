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
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
    </style>
</head>
<body class="min-h-full flex flex-col bg-slate-100 text-slate-800 pb-28">

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
                <div class="flex items-center gap-3 sm:gap-6">
                    <div class="text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-xs font-black text-slate-900 uppercase tracking-wide">
                                <?= htmlspecialchars($client['company_name'] ?? 'Odbiorca B2B') ?>
                            </span>
                        </div>
                        <div class="text-[11px] text-slate-500 truncate max-w-[200px] sm:max-w-xs">
                            <?= htmlspecialchars($client['delivery_address'] ?? 'Dostawa hurtowa') ?>
                        </div>
                    </div>

                    <a href="<?= $base ?>b2b/history" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Historia
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
                    <input type="text" id="searchInput" placeholder="Szukaj towaru (np. mango, ziemniak, pomidor)..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition">
                </div>

                <!-- Fast Filter Toggle (Tylko zamawiane) -->
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer select-none">
                        <input type="checkbox" id="filterOrderedOnly" class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500">
                        <span>Pokaż tylko wybrane pozycje (<span id="orderedCountLabel">0</span>)</span>
                    </label>
                    <button type="button" id="clearQuantitiesBtn" class="text-xs text-rose-500 hover:text-rose-700 font-semibold px-2 py-1 rounded hover:bg-rose-50 transition">
                        Wyczyść koszyk
                    </button>
                </div>
            </div>

            <!-- Categories pills -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none text-xs font-semibold">
                <button type="button" class="category-pill active px-3.5 py-1.5 rounded-xl bg-emerald-600 text-white shadow-sm transition" data-category="ALL">
                    Wszystkie towary
                </button>
                <?php foreach (($categories ?? []) as $cat): ?>
                    <button type="button" class="category-pill px-3.5 py-1.5 rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 transition" data-category="<?= htmlspecialchars($cat) ?>">
                        <?= htmlspecialchars($cat) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Product Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm" id="catalogTable">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
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
                                <td colspan="7" class="py-12 text-center text-slate-400">
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
                                <tr class="product-row hover:bg-emerald-50/40 transition group"
                                    data-id="<?= (int)$p['id'] ?>"
                                    data-name="<?= htmlspecialchars($p['name']) ?>"
                                    data-category="<?= htmlspecialchars($p['category'] ?? 'Warzywa') ?>"
                                    data-unit="<?= $unit ?>"
                                    data-price="<?= $price ?>"
                                    data-pkg-size="<?= $pkgSize ?>"
                                    data-pkg-unit="<?= $pkgUnit ?>">
                                    
                                    <!-- Lp -->
                                    <td class="py-3.5 px-4 text-center text-xs font-semibold text-slate-400">
                                        <?= $lp++ ?>
                                    </td>

                                    <!-- Nazwa towaru -->
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900 group-hover:text-emerald-700 transition">
                                            <?= htmlspecialchars($p['name']) ?>
                                        </div>
                                        <div class="text-[11px] text-slate-400">
                                            <?= htmlspecialchars($p['category'] ?? 'Świeże') ?>
                                        </div>
                                    </td>

                                    <!-- Opakowanie i asystent -->
                                    <td class="py-3.5 px-3 whitespace-nowrap">
                                        <?php if ($pkgSize > 1.0): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-amber-50 border border-amber-200 text-amber-800 text-[11px] font-bold whitespace-nowrap">
                                                <?= $pkgUnit ?> (<?= $pkgSize ?> <?= $unit ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-400 text-xs whitespace-nowrap">luzem (<?= $unit ?>)</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Cena -->
                                    <td class="py-3.5 px-3 text-right whitespace-nowrap">
                                        <div class="font-bold text-slate-800">
                                            <?= number_format($price, 2, '.', ' ') ?> <span class="text-xs font-normal text-slate-500">zł</span>
                                        </div>
                                        <div class="text-[10px] text-slate-400">za <?= $unit ?></div>
                                    </td>

                                    <!-- Pole ilości z klawiaturą i asystentem zaokrąglenia -->
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button type="button" class="btn-step-down w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold flex items-center justify-center active:scale-95 transition">
                                                -
                                            </button>
                                            <div class="relative w-24">
                                                <input type="number" step="<?= $unit === 'szt.' ? '1' : '0.5' ?>" min="0" value="" placeholder="0"
                                                    class="input-qty w-full text-center py-1.5 px-2 rounded-lg border border-slate-300 font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm transition">
                                                <span class="absolute right-2 top-2 text-[10px] font-bold text-slate-400 pointer-events-none"><?= $unit ?></span>
                                            </div>
                                            <button type="button" class="btn-step-up w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold flex items-center justify-center active:scale-95 transition">
                                                +
                                            </button>
                                        </div>
                                        <!-- Przycisk asystenta optymalizacji klatki/worka -->
                                        <div class="optimizer-hint mt-1 text-center hidden">
                                            <button type="button" class="btn-round-up text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded px-2 py-0.5 hover:bg-emerald-100 transition">
                                                Zaokrąglij do pełnej skrzynki
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Rozbicie logistyczne -->
                                    <td class="py-3.5 px-3 whitespace-nowrap">
                                        <span class="package-summary-label text-xs font-medium text-slate-600 whitespace-nowrap">-</span>
                                    </td>

                                    <!-- Wartość -->
                                    <td class="py-3.5 px-4 text-right">
                                        <span class="item-total-label font-bold text-slate-900 text-sm">0.00 zł</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Floating Bottom Bar (Pływające podsumowanie koszyka) -->
    <div id="floatingCart" class="fixed bottom-0 inset-x-0 bg-slate-900/95 backdrop-blur text-white py-3.5 px-4 sm:px-8 border-t border-slate-800 shadow-2xl z-40 transition-transform duration-300 translate-y-0">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-6 text-sm">
                <div>
                    <span class="text-xs text-slate-400 block uppercase font-bold tracking-wider">Wybrane pozycje:</span>
                    <span id="cartItemsCount" class="font-extrabold text-emerald-400 text-lg">0 pozycji</span>
                </div>
                <div class="h-8 w-px bg-slate-700"></div>
                <div>
                    <span class="text-xs text-slate-400 block uppercase font-bold tracking-wider">Łączna wartość:</span>
                    <span id="cartTotalSum" class="font-black text-white text-xl">0.00 zł</span>
                </div>
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                <button type="button" id="btnOpenReview" disabled
                    class="w-full sm:w-auto px-8 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 disabled:opacity-40 disabled:cursor-not-allowed font-extrabold text-white text-sm shadow-lg shadow-emerald-500/20 active:scale-95 transition flex items-center justify-center gap-2">
                    <span>Złóż zamówienie</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Review & Checkout Modal -->
    <div id="checkoutModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-slate-100 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            <!-- Header -->
            <div class="px-6 py-5 bg-gradient-to-r from-emerald-600 to-teal-700 text-white flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-black tracking-tight">Podsumowanie zamówienia B2B</h3>
                    <p class="text-xs text-emerald-100 mt-0.5">Sprawdź specyfikację przed przesłaniem na rampę magazynową</p>
                </div>
                <button type="button" id="btnCloseModal" class="text-white/70 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 overflow-y-auto space-y-5 flex-1">
                <!-- Client Details Snapshot -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 text-xs grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <span class="text-slate-400 font-semibold block">Odbiorca:</span>
                        <strong class="text-slate-900 text-sm"><?= htmlspecialchars($client['company_name'] ?? '') ?></strong>
                    </div>
                    <div>
                        <span class="text-slate-400 font-semibold block">Adres dostawy:</span>
                        <span class="text-slate-700"><?= htmlspecialchars($client['delivery_address'] ?? '') ?></span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-semibold block">Telefon kontaktowy:</span>
                        <span class="text-slate-700"><?= htmlspecialchars($client['phone'] ?? '-') ?></span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-semibold block">Data zamówienia:</span>
                        <span class="text-slate-700"><?= date('d.m.Y H:i') ?></span>
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
                                    <td id="modalTotalSum" class="py-3 px-3 text-right text-emerald-600 text-sm font-extrabold">0.00 zł</td>
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
                        class="w-full p-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
                        placeholder="np. prosimy o dostawę przed 6:30 rano; skrzynki na wymianę"></textarea>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-3">
                <button type="button" id="btnCancelModal" class="px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                    Wróć do edycji
                </button>
                <button type="button" id="btnConfirmOrder"
                    class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow-lg shadow-emerald-600/30 active:scale-95 transition flex items-center gap-2">
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
    <div id="successModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-8 text-center shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95 duration-200">
            <div class="w-16 h-16 rounded-3xl bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="text-xl font-black text-slate-900">Zamówienie przyjęte!</h3>
            <p class="text-xs text-slate-500 mt-1">Twoje zamówienie zostało przekazane do działu kompletacji hurtowni.</p>

            <div class="my-6 p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <div class="text-[11px] uppercase font-bold text-slate-400">Numer zamówienia</div>
                <div id="successOrderNumber" class="text-lg font-black text-emerald-600 mt-0.5">ZAM/B2B/...</div>
            </div>

            <div class="space-y-3">
                <a id="downloadPackingSheetBtn" href="#" target="_blank"
                    class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow-md shadow-emerald-600/20 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Pobierz plik
                </a>
                <button type="button" id="btnNewOrder" class="w-full py-2.5 px-4 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
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

    <!-- CSRF & JavaScript Controller -->
    <script>
        const CSRF_TOKEN = '<?= $csrfToken ?>';
        const BASE_URL   = '<?= $base ?>';

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

            let currentCategory = 'ALL';

            // Rozbicie logistyczne w JS
            function computePackageSummary(qty, pkgSize, pkgUnit, unit) {
                if (pkgSize <= 1.0) {
                    return qty > 0 ? `${qty} ${unit}` : '-';
                }
                const fullBoxes = Math.floor(qty / pkgSize);
                const remainder = Math.round((qty % pkgSize) * 100) / 100;

                if (fullBoxes > 0 && remainder > 0) {
                    return `${fullBoxes} ${pkgUnit} + ${remainder} ${unit}`;
                } else if (fullBoxes > 0) {
                    return `${fullBoxes} ${pkgUnit} (${qty} ${unit})`;
                } else if (remainder > 0) {
                    return `${remainder} ${unit}`;
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
                totalLabel.textContent = total.toFixed(2) + ' zł';

                // Asystent zaokrąglenia (Box Optimizer - min. 65% napełnienia opakowania)
                if (pkgSize > 1.0 && qty > 0) {
                    const remainder = Math.round((qty % pkgSize) * 1000) / 1000;
                    const fillRatio = remainder / pkgSize;
                    if (remainder > 0.001 && fillRatio >= 0.6499) {
                        const nextFullQty = Math.ceil(qty / pkgSize) * pkgSize;
                        const btnRound = optimizerHint.querySelector('.btn-round-up');
                        btnRound.textContent = `Zaokrąglij do ${nextFullQty} ${unit} (${Math.ceil(qty / pkgSize)} ${pkgUnit})`;
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

            // Suma koszyka
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

                cartItemsCount.textContent = `${orderedCount} ${orderedCount === 1 ? 'pozycja' : (orderedCount > 1 && orderedCount < 5 ? 'pozycje' : 'pozycji')}`;
                orderedCountLabel.textContent = orderedCount;
                cartTotalSum.textContent = totalAmount.toFixed(2) + ' zł';

                btnOpenReview.disabled = (orderedCount === 0);
            }

            // Filtrowanie widoczności wierszy
            function filterRows() {
                const searchVal = searchInput.value.toLowerCase().trim();
                const onlyOrdered = filterOrderedOnly.checked;

                rows.forEach(row => {
                    const name = row.dataset.name.toLowerCase();
                    const category = row.dataset.category;
                    const input = row.querySelector('.input-qty');
                    const qty = parseFloat(input.value) || 0;

                    const matchesSearch = name.includes(searchVal);
                    const matchesCategory = (currentCategory === 'ALL' || category === currentCategory);
                    const matchesOrdered = !onlyOrdered || (qty > 0);

                    if (matchesSearch && matchesCategory && matchesOrdered) {
                        row.classList.remove('hidden');
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
                const step = (unit === 'szt.' ? 1 : 1);

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
                    let val = (parseFloat(input.value) || 0) - step;
                    if (val < 0) val = 0;
                    input.value = val === 0 ? '' : val;
                    updateRow(row);
                });

                btnUp.addEventListener('click', () => {
                    let val = (parseFloat(input.value) || 0) + step;
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

            clearQuantitiesBtn.addEventListener('click', () => {
                if (confirm('Czy na pewno chcesz wyczyścić wszystkie wpisane ilości?')) {
                    rows.forEach(row => {
                        row.querySelector('.input-qty').value = '';
                        updateRow(row);
                    });
                    filterRows();
                }
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
                return items;
            }

            btnOpenReview.addEventListener('click', () => {
                const items = getOrderedItems();
                if (items.length === 0) return;

                modalItemsBody.innerHTML = '';
                let total = 0;

                items.forEach(it => {
                    total += it.item_total;
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-slate-50';
                    tr.innerHTML = `
                        <td class="py-2.5 px-3 font-semibold text-slate-900">${it.product_name}</td>
                        <td class="py-2.5 px-3 text-center font-bold text-emerald-700">${it.quantity} ${it.unit}</td>
                        <td class="py-2.5 px-3 text-slate-500">${it.package_summary}</td>
                        <td class="py-2.5 px-3 text-right font-bold text-slate-800">${it.item_total.toFixed(2)} zł</td>
                    `;
                    modalItemsBody.appendChild(tr);
                });

                modalTotalSum.textContent = total.toFixed(2) + ' zł';
                checkoutModal.classList.remove('hidden');
            });

            function closeModal() {
                checkoutModal.classList.add('hidden');
            }

            btnCloseModal.addEventListener('click', closeModal);
            btnCancelModal.addEventListener('click', closeModal);

            // Złożenie zamówienia AJAX
            btnConfirmOrder.addEventListener('click', async () => {
                const items = getOrderedItems();
                if (items.length === 0) return;

                btnConfirmOrder.disabled = true;
                confirmBtnText.textContent = 'Wysyłanie zamówienia...';
                confirmSpinner.classList.remove('hidden');

                try {
                    const formData = new FormData();
                    formData.append('items', JSON.stringify(items));
                    formData.append('notes', orderNotes.value.trim());
                    formData.append('_csrf', CSRF_TOKEN);

                    const res = await fetch(`${BASE_URL}b2b/saveorder`, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    const data = await res.json();
                    if (res.ok && data.ok) {
                        checkoutModal.classList.add('hidden');
                        successOrderNumber.textContent = data.order_number;
                        downloadPackingSheetBtn.href = `${BASE_URL}b2b/download?id=${data.order_id}`;
                        successModal.classList.remove('hidden');
                    } else {
                        alert(data.error || 'Wystąpił błąd podczas zapisywania zamówienia.');
                    }
                } catch (err) {
                    alert('Błąd sieciowy: ' + err.message);
                } finally {
                    btnConfirmOrder.disabled = false;
                    confirmBtnText.textContent = 'Zatwierdź i wyślij zamówienie';
                    confirmSpinner.classList.add('hidden');
                }
            });

            btnNewOrder.addEventListener('click', () => {
                successModal.classList.add('hidden');
                rows.forEach(row => {
                    row.querySelector('.input-qty').value = '';
                    updateRow(row);
                });
                filterRows();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    </script>
</body>
</html>
