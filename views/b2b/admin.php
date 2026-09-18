<?php
/**
 * Panel Hurtownika - Hurtownia Magdy
 * Profesjonalny panel zarządzania ofertą, zamówieniami i klientami B2B.
 */
$csrfToken = $view['csrfToken'] ?? '';
$base      = $view['base'] ?? '/';
$products  = $view['products'] ?? [];
$orders    = $view['orders'] ?? [];
$clients   = $view['clients'] ?? [];
$activeTab = $view['activeTab'] ?? 'products';
$isMysqlConfigured = defined('DSN') && strpos(DSN, 'CHANGEME') === false && defined('DBLOGIN') && DBLOGIN !== 'CHANGEME' && DBLOGIN !== '';
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Tools::h($view['title'] ?? 'Panel Hurtownika — Hurtownia Magdy') ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        emerald: { 50: '#ecfdf5', 100: '#d1fae5', 500: '#10b981', 600: '#059669', 700: '#047857' },
                        sky: { 50: '#f0f9ff', 100: '#e0f2fe', 500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1' }
                    }
                }
            }
        }
    </script>
    <style>
        .bg-grid {
            background-image: radial-gradient(rgba(15, 23, 42, 0.05) 1.2px, transparent 1.2px);
            background-size: 24px 24px;
        }
        .animate-fade-in { animation: fadeIn 0.2s ease-out forwards; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="h-full font-sans text-slate-800 antialiased flex flex-col relative bg-slate-50 selection:bg-emerald-500 selection:text-white">
    
    <!-- Tło dekoracyjne z delikatnymi akcentami warzyw i owoców -->
    <div class="fixed inset-0 bg-grid pointer-events-none z-0"></div>
    <div class="fixed -top-24 -left-24 w-96 h-96 rounded-full bg-emerald-100/40 blur-3xl pointer-events-none z-0"></div>
    <div class="fixed top-1/3 -right-24 w-96 h-96 rounded-full bg-amber-100/30 blur-3xl pointer-events-none z-0"></div>
    <div class="fixed -bottom-24 left-1/4 w-96 h-96 rounded-full bg-sky-100/40 blur-3xl pointer-events-none z-0"></div>

    <div class="relative z-10 flex-1 flex flex-col max-w-7xl w-full mx-auto p-4 md:p-6 lg:p-8 gap-6">

        <!-- Główny pasek nawigacji -->
        <header class="bg-white/90 backdrop-blur-md border border-slate-200/80 rounded-2xl p-4 md:px-6 shadow-sm flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-white shadow-md shadow-emerald-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="font-extrabold text-slate-900 text-lg md:text-xl leading-tight">Hurtownia Magdy</h1>
                    <p class="text-xs font-semibold text-slate-500">Panel Hurtownika — Zarządzanie Ofertą i Zamówieniami B2B</p>
                </div>
            </div>

            <!-- Przełącznik modułów i wylogowanie -->
            <div class="flex items-center gap-2">
                <a href="<?= $base ?>b2b" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-sky-700 bg-sky-50 border border-sky-200 rounded-xl hover:bg-sky-100 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    Podgląd sklepu B2B
                </a>
                <a href="<?= $base ?>home/index" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition shadow-sm">
                    Pulpit
                </a>
                <a href="<?= $base ?>home/logout" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 border border-rose-200 rounded-xl hover:bg-rose-100 transition shadow-sm">
                    Wyloguj
                </a>
            </div>
        </header>

        <!-- Zakładki nawigacyjne -->
        <nav class="flex border-b border-slate-200 gap-2 overflow-x-auto pb-1">
            <button type="button" id="tab-btn-products" onclick="switchTab('products')" class="px-5 py-2.5 font-bold text-sm rounded-xl transition flex items-center gap-2 bg-emerald-600 text-white shadow-md shadow-emerald-600/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                <span>Cennik & Oferta hurtowni</span>
                <span id="badge-products-count" class="ml-1 px-2 py-0.5 text-xs rounded-full bg-white/20 text-white"><?= count($products) ?></span>
            </button>

            <button type="button" id="tab-btn-orders" onclick="switchTab('orders')" class="px-5 py-2.5 font-bold text-sm rounded-xl transition flex items-center gap-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100/80">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                <span>Spływające Zamówienia</span>
                <span id="badge-orders-count" class="ml-1 px-2 py-0.5 text-xs rounded-full bg-slate-200 text-slate-800"><?= count($orders) ?></span>
            </button>

            <button type="button" id="tab-btn-clients" onclick="switchTab('clients')" class="px-5 py-2.5 font-bold text-sm rounded-xl transition flex items-center gap-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100/80">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span>Klienci Hurtowni</span>
                <span class="ml-1 px-2 py-0.5 text-xs rounded-full bg-slate-200 text-slate-800"><?= count($clients) ?></span>
            </button>
        </nav>

        <!-- =================================================================== -->
        <!-- ZAKŁADKA 1: Cennik & Oferta hurtowni                                -->
        <!-- =================================================================== -->
        <main id="tab-products" class="space-y-6">
            
            <!-- Strefa wgrania nowego cennika Excel -->
            <section class="bg-white/95 border border-slate-200 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs">1</span>
                            Wgraj nowy cennik hurtowni (.xlsx)
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Wgraj plik od dostawcy — parser automatycznie pominie logotypy i dopasuje klatki/skrzynki z pamięci systemu.</p>
                    </div>
                    <span class="text-xs font-bold text-slate-400 bg-slate-100 px-2.5 py-1 rounded-lg">Format: Microsoft Excel (.xlsx)</span>
                </div>

                <div id="dropzone" class="relative border-2 border-dashed border-slate-300 hover:border-emerald-500 bg-slate-50/50 hover:bg-emerald-50/20 transition rounded-xl p-8 text-center cursor-pointer">
                    <input id="file-input" type="file" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full" accept=".xlsx">
                    <div class="flex flex-col items-center justify-center gap-2 pointer-events-none">
                        <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                        </div>
                        <p class="font-bold text-sm text-slate-800">Przeciągnij plik cennika tutaj lub kliknij, aby wybrać</p>
                        <p class="text-xs text-slate-400">System zachowa zapamiętane opakowania zbiorcze (np. klatki mango, skrzynki pomidorów)</p>
                    </div>
                </div>

                <div id="upload-status" class="mt-3 text-center text-sm font-semibold hidden"></div>

                <!-- Kontener mapowania (pojawia się po uploadzie) -->
                <div id="mapping-box" class="mt-6 pt-6 border-t border-slate-200 hidden animate-fade-in">
                    <h3 class="font-bold text-sm text-slate-900 mb-3">Potwierdź przypisanie kolumn cennika:</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 bg-slate-50 border border-slate-200 rounded-xl mb-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1" for="map-header-row">Wiersz nagłówka</label>
                            <select id="map-header-row" class="w-full text-sm bg-white border border-slate-300 rounded-lg p-2 font-medium"></select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1" for="map-col-product">Kolumna: Towar</label>
                            <select id="map-col-product" class="w-full text-sm bg-white border border-slate-300 rounded-lg p-2 font-medium"></select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1" for="map-col-price">Kolumna: Cena</label>
                            <select id="map-col-price" class="w-full text-sm bg-white border border-slate-300 rounded-lg p-2 font-medium"></select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1" for="map-col-unit">Kolumna: Jednostka</label>
                            <select id="map-col-unit" class="w-full text-sm bg-white border border-slate-300 rounded-lg p-2 font-medium">
                                <option value="">-- Domyślnie (kg / szt.) --</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="cancelMapping()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">Anuluj</button>
                        <button type="button" id="btn-process-import" class="px-5 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-md transition flex items-center gap-2">
                            <span>Wdróż ten cennik do oferty B2B</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>
                    </div>
                </div>
            </section>

            <!-- Mały placeholder: Połącz z bazą danych MySQL -->
            <div class="bg-white/80 border border-dashed border-slate-300/90 rounded-2xl p-4 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3 transition">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200 text-slate-500 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-extrabold text-slate-800">Połącz z bazą danych MySQL</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full <?= $isMysqlConfigured ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500 border border-slate-200' ?>">
                                <?= $isMysqlConfigured ? 'Skonfigurowana' : 'Nieaktywny' ?>
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            <?= $isMysqlConfigured 
                                ? 'Parametry połączenia MySQL gotowe' 
                                : 'Póki co nieaktywne — wymaga skonfigurowania danych bazy' ?>
                        </p>
                    </div>
                </div>
                <div>
                    <button type="button" <?= $isMysqlConfigured ? '' : 'disabled' ?>
                        class="px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition <?= $isMysqlConfigured ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm' : 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60' ?>"
                        title="<?= $isMysqlConfigured ? 'Synchronizuj asortyment z bazy MySQL' : 'Wymaga skonfigurowania danych dostępowych do bazy, aby aktywować tę integrację' ?>">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                        Połącz z bazą danych MySQL
                    </button>
                </div>
            </div>

            <!-- Tabela bieżącego asortymentu z szybką edycją na żywo -->
            <section class="bg-white/95 border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs">2</span>
                            Bieżący Asortyment w Ofercie (Edycja na żywo)
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Edytuj ceny, kategorie, jednostki i opakowania zbiorcze towarów (aktywuj i dezaktywuj dostępność towaru na żywo)</p>
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <div class="relative flex-1 sm:w-64">
                            <input id="product-search-admin" type="text" placeholder="Filtruj asortyment..." class="w-full text-xs font-medium pl-8 pr-3 py-2 border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500">
                            <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </div>
                        <select id="filter-category-admin" class="text-xs font-bold border border-slate-300 rounded-xl px-3 py-2 bg-white">
                            <option value="all">Wszystkie kategorie</option>
                            <option value="Warzywa">Warzywa</option>
                            <option value="Owoce">Owoce</option>
                            <option value="Cytrusy">Cytrusy</option>
                            <option value="Zioła i sałaty">Zioła i sałaty</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm" id="products-table">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4 font-bold text-center w-24">Dostępny</th>
                                <th class="py-3.5 px-4 font-bold">Towar</th>
                                <th class="py-3.5 px-4 font-bold">Kategoria</th>
                                <th class="py-3.5 px-4 font-bold text-right w-36">Cena hurtowa</th>
                                <th class="py-3.5 px-4 font-bold text-center w-24">Jedn.</th>
                                <th class="py-3.5 px-4 font-bold w-48">Opakowanie zbiorcze (klatka/skrzynka)</th>
                                <th class="py-3.5 px-4 font-bold text-center w-20">Zapisz</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="products-tbody">
                            <?php if (!empty($products)): ?>
                                <?php foreach ($products as $p): ?>
                                    <tr class="hover:bg-slate-50/80 transition <?= (int)$p['is_available'] === 0 ? 'opacity-50 bg-slate-50' : '' ?>" id="prod-row-<?= $p['id'] ?>" data-cat="<?= Tools::h($p['category']) ?>" data-name="<?= Tools::h(mb_strtolower($p['name'])) ?>">
                                        <!-- Przełącznik In stock / Out of stock -->
                                        <td class="py-3 px-4 text-center">
                                            <button type="button" onclick="toggleProduct(<?= $p['id'] ?>)" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold transition <?= (int)$p['is_available'] === 1 ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-200 text-slate-600 hover:bg-slate-300' ?>">
                                                <?= (int)$p['is_available'] === 1 ? 'W ofercie' : 'Brak' ?>
                                            </button>
                                        </td>
                                        <!-- Nazwa -->
                                        <td class="py-3 px-4 font-bold text-slate-900">
                                            <input type="text" id="name-<?= $p['id'] ?>" value="<?= Tools::h($p['name']) ?>" class="w-full bg-transparent border-b border-transparent hover:border-slate-300 focus:border-emerald-500 focus:bg-white px-1.5 py-0.5 rounded text-sm font-bold">
                                        </td>
                                        <!-- Kategoria -->
                                        <td class="py-3 px-4">
                                            <select id="cat-<?= $p['id'] ?>" class="text-xs font-medium border border-slate-200 rounded-lg p-1 bg-white">
                                                <option value="Warzywa" <?= $p['category'] === 'Warzywa' ? 'selected' : '' ?>>Warzywa</option>
                                                <option value="Owoce" <?= $p['category'] === 'Owoce' ? 'selected' : '' ?>>Owoce</option>
                                                <option value="Cytrusy" <?= $p['category'] === 'Cytrusy' ? 'selected' : '' ?>>Cytrusy</option>
                                                <option value="Zioła i sałaty" <?= $p['category'] === 'Zioła i sałaty' ? 'selected' : '' ?>>Zioła i sałaty</option>
                                                <option value="Inne" <?= $p['category'] === 'Inne' ? 'selected' : '' ?>>Inne</option>
                                            </select>
                                        </td>
                                        <!-- Cena -->
                                        <td class="py-3 px-4 text-right">
                                            <div class="inline-flex items-center justify-end gap-1">
                                                <input type="number" step="0.01" min="0" id="price-<?= $p['id'] ?>" value="<?= number_format((float)$p['price'], 2, '.', '') ?>" class="w-24 text-right text-sm font-extrabold text-blue-700 border border-slate-200 focus:border-emerald-500 rounded-lg p-1">
                                                <span class="text-xs font-bold text-slate-500">zł</span>
                                            </div>
                                        </td>
                                        <!-- Jednostka -->
                                        <td class="py-3 px-4 text-center">
                                            <select id="unit-<?= $p['id'] ?>" class="text-xs font-medium border border-slate-200 rounded-lg p-1 bg-white">
                                                <option value="kg" <?= $p['unit'] === 'kg' ? 'selected' : '' ?>>kg</option>
                                                <option value="szt." <?= $p['unit'] === 'szt.' ? 'selected' : '' ?>>szt.</option>
                                                <option value="op." <?= $p['unit'] === 'op.' ? 'selected' : '' ?>>op.</option>
                                                <option value="pęczek" <?= $p['unit'] === 'pęczek' ? 'selected' : '' ?>>pęczek</option>
                                            </select>
                                        </td>
                                        <!-- Opakowanie zbiorcze -->
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-1.5">
                                                <input type="number" step="0.5" min="1" id="pkg-size-<?= $p['id'] ?>" value="<?= (float)$p['package_size'] ?>" class="w-16 text-center text-xs font-bold border border-slate-200 focus:border-emerald-500 rounded-lg p-1">
                                                <select id="pkg-unit-<?= $p['id'] ?>" class="text-xs font-medium border border-slate-200 rounded-lg p-1 bg-white">
                                                    <option value="klatka" <?= $p['package_unit'] === 'klatka' ? 'selected' : '' ?>>klatka</option>
                                                    <option value="skrzynka" <?= $p['package_unit'] === 'skrzynka' ? 'selected' : '' ?>>skrzynka</option>
                                                    <option value="karton" <?= $p['package_unit'] === 'karton' ? 'selected' : '' ?>>karton</option>
                                                    <option value="worek" <?= $p['package_unit'] === 'worek' ? 'selected' : '' ?>>worek</option>
                                                    <option value="op." <?= $p['package_unit'] === 'op.' ? 'selected' : '' ?>>op.</option>
                                                </select>
                                            </div>
                                        </td>
                                        <!-- Zapis -->
                                        <td class="py-3 px-4 text-center">
                                            <button type="button" onclick="saveProduct(<?= $p['id'] ?>)" class="p-1.5 text-emerald-600 hover:text-white hover:bg-emerald-600 rounded-lg transition" title="Zapisz zmiany">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-400 font-medium">Brak towarów w bazie. Wgraj plik Excela powyżej, aby zasilić ofertę.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <!-- =================================================================== -->
        <!-- ZAKŁADKA 2: Spływające Zamówienia B2B                               -->
        <!-- =================================================================== -->
        <main id="tab-orders" class="space-y-6 hidden">
            <section class="bg-white/95 border border-slate-200 rounded-2xl shadow-sm overflow-hidden p-5">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Spływające Zamówienia ze Sklepów B2B</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Śledź zamówienia na bieżąco, zmieniaj statusy i pobieraj arkusze kompletacji na magazyn.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4 font-bold">Numer</th>
                                <th class="py-3.5 px-4 font-bold">Klient / Sklep</th>
                                <th class="py-3.5 px-4 font-bold">Telefon</th>
                                <th class="py-3.5 px-4 font-bold text-center">Pozycje</th>
                                <th class="py-3.5 px-4 font-bold text-right">Wartość</th>
                                <th class="py-3.5 px-4 font-bold text-center">Status</th>
                                <th class="py-3.5 px-4 font-bold text-right">Data</th>
                                <th class="py-3.5 px-4 font-bold text-center">Akcje</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($orders)): ?>
                                <?php foreach ($orders as $o): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-3 px-4 font-extrabold text-blue-700"><?= Tools::h($o['order_number']) ?></td>
                                        <td class="py-3 px-4 font-bold text-slate-900"><?= Tools::h($o['client_name_snapshot']) ?></td>
                                        <td class="py-3 px-4 text-xs font-semibold text-slate-600"><?= Tools::h($o['client_phone_snapshot'] ?: '—') ?></td>
                                        <td class="py-3 px-4 text-center font-bold text-slate-700"><?= (int)$o['total_items'] ?></td>
                                        <td class="py-3 px-4 text-right font-extrabold text-slate-900"><?= number_format((float)$o['total_amount'], 2, '.', ' ') ?> zł</td>
                                        <td class="py-3 px-4 text-center">
                                            <select onchange="updateOrderStatus(<?= $o['id'] ?>, this.value)" class="text-xs font-bold rounded-lg px-2 py-1 border border-slate-200 focus:outline-none">
                                                <option value="new" <?= $o['status'] === 'new' ? 'selected' : '' ?>>Nowe</option>
                                                <option value="processing" <?= $o['status'] === 'processing' ? 'selected' : '' ?>>W kompletacji</option>
                                                <option value="completed" <?= $o['status'] === 'completed' ? 'selected' : '' ?>>Zrealizowane</option>
                                                <option value="cancelled" <?= $o['status'] === 'cancelled' ? 'selected' : '' ?>>Anulowane</option>
                                            </select>
                                        </td>
                                        <td class="py-3 px-4 text-right text-xs text-slate-400 font-medium"><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
                                        <td class="py-3 px-4 text-center">
                                            <button type="button" onclick="showOrderModal(<?= $o['id'] ?>)" class="px-2.5 py-1 text-xs font-bold text-sky-700 bg-sky-50 border border-sky-200 rounded-lg hover:bg-sky-100 transition">
                                                Szczegóły
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-slate-400 font-medium">Brak złożonych zamówień w historii.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <!-- =================================================================== -->
        <!-- ZAKŁADKA 3: Klienci Hurtowni (Baza i generowanie linków)            -->
        <!-- =================================================================== -->
        <main id="tab-clients" class="space-y-6 hidden">
            <!-- Formularz dodania nowego klienta -->
            <section class="bg-white/95 border border-slate-200 rounded-2xl p-6 shadow-sm">
                <h2 class="text-base font-extrabold text-slate-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    Dodaj Nowego Odbiorcę B2B (Sklep / Gastronomia)
                </h2>

                <form id="form-create-client" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nazwa sklepu / firmy *</label>
                        <input type="text" id="new-client-name" required placeholder="np. Warzywniak U Ani" class="w-full text-sm border border-slate-300 rounded-xl p-2.5 font-medium focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">NIP (opcjonalnie)</label>
                        <input type="text" id="new-client-nip" placeholder="np. 1234567890" class="w-full text-sm border border-slate-300 rounded-xl p-2.5 font-medium focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Telefon kontaktowy *</label>
                        <input type="text" id="new-client-phone" required placeholder="np. 500 600 700" class="w-full text-sm border border-slate-300 rounded-xl p-2.5 font-medium focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Adres e-mail</label>
                        <input type="email" id="new-client-email" placeholder="sklep@example.com" class="w-full text-sm border border-slate-300 rounded-xl p-2.5 font-medium focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Adres dostawy i uwagi dla kierowcy</label>
                        <input type="text" id="new-client-address" placeholder="np. ul. Kwiatowa 5, Warszawa (brama od podwórka)" class="w-full text-sm border border-slate-300 rounded-xl p-2.5 font-medium focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div class="md:col-span-3 flex justify-end">
                        <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl shadow-md transition flex items-center gap-2">
                            <span>Utwórz profil i wygeneruj link dostępowy</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </button>
                    </div>
                </form>
            </section>

            <!-- Tabela zarejestrowanych klientów -->
            <section class="bg-white/95 border border-slate-200 rounded-2xl shadow-sm overflow-hidden p-5">
                <h3 class="text-base font-extrabold text-slate-900 mb-4">Baza Odbiorców B2B</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm" id="clients-table">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4 font-bold">Sklep / Odbiorca</th>
                                <th class="py-3.5 px-4 font-bold">Telefon</th>
                                <th class="py-3.5 px-4 font-bold">E-mail</th>
                                <th class="py-3.5 px-4 font-bold">Adres dostawy</th>
                                <th class="py-3.5 px-4 font-bold text-center">Status</th>
                                <th class="py-3.5 px-4 font-bold text-center">Link dostępu dla klienta</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="clients-tbody">
                            <?php if (!empty($clients)): ?>
                                <?php foreach ($clients as $c): ?>
                                    <?php $tokenUrl = $base . 'b2b?token=' . $c['auth_token']; ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-3 px-4 font-extrabold text-slate-900">
                                            <?= Tools::h($c['company_name']) ?>
                                            <?php if (!empty($c['nip'])): ?>
                                                <span class="block text-xs font-normal text-slate-400">NIP: <?= Tools::h($c['nip']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4 font-semibold text-slate-700"><?= Tools::h($c['phone'] ?: '—') ?></td>
                                        <td class="py-3 px-4 text-xs text-slate-600"><?= Tools::h($c['email'] ?: '—') ?></td>
                                        <td class="py-3 px-4 text-xs text-slate-600"><?= Tools::h($c['delivery_address'] ?: '—') ?></td>
                                        <td class="py-3 px-4 text-center">
                                            <button type="button" onclick="toggleClient(<?= $c['id'] ?>)" class="text-xs font-bold px-2.5 py-1 rounded-lg <?= (int)$c['is_active'] === 1 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' ?>">
                                                <?= (int)$c['is_active'] === 1 ? 'Aktywny' : 'Zablokowany' ?>
                                            </button>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <button type="button" onclick="copyToken('<?= $tokenUrl ?>')" class="copy-token inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-sky-700 bg-sky-50 border border-sky-200 rounded-xl hover:bg-sky-100 transition shadow-sm" title="Skopiuj unikalny link do wysłania klientowi SMS-em">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                                <span>Kopiuj szybki link</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-400 font-medium">Brak dodanych odbiorców. Dodaj pierwszego klienta powyżej.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal szczegółów zamówienia -->
    <div id="modal-order" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden animate-fade-in">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl overflow-hidden border border-slate-200 flex flex-col max-h-[90vh]">
            <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900" id="modal-order-number">Szczegóły Zamówienia</h3>
                    <p class="text-xs text-slate-500" id="modal-order-client"></p>
                </div>
                <button type="button" onclick="closeOrderModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-5 overflow-y-auto space-y-4 flex-1">
                <div class="p-3 bg-slate-50 rounded-xl text-xs space-y-1">
                    <p><strong>Adres dostawy:</strong> <span id="modal-order-address"></span></p>
                    <p><strong>Uwagi dla kierowcy:</strong> <span id="modal-order-notes" class="italic text-slate-600"></span></p>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="text-xs uppercase text-slate-400 border-b border-slate-200">
                        <tr>
                            <th class="py-2">Towar</th>
                            <th class="py-2 text-right">Ilość</th>
                            <th class="py-2 text-center">Rozbicie logistyczne</th>
                            <th class="py-2 text-right">Wartość</th>
                        </tr>
                    </thead>
                    <tbody id="modal-order-items" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
                <span class="text-sm font-bold text-slate-700">Razem do zapłaty: <strong id="modal-order-total" class="text-base text-blue-700"></strong></span>
                <button type="button" onclick="closeOrderModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 font-bold text-xs rounded-xl transition">Zamknij</button>
            </div>
        </div>
    </div>

    <!-- Powiadomienie Toast -->
    <div id="toast" class="fixed bottom-5 right-5 bg-slate-900 text-white text-xs font-bold px-4 py-3 rounded-xl shadow-xl transition-all duration-300 transform translate-y-20 opacity-0 pointer-events-none z-50 flex items-center gap-2">
        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        <span id="toast-msg">Komunikat</span>
    </div>

    <script>
        const CSRF_TOKEN = '<?= $csrfToken ?>';
        const BASE_URL   = '<?= $base ?>';
        let currentFileId = null;

        function showToast(msg) {
            const t = document.getElementById('toast');
            document.getElementById('toast-msg').textContent = msg;
            t.classList.remove('translate-y-20', 'opacity-0');
            setTimeout(() => t.classList.add('translate-y-20', 'opacity-0'), 3000);
        }

        function copyToken(url) {
            const fullUrl = window.location.origin + url;
            navigator.clipboard.writeText(fullUrl).then(() => {
                showToast('Skopiowano unikalny link dostępowy dla klienta!');
            }).catch(() => {
                prompt('Skopiuj link dla klienta:', fullUrl);
            });
        }

        function switchTab(tab) {
            ['products', 'orders', 'clients'].forEach(t => {
                const el = document.getElementById('tab-' + t);
                const btn = document.getElementById('tab-btn-' + t);
                if (t === tab) {
                    el.classList.remove('hidden');
                    btn.className = 'px-5 py-2.5 font-bold text-sm rounded-xl transition flex items-center gap-2 bg-emerald-600 text-white shadow-md shadow-emerald-600/20';
                } else {
                    el.classList.add('hidden');
                    btn.className = 'px-5 py-2.5 font-bold text-sm rounded-xl transition flex items-center gap-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100/80';
                }
            });
        }

        // Filtrowanie asortymentu w panelu admina
        const searchInput = document.getElementById('product-search-admin');
        const catFilter = document.getElementById('filter-category-admin');

        function filterProducts() {
            const q = searchInput.value.toLowerCase().trim();
            const cat = catFilter.value;
            const rows = document.querySelectorAll('#products-tbody tr');

            rows.forEach(r => {
                const name = r.getAttribute('data-name') || '';
                const rCat = r.getAttribute('data-cat') || '';
                const matchQ = !q || name.includes(q);
                const matchCat = (cat === 'all') || (rCat === cat);
                r.style.display = (matchQ && matchCat) ? '' : 'none';
            });
        }

        if (searchInput && catFilter) {
            searchInput.addEventListener('input', filterProducts);
            catFilter.addEventListener('change', filterProducts);
        }

        // Upload cennika Excel
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('file-input');

        if (dropzone && fileInput) {
            fileInput.addEventListener('change', () => {
                if (fileInput.files.length > 0) uploadFile(fileInput.files[0]);
            });
        }

        function uploadFile(file) {
            const status = document.getElementById('upload-status');
            status.textContent = 'Trwa parsowanie pliku cennika...';
            status.className = 'mt-3 text-center text-sm font-semibold text-emerald-600';
            status.classList.remove('hidden');

            const fd = new FormData();
            fd.append('cennik', file);
            fd.append('_csrf', CSRF_TOKEN);

            fetch(BASE_URL + 'b2b/upload', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (!data.ok) throw new Error(data.error || 'Błąd uploadu');
                    currentFileId = data.file_id;
                    status.textContent = 'Plik załadowany pomyślnie. Sprawdź mapowanie poniżej.';
                    renderMapping(data.preview_rows, data.candidate_columns);
                })
                .catch(err => {
                    status.textContent = 'Błąd: ' + err.message;
                    status.className = 'mt-3 text-center text-sm font-semibold text-rose-600';
                });
        }

        function renderMapping(rows, cand) {
            const box = document.getElementById('mapping-box');
            box.classList.remove('hidden');

            const selHeader = document.getElementById('map-header-row');
            const selProd = document.getElementById('map-col-product');
            const selPrice = document.getElementById('map-col-price');
            const selUnit = document.getElementById('map-col-unit');

            selHeader.innerHTML = '';
            selProd.innerHTML = '';
            selPrice.innerHTML = '';
            selUnit.innerHTML = '<option value="">-- Domyślnie (kg / szt.) --</option>';

            rows.slice(0, 10).forEach(r => {
                const opt = document.createElement('option');
                opt.value = r.row_index;
                opt.textContent = 'Wiersz ' + r.row_index + ': ' + Object.values(r.cells).slice(0, 3).join(' | ');
                if (r.row_index === cand.header_row_index) opt.selected = true;
                selHeader.appendChild(opt);
            });

            // Kolumny
            const firstRowCells = rows[0] ? rows[0].cells : {};
            Object.keys(firstRowCells).forEach(cIdx => {
                const colLetter = String.fromCharCode(65 + parseInt(cIdx));
                const label = 'Kolumna ' + colLetter + ' (' + (firstRowCells[cIdx] || '') + ')';

                const oP = new Option(label, cIdx, false, parseInt(cIdx) === cand.product_col_index);
                selProd.appendChild(oP);

                const oPr = new Option(label, cIdx, false, parseInt(cIdx) === cand.price_col_index);
                selPrice.appendChild(oPr);

                const oU = new Option(label, cIdx, false, cand.unit_col_index !== null && parseInt(cIdx) === cand.unit_col_index);
                selUnit.appendChild(oU);
            });
        }

        function cancelMapping() {
            document.getElementById('mapping-box').classList.add('hidden');
            document.getElementById('upload-status').classList.add('hidden');
        }

        document.getElementById('btn-process-import').addEventListener('click', () => {
            if (!currentFileId) return;
            const btn = document.getElementById('btn-process-import');
            btn.disabled = true;
            btn.textContent = 'Wdrażanie...';

            const fd = new FormData();
            fd.append('file_id', currentFileId);
            fd.append('header_row', document.getElementById('map-header-row').value);
            fd.append('col_product', document.getElementById('map-col-product').value);
            fd.append('col_price', document.getElementById('map-col-price').value);
            fd.append('col_unit', document.getElementById('map-col-unit').value);
            fd.append('_csrf', CSRF_TOKEN);

            fetch(BASE_URL + 'b2b/processimport', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (!data.ok) throw new Error(data.error || 'Błąd importu');
                    alert('Sukces! Zaimportowano ' + data.total_imported + ' pozycji do oferty hurtowni.');
                    window.location.reload();
                })
                .catch(err => {
                    alert('Błąd: ' + err.message);
                    btn.disabled = false;
                    btn.textContent = 'Wdróż ten cennik do oferty B2B';
                });
        });

        // Edycja produktu na żywo
        function saveProduct(id) {
            const fd = new FormData();
            fd.append('id', id);
            fd.append('name', document.getElementById('name-' + id).value);
            fd.append('category', document.getElementById('cat-' + id).value);
            fd.append('price', document.getElementById('price-' + id).value);
            fd.append('unit', document.getElementById('unit-' + id).value);
            fd.append('package_size', document.getElementById('pkg-size-' + id).value);
            fd.append('package_unit', document.getElementById('pkg-unit-' + id).value);
            fd.append('_csrf', CSRF_TOKEN);

            fetch(BASE_URL + 'b2b/updateproduct', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.ok) showToast('Zapisano zmiany w produkcie!');
                    else alert(d.error || 'Błąd zapisu');
                });
        }

        function toggleProduct(id) {
            const fd = new FormData();
            fd.append('id', id);
            fd.append('_csrf', CSRF_TOKEN);

            fetch(BASE_URL + 'b2b/toggleproduct', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.ok) window.location.reload();
                });
        }

        // Dodanie klienta
        document.getElementById('form-create-client').addEventListener('submit', (e) => {
            e.preventDefault();
            const fd = new FormData();
            fd.append('company_name', document.getElementById('new-client-name').value);
            fd.append('nip', document.getElementById('new-client-nip').value);
            fd.append('phone', document.getElementById('new-client-phone').value);
            fd.append('email', document.getElementById('new-client-email').value);
            fd.append('delivery_address', document.getElementById('new-client-address').value);
            fd.append('_csrf', CSRF_TOKEN);

            fetch(BASE_URL + 'b2b/createclient', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (!d.ok) throw new Error(d.error || 'Błąd tworzenia klienta');
                    alert('Klient utworzony! Link dostępu dla odbiorcy: ' + d.token_url);
                    window.location.reload();
                })
                .catch(err => alert(err.message));
        });

        function toggleClient(id) {
            const fd = new FormData();
            fd.append('id', id);
            fd.append('_csrf', CSRF_TOKEN);

            fetch(BASE_URL + 'b2b/toggleclient', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.ok) window.location.reload();
                });
        }

        function updateOrderStatus(id, status) {
            const fd = new FormData();
            fd.append('id', id);
            fd.append('status', status);
            fd.append('_csrf', CSRF_TOKEN);

            fetch(BASE_URL + 'b2b/updateorderstatus', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.ok) showToast('Zaktualizowano status zamówienia!');
                });
        }

        function showOrderModal(id) {
            fetch(BASE_URL + 'b2b/orderdetails?id=' + id)
                .then(r => r.json())
                .then(d => {
                    if (!d.ok) return alert('Błąd pobierania szczegółów');
                    document.getElementById('modal-order-number').textContent = 'Zamówienie ' + d.order.order_number;
                    document.getElementById('modal-order-client').textContent = d.order.client_name_snapshot + ' (tel. ' + (d.order.client_phone_snapshot || '—') + ')';
                    document.getElementById('modal-order-address').textContent = d.order.delivery_address_snapshot || 'Brak';
                    document.getElementById('modal-order-notes').textContent = d.order.notes || 'Brak uwag';
                    document.getElementById('modal-order-total').textContent = Number(d.order.total_amount).toFixed(2) + ' zł';

                    const tbody = document.getElementById('modal-order-items');
                    tbody.innerHTML = '';
                    d.items.forEach(i => {
                        tbody.innerHTML += `
                            <tr>
                                <td class="py-2 font-bold text-slate-800">${i.product_name}</td>
                                <td class="py-2 text-right font-bold text-slate-900">${i.quantity} ${i.unit}</td>
                                <td class="py-2 text-center text-xs font-semibold text-emerald-700 bg-emerald-50 rounded">${i.package_summary || '—'}</td>
                                <td class="py-2 text-right font-bold text-slate-800">${Number(i.item_total).toFixed(2)} zł</td>
                            </tr>
                        `;
                    });
                    document.getElementById('modal-order').classList.remove('hidden');
                });
        }

        function closeOrderModal() {
            document.getElementById('modal-order').classList.add('hidden');
        }
    </script>
</body>
</html>
