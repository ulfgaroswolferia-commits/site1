<?php
/**
 * Historia zamówień warzyw i owoców.
 */
$pageTitle = Tools::h($view['title'] ?? 'Historia zamówień');
$userName  = Tools::h($view['user'] ?? 'użytkownik');
$orders    = $view['orders'] ?? [];
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
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .card-title {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 800;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            padding: 10px 18px;
            font-size: 0.9rem;
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

        .btn-sm {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }

        .btn-sm-download {
            background: var(--emerald-light);
            color: #065f46;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .btn-sm-download:hover {
            background: #d1fae5;
        }

        .btn-sm-view {
            background: var(--blue-light);
            color: var(--blue);
            border: 1px solid rgba(37, 99, 235, 0.25);
        }

        .btn-sm-view:hover {
            background: #dbeafe;
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
            font-size: 0.9rem;
        }

        .table th {
            background: #f8fafc;
            padding: 12px 18px;
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

        .table tr:hover td {
            background: #f8fafc;
        }

        .order-badge {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-weight: 700;
            font-size: 0.88rem;
            color: var(--blue);
        }

        .order-amount {
            font-weight: 800;
            color: #1e3a8a;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
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
                    <h1 class="card-title">Historia złożonych zamówień</h1>
                    <span style="font-size: 0.88rem; color: var(--muted);">Zestawienie wygenerowanych zamówień do hurtowni warzyw i owoców</span>
                </div>

                <a href="<?= $base ?>order/index" class="btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <span>Nowe zamówienie</span>
                </a>
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 12px; color: #cbd5e1;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <h3 style="margin: 0 0 6px; font-size: 1.1rem; color: var(--fg);">Brak zamówień w historii</h3>
                    <p style="margin: 0 0 16px;">Nie utworzono jeszcze żadnego zamówienia.</p>
                    <a href="<?= $base ?>order/index" class="btn-primary">Utwórz pierwsze zamówienie</a>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Numer zamówienia</th>
                                <th>Data i godzina</th>
                                <th>Hurtownia / Źródło</th>
                                <th>Pozycje</th>
                                <th>Łączna kwota</th>
                                <th style="text-align: right;">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td>
                                        <span class="order-badge"><?= Tools::h($o['order_number']) ?></span>
                                    </td>
                                    <td style="color: var(--muted); font-size: 0.85rem;">
                                        <?= Tools::h($o['created_at']) ?>
                                    </td>
                                    <td>
                                        <?= Tools::h($o['supplier_name'] ?: 'Hurtownia') ?>
                                        <span style="font-size: 0.75rem; color: var(--muted); display: block;">plik: <?= Tools::h($o['original_filename']) ?></span>
                                    </td>
                                    <td>
                                        <span style="font-weight: 700;"><?= (int)$o['total_items'] ?></span> poz.
                                    </td>
                                    <td>
                                        <span class="order-amount"><?= number_format((float)$o['total_amount'], 2, '.', ' ') ?> zł</span>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="<?= $base ?>order/view/id/<?= (int)$o['id'] ?>" class="btn-sm btn-sm-view" title="Zobacz pozycje">
                                            Szczegóły
                                        </a>
                                        <a href="<?= $base ?>order/download/id/<?= (int)$o['id'] ?>" class="btn-sm btn-sm-download" title="Pobierz plik Excela">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                            Excel
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>
