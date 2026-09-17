<?php
/**
 * Podgląd szczegółów archiwalnego zamówienia warzyw i owoców.
 */
$pageTitle = Tools::h($view['title'] ?? 'Szczegóły zamówienia');
$userName  = Tools::h($view['user'] ?? 'użytkownik');
$order     = $view['order'] ?? [];
$items     = $view['items'] ?? [];
$base      = App::baseUrl();
$appName   = defined('APP_NAME') ? APP_NAME : 'TwiiCoreF';
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

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--card);
            backdrop-filter: blur(20px);
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

        .card {
            background: var(--card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: var(--radius-outer);
            padding: 28px 32px;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            background: var(--card-inner);
            border: 1px solid var(--border);
            border-radius: var(--radius-inner);
            padding: 18px 20px;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .meta-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
            font-weight: 700;
        }

        .meta-val {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--fg);
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            padding: 10px 20px;
            font-size: 0.95rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 8px 16px -6px rgba(37, 99, 235, 0.5);
            transition: transform 0.15s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #fff;
            color: var(--fg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px 18px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.15s ease;
        }

        .btn-secondary:hover {
            background: #f8fafc;
        }

        .table-wrap {
            border: 1px solid var(--border);
            border-radius: var(--radius-inner);
            overflow: hidden;
            background: #fff;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.92rem;
        }

        .table th {
            background: #f8fafc;
            padding: 14px 18px;
            text-align: left;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
            border-bottom: 2px solid var(--border);
        }

        .table td {
            padding: 14px 18px;
            border-bottom: 1px solid #f1f5f9;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .total-row td {
            background: #f8fafc;
            font-weight: 800;
            font-size: 1.05rem;
            border-top: 2px solid var(--border);
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>

    <div class="shell">
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
                <a href="<?= $base ?>order/index" class="nav-btn">Nowe zamówienie</a>
                <a href="<?= $base ?>order/history" class="nav-btn active">Historia zamówień</a>
                <a href="<?= $base ?>home/index" class="nav-btn">Pulpit</a>
                <a href="<?= $base ?>home/logout" class="nav-btn" style="color: var(--danger);">Wyloguj</a>
            </nav>
        </header>

        <section class="card">
            <div class="card-header">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                        <a href="<?= $base ?>order/history" style="color: var(--muted); text-decoration: none; font-size: 0.88rem;">&larr; Wróć do listy</a>
                    </div>
                    <h1 style="margin: 0; font-size: 1.5rem; font-weight: 800;"><?= Tools::h($order['order_number'] ?? 'Zamówienie') ?></h1>
                </div>

                <div style="display: flex; gap: 12px; align-items: center;">
                    <a href="<?= $base ?>order/download/id/<?= (int)$order['id'] ?>" class="btn-primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        <span>Pobierz arkusz (.xlsx)</span>
                    </a>
                </div>
            </div>

            <!-- Metadane -->
            <div class="meta-grid">
                <div class="meta-item">
                    <span class="meta-label">Data utworzenia</span>
                    <span class="meta-val"><?= Tools::h($order['created_at']) ?></span>
                </div>

                <div class="meta-item">
                    <span class="meta-label">Hurtownia / Dostawca</span>
                    <span class="meta-val"><?= Tools::h($order['supplier_name'] ?: 'Nie określono') ?></span>
                </div>

                <div class="meta-item">
                    <span class="meta-label">Plik cennika źródłowego</span>
                    <span class="meta-val" style="font-size: 0.9rem;"><?= Tools::h($order['original_filename']) ?></span>
                </div>

                <div class="meta-item">
                    <span class="meta-label">Łączna kwota</span>
                    <span class="meta-val" style="color: #1e3a8a;"><?= number_format((float)$order['total_amount'], 2, '.', ' ') ?> zł</span>
                </div>
            </div>

            <!-- Tabela pozycji -->
            <div>
                <h3 style="margin: 0 0 14px; font-size: 1.1rem; font-weight: 800;">Pozycje zamówienia (<?= count($items) ?>)</h3>

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">Lp.</th>
                                <th>Nazwa towaru</th>
                                <th style="width: 140px;">Cena jednostkowa</th>
                                <th style="width: 140px;">Zamówiona ilość</th>
                                <th style="width: 120px;">Jednostka</th>
                                <th style="width: 150px; text-align: right;">Wartość</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $lp = 1; foreach ($items as $it): ?>
                                <tr>
                                    <td style="color: var(--muted);"><?= $lp++ ?></td>
                                    <td style="font-weight: 700;"><?= Tools::h($it['product_name']) ?></td>
                                    <td><?= number_format((float)$it['unit_price'], 2, '.', ' ') ?> zł</td>
                                    <td><strong style="color: var(--blue); font-size: 1rem;"><?= (float)$it['quantity'] ?></strong></td>
                                    <td><?= Tools::h($it['unit']) ?></td>
                                    <td style="text-align: right; font-weight: 700; color: #1e3a8a;">
                                        <?= number_format((float)$it['item_total'], 2, '.', ' ') ?> zł
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td colspan="5" style="text-align: right;">RAZEM DO ZAPŁATY:</td>
                                <td style="text-align: right; color: #1e3a8a;"><?= number_format((float)$order['total_amount'], 2, '.', ' ') ?> zł</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
