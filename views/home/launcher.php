<?php
/**
 * Ekran wyboru modułu po zalogowaniu.
 */
$base  = App::baseUrl();
$title = $view['title'] ?? 'Panel użytkownika';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Tools::h($title) ?></title>
    <style>
        :root {
            --bg: #f8fafc;
            --fg: #0f172a;
            --muted: #64748b;
            --card: #ffffff;
            --border: #e2e8f0;
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --placeholder-bg: #f1f5f9;
        }

        * { box-sizing: border-box; }

        html, body {
            min-height: 100%;
            margin: 0;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
            background: var(--bg);
            color: var(--fg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        .launcher {
            width: min(100%, 620px);
        }

        .launcher-header {
            margin-bottom: 24px;
            text-align: center;
        }

        .logout-link {
            display: inline-block;
            margin-top: 14px;
            color: #dc2626;
            font-size: .9rem;
            font-weight: 600;
            text-decoration: none;
        }

        .logout-link:hover,
        .logout-link:focus-visible {
            color: #991b1b;
            text-decoration: underline;
        }

        .logout-link:focus-visible {
            outline: 2px solid #dc2626;
            outline-offset: 3px;
        }

        .launcher-title {
            margin: 0;
            font-size: clamp(1.75rem, 4vw, 2.4rem);
            letter-spacing: -0.03em;
        }

        .launcher-subtitle {
            margin: 8px 0 0;
            color: var(--muted);
        }

        .module-list {
            display: grid;
            gap: 14px;
        }

        .module-card,
        .module-placeholder {
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 22px 24px;
        }

        .module-card {
            display: block;
            background: var(--card);
            color: inherit;
            text-decoration: none;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .module-card:hover,
        .module-card:focus-visible {
            border-color: var(--primary);
            box-shadow: 0 18px 36px rgba(37, 99, 235, 0.16);
            outline: none;
            transform: translateY(-2px);
        }

        .module-card-primary {
            border-color: rgba(16, 185, 129, 0.45);
        }

        .module-card-title {
            display: block;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .module-card-description {
            display: block;
            margin-top: 5px;
            color: var(--muted);
        }

        .module-placeholder {
            background: var(--placeholder-bg);
            border-style: dashed;
            color: var(--muted);
            text-align: center;
        }

        .dashboard-link {
            text-align: center;
            box-shadow: none;
        }
    </style>
</head>
<body>
    <main class="launcher">
        <header class="launcher-header">
            <h1 class="launcher-title">Wybierz moduł</h1>
            <p class="launcher-subtitle">Wybierz obszar, z którym chcesz pracować.</p>
            <a class="logout-link" href="<?= $base ?>home/logout">Wyloguj</a>
        </header>

        <div class="module-list">
            <a class="module-card module-card-primary" href="<?= $base ?>order/index">
                <span class="module-card-title">Zamówienia z cennika excel</span>
                <span class="module-card-description">Zarządzaj zamówieniami.</span>
            </a>

            <div class="module-placeholder">Miejsce na następny moduł</div>

            <a class="module-card dashboard-link" href="<?= $base ?>home/dashboard">
                <span class="module-card-title">Otwórz pulpit</span>
                <span class="module-card-description">Przejdź do dotychczasowego pulpitu.</span>
            </a>
        </div>
    </main>
</body>
</html>
