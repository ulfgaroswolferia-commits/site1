<?php
/**
 * Kreator zamówienia warzyw i owoców ze sklepu spożywczego do hurtowni.
 */
$pageTitle  = Tools::h($view['title'] ?? 'Kreator zamówienia');
$userName   = Tools::h($view['user'] ?? 'użytkownik');
$csrfToken  = Tools::h($view['csrf_token'] ?? Tools::csrfToken());
$base       = App::baseUrl();
$appName    = defined('APP_NAME') ? APP_NAME : 'TwiiCoreF';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?> — <?= Tools::h($appName) ?></title>
    <style>
        :root {
            --bg: #f8fafc;
            --fg: #0f172a;
            --muted: #64748b;
            --card: rgba(255, 255, 255, 0.88);
            --card-inner: rgba(255, 255, 255, 0.96);
            --border: rgba(226, 232, 240, 0.85);
            --blue: #2563eb;
            --blue-hover: #1d4ed8;
            --blue-light: rgba(37, 99, 235, 0.08);
            --emerald: #10b981;
            --emerald-light: rgba(16, 185, 129, 0.12);
            --danger: #ef4444;
            --shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.08);
            --radius-outer: 24px;
            --radius-inner: 16px;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--fg);
            min-height: 100vh;
        }

        .bg-grid {
            position: fixed;
            inset: 0;
            background-image: radial-gradient(rgba(15, 23, 42, 0.06) 1.2px, transparent 1.2px);
            background-size: 24px 24px;
            opacity: 0.85;
            pointer-events: none;
            z-index: 0;
        }

        .shell {
            position: relative;
            z-index: 1;
            max-width: 1240px;
            margin: 0 auto;
            padding: 24px 20px 80px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        /* Topbar */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 12px 20px;
            box-shadow: var(--shadow);
            flex-wrap: wrap;
            gap: 12px;
        }

        .brand-group {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #10b981 0%, #06b6d4 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 8px 16px -6px rgba(16, 185, 129, 0.4);
        }

        .brand-icon svg { width: 22px; height: 22px; }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-btn {
            padding: 8px 14px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.86rem;
            font-weight: 600;
            color: var(--muted);
            transition: all 0.15s ease;
        }

        .nav-btn:hover, .nav-btn.active {
            color: var(--blue);
            background: var(--blue-light);
        }

        /* Wizard Cards */
        .wizard-card {
            background: var(--card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: var(--radius-outer);
            padding: 32px;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .step-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 12px;
        }

        .step-title {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .step-badge {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: 800;
        }

        /* Upload Dropzone */
        .dropzone {
            border: 2px dashed rgba(37, 99, 235, 0.35);
            border-radius: var(--radius-inner);
            padding: 48px 24px;
            text-align: center;
            background: rgba(255, 255, 255, 0.6);
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .dropzone:hover, .dropzone.dragover {
            border-color: var(--blue);
            background: rgba(37, 99, 235, 0.04);
            transform: scale(1.005);
        }

        .dropzone-icon {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            background: var(--blue-light);
            color: var(--blue);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .dropzone-icon svg { width: 32px; height: 32px; }

        .dropzone h3 {
            margin: 0 0 6px;
            font-size: 1.15rem;
            font-weight: 700;
        }

        .dropzone p {
            margin: 0;
            color: var(--muted);
            font-size: 0.88rem;
        }

        .file-input-hidden {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        /* Form elements */
        .form-row {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .form-col {
            flex: 1;
            min-width: 220px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-label {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #334155;
        }

        .form-input, .form-select {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 0.95rem;
            background: #fff;
            color: var(--fg);
            outline: none;
            transition: border-color 0.15s ease;
        }

        .form-input:focus, .form-select:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px var(--blue-light);
        }

        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%);
            color: white;
            border: none;
            border-radius: 14px;
            padding: 14px 24px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 10px 20px -8px rgba(37, 99, 235, 0.6);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.15s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 24px -8px rgba(37, 99, 235, 0.7);
        }

        .btn-secondary {
            background: #fff;
            color: var(--fg);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 12px 20px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        /* Mapping Preview Table */
        .preview-box {
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: var(--radius-inner);
            background: #fff;
            max-height: 280px;
        }

        .table-preview {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .table-preview th {
            background: #f1f5f9;
            padding: 10px 14px;
            font-weight: 700;
            color: #475569;
            border-bottom: 2px solid var(--border);
            text-align: left;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .table-preview td {
            padding: 10px 14px;
            border-bottom: 1px solid #f1f5f9;
            white-space: nowrap;
        }

        .table-preview tr:hover td {
            background: #f8fafc;
        }

        /* Products Order Table */
        .toolbar-sticky {
            position: sticky;
            top: 16px;
            z-index: 10;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 14px 20px;
            box-shadow: 0 10px 25px -10px rgba(15, 23, 42, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
            flex: 1;
            min-width: 260px;
        }

        .search-input {
            width: 100%;
            padding: 10px 14px 10px 38px;
            border-radius: 12px;
            border: 1px solid var(--border);
            font-size: 0.95rem;
            outline: none;
            background: #fff;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            pointer-events: none;
        }

        .order-summary-pill {
            display: flex;
            align-items: center;
            gap: 16px;
            background: var(--blue-light);
            border: 1px solid rgba(37, 99, 235, 0.2);
            padding: 8px 18px;
            border-radius: 12px;
        }

        .summary-stat {
            display: flex;
            flex-direction: column;
            line-height: 1.15;
        }

        .summary-label {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--blue);
            font-weight: 700;
        }

        .summary-value {
            font-size: 1.15rem;
            font-weight: 800;
            color: #1e3a8a;
        }

        .table-products-wrap {
            border: 1px solid var(--border);
            border-radius: var(--radius-inner);
            overflow: hidden;
            background: #fff;
            box-shadow: var(--shadow);
        }

        .table-products {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.92rem;
        }

        .table-products th {
            background: #f8fafc;
            padding: 14px 18px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
            border-bottom: 2px solid var(--border);
            text-align: left;
        }

        .table-products td {
            padding: 14px 18px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .table-products tr.has-qty {
            background: rgba(16, 185, 129, 0.04);
        }

        .table-products tr:hover {
            background: #f8fafc;
        }

        .prod-name {
            font-weight: 700;
            color: var(--fg);
        }

        .prod-price {
            font-weight: 600;
            color: #334155;
            white-space: nowrap;
        }

        /* Quantity input group */
        .qty-control {
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #fff;
            overflow: hidden;
        }

        .qty-btn {
            background: #f8fafc;
            border: none;
            width: 32px;
            height: 36px;
            font-size: 1.1rem;
            font-weight: 700;
            color: #475569;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s ease;
        }

        .qty-btn:hover {
            background: #e2e8f0;
        }

        .qty-input {
            width: 64px;
            height: 36px;
            border: none;
            text-align: center;
            font-size: 1rem;
            font-weight: 700;
            outline: none;
            color: var(--fg);
        }

        .item-val {
            font-weight: 800;
            color: #1e3a8a;
            white-space: nowrap;
        }

        /* Success Step Card */
        .success-box {
            text-align: center;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
        }

        .success-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: var(--emerald-light);
            color: var(--emerald);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-icon svg { width: 40px; height: 40px; }

        .hidden { display: none !important; }
    </style>
</head>
<body>
    <div class="bg-grid"></div>

    <div class="shell">
        <!-- Top Navigation -->
        <header class="topbar">
            <a href="<?= $base ?>home/index" class="brand-group">
                <div class="brand-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                </div>
                <div>
                    <strong style="font-size: 1.1rem;"><?= Tools::h($appName) ?></strong>
                    <span style="font-size: 0.8rem; color: var(--muted); display: block;">Moduł zamówień hurtowych</span>
                </div>
            </a>

            <nav class="nav-links">
                <a href="<?= $base ?>order/index" class="nav-btn active">Nowe zamówienie</a>
                <a href="<?= $base ?>order/history" class="nav-btn">Historia zamówień</a>
                <a href="<?= $base ?>home/index" class="nav-btn">Pulpit</a>
                <a href="<?= $base ?>home/logout" class="nav-btn" style="color: var(--danger);">Wyloguj</a>
            </nav>
        </header>

        <!-- KROK 1: Wgranie cennika -->
        <section id="step-1" class="wizard-card">
            <div class="step-header">
                <h2 class="step-title">
                    <span class="step-badge">1</span>
                    Wgraj plik cennika z hurtowni
                </h2>
                <span style="font-size: 0.85rem; color: var(--muted);">Format: <strong>.xlsx</strong> (nawet ze zdjęciami i banerami)</span>
            </div>

            <p style="margin: 0; color: var(--muted); line-height: 1.5;">
                Przeciągnij lub wybierz plik Excela otrzymany z hurtowni warzyw i owoców. Parser automatycznie pominie wszelkie logotypy i grafiki, odczytując surową tabelę z towarami i cenami.
            </p>

            <div class="form-row">
                <div class="form-col">
                    <label class="form-label" for="supplier-name">Nazwa hurtowni / Notatka (opcjonalnie)</label>
                    <input id="supplier-name" type="text" class="form-input" placeholder="np. Hurtownia Agro-Plon (czwartek)" maxlength="100">
                </div>
            </div>

            <div id="dropzone" class="dropzone">
                <input id="file-input" type="file" class="file-input-hidden" accept=".xlsx">
                <div class="dropzone-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                </div>
                <h3>Przeciągnij plik cennika tutaj lub kliknij, aby wybrać</h3>
                <p>Obsługiwany format: Microsoft Excel OpenXML (.xlsx)</p>
            </div>

            <div id="upload-status" style="font-weight: 600; font-size: 0.9rem; text-align: center;" class="hidden"></div>
        </section>

        <!-- KROK 2: Mapowanie kolumn -->
        <section id="step-2" class="wizard-card hidden">
            <div class="step-header">
                <h2 class="step-title">
                    <span class="step-badge">2</span>
                    Potwierdź mapowanie kolumn cennika
                </h2>
                <button type="button" class="btn-secondary" onclick="resetToStep1()">Zmień plik</button>
            </div>

            <p style="margin: 0; color: var(--muted); font-size: 0.92rem;">
                Sprawdź podgląd wczytanych wierszy i potwierdź, która kolumna zawiera nazwy towarów, a która ceny. System automatycznie zasugerował najczęstsze dopasowania.
            </p>

            <!-- Podgląd wierszy -->
            <div class="preview-box">
                <table class="table-preview" id="preview-table">
                    <!-- Dynamicznie generowane wiersze -->
                </table>
            </div>

            <!-- Formularz wyboru kolumn -->
            <div class="form-row" style="background: var(--card-inner); padding: 18px; border-radius: 14px; border: 1px solid var(--border);">
                <div class="form-col">
                    <label class="form-label" for="map-header-row">Wiersz z nagłówkami kolumn</label>
                    <select id="map-header-row" class="form-select"></select>
                </div>

                <div class="form-col">
                    <label class="form-label" for="map-col-product">Kolumna: Nazwa towaru</label>
                    <select id="map-col-product" class="form-select"></select>
                </div>

                <div class="form-col">
                    <label class="form-label" for="map-col-price">Kolumna: Cena jednostkowa</label>
                    <select id="map-col-price" class="form-select"></select>
                </div>

                <div class="form-col">
                    <label class="form-label" for="map-col-unit">Kolumna: Jednostka (opcjonalnie)</label>
                    <select id="map-col-unit" class="form-select">
                        <option value="">-- Domyślnie (kg / szt.) --</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" id="btn-process-columns" class="btn-primary">
                    <span>Załaduj asortyment do zamówienia</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>
        </section>

        <!-- KROK 3: Wprowadzanie ilości i zamówienie -->
        <section id="step-3" class="wizard-card hidden" style="padding: 0; background: transparent; border: none; box-shadow: none;">
            <!-- Sticky Toolbar -->
            <div class="toolbar-sticky">
                <div class="search-box">
                    <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input id="product-search" type="text" class="search-input" placeholder="Filtruj produkty (np. pomidor, ziemniak, jabłko)...">
                </div>

                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; color: #334155;">
                    <input id="filter-ordered-only" type="checkbox" style="width: 16px; height: 16px; accent-color: var(--blue);">
                    <span>Tylko z wpisaną ilością</span>
                </label>

                <div class="order-summary-pill">
                    <div class="summary-stat">
                        <span class="summary-label">Pozycje</span>
                        <span id="summary-items-count" class="summary-value">0</span>
                    </div>
                    <div style="width: 1px; height: 28px; background: rgba(37, 99, 235, 0.2);"></div>
                    <div class="summary-stat">
                        <span class="summary-label">Razem do zapłaty</span>
                        <span id="summary-total-amount" class="summary-value">0.00 zł</span>
                    </div>
                </div>

                <button type="button" id="btn-submit-order" class="btn-primary" style="padding: 11px 22px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <span>Zatwierdź i pobierz Excel</span>
                </button>
            </div>

            <!-- Products Table -->
            <div class="table-products-wrap">
                <table class="table-products">
                    <thead>
                        <tr>
                            <th style="width: 50px;">Lp.</th>
                            <th>Nazwa towaru</th>
                            <th style="width: 130px;">Cena hurtowa</th>
                            <th style="width: 180px;">Zamawiana ilość</th>
                            <th style="width: 110px;">Jednostka</th>
                            <th style="width: 140px;">Wartość</th>
                        </tr>
                    </thead>
                    <tbody id="products-table-body">
                        <!-- Wiersze produktów wstawiane dynamicznie -->
                    </tbody>
                </table>
            </div>
        </section>

        <!-- KROK 4: Ekran sukcesu -->
        <section id="step-4" class="wizard-card hidden">
            <div class="success-box">
                <div class="success-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>

                <h2 style="margin: 0; font-size: 1.8rem; font-weight: 800;">Zamówienie zostało zapisane!</h2>
                <p style="margin: 0; color: var(--muted); font-size: 1.05rem; max-width: 540px;">
                    Czysty plik arkusza Excel dla hurtowni o numerze <strong id="success-order-num" style="color: var(--blue);"></strong> jest gotowy do wysyłki.
                </p>

                <div style="background: var(--card-inner); border: 1px solid var(--border); border-radius: 16px; padding: 18px 28px; display: flex; gap: 32px;">
                    <div>
                        <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--muted); font-weight: 700; display: block;">Zamówione pozycje</span>
                        <strong id="success-items-count" style="font-size: 1.4rem; color: var(--fg);"></strong>
                    </div>
                    <div>
                        <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--muted); font-weight: 700; display: block;">Łączna wartość</span>
                        <strong id="success-total-amount" style="font-size: 1.4rem; color: #1e3a8a;"></strong>
                    </div>
                </div>

                <div style="display: flex; gap: 14px; flex-wrap: wrap; justify-content: center; margin-top: 10px;">
                    <a id="btn-download-again" href="#" class="btn-primary" style="text-decoration: none;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        <span>Pobierz plik ponownie (.xlsx)</span>
                    </a>

                    <a href="<?= $base ?>order/history" class="btn-secondary">
                        <span>Zobacz w historii zamówień</span>
                    </a>

                    <a href="<?= $base ?>order/index" class="btn-secondary">
                        <span>Nowe zamówienie</span>
                    </a>
                </div>
            </div>
        </section>
    </div>

    <!-- Interaktywna logika kreatora (Vanilla JS) -->
    <script>
        const CSRF_TOKEN = '<?= $csrfToken ?>';
        const BASE_URL   = '<?= $base ?>';

        let currentFileId = null;
        let originalFileName = null;
        let previewRowsData = {};
        let productsList = [];

        // Elementy DOM
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('file-input');
        const uploadStatus = document.getElementById('upload-status');
        const step1 = document.getElementById('step-1');
        const step2 = document.getElementById('step-2');
        const step3 = document.getElementById('step-3');
        const step4 = document.getElementById('step-4');

        // Obsługa Drag & Drop
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });
        dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) {
                handleFileUpload(e.dataTransfer.files[0]);
            }
        });
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                handleFileUpload(fileInput.files[0]);
            }
        });

        function showStatus(text, isError = false) {
            uploadStatus.textContent = text;
            uploadStatus.style.color = isError ? '#ef4444' : '#2563eb';
            uploadStatus.classList.remove('hidden');
        }

        // 1. Upload pliku do actionUpload
        function handleFileUpload(file) {
            if (!file.name.toLowerCase().endsWith('.xlsx')) {
                showStatus('Proszę wybrać plik z rozszerzeniem .xlsx', true);
                return;
            }

            showStatus('Wczytywanie i parsowanie arkusza Excel...');
            const formData = new FormData();
            formData.append('price_list', file);
            formData.append('csrf_token', CSRF_TOKEN);

            fetch(BASE_URL + 'order/upload', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (!data.ok) {
                    showStatus(data.error || 'Wystąpił błąd przy przetwarzaniu pliku.', true);
                    return;
                }
                currentFileId = data.file_id;
                originalFileName = data.filename;
                previewRowsData = data.preview;
                renderStep2(data.preview, data.candidates);
            })
            .catch(err => {
                showStatus('Błąd połączenia z serwerem: ' + err.message, true);
            });
        }

        function resetToStep1() {
            step2.classList.add('hidden');
            step3.classList.add('hidden');
            step4.classList.add('hidden');
            step1.classList.remove('hidden');
            uploadStatus.classList.add('hidden');
            fileInput.value = '';
        }

        // 2. Krok 2: Renderowanie podglądu i selektorów mapowania
        function renderStep2(previewRows, candidates) {
            step1.classList.add('hidden');
            step2.classList.remove('hidden');

            const previewTable = document.getElementById('preview-table');
            const mapHeader = document.getElementById('map-header-row');
            const mapProd = document.getElementById('map-col-product');
            const mapPrice = document.getElementById('map-col-price');
            const mapUnit = document.getElementById('map-col-unit');

            // Czyszczenie
            previewTable.innerHTML = '';
            mapHeader.innerHTML = '';
            mapProd.innerHTML = '';
            mapPrice.innerHTML = '';
            mapUnit.innerHTML = '<option value="">-- Domyślnie (kg / szt.) --</option>';

            // Znalezienie maksymalnej liczby kolumn
            let maxCols = 0;
            for (const r in previewRows) {
                const keys = Object.keys(previewRows[r]).map(Number);
                if (keys.length > 0) {
                    maxCols = Math.max(maxCols, Math.max(...keys) + 1);
                }
            }

            // Nagłówek tabeli podglądu (Litery A, B, C...)
            let theadHtml = '<tr><th style="width: 40px;">#</th>';
            for (let c = 0; c < maxCols; c++) {
                const letter = String.fromCharCode(65 + (c % 26));
                theadHtml += `<th>Kolumna ${letter}</th>`;
            }
            theadHtml += '</tr>';
            previewTable.innerHTML += theadHtml;

            // Wiersze podglądu
            for (const r in previewRows) {
                const rowCells = previewRows[r];
                let rowHtml = `<tr><td style="font-weight: 700; color: #94a3b8;">${r}</td>`;
                for (let c = 0; c < maxCols; c++) {
                    const val = rowCells[c] !== undefined ? rowCells[c] : '';
                    rowHtml += `<td>${escapeHtml(val)}</td>`;
                }
                rowHtml += '</tr>';
                previewTable.innerHTML += rowHtml;

                // Opcja wyboru wiersza nagłówka
                const optHeader = document.createElement('option');
                optHeader.value = r;
                optHeader.textContent = `Wiersz ${r}`;
                if (Number(r) === Number(candidates.headerRow)) {
                    optHeader.selected = true;
                }
                mapHeader.appendChild(optHeader);
            }

            // Opcje dla kolumn
            for (let c = 0; c < maxCols; c++) {
                const letter = String.fromCharCode(65 + (c % 26));
                // Weź przykładową niepustą wartość z kolumny
                let sampleVal = '';
                for (const r in previewRows) {
                    if (previewRows[r][c]) {
                        sampleVal = ` (np. "${previewRows[r][c]}")`;
                        break;
                    }
                }

                const optProd = new Option(`Kolumna ${letter}${sampleVal}`, c);
                if (c === Number(candidates.productCol)) optProd.selected = true;
                mapProd.appendChild(optProd);

                const optPrice = new Option(`Kolumna ${letter}${sampleVal}`, c);
                if (c === Number(candidates.priceCol)) optPrice.selected = true;
                mapPrice.appendChild(optPrice);

                const optUnit = new Option(`Kolumna ${letter}${sampleVal}`, c);
                if (candidates.unitCol !== null && c === Number(candidates.unitCol)) optUnit.selected = true;
                mapUnit.appendChild(optUnit);
            }
        }

        // Obsługa przycisku "Załaduj asortyment"
        document.getElementById('btn-process-columns').addEventListener('click', () => {
            const headerRow = document.getElementById('map-header-row').value;
            const colProd = document.getElementById('map-col-product').value;
            const colPrice = document.getElementById('map-col-price').value;
            const colUnit = document.getElementById('map-col-unit').value;

            const formData = new FormData();
            formData.append('file_id', currentFileId);
            formData.append('header_row', headerRow);
            formData.append('col_product', colProd);
            formData.append('col_price', colPrice);
            formData.append('col_unit', colUnit);
            formData.append('csrf_token', CSRF_TOKEN);

            fetch(BASE_URL + 'order/process', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (!data.ok) {
                    alert(data.error || 'Błąd przetwarzania produktów.');
                    return;
                }
                productsList = data.products.map(p => ({
                    ...p,
                    quantity: 0
                }));
                renderStep3();
            })
            .catch(err => alert('Błąd: ' + err.message));
        });

        // 3. Krok 3: Lista produktów i interaktywna tabela
        function renderStep3() {
            step2.classList.add('hidden');
            step3.classList.remove('hidden');
            renderProductsTable();
        }

        function renderProductsTable() {
            const tbody = document.getElementById('products-table-body');
            const searchVal = document.getElementById('product-search').value.toLowerCase().trim();
            const onlyOrdered = document.getElementById('filter-ordered-only').checked;

            tbody.innerHTML = '';

            let matchedCount = 0;

            productsList.forEach((prod, index) => {
                const nameLower = prod.name.toLowerCase();
                const matchesSearch = !searchVal || nameLower.includes(searchVal);
                const matchesOrdered = !onlyOrdered || prod.quantity > 0;

                if (!matchesSearch || !matchesOrdered) {
                    return;
                }

                matchedCount++;
                const itemTotal = (prod.quantity * prod.price).toFixed(2);
                const hasQtyClass = prod.quantity > 0 ? 'has-qty' : '';

                const tr = document.createElement('tr');
                tr.className = hasQtyClass;
                tr.innerHTML = `
                    <td style="color: #94a3b8; font-weight: 600;">${index + 1}</td>
                    <td>
                        <span class="prod-name">${escapeHtml(prod.name)}</span>
                    </td>
                    <td>
                        <span class="prod-price">${prod.price.toFixed(2)} zł</span>
                    </td>
                    <td>
                        <div class="qty-control">
                            <button type="button" class="qty-btn" onclick="changeQty(${index}, -1)">-</button>
                            <input type="number" step="0.5" min="0" class="qty-input" value="${prod.quantity === 0 ? '' : prod.quantity}" placeholder="0" oninput="setQty(${index}, this.value)">
                            <button type="button" class="qty-btn" onclick="changeQty(${index}, 1)">+</button>
                        </div>
                    </td>
                    <td>
                        <select class="form-select" style="padding: 6px 8px; font-size: 0.85rem;" onchange="setUnit(${index}, this.value)">
                            <option value="kg" ${prod.unit === 'kg' ? 'selected' : ''}>kg</option>
                            <option value="szt." ${prod.unit === 'szt.' ? 'selected' : ''}>szt.</option>
                            <option value="op." ${prod.unit === 'op.' ? 'selected' : ''}>op.</option>
                            <option value="pęczek" ${prod.unit === 'pęczek' ? 'selected' : ''}>pęczek</option>
                        </select>
                    </td>
                    <td>
                        <span class="item-val" id="val-${index}">${itemTotal} zł</span>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            if (matchedCount === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 32px; color: var(--muted);">Brak produktów pasujących do filtra.</td></tr>';
            }

            updateSummary();
        }

        function changeQty(index, delta) {
            let current = parseFloat(productsList[index].quantity) || 0;
            current = Math.max(0, current + delta);
            productsList[index].quantity = current;
            renderProductsTable();
        }

        function setQty(index, value) {
            let val = parseFloat(value) || 0;
            if (val < 0) val = 0;
            productsList[index].quantity = val;
            const itemTotal = (val * productsList[index].price).toFixed(2);
            const valEl = document.getElementById('val-' + index);
            if (valEl) valEl.textContent = itemTotal + ' zł';
            updateSummary();
        }

        function setUnit(index, unitVal) {
            productsList[index].unit = unitVal;
        }

        function updateSummary() {
            let itemsCount = 0;
            let totalAmount = 0.0;

            productsList.forEach(p => {
                if (p.quantity > 0) {
                    itemsCount++;
                    totalAmount += p.quantity * p.price;
                }
            });

            document.getElementById('summary-items-count').textContent = itemsCount;
            document.getElementById('summary-total-amount').textContent = totalAmount.toFixed(2) + ' zł';
        }

        document.getElementById('product-search').addEventListener('input', renderProductsTable);
        document.getElementById('filter-ordered-only').addEventListener('change', renderProductsTable);

        // 4. Zatwierdzenie i generowanie zamówienia
        document.getElementById('btn-submit-order').addEventListener('click', () => {
            const orderedItems = productsList.filter(p => p.quantity > 0);
            if (orderedItems.length === 0) {
                alert('Wprowadź ilość dla przynajmniej jednego produktu, aby utworzyć zamówienie.');
                return;
            }

            const supplierName = document.getElementById('supplier-name').value;
            const formData = new FormData();
            formData.append('supplier_name', supplierName);
            formData.append('original_filename', originalFileName || 'cennik.xlsx');
            formData.append('items', JSON.stringify(orderedItems));
            formData.append('csrf_token', CSRF_TOKEN);

            const btn = document.getElementById('btn-submit-order');
            btn.disabled = true;
            btn.innerHTML = '<span>Generowanie pliku...</span>';

            fetch(BASE_URL + 'order/save', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<span>Zatwierdź i pobierz Excel</span>';

                if (!data.ok) {
                    alert(data.error || 'Błąd podczas zapisu zamówienia.');
                    return;
                }

                // Sukces: przejście do Kroku 4
                step3.classList.add('hidden');
                step4.classList.remove('hidden');

                document.getElementById('success-order-num').textContent = data.order_number;
                document.getElementById('success-items-count').textContent = data.total_items + ' pozycji';
                document.getElementById('success-total-amount').textContent = Number(data.total_amount).toFixed(2) + ' zł';
                document.getElementById('btn-download-again').href = data.download_url;

                // Automatyczne pobranie pliku Excela
                window.location.href = data.download_url;
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<span>Zatwierdź i pobierz Excel</span>';
                alert('Błąd połączenia: ' + err.message);
            });
        });

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    </script>
</body>
</html>
