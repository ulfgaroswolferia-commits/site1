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
        @media print {
            body { background: white !important; color: #000 !important; }
            .bg-grid, header, nav, #tab-products, #tab-clients, .no-print, [role="tablist"], button { display: none !important; }
            #modal-order { position: static !important; display: block !important; background: none !important; padding: 0 !important; }
            #modal-order > div { box-shadow: none !important; border: 1px solid #cbd5e1 !important; max-width: 100% !important; max-height: none !important; }
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

        <?php
        $newOrdersCount = count(array_filter($orders ?? [], fn($o) => ($o['status'] ?? '') === 'new'));
        ?>
        <!-- Zakładki nawigacyjne -->
        <nav class="flex border-b border-slate-200 gap-2 overflow-x-auto pb-1">
            <button type="button" id="tab-btn-products" onclick="switchTab('products')" class="px-5 py-2.5 font-bold text-sm rounded-xl transition flex items-center gap-2 bg-emerald-600 text-white shadow-md shadow-emerald-600/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                <span>Aktualny cennik</span>
                <span id="badge-products-count" class="ml-1 px-2 py-0.5 text-xs rounded-full bg-white/20 text-white"><?= count($products) ?></span>
            </button>

            <button type="button" id="tab-btn-orders" onclick="switchTab('orders')" class="px-5 py-2.5 font-bold text-sm rounded-xl transition flex items-center gap-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100/80">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                <span>Zamówienia</span>
                <span id="badge-orders-count" class="ml-1 px-2 py-0.5 text-xs rounded-full <?= $newOrdersCount > 0 ? 'bg-amber-500 text-white font-bold' : 'bg-slate-200 text-slate-700' ?>" title="<?= $newOrdersCount ?> nowych zamówień"><?= $newOrdersCount ?></span>
            </button>

            <button type="button" id="tab-btn-clients" onclick="switchTab('clients')" class="px-5 py-2.5 font-bold text-sm rounded-xl transition flex items-center gap-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100/80">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span>Klienci Hurtowni</span>
                <span class="ml-1 px-2 py-0.5 text-xs rounded-full bg-slate-200 text-slate-800"><?= count($clients) ?></span>
            </button>

            <button type="button" id="tab-btn-settings" onclick="switchTab('settings')" class="px-5 py-2.5 font-bold text-sm rounded-xl transition flex items-center gap-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100/80">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                <span>Ustawienia & ERP</span>
            </button>
        </nav>

        <!-- =================================================================== -->
        <!-- ZAKŁADKA 1: Aktualny cennik                                         -->
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

                    <!-- Podgląd wierszy arkusza Excel -->
                    <div class="mb-4">
                        <div class="text-xs font-bold text-slate-600 mb-2 flex items-center justify-between">
                            <span>Podgląd zawartości arkusza Excel:</span>
                            <span class="text-slate-400 font-normal text-[11px]">Wiersz wyróżniony na zielono = nagłówek</span>
                        </div>
                        <div class="overflow-x-auto border border-slate-200 rounded-xl max-h-56 overflow-y-auto bg-white shadow-inner">
                            <table class="w-full text-left text-xs" id="preview-sheet-table">
                                <thead class="bg-slate-100 text-slate-700 font-bold sticky top-0 border-b border-slate-200" id="preview-sheet-thead"></thead>
                                <tbody class="divide-y divide-slate-100" id="preview-sheet-tbody"></tbody>
                            </table>
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
                        <div id="unsaved-alert-pill" class="hidden items-center gap-1.5 px-3 py-1.5 bg-amber-50 border border-amber-300 text-amber-900 rounded-xl text-xs font-extrabold shadow-sm animate-pulse">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                            </span>
                            <span id="unsaved-count-text">0 niezatwierdzonych zmian</span>
                        </div>

                        <!-- Przycisk Zapisz wszystkie zmiany -->
                        <button type="button" id="btn-save-all-products" onclick="saveAllProducts()" class="hidden items-center gap-1.5 px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl text-xs font-extrabold shadow-md shadow-emerald-600/25 transition-all cursor-pointer" title="Zapisz i zatwierdź wszystkie zmodyfikowane pozycje na raz (Ctrl+S)">
                            <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            <span id="btn-save-all-text">Zapisz wszystkie zmiany</span>
                            <span id="btn-save-all-count" class="ml-1 px-1.5 py-0.5 rounded-full bg-white/20 text-white text-[10px] font-black">0</span>
                        </button>

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
                                <th class="py-3.5 px-4 font-bold w-32">Kod ERP</th>
                                <th class="py-3.5 px-4 font-bold">Kategoria</th>
                                <th class="py-3.5 px-4 font-bold text-right w-36">Cena hurtowa</th>
                                <th class="py-3.5 px-4 font-bold text-center w-24">Jedn.</th>
                                <th class="py-3.5 px-4 font-bold w-48">Opakowanie zbiorcze (klatka/skrzynka)</th>
                                <th class="py-3.5 px-4 font-bold text-center w-28">Zapisz</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="products-tbody">
                            <?php if (!empty($products)): ?>
                                <?php foreach ($products as $p): ?>
                                    <?php
                                        $uRaw = mb_strtolower(trim((string)($p['unit'] ?? 'kg')), 'UTF-8');
                                        $uClean = str_replace('.', '', $uRaw);
                                        $isSzt = ($uClean === 'szt' || $uClean === 'sztuka' || $uClean === 'sztuki');
                                        $isPeczek = ($uClean === 'pęczek' || $uClean === 'peczek' || $uClean === 'pecz');
                                        $isOp = ($uClean === 'op' || $uClean === 'kart' || $uClean === 'skrz');
                                        $isKg = (!$isSzt && !$isPeczek && !$isOp);
                                        $unitVal = $isKg ? 'kg' : ($isSzt ? 'szt.' : ($isOp ? 'op.' : 'pęczek'));
                                        $priceVal = number_format((float)$p['price'], 2, '.', '');
                                        $pkgSizeVal = (float)$p['package_size'];
                                        $pkgUnitVal = (string)$p['package_unit'];
                                    ?>
                                    <tr class="prod-row hover:bg-slate-50/80 transition-all border-l-4 border-l-transparent <?= (int)$p['is_available'] === 0 ? 'opacity-50 bg-slate-50' : '' ?>" id="prod-row-<?= $p['id'] ?>" data-prod-id="<?= $p['id'] ?>" data-cat="<?= Tools::h($p['category']) ?>" data-name="<?= Tools::h(mb_strtolower($p['name'])) ?>">
                                        <!-- Przełącznik In stock / Out of stock -->
                                        <td class="py-3 px-4 text-center">
                                            <button type="button" onclick="toggleProduct(<?= $p['id'] ?>)" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold transition <?= (int)$p['is_available'] === 1 ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-200 text-slate-600 hover:bg-slate-300' ?>">
                                                <?= (int)$p['is_available'] === 1 ? 'W ofercie' : 'Brak' ?>
                                            </button>
                                        </td>
                                        <!-- Nazwa -->
                                        <td class="py-3 px-4 font-bold text-slate-900">
                                            <div class="flex items-center gap-2">
                                                <input type="text" id="name-<?= $p['id'] ?>" data-prod-id="<?= $p['id'] ?>" data-field-name="name" data-initial="<?= Tools::h($p['name']) ?>" value="<?= Tools::h($p['name']) ?>" class="prod-field w-full bg-transparent border-b border-transparent hover:border-slate-300 focus:border-emerald-500 focus:bg-white px-1.5 py-0.5 rounded text-sm font-bold transition">
                                                <?php if (!empty($p['is_new'])): ?>
                                                    <span id="new-badge-<?= $p['id'] ?>" class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-sky-100 text-sky-800 border border-sky-300/70 shadow-2xs" title="Nowy artykuł z cennika — sprawdź jednostkę i opakowanie zbiorcze">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-pulse"></span>
                                                        Nowość
                                                    </span>
                                                <?php endif; ?>
                                                <span id="dirty-badge-<?= $p['id'] ?>" class="hidden shrink-0 items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-900 border border-amber-300/80 shadow-xs" title="Pozycja zmodyfikowana — wymaga zatwierdzenia">
                                                    Edytowano
                                                </span>
                                            </div>
                                        </td>
                                        <!-- Kod ERP -->
                                        <td class="py-3 px-4">
                                            <input type="text" id="erp-<?= $p['id'] ?>" data-prod-id="<?= $p['id'] ?>" data-field-name="erp_code" data-initial="<?= Tools::h($p['erp_code'] ?? '') ?>" value="<?= Tools::h($p['erp_code'] ?? '') ?>" placeholder="—" class="prod-field w-28 uppercase text-xs font-mono text-slate-700 bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white rounded-lg px-2 py-1 transition focus:ring-1 focus:ring-indigo-500" title="Kod artykułu w systemie ERP (Subiekt / Optima / Symfonia / Wf-Mag)">
                                        </td>
                                        <!-- Kategoria -->
                                        <td class="py-3 px-4">
                                            <select id="cat-<?= $p['id'] ?>" data-prod-id="<?= $p['id'] ?>" data-field-name="category" data-initial="<?= Tools::h($p['category']) ?>" class="prod-field text-xs font-medium border border-slate-200 rounded-lg p-1 bg-white transition focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
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
                                                <input type="number" step="0.01" min="0" id="price-<?= $p['id'] ?>" data-prod-id="<?= $p['id'] ?>" data-field-name="price" data-initial="<?= $priceVal ?>" value="<?= $priceVal ?>" class="prod-field w-24 text-right text-sm font-extrabold text-blue-700 border border-slate-200 focus:border-emerald-500 rounded-lg p-1 transition focus:ring-1 focus:ring-emerald-500">
                                                <span class="text-xs font-bold text-slate-500">zł</span>
                                            </div>
                                        </td>
                                        <!-- Jednostka -->
                                        <td class="py-3 px-4 text-center">
                                            <select id="unit-<?= $p['id'] ?>" data-prod-id="<?= $p['id'] ?>" data-field-name="unit" data-initial="<?= $unitVal ?>" class="prod-field text-xs font-medium border border-slate-200 rounded-lg p-1 bg-white transition focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                                                <option value="kg" <?= $isKg ? 'selected' : '' ?>>kg</option>
                                                <option value="szt." <?= $isSzt ? 'selected' : '' ?>>szt.</option>
                                                <option value="op." <?= $isOp ? 'selected' : '' ?>>op.</option>
                                                <option value="pęczek" <?= $isPeczek ? 'selected' : '' ?>>pęczek</option>
                                            </select>
                                        </td>
                                        <!-- Opakowanie zbiorcze -->
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-1.5">
                                                <input type="number" step="0.5" min="1" id="pkg-size-<?= $p['id'] ?>" data-prod-id="<?= $p['id'] ?>" data-field-name="package_size" data-initial="<?= $pkgSizeVal ?>" value="<?= $pkgSizeVal ?>" class="prod-field w-16 text-center text-xs font-bold border border-slate-200 focus:border-emerald-500 rounded-lg p-1 transition focus:ring-1 focus:ring-emerald-500">
                                                <select id="pkg-unit-<?= $p['id'] ?>" data-prod-id="<?= $p['id'] ?>" data-field-name="package_unit" data-initial="<?= Tools::h($pkgUnitVal) ?>" class="prod-field text-xs font-medium border border-slate-200 rounded-lg p-1 bg-white transition focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                                                    <option value="klatka" <?= $p['package_unit'] === 'klatka' ? 'selected' : '' ?>>klatka</option>
                                                    <option value="skrzynka" <?= $p['package_unit'] === 'skrzynka' ? 'selected' : '' ?>>skrzynka</option>
                                                    <option value="karton" <?= $p['package_unit'] === 'karton' ? 'selected' : '' ?>>karton</option>
                                                    <option value="worek" <?= $p['package_unit'] === 'worek' ? 'selected' : '' ?>>worek</option>
                                                    <option value="op." <?= $p['package_unit'] === 'op.' ? 'selected' : '' ?>>op.</option>
                                                </select>
                                            </div>
                                        </td>
                                        <!-- Zapis -->
                                        <td class="py-3 px-4 text-center whitespace-nowrap">
                                            <button type="button" id="save-btn-<?= $p['id'] ?>" onclick="saveProduct(<?= $p['id'] ?>)" class="save-btn inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-emerald-700 hover:bg-emerald-50 border border-transparent transition-all" title="Zapisz zmiany">
                                                <svg id="save-icon-<?= $p['id'] ?>" class="w-4 h-4 shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                <span id="save-label-<?= $p['id'] ?>" class="hidden font-bold">Zapisz</span>
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
        <!-- ZAKŁADKA 2: Zamówienia B2B                                          -->
        <!-- =================================================================== -->
        <main id="tab-orders" class="space-y-6 hidden">
            <?php
            $countOrdersNew = 0;
            $countOrdersProcessing = 0;
            $countOrdersCompleted = 0;
            $countOrdersCancelled = 0;
            $countOrdersAll = count($orders);
            foreach ($orders as $o) {
                if (($o['status'] ?? '') === 'new') $countOrdersNew++;
                elseif (($o['status'] ?? '') === 'processing') $countOrdersProcessing++;
                elseif (($o['status'] ?? '') === 'completed') $countOrdersCompleted++;
                elseif (($o['status'] ?? '') === 'cancelled') $countOrdersCancelled++;
            }
            ?>
            <section class="bg-white/95 border border-slate-200 rounded-2xl shadow-sm overflow-hidden p-5">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Zamówienia ze Sklepów B2B</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Śledź zamówienia na bieżąco, zmieniaj statusy i generuj dokumenty dla logistyki do kompletacji i wysyłki.</p>
                    </div>

                    <!-- Filtry po statusie zamówienia (domyślnie Nowe) -->
                    <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200/80 gap-1 flex-wrap" role="tablist">
                        <button type="button" id="order-filter-new" onclick="filterOrdersByStatus('new')" class="order-filter-btn px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 bg-white text-emerald-700 shadow-2xs border border-emerald-200/60" data-status="new">
                            <span>Nowe</span>
                            <span class="px-1.5 py-0.2 text-[10px] rounded-full bg-emerald-100 text-emerald-800 font-extrabold" id="order-filter-count-new"><?= $countOrdersNew ?></span>
                        </button>
                        <button type="button" id="order-filter-processing" onclick="filterOrdersByStatus('processing')" class="order-filter-btn px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5" data-status="processing">
                            <span>W kompletacji</span>
                            <span class="px-1.5 py-0.2 text-[10px] rounded-full bg-amber-100 text-amber-800 font-extrabold" id="order-filter-count-processing"><?= $countOrdersProcessing ?></span>
                        </button>
                        <button type="button" id="order-filter-completed" onclick="filterOrdersByStatus('completed')" class="order-filter-btn px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5" data-status="completed">
                            <span>Zrealizowane</span>
                            <span class="px-1.5 py-0.2 text-[10px] rounded-full bg-slate-200 text-slate-700 font-extrabold" id="order-filter-count-completed"><?= $countOrdersCompleted ?></span>
                        </button>
                        <button type="button" id="order-filter-cancelled" onclick="filterOrdersByStatus('cancelled')" class="order-filter-btn px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5" data-status="cancelled">
                            <span>Anulowane</span>
                            <span class="px-1.5 py-0.2 text-[10px] rounded-full bg-rose-100 text-rose-700 font-extrabold" id="order-filter-count-cancelled"><?= $countOrdersCancelled ?></span>
                        </button>
                        <button type="button" id="order-filter-all" onclick="filterOrdersByStatus('all')" class="order-filter-btn px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5" data-status="all">
                            <span>Wszystkie</span>
                            <span class="px-1.5 py-0.2 text-[10px] rounded-full bg-slate-200 text-slate-700 font-extrabold" id="order-filter-count-all"><?= $countOrdersAll ?></span>
                        </button>
                    </div>

                    <!-- Pobieranie paczki ERP -->
                    <div class="inline-flex items-center rounded-xl bg-slate-100 border border-slate-200/80 p-1 shadow-2xs gap-1">
                        <span class="text-xs font-bold text-slate-700 px-2 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Paczka ERP:
                        </span>
                        <select id="batch-erp-format" onchange="savePreferredErp(this.value)" class="text-xs font-bold bg-white border border-slate-200 rounded-lg px-2 py-1 text-slate-800 focus:outline-none cursor-pointer">
                            <option value="subiekt">Subiekt GT / Nexo (.epp)</option>
                            <option value="optima">Comarch Optima (.xml)</option>
                            <option value="symfonia">Symfonia (.txt)</option>
                            <option value="wfmag">Wf-Mag (.xml)</option>
                        </select>
                        <button type="button" onclick="downloadBatchErp()" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-lg shadow-2xs transition flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <span>Pobierz paczkę</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm" id="orders-table">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4 font-bold">NUMER / DATA</th>
                                <th class="py-3.5 px-4 font-bold">Klient / Sklep</th>
                                <th class="py-3.5 px-4 font-bold">Telefon</th>
                                <th class="py-3.5 px-4 font-bold text-center">Pozycje</th>
                                <th class="py-3.5 px-4 font-bold text-right">Wartość</th>
                                <th class="py-3.5 px-4 font-bold text-center">Status</th>
                                <th class="py-3.5 px-4 font-bold text-center">Akcja</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="orders-tbody">
                            <?php if (!empty($orders)): ?>
                                <?php foreach ($orders as $o): ?>
                                    <tr class="hover:bg-slate-50/80 transition order-data-row" id="order-row-<?= $o['id'] ?>" data-order-status="<?= Tools::h($o['status']) ?>">
                                        <td class="py-3.5 px-4">
                                            <div class="flex flex-col items-start gap-1">
                                                <button type="button" onclick="showOrderModal(<?= $o['id'] ?>)" class="font-extrabold text-blue-700 hover:text-blue-900 hover:underline text-left cursor-pointer transition text-sm order-details-btn-<?= $o['id'] ?>" title="Kliknij, aby otworzyć szczegóły zamówienia">
                                                    <?= Tools::h($o['order_number']) ?>
                                                </button>
                                                <div class="flex items-center gap-1.5 text-xs text-slate-400 font-medium">
                                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                    <span><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></span>
                                                </div>
                                                <?php if (!empty($o['delivery_date'])): ?>
                                                    <div class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/80 mt-0.5" title="Wybrana data dostawy">
                                                        <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                        <span>Dostawa: <?= date('d.m.Y', strtotime($o['delivery_date'])) ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <button type="button" onclick="showOrderModal(<?= $o['id'] ?>)" class="inline-flex items-center gap-1 text-[11px] font-bold text-sky-600 hover:text-sky-800 hover:underline cursor-pointer transition mt-0.5" title="Zobacz pozycje i dane zamówienia">
                                                    <svg class="w-3 h-3 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    <span>Szczegóły zamówienia</span>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4 font-bold text-slate-900"><?= Tools::h($o['client_name_snapshot']) ?></td>
                                        <td class="py-3.5 px-4 text-xs font-semibold text-slate-600"><?= Tools::h($o['client_phone_snapshot'] ?: '—') ?></td>
                                        <td class="py-3.5 px-4 text-center font-bold text-slate-700"><?= (int)$o['total_items'] ?></td>
                                        <td class="py-3.5 px-4 text-right font-extrabold text-slate-900"><?= number_format((float)$o['total_amount'], 2, '.', ' ') ?> zł</td>
                                        <td class="py-3.5 px-4 text-center">
                                            <select id="order-status-select-<?= $o['id'] ?>" onchange="updateOrderStatus(<?= $o['id'] ?>, this.value)" class="text-xs font-bold rounded-lg px-2 py-1 border border-slate-200 focus:outline-none">
                                                <option value="new" <?= $o['status'] === 'new' ? 'selected' : '' ?>>Nowe</option>
                                                <option value="processing" <?= $o['status'] === 'processing' ? 'selected' : '' ?>>W kompletacji</option>
                                                <option value="completed" <?= $o['status'] === 'completed' ? 'selected' : '' ?>>Zrealizowane</option>
                                                <option value="cancelled" <?= $o['status'] === 'cancelled' ? 'selected' : '' ?>>Anulowane</option>
                                            </select>
                                        </td>
                                        <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                            <button type="button" onclick="showOrderModal(<?= $o['id'] ?>)" class="btn-finalize-order inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-2xs hover:shadow transition" title="Otwórz podsumowanie i specyfikację kompletacji zamówienia">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                <span>Finalizuj zamówienie</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr id="orders-filter-empty-row" class="hidden">
                                    <td colspan="7" class="py-8 text-center text-slate-400 font-medium">
                                        Brak zamówień o statusie: <strong id="orders-filter-empty-label" class="text-slate-600">Nowe</strong>.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-400 font-medium">Brak złożonych zamówień w historii.</td>
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
                <div class="flex items-center justify-between mb-4 flex-wrap gap-4">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Baza Odbiorców B2B</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Lista klientów posortowana chronologicznie — od ostatnio zarejestrowanego odbiorcy</p>
                    </div>
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <!-- Wyszukiwarka klientów po nazwie -->
                        <div class="relative flex-1 sm:w-72">
                            <input id="client-search-admin" type="text" placeholder="Szukaj klienta po nazwie / NIP / tel..." class="w-full text-xs font-medium pl-8 pr-8 py-2 border border-slate-300 rounded-xl focus:outline-none focus:border-emerald-500 shadow-2xs">
                            <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <button type="button" id="client-search-clear" onclick="clearClientSearch()" class="hidden absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600" title="Wyczyść szukanie">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <span id="clients-count-badge" class="text-xs font-bold text-slate-600 bg-slate-100 border border-slate-200 px-3 py-2 rounded-xl whitespace-nowrap shadow-2xs">
                            <?= count($clients) ?> odbiorców
                        </span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm" id="clients-table">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4 font-bold">Sklep / Odbiorca</th>
                                <th class="py-3.5 px-4 font-bold">Telefon</th>
                                <th class="py-3.5 px-4 font-bold">E-mail</th>
                                <th class="py-3.5 px-4 font-bold">Adres dostawy</th>
                                <th class="py-3.5 px-4 font-bold text-center">Status</th>
                                <th class="py-3.5 px-4 font-bold text-center">Dostęp i Wysyłka Linku</th>
                                <th class="py-3.5 px-4 font-bold text-center">Akcje</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="clients-tbody">
                            <?php if (!empty($clients)): ?>
                                <?php foreach ($clients as $c): ?>
                                    <?php $tokenUrl = $base . 'b2b?token=' . $c['auth_token']; ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors client-data-row" id="client-row-<?= $c['id'] ?>" data-client-name="<?= mb_strtolower(Tools::h($c['company_name']), 'UTF-8') ?>" data-client-nip="<?= mb_strtolower(Tools::h($c['nip'] ?? ''), 'UTF-8') ?>" data-client-phone="<?= mb_strtolower(Tools::h($c['phone'] ?? ''), 'UTF-8') ?>">
                                        <td class="py-3 px-4 font-extrabold text-slate-900">
                                            <div class="flex items-center gap-2">
                                                <span id="client-name-display-<?= $c['id'] ?>"><?= Tools::h($c['company_name']) ?></span>
                                            </div>
                                            <div class="flex items-center gap-2 text-xs font-normal text-slate-400 mt-0.5">
                                                <span id="client-nip-display-<?= $c['id'] ?>" class="<?= empty($c['nip']) ? 'hidden' : '' ?>">NIP: <?= Tools::h($c['nip'] ?? '') ?></span>
                                                <?php if (!empty($c['created_at'])): ?>
                                                    <span class="text-slate-300 <?= empty($c['nip']) ? 'hidden' : '' ?>">•</span>
                                                    <span class="text-slate-400" title="Data rejestracji">Dodano: <?= date('d.m.Y H:i', strtotime($c['created_at'])) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 font-semibold text-slate-700">
                                            <span id="client-phone-display-<?= $c['id'] ?>"><?= Tools::h($c['phone'] ?: '—') ?></span>
                                        </td>
                                        <td class="py-3 px-4 text-xs text-slate-600">
                                            <span id="client-email-display-<?= $c['id'] ?>"><?= Tools::h($c['email'] ?: '—') ?></span>
                                        </td>
                                        <td class="py-3 px-4 text-xs text-slate-600">
                                            <span id="client-address-display-<?= $c['id'] ?>"><?= Tools::h($c['delivery_address'] ?: '—') ?></span>
                                        </td>
                                        <td class="py-3 px-4 text-center whitespace-nowrap">
                                            <button type="button" id="client-status-btn-<?= $c['id'] ?>" onclick="toggleClient(<?= $c['id'] ?>)" class="text-xs font-bold px-2.5 py-1 rounded-lg <?= (int)$c['is_active'] === 1 ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-200 text-slate-600 hover:bg-slate-300' ?> transition">
                                                <?= (int)$c['is_active'] === 1 ? 'Aktywny' : 'Zablokowany' ?>
                                            </button>
                                        </td>
                                        <td class="py-3 px-4 text-center whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1.5 flex-wrap justify-center">
                                                <!-- Kopiuj link -->
                                                <button type="button" onclick="copyToken('<?= $tokenUrl ?>')" class="copy-token inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold text-sky-700 bg-sky-50 border border-sky-200 rounded-xl hover:bg-sky-100 transition shadow-2xs" title="Skopiuj bezpośredni link logowania klienta do schowka">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                                    <span>Kopiuj</span>
                                                </button>

                                                <!-- Wyślij E-mail -->
                                                <button type="button" id="btn-send-email-<?= $c['id'] ?>" onclick="sendTokenEmail(<?= $c['id'] ?>)" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-xl hover:bg-indigo-100 transition shadow-2xs" title="<?= !empty($c['email']) ? 'Wyślij bezpośredni link dostępowy na e-mail: ' . Tools::h($c['email']) : 'Brak e-maila klienta' ?>">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                                    <span>E-mail</span>
                                                </button>

                                                <!-- Wyślij SMS / Tel -->
                                                <button type="button" onclick="openSendSmsModal(<?= $c['id'] ?>)" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl hover:bg-emerald-100 transition shadow-2xs" title="Wyślij link SMS-em lub przez WhatsApp">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                                    <span>SMS / Tel</span>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-center whitespace-nowrap">
                                            <button type="button" onclick="openEditClientModal(<?= $c['id'] ?>)" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 rounded-xl transition border border-slate-200 shadow-2xs" title="Edytuj dane odbiorcy">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                <span>Edytuj</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr id="no-clients-search-row" class="hidden">
                                    <td colspan="7" class="py-10 text-center text-slate-400 font-medium">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                            <p>Nie znaleziono klientów pasujących do frazy: <strong id="no-clients-search-term" class="text-slate-700"></strong></p>
                                            <button type="button" onclick="clearClientSearch()" class="mt-1 text-xs font-bold text-emerald-600 hover:text-emerald-700 underline">Wyczyść filtr wyszukiwania</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-400 font-medium">Brak dodanych odbiorców. Dodaj pierwszego klienta powyżej.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <!-- =================================================================== -->
        <!-- ZAKŁADKA 4: Ustawienia hurtowni & ERP                               -->
        <!-- =================================================================== -->
        <main id="tab-settings" class="space-y-6 hidden">
            <?php
            $cutoffVal   = $settings['cutoff_time'] ?? '21:30';
            $daysVal     = explode(',', $settings['delivery_days'] ?? 'mon,tue,wed,thu,fri,sat');
            $defaultFmt  = $settings['default_erp_format'] ?? 'subiekt';
            ?>
            <section class="bg-white/95 border border-slate-200 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200 flex-wrap gap-2">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Harmonogram Przyjmowania Zamówień & Eksport ERP
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Konfiguruj godzinę graniczną (cut-off time), dni realizacji dostaw oraz domyślny program magazynowo-księgowy.</p>
                    </div>
                </div>

                <form id="settings-form" onsubmit="saveWholesaleSettings(event)" class="space-y-6 max-w-2xl">
                    <!-- 1. Godzina graniczna (Cut-off Time) -->
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                        <label for="settings-cutoff" class="block font-bold text-sm text-slate-800 mb-1">
                            Godzina graniczna składania zamówień na kolejny dzień roboczy (Cut-off Time)
                        </label>
                        <p class="text-xs text-slate-500 mb-3">
                            Po tej godzinie system informuje zamawiającego sklep, że dostawy na jutro rano są już zamknięte i automatycznie proponuje dostawę na kolejny dostępny dzień roboczy.
                        </p>
                        <div class="flex items-center gap-3">
                            <input type="time" id="settings-cutoff" name="cutoff_time" value="<?= Tools::h($cutoffVal) ?>" class="text-base font-extrabold text-slate-800 bg-white border border-slate-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs" required>
                            <span class="text-xs font-semibold text-slate-500">Domyślnie: 21:30</span>
                        </div>
                    </div>

                    <!-- 2. Dni dostaw -->
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                        <label class="block font-bold text-sm text-slate-800 mb-1">
                            Dni realizacji dostaw towaru do sklepów
                        </label>
                        <p class="text-xs text-slate-500 mb-3">
                            Zaznacz dni tygodnia, w których kierowcy hurtowni rozwożą towar do klientów. W pozostałe dni (np. niedziele) zamówienia nie są realizowane.
                        </p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                            <?php
                            $weekDays = [
                                'mon' => 'Poniedziałek',
                                'tue' => 'Wtorek',
                                'wed' => 'Środa',
                                'thu' => 'Czwartek',
                                'fri' => 'Piątek',
                                'sat' => 'Sobota',
                                'sun' => 'Niedziela',
                            ];
                            foreach ($weekDays as $code => $lbl):
                                $chk = in_array($code, $daysVal, true) ? 'checked' : '';
                            ?>
                                <label class="flex items-center gap-2 p-2.5 rounded-lg border bg-white border-slate-200 cursor-pointer hover:bg-slate-100/80 transition text-xs font-bold text-slate-700">
                                    <input type="checkbox" name="delivery_day" value="<?= $code ?>" <?= $chk ?> class="settings-day-chk rounded text-indigo-600 focus:ring-indigo-500">
                                    <span><?= $lbl ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 3. Domyślny format ERP -->
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                        <label for="settings-erp-format" class="block font-bold text-sm text-slate-800 mb-1">
                            Główny system handlowo-magazynowy (ERP)
                        </label>
                        <p class="text-xs text-slate-500 mb-3">
                            Wybierz format eksportu, z którego korzysta Twoja hurtownia. Wybór będzie domyślnie podpowiadany przy pobieraniu pojedynczych zamówień i paczek zbiorczych.
                        </p>
                        <select id="settings-erp-format" name="default_erp_format" class="w-full sm:w-80 text-sm font-bold bg-white border border-slate-300 rounded-xl px-4 py-2.5 text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-2xs cursor-pointer">
                            <option value="subiekt" <?= $defaultFmt === 'subiekt' ? 'selected' : '' ?>>InsERT Subiekt GT / Nexo (.epp / EDI++)</option>
                            <option value="optima" <?= $defaultFmt === 'optima' ? 'selected' : '' ?>>Comarch ERP Optima (.xml)</option>
                            <option value="symfonia" <?= $defaultFmt === 'symfonia' ? 'selected' : '' ?>>Symfonia Handel (.txt)</option>
                            <option value="wfmag" <?= $defaultFmt === 'wfmag' ? 'selected' : '' ?>>Asseco WAPRO / Wf-Mag (.xml)</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" id="btn-save-settings" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl shadow-md shadow-indigo-600/20 transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Zapisz ustawienia</span>
                        </button>
                        <span id="settings-status-msg" class="text-xs font-bold text-emerald-600 hidden">Zapisano pomyślnie!</span>
                    </div>
                </form>

                <!-- Instrukcja importu ERP -->
                <div class="mt-8 pt-6 border-t border-slate-200">
                    <h3 class="text-xs font-extrabold uppercase text-slate-400 tracking-wider mb-4">Informacje o obsługiwanych formatach importu ERP:</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/70">
                            <div class="text-xs font-black text-indigo-700 mb-1">Subiekt GT / Nexo</div>
                            <p class="text-[11px] text-slate-600 leading-relaxed">Format EPP (EDI++ ANSI Windows-1250). W Subiekcie: <em>Operacje -&gt; Dodaj na podstawie... -&gt; Komunikacja EDI++</em>. Automatycznie dopasowuje kontrahenta po NIP i towary po kodzie.</p>
                        </div>
                        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/70">
                            <div class="text-xs font-black text-sky-700 mb-1">Comarch ERP Optima</div>
                            <p class="text-[11px] text-slate-600 leading-relaxed">Format XML UTF-8 (Optima Offline Document Import). W Optimie: <em>Narzędzia -&gt; Praca rozproszona -&gt; Eksport/Import XML</em>. Tworzy dokument RO (Rezerwacja Odbiorcy).</p>
                        </div>
                        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/70">
                            <div class="text-xs font-black text-purple-700 mb-1">Symfonia Handel</div>
                            <p class="text-[11px] text-slate-600 leading-relaxed">Format tekstowy TXT (Windows-1250 sekcje <code>#DOKUMENT</code>, <code>#POZYCJA</code>). W Symfonii: <em>Firma -&gt; Import specjalny -&gt; Profile importu</em>. Generuje zamówienie obce ZO.</p>
                        </div>
                        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/70">
                            <div class="text-xs font-black text-emerald-700 mb-1">Asseco WAPRO Mag</div>
                            <p class="text-[11px] text-slate-600 leading-relaxed">Format XML UTF-8 (Dokumenty Magazynowe WAPRO). W Wf-Mag: <em>Inne -&gt; Wymiana danych -&gt; Import dokumentów XML</em>. Tworzy zamówienie od klienta ZK.</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal szczegółów zamówienia -->
    <div id="modal-order" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden animate-fade-in">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl overflow-hidden border border-slate-200 flex flex-col max-h-[90vh]">
            <div class="p-5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 bg-slate-50">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900" id="modal-order-number">Szczegóły Zamówienia</h3>
                    <p class="text-xs text-slate-500 mt-0.5" id="modal-order-client"></p>
                </div>
                <div class="flex items-center gap-2.5">
                    <div class="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl px-3 py-1.5 shadow-2xs">
                        <label for="modal-order-status" class="text-xs font-bold text-slate-600">Status:</label>
                        <select id="modal-order-status" onchange="changeModalOrderStatus(this.value)" class="text-xs font-bold text-slate-800 bg-transparent border-none focus:outline-none cursor-pointer">
                            <option value="new">Nowe</option>
                            <option value="processing">W kompletacji</option>
                            <option value="completed">Zrealizowane</option>
                            <option value="cancelled">Anulowane</option>
                        </select>
                    </div>
                    <button type="button" onclick="closeOrderModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>
            <div class="p-5 overflow-y-auto space-y-4 flex-1">
                <div class="p-3 bg-slate-50 rounded-xl text-xs space-y-1">
                    <p id="modal-order-delivery-wrap"><strong>Data dostawy:</strong> <span id="modal-order-delivery-date" class="font-extrabold text-amber-800 bg-amber-100/70 px-2 py-0.5 rounded">Standardowa</span></p>
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
            <div class="p-4 border-t border-slate-200 bg-slate-50 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <span class="text-xs text-slate-500 block">Razem do zapłaty:</span>
                    <strong id="modal-order-total" class="text-lg font-black text-emerald-700"></strong>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" id="modal-order-print-btn" onclick="printOrderSpecification()" class="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-bold text-xs rounded-xl shadow-2xs transition" title="Drukuj kartę kompletacji / specyfikację zlecenia dla logistyki i kierowcy">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                        <span>Drukuj specyfikację</span>
                    </button>
                    <!-- Eksport ERP pojedynczego zamówienia -->
                    <div class="inline-flex items-center rounded-xl shadow-2xs border border-indigo-200 overflow-hidden">
                        <button type="button" id="modal-order-erp-btn" onclick="exportCurrentOrderErp()" class="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition" title="Eksportuj zamówienie do formatu wybranego programu ERP">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <span id="modal-erp-btn-label">Eksport ERP</span>
                        </button>
                        <select id="modal-erp-format" onchange="savePreferredErp(this.value); updateModalErpBtnLabel(this.value);" class="bg-indigo-700 hover:bg-indigo-800 text-white text-xs font-bold py-2 px-1.5 border-l border-indigo-500 focus:outline-none cursor-pointer">
                            <option value="subiekt">Subiekt (.epp)</option>
                            <option value="optima">Optima (.xml)</option>
                            <option value="symfonia">Symfonia (.txt)</option>
                            <option value="wfmag">Wf-Mag (.xml)</option>
                        </select>
                    </div>
                    <a id="modal-order-download-btn" href="#" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition" title="Pobierz kartę kompletacji zamówienia (.xlsx)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        <span>Pobierz Excel (.xlsx)</span>
                    </a>
                    <button type="button" onclick="closeOrderModal()" class="px-3.5 py-2 bg-slate-200 hover:bg-slate-300 font-bold text-xs text-slate-700 rounded-xl transition">
                        Zamknij
                    </button>
                </div>
            </div>

            <!-- Wyróżniona strefa finalizacji zamówienia na samym dole okna modalnego -->
            <div class="px-6 py-4 bg-gradient-to-b from-emerald-50/90 to-emerald-100/50 border-t border-emerald-200 flex flex-col items-center justify-center text-center gap-1.5">
                <button type="button" id="btn-modal-finalize-order" onclick="finalizeOrderAndPrint()" class="w-full sm:w-auto px-8 py-3 bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 hover:from-emerald-700 hover:to-teal-800 text-white font-black text-sm rounded-xl shadow-md shadow-emerald-700/25 hover:shadow-lg hover:shadow-emerald-700/35 transform hover:-translate-y-0.5 active:translate-y-0 transition flex items-center justify-center gap-2.5 cursor-pointer">
                    <svg class="w-5 h-5 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Finalizuj zamówienie</span>
                </button>
                <p class="text-xs font-bold text-emerald-900/80">
                    Drukuje specyfikację i zmienia status na Zrealizowane
                </p>
            </div>
        </div>
    </div>

    <!-- Modal edycji klienta -->
    <div id="modal-edit-client" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden animate-fade-in">
        <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl overflow-hidden border border-slate-200 flex flex-col max-h-[90vh]">
            <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                        <span class="p-1.5 bg-emerald-100 text-emerald-700 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </span>
                        Edycja Danych Klienta
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5" id="modal-edit-client-subtitle">Zmień dane kontaktowe i parametry dostępu</p>
                </div>
                <button type="button" onclick="closeEditClientModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form id="form-edit-client" class="p-6 space-y-4 overflow-y-auto">
                <input type="hidden" id="edit-client-id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nazwa sklepu / Odbiorcy *</label>
                        <input type="text" id="edit-client-name" required class="w-full text-sm border border-slate-300 rounded-xl p-2.5 font-bold focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">NIP (opcjonalnie)</label>
                        <input type="text" id="edit-client-nip" class="w-full text-sm border border-slate-300 rounded-xl p-2.5 font-medium focus:border-emerald-500 focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Telefon kontaktowy *</label>
                        <input type="tel" id="edit-client-phone" required class="w-full text-sm border border-slate-300 rounded-xl p-2.5 font-bold focus:border-emerald-500 focus:outline-none">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Adres e-mail (do wysyłki linku i powiadomień)</label>
                        <input type="email" id="edit-client-email" class="w-full text-sm border border-slate-300 rounded-xl p-2.5 font-medium focus:border-emerald-500 focus:outline-none">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Adres dostawy towaru</label>
                        <input type="text" id="edit-client-address" class="w-full text-sm border border-slate-300 rounded-xl p-2.5 font-medium focus:border-emerald-500 focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Login (opcjonalny)</label>
                        <input type="text" id="edit-client-login" class="w-full text-sm border border-slate-300 rounded-xl p-2.5 font-medium focus:border-emerald-500 focus:outline-none" placeholder="Pozostaw puste dla logowania linkiem">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nowe hasło (opcjonalnie)</label>
                        <input type="password" id="edit-client-password" class="w-full text-sm border border-slate-300 rounded-xl p-2.5 font-medium focus:border-emerald-500 focus:outline-none" placeholder="Wypełnij tylko, aby zmienić">
                    </div>

                    <div class="md:col-span-2 bg-slate-50 border border-slate-200 rounded-xl p-3.5">
                        <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center justify-between">
                            <span>Status konta odbiorcy B2B</span>
                            <span class="text-[11px] font-normal text-slate-400">Blokada uniemożliwia logowanie i składanie zamówień</span>
                        </label>
                        <select id="edit-client-status" class="w-full text-sm border border-slate-300 rounded-xl p-2.5 font-bold bg-white focus:border-emerald-500 focus:outline-none">
                            <option value="1">🟢 Aktywny (Klient ma dostęp do składania zamówień)</option>
                            <option value="0">⛔ Zablokowany (Dostęp do sklepu B2B wstrzymany)</option>
                        </select>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="edit-client-regen-token" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-xs font-medium text-slate-600">Wygeneruj nowy unikalny token dostępu (unieważni poprzedni link)</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-slate-200 flex items-center justify-between gap-3">
                    <button type="button" id="btn-delete-client" onclick="deleteCurrentClient()" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 hover:text-rose-800 border border-rose-200 rounded-xl transition shadow-2xs hover:shadow" title="Usuń tego klienta na stałe z bazy danych">
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        <span>Usuń klienta</span>
                    </button>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="closeEditClientModal()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                            Anuluj
                        </button>
                        <button type="submit" id="edit-client-submit-btn" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md shadow-emerald-600/20 transition flex items-center gap-2">
                            <span>Zapisz zmiany</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal wysyłki SMS / WhatsApp -->
    <div id="modal-send-sms" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden animate-fade-in">
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl overflow-hidden border border-slate-200 flex flex-col">
            <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                        <span class="p-1.5 bg-emerald-100 text-emerald-700 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                        </span>
                        Wysyłka Linku na Telefon (SMS / WhatsApp)
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5" id="sms-modal-recipient">Odbiorca: —</p>
                </div>
                <button type="button" onclick="closeSendSmsModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Numer telefonu odbiorcy</label>
                    <input type="text" id="sms-phone-display" readonly class="w-full text-sm font-bold bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-slate-800">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Treść wiadomości z bezpośrednim linkiem (możesz edytować)</label>
                    <textarea id="sms-text-preview" rows="4" class="w-full text-xs font-medium border border-slate-300 rounded-xl p-3 focus:border-emerald-500 focus:outline-none"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-2">
                    <!-- Otwórz SMS -->
                    <a id="sms-btn-native" href="#" class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition text-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        <span>Otwórz SMS</span>
                    </a>

                    <!-- WhatsApp -->
                    <a id="sms-btn-whatsapp" href="#" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm transition text-center">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.53 1.771.78 2.791.78 3.182 0 5.77-2.587 5.77-5.766.001-3.182-2.585-5.766-5.77-5.766zm0 10.514c-.878 0-1.637-.251-2.316-.677l-.165-.104-1.579.414.421-1.54-.108-.172c-.476-.757-.746-1.564-.745-2.669.001-2.618 2.13-4.747 4.752-4.747 2.62 0 4.749 2.129 4.749 4.749.001 2.62-2.129 4.75-4.75 4.75z"/></svg>
                        <span>WhatsApp</span>
                    </a>

                    <!-- Kopiuj treść SMS -->
                    <button type="button" onclick="copySmsText()" class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition text-center border border-slate-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                        <span>Kopiuj treść</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Powiadomienie Toast -->
    <div id="toast" class="fixed bottom-5 right-5 bg-slate-900 text-white text-xs font-bold px-4 py-3 rounded-xl shadow-xl transition-all duration-300 transform translate-y-20 opacity-0 pointer-events-none z-50 flex items-center gap-2">
        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        <span id="toast-msg">Komunikat</span>
    </div>

    <script>
        const CSRF_TOKEN   = '<?= $csrfToken ?>';
        const BASE_URL     = '<?= $base ?>';
        const CLIENTS_DATA = <?= json_encode(!empty($clients) ? array_column($clients, null, 'id') : (object)[], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        let currentFileId  = null;

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

        function switchTab(tab, saveState = true) {
            ['products', 'orders', 'clients', 'settings'].forEach(t => {
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
            if (saveState) {
                try {
                    localStorage.setItem('b2b_admin_tab', tab);
                    if (window.location.hash !== '#' + tab) {
                        history.replaceState(null, '', '#' + tab);
                    }
                } catch(e) {}
            }
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

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function normalizeRows(rows) {
            if (!rows) return [];
            if (Array.isArray(rows)) {
                return rows.map((r, idx) => {
                    if (r && typeof r === 'object' && 'row_index' in r && 'cells' in r) {
                        return {
                            row_index: Number(r.row_index),
                            cells: (r.cells && typeof r.cells === 'object') ? r.cells : {}
                        };
                    }
                    if (r && typeof r === 'object') {
                        return {
                            row_index: idx + 1,
                            cells: r
                        };
                    }
                    return {
                        row_index: idx + 1,
                        cells: {}
                    };
                });
            }
            if (typeof rows === 'object') {
                return Object.keys(rows).map(rowKey => {
                    const rowVal = rows[rowKey];
                    return {
                        row_index: parseInt(rowKey, 10),
                        cells: (rowVal && typeof rowVal === 'object') ? rowVal : {}
                    };
                }).sort((a, b) => a.row_index - b.row_index);
            }
            return [];
        }

        let currentNormRows = [];
        let currentMaxCols = 0;

        function renderPreviewTable(normRows, maxCols, headerRowIndex) {
            const thead = document.getElementById('preview-sheet-thead');
            const tbody = document.getElementById('preview-sheet-tbody');
            if (!thead || !tbody) return;

            let thHtml = '<tr><th class="py-2 px-3 w-12 text-slate-400 font-mono text-[11px] bg-slate-100">#</th>';
            for (let c = 0; c < maxCols; c++) {
                const colLetter = String.fromCharCode(65 + (c % 26));
                thHtml += `<th class="py-2 px-3 whitespace-nowrap bg-slate-100">Kolumna ${colLetter}</th>`;
            }
            thHtml += '</tr>';
            thead.innerHTML = thHtml;

            let tbHtml = '';
            normRows.slice(0, 10).forEach(r => {
                const isHeader = (r.row_index === Number(headerRowIndex));
                tbHtml += `<tr class="${isHeader ? 'bg-emerald-50/80 font-semibold text-emerald-950' : 'hover:bg-slate-50 text-slate-700'}">`;
                tbHtml += `<td class="py-2 px-3 font-mono ${isHeader ? 'text-emerald-700 font-bold' : 'text-slate-400'}">${r.row_index}</td>`;
                for (let c = 0; c < maxCols; c++) {
                    const val = (r.cells[c] !== undefined && r.cells[c] !== null) ? String(r.cells[c]) : '';
                    tbHtml += `<td class="py-2 px-3 whitespace-nowrap max-w-xs truncate">${escapeHtml(val)}</td>`;
                }
                tbHtml += '</tr>';
            });
            tbody.innerHTML = tbHtml;
        }

        function renderMapping(rows, cand) {
            const normRows = normalizeRows(rows);
            if (normRows.length === 0) {
                const status = document.getElementById('upload-status');
                status.textContent = 'Błąd: Przesłany arkusz jest pusty lub nie zawiera czytelnych wierszy.';
                status.className = 'mt-3 text-center text-sm font-semibold text-rose-600';
                return;
            }

            const box = document.getElementById('mapping-box');
            box.classList.remove('hidden');

            const selHeader = document.getElementById('map-header-row');
            const selProd   = document.getElementById('map-col-product');
            const selPrice  = document.getElementById('map-col-price');
            const selUnit   = document.getElementById('map-col-unit');

            selHeader.innerHTML = '';
            selProd.innerHTML   = '';
            selPrice.innerHTML  = '';
            selUnit.innerHTML   = '<option value="">-- Domyślnie (kg / szt.) --</option>';

            // Liczba kolumn w arkuszu
            let maxCols = 0;
            normRows.forEach(r => {
                const keys = Object.keys(r.cells).map(Number);
                if (keys.length > 0) {
                    maxCols = Math.max(maxCols, Math.max(...keys) + 1);
                }
            });

            currentNormRows = normRows;
            currentMaxCols  = maxCols;

            const candHeaderRow = Number(cand?.header_row_index ?? cand?.headerRow ?? normRows[0].row_index);
            const candProdCol   = Number(cand?.product_col_index ?? cand?.productCol ?? 0);
            const candPriceCol  = Number(cand?.price_col_index ?? cand?.priceCol ?? 1);
            const rawUnitCol    = cand?.unit_col_index ?? cand?.unitCol;
            const candUnitCol   = (rawUnitCol !== null && rawUnitCol !== undefined && rawUnitCol !== '') ? Number(rawUnitCol) : null;

            // Opcje wiersza nagłówka
            normRows.slice(0, 15).forEach(r => {
                const opt = document.createElement('option');
                opt.value = r.row_index;
                const sampleValues = Object.values(r.cells)
                    .map(v => (v !== null && v !== undefined ? String(v).trim() : ''))
                    .filter(v => v.length > 0);
                const preview = sampleValues.slice(0, 3).join(' | ');
                opt.textContent = 'Wiersz ' + r.row_index + (preview ? ': ' + preview : ' (pusty)');
                if (r.row_index === candHeaderRow) {
                    opt.selected = true;
                }
                selHeader.appendChild(opt);
            });

            // Kolumny
            const headerRowObj = normRows.find(r => r.row_index === candHeaderRow);

            for (let c = 0; c < maxCols; c++) {
                const colLetter = String.fromCharCode(65 + (c % 26));
                let headerText = '';
                if (headerRowObj && headerRowObj.cells && headerRowObj.cells[c]) {
                    headerText = String(headerRowObj.cells[c]).trim();
                }

                let sampleVal = '';
                for (const r of normRows) {
                    if (r.row_index <= candHeaderRow) continue;
                    const val = r.cells[c];
                    if (val !== undefined && val !== null && String(val).trim() !== '') {
                        sampleVal = String(val).trim();
                        break;
                    }
                }

                let label = `Kolumna ${colLetter}`;
                if (headerText) {
                    label += `: "${headerText}"`;
                }
                if (sampleVal) {
                    label += ` (np. "${sampleVal}")`;
                }

                const oP = new Option(label, c, false, c === candProdCol);
                selProd.appendChild(oP);

                const oPr = new Option(label, c, false, c === candPriceCol);
                selPrice.appendChild(oPr);

                const oU = new Option(label, c, false, candUnitCol !== null && c === candUnitCol);
                selUnit.appendChild(oU);
            }

            // Jawnie ustawiamy wybrane wartości w polach select
            if (candProdCol !== null && candProdCol !== undefined) {
                selProd.value = String(candProdCol);
            }
            if (candPriceCol !== null && candPriceCol !== undefined) {
                selPrice.value = String(candPriceCol);
            }
            if (candUnitCol !== null && candUnitCol !== undefined) {
                selUnit.value = String(candUnitCol);
            }

            renderPreviewTable(normRows, maxCols, candHeaderRow);
        }

        const selHeaderEl = document.getElementById('map-header-row');
        if (selHeaderEl) {
            selHeaderEl.addEventListener('change', function() {
                renderPreviewTable(currentNormRows, currentMaxCols, this.value);
            });
        }

        function cancelMapping() {
            document.getElementById('mapping-box').classList.add('hidden');
            document.getElementById('upload-status').classList.add('hidden');
            const fi = document.getElementById('file-input');
            if (fi) fi.value = '';
            currentFileId = null;
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

        // ===================================================================
        // Śledzenie modyfikacji (Dirty State) i zatwierdzanie zmian produktu
        // ===================================================================
        function checkFieldDirty(field) {
            const initial = field.getAttribute('data-initial') ?? '';
            const current = field.value;
            const fieldType = field.getAttribute('data-field-name');

            let isDirty = false;
            if (fieldType === 'price' || fieldType === 'package_size') {
                const numInit = parseFloat(initial);
                const numCurr = parseFloat(current);
                if (isNaN(numCurr) || current.trim() === '') {
                    isDirty = true;
                } else if (isNaN(numInit)) {
                    isDirty = true;
                } else {
                    isDirty = Math.abs(numInit - numCurr) > 0.0001;
                }
            } else {
                isDirty = (initial.trim() !== current.trim());
            }

            if (isDirty) {
                field.classList.add('is-dirty-field', 'border-amber-400', 'bg-amber-50/80', 'ring-2', 'ring-amber-300/60', 'text-amber-950', 'font-semibold');
                field.classList.remove('border-slate-200', 'hover:border-slate-300', 'border-transparent');
            } else {
                field.classList.remove('is-dirty-field', 'border-amber-400', 'bg-amber-50/80', 'ring-2', 'ring-amber-300/60', 'text-amber-950', 'font-semibold');
                if (fieldType === 'name') {
                    field.classList.add('border-transparent', 'hover:border-slate-300');
                } else {
                    field.classList.add('border-slate-200');
                }
            }
            return isDirty;
        }

        function updateRowDirtyState(prodId) {
            const row = document.getElementById('prod-row-' + prodId);
            if (!row) return;

            const fields = row.querySelectorAll('.prod-field');
            let hasDirty = false;
            fields.forEach(f => {
                if (checkFieldDirty(f)) {
                    hasDirty = true;
                }
            });

            const btn   = document.getElementById('save-btn-' + prodId);
            const label = document.getElementById('save-label-' + prodId);
            const badge = document.getElementById('dirty-badge-' + prodId);

            if (hasDirty) {
                // Podkreślenie pozycji edytowanej w tabeli
                row.classList.add('bg-amber-50/70', 'border-l-amber-500', 'is-row-dirty');
                row.classList.remove('border-l-transparent', 'hover:bg-slate-50/80');
                if (badge) {
                    badge.classList.remove('hidden');
                    badge.classList.add('inline-flex');
                }

                // Subtelne polecenie zatwierdzenia zmian przy akcji Zapisz (animowany pulse, wyraźny CTA)
                if (btn) {
                    btn.className = 'save-btn inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-md shadow-emerald-600/30 ring-2 ring-emerald-400/60 animate-pulse transition-all cursor-pointer';
                    btn.title = 'Wprowadzono zmiany — kliknij, aby zatwierdzić';
                }
                if (label) {
                    label.classList.remove('hidden');
                }
            } else {
                // Powrót do neutralnego stanu spoczynku
                row.classList.remove('bg-amber-50/70', 'border-l-amber-500', 'is-row-dirty');
                row.classList.add('border-l-transparent', 'hover:bg-slate-50/80');
                if (badge) {
                    badge.classList.add('hidden');
                    badge.classList.remove('inline-flex');
                }

                if (btn) {
                    btn.className = 'save-btn inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-emerald-700 hover:bg-emerald-50 border border-transparent transition-all';
                    btn.title = 'Zapisz zmiany';
                }
                if (label) {
                    label.classList.add('hidden');
                }
            }

            updateGlobalUnsavedCount();
        }

        function updateGlobalUnsavedCount() {
            const dirtyRows = document.querySelectorAll('.prod-row.is-row-dirty');
            const pill = document.getElementById('unsaved-alert-pill');
            const text = document.getElementById('unsaved-count-text');
            const btnSaveAll = document.getElementById('btn-save-all-products');
            const countBadge = document.getElementById('btn-save-all-count');
            if (!pill || !text) return;

            const count = dirtyRows.length;
            if (count > 0) {
                pill.classList.remove('hidden');
                pill.classList.add('inline-flex');
                if (count === 1) {
                    text.textContent = '1 niezatwierdzona zmiana';
                } else if (count >= 2 && count <= 4) {
                    text.textContent = count + ' niezatwierdzone zmiany';
                } else {
                    text.textContent = count + ' niezatwierdzonych zmian';
                }
                if (btnSaveAll) {
                    btnSaveAll.classList.remove('hidden');
                    btnSaveAll.classList.add('inline-flex');
                }
                if (countBadge) {
                    countBadge.textContent = count;
                }
            } else {
                pill.classList.add('hidden');
                pill.classList.remove('inline-flex');
                if (btnSaveAll) {
                    btnSaveAll.classList.add('hidden');
                    btnSaveAll.classList.remove('inline-flex');
                }
            }
        }

        // Rejestracja zdarzeń dla pól produktów
        const productsTbody = document.getElementById('products-tbody');
        if (productsTbody) {
            productsTbody.addEventListener('input', (e) => {
                const field = e.target.closest('.prod-field');
                if (field && field.dataset.prodId) {
                    updateRowDirtyState(field.dataset.prodId);
                }
            });

            productsTbody.addEventListener('change', (e) => {
                const field = e.target.closest('.prod-field');
                if (field && field.dataset.prodId) {
                    updateRowDirtyState(field.dataset.prodId);
                }
            });

            productsTbody.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    const field = e.target.closest('.prod-field');
                    if (field && field.dataset.prodId) {
                        e.preventDefault();
                        saveProduct(field.dataset.prodId);
                    }
                }
            });
        }

        // Skrót klawiaturowy Ctrl+S / Cmd+S do zapisu wszystkich zmian
        window.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                const dirtyRows = document.querySelectorAll('.prod-row.is-row-dirty');
                if (dirtyRows.length > 0) {
                    e.preventDefault();
                    saveAllProducts();
                }
            }
        });

        // Ostrzeżenie przed przypadkowym opuszczeniem strony z niezapisanymi zmianami
        window.addEventListener('beforeunload', (e) => {
            const count = document.querySelectorAll('.prod-row.is-row-dirty').length;
            if (count > 0) {
                e.preventDefault();
                e.returnValue = 'Masz ' + count + ' niezatwierdzonych zmian w ofercie. Czy na pewno chcesz opuścić stronę?';
            }
        });

        // Zapis produktu na żywo
        function saveProduct(id) {
            const row = document.getElementById('prod-row-' + id);
            const btn = document.getElementById('save-btn-' + id);
            const badge = document.getElementById('dirty-badge-' + id);

            const nameInput    = document.getElementById('name-' + id);
            const erpInput     = document.getElementById('erp-' + id);
            const catSelect    = document.getElementById('cat-' + id);
            const priceInput   = document.getElementById('price-' + id);
            const unitSelect   = document.getElementById('unit-' + id);
            const pkgSizeInput = document.getElementById('pkg-size-' + id);
            const pkgUnitSelect= document.getElementById('pkg-unit-' + id);

            if (!nameInput || !priceInput) return;

            const prodName = nameInput.value.trim();
            if (!prodName) {
                alert('Nazwa towaru nie może być pusta!');
                nameInput.focus();
                return;
            }

            // Stan ładowania przycisku
            if (btn) {
                btn.disabled = true;
                btn.classList.remove('animate-pulse');
                btn.className = 'save-btn inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-700 text-white cursor-wait opacity-90 shadow-sm';
                btn.innerHTML = `
                    <svg class="animate-spin -ml-0.5 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Zapisywanie...</span>
                `;
            }

            const fd = new FormData();
            fd.append('id', id);
            fd.append('name', prodName);
            fd.append('erp_code', erpInput ? erpInput.value.trim() : '');
            fd.append('category', catSelect ? catSelect.value : 'Warzywa');
            fd.append('price', priceInput.value);
            fd.append('unit', unitSelect ? unitSelect.value : 'kg');
            fd.append('package_size', pkgSizeInput ? pkgSizeInput.value : '1');
            fd.append('package_unit', pkgUnitSelect ? pkgUnitSelect.value : 'skrzynka');
            fd.append('_csrf', CSRF_TOKEN);

            fetch(BASE_URL + 'b2b/updateproduct', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (!d.ok) throw new Error(d.error || 'Błąd zapisu produktu');

                    // 1. Zaktualizuj data-initial dla wszystkich pól w wierszu
                    if (nameInput)    nameInput.setAttribute('data-initial', prodName);
                    if (erpInput)     erpInput.setAttribute('data-initial', erpInput.value.trim());
                    if (catSelect)    catSelect.setAttribute('data-initial', catSelect.value);
                    if (priceInput)   priceInput.setAttribute('data-initial', parseFloat(priceInput.value).toFixed(2));
                    if (unitSelect)   unitSelect.setAttribute('data-initial', unitSelect.value);
                    if (pkgSizeInput) pkgSizeInput.setAttribute('data-initial', parseFloat(pkgSizeInput.value).toString());
                    if (pkgUnitSelect)pkgUnitSelect.setAttribute('data-initial', pkgUnitSelect.value);

                    // Aktualizacja atrybutów wyszukiwania w wierszu
                    if (row) {
                        row.setAttribute('data-name', prodName.toLowerCase());
                        if (catSelect) row.setAttribute('data-cat', catSelect.value);

                        // Usunięcie podświetlenia "dirty" z pól
                        row.querySelectorAll('.prod-field').forEach(f => {
                            f.classList.remove('is-dirty-field', 'border-amber-400', 'bg-amber-50/80', 'ring-2', 'ring-amber-300/60', 'text-amber-950', 'font-semibold');
                            if (f.getAttribute('data-field-name') === 'name') {
                                f.classList.add('border-transparent', 'hover:border-slate-300');
                            } else {
                                f.classList.add('border-slate-200');
                            }
                        });

                        // 2. Efektowny rozbłysk sukcesu na wierszu
                        row.classList.remove('bg-amber-50/70', 'border-l-amber-500', 'is-row-dirty');
                        row.classList.add('bg-emerald-100/80', 'border-l-emerald-500');
                    }

                    if (badge) {
                        badge.classList.add('hidden');
                        badge.classList.remove('inline-flex');
                    }
                    const newBadge = document.getElementById('new-badge-' + id);
                    if (newBadge) newBadge.remove();

                    // 3. Stan sukcesu na przycisku
                    if (btn) {
                        btn.className = 'save-btn inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 text-white shadow-sm transition-all';
                        btn.innerHTML = `
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Zatwierdzono!</span>
                        `;
                    }

                    showToast('Zapisano zmiany dla pozycji: ' + prodName);
                    updateGlobalUnsavedCount();

                    // 4. Płynny powrót do neutralnego stanu spoczynku
                    setTimeout(() => {
                        if (row) {
                            row.classList.remove('bg-emerald-100/80', 'border-l-emerald-500');
                            row.classList.add('border-l-transparent', 'hover:bg-slate-50/80');
                        }
                        if (btn) {
                            btn.disabled = false;
                            btn.className = 'save-btn inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-emerald-700 hover:bg-emerald-50 border border-transparent transition-all';
                            btn.title = 'Zapisz zmiany';
                            btn.innerHTML = `
                                <svg id="save-icon-${id}" class="w-4 h-4 shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span id="save-label-${id}" class="hidden font-bold">Zapisz</span>
                            `;
                        }
                    }, 1200);
                })
                .catch(err => {
                    alert('Błąd zapisu produktu: ' + err.message);
                    if (btn) {
                        btn.disabled = false;
                        updateRowDirtyState(id);
                    }
                });
        }

        // Hurtowy zapis wszystkich zmodyfikowanych pozycji asortymentu
        function saveAllProducts() {
            const dirtyRows = Array.from(document.querySelectorAll('.prod-row.is-row-dirty'));
            if (dirtyRows.length === 0) {
                showToast('Brak niezatwierdzonych zmian do zapisania.');
                return;
            }

            const btnSaveAll = document.getElementById('btn-save-all-products');
            const btnText = document.getElementById('btn-save-all-text');
            const countBadge = document.getElementById('btn-save-all-count');

            const productsData = [];
            const rowsToUpdate = [];

            for (const row of dirtyRows) {
                const id = row.getAttribute('data-prod-id');
                if (!id) continue;

                const nameInput    = document.getElementById('name-' + id);
                const catSelect    = document.getElementById('cat-' + id);
                const priceInput   = document.getElementById('price-' + id);
                const unitSelect   = document.getElementById('unit-' + id);
                const pkgSizeInput = document.getElementById('pkg-size-' + id);
                const pkgUnitSelect= document.getElementById('pkg-unit-' + id);

                if (!nameInput || !priceInput) continue;

                const prodName = nameInput.value.trim();
                if (!prodName) {
                    alert('Nazwa towaru dla pozycji ID ' + id + ' nie może być pusta!');
                    nameInput.focus();
                    return;
                }

                const erpInput = document.getElementById('erp-' + id);

                productsData.push({
                    id: id,
                    name: prodName,
                    erp_code: erpInput ? erpInput.value.trim() : '',
                    category: catSelect ? catSelect.value : 'Warzywa',
                    price: priceInput.value,
                    unit: unitSelect ? unitSelect.value : 'kg',
                    package_size: pkgSizeInput ? pkgSizeInput.value : '1',
                    package_unit: pkgUnitSelect ? pkgUnitSelect.value : 'skrzynka'
                });

                rowsToUpdate.push({
                    id: id,
                    row: row,
                    nameInput: nameInput,
                    erpInput: erpInput,
                    catSelect: catSelect,
                    priceInput: priceInput,
                    unitSelect: unitSelect,
                    pkgSizeInput: pkgSizeInput,
                    pkgUnitSelect: pkgUnitSelect,
                    btn: document.getElementById('save-btn-' + id),
                    badge: document.getElementById('dirty-badge-' + id),
                    prodName: prodName
                });
            }

            if (productsData.length === 0) return;

            // Stan ładowania na przycisku głównym
            if (btnSaveAll) {
                btnSaveAll.disabled = true;
                btnSaveAll.classList.add('cursor-wait', 'opacity-90');
                if (btnText) btnText.textContent = 'Zapisywanie (' + productsData.length + ')...';
            }

            // Stan ładowania na przyciskach poszczególnych wierszy
            rowsToUpdate.forEach(item => {
                if (item.btn) {
                    item.btn.disabled = true;
                    item.btn.className = 'save-btn inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-700 text-white cursor-wait opacity-90 shadow-sm';
                    item.btn.innerHTML = `
                        <svg class="animate-spin -ml-0.5 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Zapisywanie...</span>
                    `;
                }
            });

            const fd = new FormData();
            fd.append('products', JSON.stringify(productsData));
            fd.append('_csrf', CSRF_TOKEN);

            fetch(BASE_URL + 'b2b/updateproductsbatch', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (!d.ok) throw new Error(d.error || 'Błąd hurtowego zapisu produktów');

                    // Aktualizacja każdego zapisanego wiersza
                    rowsToUpdate.forEach(item => {
                        const { id, row, nameInput, erpInput, catSelect, priceInput, unitSelect, pkgSizeInput, pkgUnitSelect, btn, badge, prodName } = item;

                        if (nameInput)    nameInput.setAttribute('data-initial', prodName);
                        if (erpInput)     erpInput.setAttribute('data-initial', erpInput.value.trim());
                        if (catSelect)    catSelect.setAttribute('data-initial', catSelect.value);
                        if (priceInput)   priceInput.setAttribute('data-initial', parseFloat(priceInput.value).toFixed(2));
                        if (unitSelect)   unitSelect.setAttribute('data-initial', unitSelect.value);
                        if (pkgSizeInput) pkgSizeInput.setAttribute('data-initial', parseFloat(pkgSizeInput.value).toString());
                        if (pkgUnitSelect)pkgUnitSelect.setAttribute('data-initial', pkgUnitSelect.value);

                        if (row) {
                            row.setAttribute('data-name', prodName.toLowerCase());
                            if (catSelect) row.setAttribute('data-cat', catSelect.value);

                            row.querySelectorAll('.prod-field').forEach(f => {
                                f.classList.remove('is-dirty-field', 'border-amber-400', 'bg-amber-50/80', 'ring-2', 'ring-amber-300/60', 'text-amber-950', 'font-semibold');
                                if (f.getAttribute('data-field-name') === 'name') {
                                    f.classList.add('border-transparent', 'hover:border-slate-300');
                                } else {
                                    f.classList.add('border-slate-200');
                                }
                            });

                            row.classList.remove('bg-amber-50/70', 'border-l-amber-500', 'is-row-dirty');
                            row.classList.add('bg-emerald-100/80', 'border-l-emerald-500');
                        }

                        if (badge) {
                            badge.classList.add('hidden');
                            badge.classList.remove('inline-flex');
                        }
                        const newBadge = document.getElementById('new-badge-' + id);
                        if (newBadge) newBadge.remove();

                        if (btn) {
                            btn.className = 'save-btn inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 text-white shadow-sm transition-all';
                            btn.innerHTML = `
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Zatwierdzono!</span>
                            `;
                        }

                        setTimeout(() => {
                            if (row) {
                                row.classList.remove('bg-emerald-100/80', 'border-l-emerald-500');
                                row.classList.add('border-l-transparent', 'hover:bg-slate-50/80');
                            }
                            if (btn) {
                                btn.disabled = false;
                                btn.className = 'save-btn inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-emerald-700 hover:bg-emerald-50 border border-transparent transition-all';
                                btn.title = 'Zapisz zmiany';
                                btn.innerHTML = `
                                    <svg id="save-icon-${id}" class="w-4 h-4 shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span id="save-label-${id}" class="hidden font-bold">Zapisz</span>
                                `;
                            }
                        }, 1400);
                    });

                    // Stan sukcesu na przycisku głównym
                    if (btnSaveAll) {
                        btnSaveAll.className = 'inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-700 text-white rounded-xl text-xs font-extrabold shadow-md transition-all';
                        if (btnText) btnText.textContent = 'Zatwierdzono wszystkie!';
                    }

                    showToast('Zapisano hurtowo zmiany dla ' + (d.saved_count || productsData.length) + ' pozycji asortymentu!');
                    updateGlobalUnsavedCount();

                    setTimeout(() => {
                        if (btnSaveAll) {
                            btnSaveAll.disabled = false;
                            btnSaveAll.classList.remove('cursor-wait', 'opacity-90');
                            btnSaveAll.className = 'hidden items-center gap-1.5 px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl text-xs font-extrabold shadow-md shadow-emerald-600/25 transition-all cursor-pointer';
                            if (btnText) btnText.textContent = 'Zapisz wszystkie zmiany';
                        }
                    }, 1800);
                })
                .catch(err => {
                    console.error(err);
                    alert('Błąd podczas zapisywania zmian: ' + err.message);
                    if (btnSaveAll) {
                        btnSaveAll.disabled = false;
                        btnSaveAll.classList.remove('cursor-wait', 'opacity-90');
                        if (btnText) btnText.textContent = 'Zapisz wszystkie zmiany';
                    }
                    rowsToUpdate.forEach(item => {
                        if (item.btn) {
                            item.btn.disabled = false;
                            updateRowDirtyState(item.id);
                        }
                    });
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
            const btn = document.getElementById('client-status-btn-' + id);
            if (btn) {
                btn.disabled = true;
                btn.style.opacity = '0.6';
            }

            const fd = new FormData();
            fd.append('id', id);
            fd.append('_csrf', CSRF_TOKEN);

            fetch(BASE_URL + 'b2b/toggleclient', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (btn) {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                    }
                    if (d.ok) {
                        const isActive = (d.is_active === 1 || d.is_active === '1');
                        if (CLIENTS_DATA[id]) {
                            CLIENTS_DATA[id].is_active = isActive ? 1 : 0;
                        }
                        if (btn) {
                            if (isActive) {
                                btn.textContent = 'Aktywny';
                                btn.className = 'text-xs font-bold px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 hover:bg-emerald-200 transition';
                            } else {
                                btn.textContent = 'Zablokowany';
                                btn.className = 'text-xs font-bold px-2.5 py-1 rounded-lg bg-slate-200 text-slate-600 hover:bg-slate-300 transition';
                            }
                        }

                        // Zsynchronizuj pole w modalu edycji, jeśli otwarte
                        const editStatusSelect = document.getElementById('edit-client-status');
                        const editClientId = document.getElementById('edit-client-id');
                        if (editStatusSelect && editClientId && parseInt(editClientId.value, 10) === id) {
                            editStatusSelect.value = isActive ? '1' : '0';
                        }

                        showToast(d.message || (isActive ? 'Konto klienta zostało odblokowane' : 'Konto klienta zostało zablokowane'));
                    } else {
                        alert(d.error || 'Wystąpił błąd podczas zmiany statusu klienta');
                    }
                })
                .catch(err => {
                    if (btn) {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                    }
                    alert('Błąd sieci: ' + err.message);
                });
        }

        // ===================================================================
        // WYSZUKIWANIE KLIENTÓW PO NAZWIE / NIP / TELEFONIE
        // ===================================================================
        const clientSearchInput = document.getElementById('client-search-admin');
        const clientSearchClear = document.getElementById('client-search-clear');
        const clientsCountBadge = document.getElementById('clients-count-badge');
        const noClientsRow = document.getElementById('no-clients-search-row');
        const noClientsTerm = document.getElementById('no-clients-search-term');

        function filterClients() {
            if (!clientSearchInput) return;
            const q = clientSearchInput.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#clients-tbody tr.client-data-row');

            if (clientSearchClear) {
                if (q.length > 0) {
                    clientSearchClear.classList.remove('hidden');
                } else {
                    clientSearchClear.classList.add('hidden');
                }
            }

            let visibleCount = 0;
            rows.forEach(r => {
                const name  = (r.getAttribute('data-client-name') || '').toLowerCase();
                const nip   = (r.getAttribute('data-client-nip') || '').toLowerCase();
                const phone = (r.getAttribute('data-client-phone') || '').toLowerCase();

                const match = q === '' || name.includes(q) || nip.includes(q) || phone.includes(q);
                if (match) {
                    r.classList.remove('hidden');
                    visibleCount++;
                } else {
                    r.classList.add('hidden');
                }
            });

            if (noClientsRow) {
                if (visibleCount === 0 && rows.length > 0) {
                    noClientsRow.classList.remove('hidden');
                    if (noClientsTerm) noClientsTerm.textContent = clientSearchInput.value.trim();
                } else {
                    noClientsRow.classList.add('hidden');
                }
            }

            if (clientsCountBadge) {
                if (q === '') {
                    clientsCountBadge.textContent = rows.length + ' odbiorców';
                } else {
                    clientsCountBadge.textContent = 'Wyniki: ' + visibleCount + ' z ' + rows.length;
                }
            }
        }

        function clearClientSearch() {
            if (clientSearchInput) {
                clientSearchInput.value = '';
                filterClients();
                clientSearchInput.focus();
            }
        }

        if (clientSearchInput) {
            clientSearchInput.addEventListener('input', filterClients);
        }

        // ===================================================================
        // ZARZĄDZANIE ODBIORCAMI (EDYCJA I WYSYŁKA TOKENÓW)
        // ===================================================================
        function openEditClientModal(id) {
            const c = CLIENTS_DATA[id];
            if (!c) return;

            document.getElementById('edit-client-id').value = c.id;
            document.getElementById('edit-client-name').value = c.company_name || '';
            document.getElementById('edit-client-nip').value = c.nip || '';
            document.getElementById('edit-client-phone').value = c.phone || '';
            document.getElementById('edit-client-email').value = c.email || '';
            document.getElementById('edit-client-address').value = c.delivery_address || '';
            document.getElementById('edit-client-login').value = c.login || '';
            document.getElementById('edit-client-password').value = '';
            document.getElementById('edit-client-regen-token').checked = false;

            const statusSelect = document.getElementById('edit-client-status');
            if (statusSelect) {
                statusSelect.value = (c.is_active === 0 || c.is_active === '0') ? '0' : '1';
            }

            document.getElementById('modal-edit-client-subtitle').textContent = 'Edycja danych dla: ' + c.company_name;
            document.getElementById('modal-edit-client').classList.remove('hidden');
        }

        function closeEditClientModal() {
            document.getElementById('modal-edit-client').classList.add('hidden');
        }

        const formEditClient = document.getElementById('form-edit-client');
        if (formEditClient) {
            formEditClient.addEventListener('submit', (e) => {
                e.preventDefault();
                const id = document.getElementById('edit-client-id').value;
                const btn = document.getElementById('edit-client-submit-btn');
                const origBtnHtml = btn.innerHTML;

                btn.disabled = true;
                btn.innerHTML = `
                    <svg class="animate-spin -ml-0.5 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Zapisywanie...</span>
                `;

                const fd = new FormData();
                fd.append('id', id);
                fd.append('company_name', document.getElementById('edit-client-name').value);
                fd.append('nip', document.getElementById('edit-client-nip').value);
                fd.append('phone', document.getElementById('edit-client-phone').value);
                fd.append('email', document.getElementById('edit-client-email').value);
                fd.append('delivery_address', document.getElementById('edit-client-address').value);
                fd.append('login', document.getElementById('edit-client-login').value);
                const statusSelect = document.getElementById('edit-client-status');
                if (statusSelect) {
                    fd.append('is_active', statusSelect.value);
                }
                const pass = document.getElementById('edit-client-password').value;
                if (pass) fd.append('password', pass);
                if (document.getElementById('edit-client-regen-token').checked) {
                    fd.append('regenerate_token', '1');
                }
                fd.append('_csrf', CSRF_TOKEN);

                fetch(BASE_URL + 'b2b/updateclient', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(d => {
                        btn.disabled = false;
                        btn.innerHTML = origBtnHtml;

                        if (!d.ok) throw new Error(d.error || 'Błąd aktualizacji klienta');

                        CLIENTS_DATA[id] = d.client;

                        // Aktualizacja wiersza w tabeli
                        const nameEl = document.getElementById('client-name-display-' + id);
                        const nipEl = document.getElementById('client-nip-display-' + id);
                        const phoneEl = document.getElementById('client-phone-display-' + id);
                        const emailEl = document.getElementById('client-email-display-' + id);
                        const addrEl = document.getElementById('client-address-display-' + id);
                        const emailBtn = document.getElementById('btn-send-email-' + id);
                        const statusBtn = document.getElementById('client-status-btn-' + id);

                        if (nameEl) nameEl.textContent = d.client.company_name;
                        if (nipEl) {
                            if (d.client.nip) {
                                nipEl.textContent = 'NIP: ' + d.client.nip;
                                nipEl.classList.remove('hidden');
                            } else {
                                nipEl.classList.add('hidden');
                            }
                        }
                        if (phoneEl) phoneEl.textContent = d.client.phone || '—';
                        if (emailEl) emailEl.textContent = d.client.email || '—';
                        if (addrEl) addrEl.textContent = d.client.delivery_address || '—';
                        if (emailBtn) {
                            emailBtn.title = d.client.email ? 'Wyślij bezpośredni link dostępowy na e-mail: ' + d.client.email : 'Brak e-maila klienta';
                        }
                        if (statusBtn) {
                            const isActive = (d.client.is_active === 1 || d.client.is_active === '1');
                            statusBtn.textContent = isActive ? 'Aktywny' : 'Zablokowany';
                            statusBtn.className = 'text-xs font-bold px-2.5 py-1 rounded-lg ' + (isActive ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-200 text-slate-600 hover:bg-slate-300') + ' transition';
                        }

                        const row = document.getElementById('client-row-' + id);
                        if (row) {
                            row.setAttribute('data-client-name', (d.client.company_name || '').toLowerCase());
                            row.setAttribute('data-client-nip', (d.client.nip || '').toLowerCase());
                            row.setAttribute('data-client-phone', (d.client.phone || '').toLowerCase());
                            row.classList.add('bg-emerald-50');
                            setTimeout(() => row.classList.remove('bg-emerald-50'), 1500);
                        }

                        closeEditClientModal();
                        showToast(d.message || 'Zaktualizowano dane klienta!');
                    })
                    .catch(err => {
                        btn.disabled = false;
                        btn.innerHTML = origBtnHtml;
                        alert(err.message);
                    });
            });
        }

        function deleteCurrentClient() {
            const id = parseInt(document.getElementById('edit-client-id').value, 10);
            if (!id) return;

            const c = CLIENTS_DATA[id];
            const name = c ? c.company_name : 'tego klienta';

            if (!confirm('Czy na pewno chcesz bezpowrotnie usunąć klienta "' + name + '" z bazy danych?\n\nTa operacja usunie konto odbiorcy i unieważni jego linki dostępowe.')) {
                return;
            }

            const btn = document.getElementById('btn-delete-client');
            let origHtml = '';
            if (btn) {
                origHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = `
                    <svg class="animate-spin h-3.5 w-3.5 text-rose-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Usuwanie...</span>
                `;
            }

            const fd = new FormData();
            fd.append('id', id);
            fd.append('_csrf', CSRF_TOKEN);

            fetch(BASE_URL + 'b2b/deleteclient', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(d => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                }

                if (!d.ok) throw new Error(d.error || 'Nie udało się usunąć klienta.');

                closeEditClientModal();

                // Płynne usunięcie wiersza z tabeli
                const row = document.getElementById('client-row-' + id);
                if (row) {
                    row.style.transition = 'all 0.3s ease-out';
                    row.style.opacity = '0';
                    row.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        row.remove();
                        delete CLIENTS_DATA[id];
                        filterClients();
                    }, 300);
                } else {
                    delete CLIENTS_DATA[id];
                    filterClients();
                }

                showToast(d.message || 'Klient został pomyślnie usunięty.');
            })
            .catch(err => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                }
                alert(err.message);
            });
        }

        function sendTokenEmail(id) {
            const c = CLIENTS_DATA[id];
            if (!c) return;

            if (!c.email || !c.email.includes('@')) {
                alert('Odbiorca nie ma zapisanego adresu e-mail. Wprowadź e-mail w oknie edycji klienta.');
                openEditClientModal(id);
                return;
            }

            if (!confirm('Czy na pewno chcesz wysłać wiadomość e-mail z bezpośrednim linkiem dostępowym do odbiorcy "' + c.company_name + '" na adres: ' + c.email + '?')) {
                return;
            }

            const btn = document.getElementById('btn-send-email-' + id);
            let origHtml = '';
            if (btn) {
                origHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = `
                    <svg class="animate-spin w-3.5 h-3.5 text-indigo-600 inline" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Wysyłam...</span>
                `;
            }

            const fd = new FormData();
            fd.append('id', id);
            fd.append('channel', 'email');
            fd.append('_csrf', CSRF_TOKEN);

            fetch(BASE_URL + 'b2b/sendtoken', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = origHtml;
                    }

                    if (!d.ok) {
                        if (d.fallback_mailto) {
                            if (confirm('Wystąpił problem z bezpośrednią wysyłką przez serwer pocztowy:\n' + d.error + '\n\nCzy chcesz otworzyć wiadomość w domyślnym programie pocztowym (np. Thunderbird/Outlook)?')) {
                                window.location.href = d.fallback_mailto;
                            }
                            return;
                        }
                        throw new Error(d.error || 'Błąd wysyłki e-mail');
                    }

                    showToast(d.message || 'Wysłano link dostępowy na e-mail: ' + c.email);
                })
                .catch(err => {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = origHtml;
                    }
                    alert(err.message);
                });
        }

        let currentSmsText = '';

        function openSendSmsModal(id) {
            const c = CLIENTS_DATA[id];
            if (!c) return;

            const tokenUrl = BASE_URL + 'b2b?token=' + c.auth_token;
            const phone = c.phone ? c.phone.trim() : '';
            const cleanPhone = phone.replace(/[^0-9+]/g, '');

            const message = 'Dzień dobry! Oto Twój bezpieczny link do składania zamówień hurtowych w Hurtowni: ' + tokenUrl + ' - kliknij, aby przejrzeć bieżącą ofertę i złożyć zamówienie.';
            currentSmsText = message;

            document.getElementById('sms-modal-recipient').textContent = 'Odbiorca: ' + c.company_name;
            document.getElementById('sms-phone-display').value = phone ? phone : 'Brak numeru telefonu';
            document.getElementById('sms-text-preview').value = message;

            // Link do natywnej aplikacji SMS (iOS vs Android/Desktop)
            const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            const smsSep = isIos ? '&' : '?';
            const smsHref = cleanPhone ? 'sms:' + cleanPhone + smsSep + 'body=' + encodeURIComponent(message) : 'javascript:alert("Brak numeru telefonu klienta")';
            document.getElementById('sms-btn-native').href = smsHref;

            // Link do WhatsApp
            const waPhone = cleanPhone.replace(/^\+/, '');
            const waHref = waPhone ? 'https://wa.me/' + waPhone + '?text=' + encodeURIComponent(message) : 'javascript:alert("Brak numeru telefonu klienta")';
            document.getElementById('sms-btn-whatsapp').href = waHref;

            document.getElementById('modal-send-sms').classList.remove('hidden');
        }

        function closeSendSmsModal() {
            document.getElementById('modal-send-sms').classList.add('hidden');
        }

        function copySmsText() {
            const text = document.getElementById('sms-text-preview').value || currentSmsText;
            navigator.clipboard.writeText(text).then(() => {
                showToast('Skopiowano treść SMS z linkiem do schowka!');
            }).catch(() => {
                prompt('Skopiuj treść wiadomości SMS:', text);
            });
        }

        let currentModalOrderId = null;
        let currentOrderDetails = null;
        let currentOrderStatusFilter = 'new';

        function filterOrdersByStatus(status) {
            currentOrderStatusFilter = status || 'new';

            try {
                sessionStorage.setItem('b2b_orders_status_filter', currentOrderStatusFilter);
            } catch (e) {}

            document.querySelectorAll('.order-filter-btn').forEach(btn => {
                const btnStatus = btn.getAttribute('data-status');
                if (btnStatus === currentOrderStatusFilter) {
                    btn.className = 'order-filter-btn px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 bg-white text-emerald-700 shadow-2xs border border-emerald-200/60';
                } else {
                    btn.className = 'order-filter-btn px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5';
                }
            });

            const rows = document.querySelectorAll('.order-data-row');
            let visibleCount = 0;
            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-order-status') || '';
                if (currentOrderStatusFilter === 'all' || rowStatus === currentOrderStatusFilter) {
                    row.classList.remove('hidden');
                    visibleCount++;
                } else {
                    row.classList.add('hidden');
                }
            });

            const emptyEl = document.getElementById('orders-filter-empty-row');
            if (emptyEl) {
                if (visibleCount === 0 && rows.length > 0) {
                    emptyEl.classList.remove('hidden');
                    const statusLabels = {
                        'new': 'Nowe',
                        'processing': 'W kompletacji',
                        'completed': 'Zrealizowane',
                        'cancelled': 'Anulowane',
                        'all': 'wszystkie'
                    };
                    const labelEl = document.getElementById('orders-filter-empty-label');
                    if (labelEl) labelEl.textContent = statusLabels[currentOrderStatusFilter] || currentOrderStatusFilter;
                } else {
                    emptyEl.classList.add('hidden');
                }
            }
        }

        function refreshOrdersFilterCounts() {
            const rows = document.querySelectorAll('.order-data-row');
            const counts = { new: 0, processing: 0, completed: 0, cancelled: 0, all: rows.length };
            rows.forEach(r => {
                const s = r.getAttribute('data-order-status');
                if (counts[s] !== undefined) counts[s]++;
            });
            const cNew = document.getElementById('order-filter-count-new');
            const cProc = document.getElementById('order-filter-count-processing');
            const cComp = document.getElementById('order-filter-count-completed');
            const cCanc = document.getElementById('order-filter-count-cancelled');
            const cAll = document.getElementById('order-filter-count-all');
            if (cNew) cNew.textContent = counts.new;
            if (cProc) cProc.textContent = counts.processing;
            if (cComp) cComp.textContent = counts.completed;
            if (cCanc) cCanc.textContent = counts.cancelled;
            if (cAll) cAll.textContent = counts.all;
        }

        function updateOrderStatus(id, status) {
            const fd = new FormData();
            fd.append('id', id);
            fd.append('status', status);
            fd.append('_csrf', CSRF_TOKEN);

            fetch(BASE_URL + 'b2b/updateorderstatus', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.ok) {
                        showToast('Zaktualizowano status zamówienia!');
                        const row = document.getElementById('order-row-' + id);
                        if (row) {
                            row.setAttribute('data-order-status', status);
                        }
                        const rowSelect = document.getElementById('order-status-select-' + id);
                        if (rowSelect) rowSelect.value = status;
                        const modalStatus = document.getElementById('modal-order-status');
                        if (modalStatus && currentModalOrderId === id) {
                            modalStatus.value = status;
                        }
                        if (currentOrderDetails && currentOrderDetails.order && currentOrderDetails.order.id == id) {
                            currentOrderDetails.order.status = status;
                        }
                        refreshOrdersFilterCounts();
                        refreshNewOrdersBadge();
                        filterOrdersByStatus(currentOrderStatusFilter);
                    }
                });
        }

        function changeModalOrderStatus(status) {
            if (!currentModalOrderId) return;
            updateOrderStatus(currentModalOrderId, status);
        }

        function refreshNewOrdersBadge() {
            const badge = document.getElementById('badge-orders-count');
            if (!badge) return;
            const selects = document.querySelectorAll('#tab-orders select');
            let count = 0;
            selects.forEach(s => {
                if (s.value === 'new') count++;
            });
            badge.textContent = count;
            badge.title = count + ' nowych zamówień';
            if (count > 0) {
                badge.className = 'ml-1 px-2 py-0.5 text-xs rounded-full bg-amber-500 text-white font-bold';
            } else {
                badge.className = 'ml-1 px-2 py-0.5 text-xs rounded-full bg-slate-200 text-slate-700';
            }
        }

        function showOrderModal(id) {
            currentModalOrderId = id;
            fetch(BASE_URL + 'b2b/orderdetails?id=' + id)
                .then(r => r.json())
                .then(d => {
                    if (!d.ok) return alert('Błąd pobierania szczegółów');
                    currentOrderDetails = d;

                    document.getElementById('modal-order-number').textContent = 'Zamówienie ' + d.order.order_number;
                    document.getElementById('modal-order-client').textContent = d.order.client_name_snapshot + ' (tel. ' + (d.order.client_phone_snapshot || '—') + ')';
                    document.getElementById('modal-order-address').textContent = d.order.delivery_address_snapshot || 'Brak';
                    document.getElementById('modal-order-notes').textContent = d.order.notes || 'Brak uwag';
                    document.getElementById('modal-order-total').textContent = Number(d.order.total_amount).toFixed(2) + ' zł';

                    const delivDateEl = document.getElementById('modal-order-delivery-date');
                    if (delivDateEl) {
                        delivDateEl.textContent = d.order.delivery_date ? d.order.delivery_date : 'Standardowa (najbliższy dzień roboczy)';
                    }

                    const modalStatus = document.getElementById('modal-order-status');
                    if (modalStatus) {
                        modalStatus.value = d.order.status || 'new';
                    }

                    const downloadBtn = document.getElementById('modal-order-download-btn');
                    if (downloadBtn) {
                        downloadBtn.href = BASE_URL + 'b2b/download?id=' + d.order.id;
                    }

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

        function printOrderSpecification() {
            if (!currentOrderDetails || !currentOrderDetails.order) {
                window.print();
                return;
            }

            const o = currentOrderDetails.order;
            const items = currentOrderDetails.items || [];
            const printDate = new Date().toLocaleString('pl-PL');

            const printWindow = window.open('', '_blank', 'width=900,height=800');
            if (!printWindow) {
                alert('Proszę zezwolić na otwieranie okien wyskakujących, aby wydrukować specyfikację.');
                return;
            }

            const rowsHtml = items.map((it, idx) => `
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 8px 10px; text-align: center; font-size: 12px; color: #64748b;">${idx + 1}</td>
                    <td style="padding: 8px 10px; font-weight: 700; font-size: 13px; color: #0f172a;">${it.product_name}</td>
                    <td style="padding: 8px 10px; text-align: right; font-weight: 700; font-size: 13px;">${it.quantity} ${it.unit}</td>
                    <td style="padding: 8px 10px; text-align: center; font-size: 12px; background: #f8fafc; font-weight: 600; color: #047857;">${it.package_summary || '—'}</td>
                    <td style="padding: 8px 10px; text-align: right; font-size: 12px; color: #64748b;">${Number(it.price).toFixed(2)} zł</td>
                    <td style="padding: 8px 10px; text-align: right; font-weight: 700; font-size: 13px; color: #0f172a;">${Number(it.item_total).toFixed(2)} zł</td>
                </tr>
            `).join('');

            const html = `<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Specyfikacja zamówienia ${o.order_number}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; color: #0f172a; margin: 0; padding: 15px; font-size: 13px; line-height: 1.4; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 14px; }
        .title { font-size: 18px; font-weight: 900; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
        .subtitle { font-size: 12px; color: #475569; margin-top: 3px; }
        .meta-box { border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; margin-bottom: 16px; background: #f8fafc; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .meta-item strong { display: block; font-size: 10px; text-transform: uppercase; color: #64748b; margin-bottom: 2px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background: #f1f5f9; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #cbd5e1; color: #475569; }
        .summary { display: flex; justify-content: flex-end; margin-bottom: 24px; }
        .summary-box { width: 280px; border-top: 2px solid #0f172a; padding-top: 8px; text-align: right; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 35px; padding-top: 15px; }
        .sign-line { border-top: 1px dashed #94a3b8; padding-top: 6px; text-align: center; font-size: 11px; color: #64748b; }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 15px; padding: 10px 14px; background: #e0f2fe; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #bae6fd;">
        <span style="font-weight: bold; color: #0369a1; font-size: 12px;">Specyfikacja logistyczna gotowa do wydruku (A4).</span>
        <button onclick="window.print()" style="background: #0284c7; color: white; border: none; padding: 6px 14px; font-weight: bold; border-radius: 6px; cursor: pointer; font-size: 12px;">Drukuj teraz</button>
    </div>

    <div class="header">
        <div>
            <h1 class="title">Specyfikacja Zamówienia / Kompletacja</h1>
            <div class="subtitle">Karta kompletacyjna dla magazynu i kierowcy</div>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 16px; font-weight: 900; color: #0f172a;">${o.order_number}</div>
            <div style="font-size: 11px; color: #64748b;">Złożono: ${o.created_at || '—'}</div>
            <div style="font-size: 11px; color: #64748b;">Wydruk: ${printDate}</div>
        </div>
    </div>

    <div class="meta-box">
        <div class="meta-item">
            <strong>Odbiorca / Sklep:</strong>
            <div style="font-weight: bold; font-size: 13px;">${o.client_name_snapshot}</div>
            <div style="font-size: 12px; color: #334155;">Tel: ${o.client_phone_snapshot || '—'}</div>
        </div>
        <div class="meta-item">
            <strong>Adres dostawy towaru:</strong>
            <div style="font-weight: 600; font-size: 12px;">${o.delivery_address_snapshot || 'Brak'}</div>
        </div>
        <div class="meta-item">
            <strong>Planowany termin dostawy:</strong>
            <div style="font-weight: 800; font-size: 13px; color: #b45309;">${o.delivery_date || 'Standardowa (najbliższy dzień roboczy)'}</div>
        </div>
        <div class="meta-item">
            <strong>Status zlecenia:</strong>
            <div style="font-weight: 700; font-size: 12px; text-transform: uppercase;">${o.status || 'new'}</div>
        </div>
        <div class="meta-item" style="grid-column: span 2;">
            <strong>Ważne uwagi dla kierowcy i magazyniera:</strong>
            <div style="font-style: italic; color: #334155; font-size: 12px;">${o.notes || 'Brak szczególnych uwag'}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 35px; text-align: center;">Lp.</th>
                <th>Towar / Artykuł</th>
                <th style="text-align: right;">Ilość</th>
                <th style="text-align: center;">Rozbicie opakowań</th>
                <th style="text-align: right;">Cena j.</th>
                <th style="text-align: right;">Wartość</th>
            </tr>
        </thead>
        <tbody>
            ${rowsHtml}
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-box">
            <div style="font-size: 12px; color: #475569;">Liczba zamówionych pozycji: <strong>${items.length}</strong></div>
            <div style="margin-top: 4px; font-size: 15px; font-weight: 900; color: #047857;">Łącznie do zapłaty: ${Number(o.total_amount).toFixed(2)} zł</div>
        </div>
    </div>

    <div class="signatures">
        <div class="sign-line">Podpis osoby kompletującej (magazynier)</div>
        <div class="sign-line">Potwierdzenie odbioru (podpis i data)</div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 300);
        };
    <\/script>
</body>
</html>`;

            printWindow.document.open();
            printWindow.document.write(html);
            printWindow.document.close();
        }

        function finalizeOrderAndPrint() {
            if (!currentModalOrderId) return;

            // 1. Zmień status zamówienia na 'completed' (Zrealizowane)
            updateOrderStatus(currentModalOrderId, 'completed');

            // 2. Wydrukuj specyfikację logistyczną
            printOrderSpecification();

            // 3. Zamknij okno modalne
            closeOrderModal();

            showToast('Zamówienie zostało sfinalizowane i oznaczone jako Zrealizowane!');
        }

        // =========================================================================
        // EKSPORT ERP & USTAWIENIA HURTOWNI
        // =========================================================================

        function savePreferredErp(format) {
            if (!format) return;
            try {
                localStorage.setItem('b2b_preferred_erp_format', format);
            } catch(e) {}
            const mSel = document.getElementById('modal-erp-format');
            const bSel = document.getElementById('batch-erp-format');
            const sSel = document.getElementById('settings-erp-format');
            if (mSel && mSel.value !== format) mSel.value = format;
            if (bSel && bSel.value !== format) bSel.value = format;
            if (sSel && sSel.value !== format) sSel.value = format;
            updateModalErpBtnLabel(format);
        }

        function updateModalErpBtnLabel(format) {
            const lbl = document.getElementById('modal-erp-btn-label');
            if (!lbl) return;
            const names = {
                'subiekt': 'Subiekt (.epp)',
                'optima': 'Optima (.xml)',
                'symfonia': 'Symfonia (.txt)',
                'wfmag': 'Wf-Mag (.xml)'
            };
            lbl.textContent = 'Eksport ' + (names[format] || 'ERP');
        }

        function exportCurrentOrderErp() {
            if (!currentModalOrderId) {
                showToast('Nie wybrano zamówienia do eksportu.');
                return;
            }
            const sel = document.getElementById('modal-erp-format');
            const fmt = sel ? sel.value : (localStorage.getItem('b2b_preferred_erp_format') || 'subiekt');
            savePreferredErp(fmt);
            window.location.href = BASE_URL + 'b2b/exporterp?id=' + currentModalOrderId + '&format=' + encodeURIComponent(fmt);
        }

        function downloadBatchErp() {
            const sel = document.getElementById('batch-erp-format');
            const fmt = sel ? sel.value : (localStorage.getItem('b2b_preferred_erp_format') || 'subiekt');
            savePreferredErp(fmt);
            let statusParam = currentOrderStatusFilter || 'all';
            let url = BASE_URL + 'b2b/exportbatch?format=' + encodeURIComponent(fmt);
            if (statusParam && statusParam !== 'all') {
                url += '&status=' + encodeURIComponent(statusParam);
            }
            window.location.href = url;
        }

        function saveWholesaleSettings(e) {
            if (e) e.preventDefault();
            const btn = document.getElementById('btn-save-settings');
            const statusMsg = document.getElementById('settings-status-msg');
            const cutoffInput = document.getElementById('settings-cutoff');
            const erpSelect = document.getElementById('settings-erp-format');

            const dayCheckboxes = document.querySelectorAll('.settings-day-chk:checked');
            const selectedDays = Array.from(dayCheckboxes).map(c => c.value).join(',');

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = `
                    <svg class="animate-spin -ml-0.5 mr-1 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Zapisywanie...</span>
                `;
            }

            const fd = new FormData();
            fd.append('cutoff_time', cutoffInput ? cutoffInput.value : '21:30');
            fd.append('delivery_days', selectedDays);
            fd.append('default_erp_format', erpSelect ? erpSelect.value : 'subiekt');
            fd.append('_csrf', CSRF_TOKEN);

            fetch(BASE_URL + 'b2b/savesettings', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            })
            .then(r => r.json())
            .then(d => {
                if (!d.ok) throw new Error(d.error || 'Błąd zapisu ustawień');
                savePreferredErp(erpSelect ? erpSelect.value : 'subiekt');
                showToast('Ustawienia hurtowni i ERP zostały pomyślnie zaktualizowane!');
                if (statusMsg) {
                    statusMsg.textContent = 'Zapisano pomyślnie!';
                    statusMsg.classList.remove('hidden');
                    setTimeout(() => statusMsg.classList.add('hidden'), 3500);
                }
            })
            .catch(err => {
                alert('Błąd: ' + err.message);
            })
            .finally(() => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = `
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Zapisz ustawienia</span>
                    `;
                }
            });
        }

        // Inicjalizacja domyślnego filtru statusu zamówień (domyślnie 'new')
        const savedOrdersFilter = (function() {
            try { return sessionStorage.getItem('b2b_orders_status_filter') || 'new'; } catch(e) { return 'new'; }
        })();
        filterOrdersByStatus(savedOrdersFilter);

        // Inicjalizacja aktywnej zakładki (przywrócenie ostatnio otwartej, np. Klienci po odświeżeniu)
        (function initActiveTab() {
            const validTabs = ['products', 'orders', 'clients', 'settings'];
            let targetTab = null;

            // 1. Sprawdź hash w URL (#clients, #orders, #products, #settings)
            const hash = (window.location.hash || '').replace('#', '').trim();
            if (validTabs.includes(hash)) {
                targetTab = hash;
            }

            // 2. Jeśli brak hasha, sprawdź parametr URL (?tab=settings)
            if (!targetTab) {
                const urlParams = new URLSearchParams(window.location.search);
                const qTab = urlParams.get('tab');
                if (validTabs.includes(qTab)) {
                    targetTab = qTab;
                }
            }

            // 3. Jeśli brak w URL, odczytaj z localStorage
            if (!targetTab) {
                try {
                    const saved = localStorage.getItem('b2b_admin_tab');
                    if (validTabs.includes(saved)) {
                        targetTab = saved;
                    }
                } catch (e) {}
            }

            if (targetTab && targetTab !== 'products') {
                switchTab(targetTab, false);
            }
        })();

        // Inicjalizacja zapamiętanego formatu ERP (z localStorage lub domyślnego z bazy)
        (function initPreferredErp() {
            let pref = '';
            try {
                pref = localStorage.getItem('b2b_preferred_erp_format');
            } catch(e) {}
            if (!pref) {
                const sSel = document.getElementById('settings-erp-format');
                pref = sSel ? sSel.value : 'subiekt';
            }
            savePreferredErp(pref);
        })();
    </script>
</body>
</html>
