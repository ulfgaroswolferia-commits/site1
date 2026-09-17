<?php
/**
 * Panel użytkownika / Dashboard po zalogowaniu (TwiiCoreF).
 */
$loginName   = Tools::h($view['user'] ?? (defined('APP_LOGIN') ? APP_LOGIN : 'użytkownik'));
$pageTitle   = Tools::h($view['title'] ?? 'Panel użytkownika');
$base        = App::baseUrl();
$appEnv      = defined('APP_ENV') ? APP_ENV : 'development';
$appName     = defined('APP_NAME') ? APP_NAME : 'TwiiCoreF';
$sessionName = defined('SESSION_NAME') ? SESSION_NAME : session_name();
$dbCharset   = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
$sessionId   = session_id();
$clientIp    = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$routes      = class_exists('Config') ? (Config::get('routes') ?: []) : [];
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
            --card: rgba(255, 255, 255, 0.82);
            --card-inner: rgba(255, 255, 255, 0.95);
            --border: rgba(226, 232, 240, 0.85);
            --blue: #2563eb;
            --blue-hover: #1d4ed8;
            --blue-light: rgba(37, 99, 235, 0.08);
            --cyan: #06b6d4;
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

        /* Ambient background */
        .bg-grid {
            position: fixed;
            inset: 0;
            background-image: radial-gradient(rgba(15, 23, 42, 0.06) 1.2px, transparent 1.2px);
            background-size: 24px 24px;
            opacity: 0.85;
            pointer-events: none;
            z-index: 0;
        }

        .ambient-blob {
            position: fixed;
            border-radius: 9999px;
            filter: blur(120px);
            opacity: 0.45;
            pointer-events: none;
            z-index: 0;
        }

        .blob-1 {
            width: 500px;
            height: 500px;
            background: rgba(147, 197, 253, 0.55);
            top: -100px;
            left: -100px;
        }

        .blob-2 {
            width: 600px;
            height: 600px;
            background: rgba(165, 243, 252, 0.4);
            bottom: -150px;
            right: -100px;
        }

        /* Shell layout */
        .dashboard-shell {
            position: relative;
            z-index: 1;
            max-width: 1240px;
            margin: 0 auto;
            padding: 24px 20px 60px;
            display: flex;
            flex-direction: column;
            gap: 28px;
        }

        /* Navigation bar */
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
        }

        .brand-group {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 8px 16px -6px rgba(37, 99, 235, 0.4);
        }

        .brand-icon svg { width: 22px; height: 22px; }

        .brand-title {
            font-weight: 800;
            font-size: 1.15rem;
            letter-spacing: -0.02em;
        }

        .env-badge {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 4px 9px;
            border-radius: 999px;
            background: var(--blue-light);
            color: var(--blue);
            border: 1px solid rgba(37, 99, 235, 0.2);
        }

        .user-nav {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 5px 14px 5px 6px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            font-weight: 700;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            line-height: 1.15;
        }

        .user-name {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--fg);
        }

        .user-role {
            font-size: 0.7rem;
            color: var(--muted);
        }

        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 12px;
            background: #fff;
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: var(--danger);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            background: #fef2f2;
            border-color: var(--danger);
            transform: translateY(-1px);
        }

        /* Hero welcome card */
        .hero-card {
            background: var(--card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: var(--radius-outer);
            padding: 32px 36px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
        }

        .hero-text h1 {
            margin: 0 0 8px;
            font-size: clamp(1.6rem, 2.2vw, 2.2rem);
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .hero-text p {
            margin: 0;
            color: var(--muted);
            font-size: 1rem;
            max-width: 620px;
            line-height: 1.5;
        }

        .hero-meta {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--emerald-light);
            border: 1px solid rgba(16, 185, 129, 0.25);
            color: #065f46;
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--emerald);
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-ring 2s infinite;
        }

        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6); }
            70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .time-badge {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid var(--border);
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Metrics grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        .metric-card {
            background: var(--card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 22px 24px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: flex-start;
            gap: 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 24px 48px -18px rgba(15, 23, 42, 0.12);
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .metric-icon.blue   { background: rgba(37, 99, 235, 0.1); color: var(--blue); }
        .metric-icon.cyan   { background: rgba(6, 182, 212, 0.12); color: var(--cyan); }
        .metric-icon.green  { background: rgba(16, 185, 129, 0.12); color: var(--emerald); }
        .metric-icon.purple { background: rgba(139, 92, 246, 0.12); color: #8b5cf6; }

        .metric-icon svg { width: 24px; height: 24px; }

        .metric-body {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .metric-label {
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
            font-weight: 700;
        }

        .metric-value {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--fg);
        }

        .metric-sub {
            font-size: 0.78rem;
            color: var(--muted);
        }

        /* Bento 2-column layout */
        .bento-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 24px;
        }

        @media (max-width: 960px) {
            .bento-grid {
                grid-template-columns: 1fr;
            }
        }

        .bento-card {
            background: var(--card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: var(--radius-outer);
            padding: 28px 30px;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .bento-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 14px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        }

        .bento-title {
            font-size: 1.15rem;
            font-weight: 800;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.01em;
        }

        .bento-title svg { width: 20px; height: 20px; color: var(--blue); }

        /* Quick actions list */
        .action-tiles {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
        }

        .action-tile {
            background: var(--card-inner);
            border: 1px solid var(--border);
            border-radius: var(--radius-inner);
            padding: 16px 18px;
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.2s ease;
        }

        .action-tile:hover {
            transform: translateY(-2px);
            border-color: rgba(37, 99, 235, 0.4);
            box-shadow: 0 12px 24px -10px rgba(37, 99, 235, 0.15);
        }

        .action-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--blue-light);
            color: var(--blue);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .action-icon svg { width: 20px; height: 20px; }

        .action-info h4 {
            margin: 0 0 3px;
            font-size: 0.92rem;
            font-weight: 700;
        }

        .action-info p {
            margin: 0;
            font-size: 0.78rem;
            color: var(--muted);
            line-height: 1.3;
        }

        /* System info list */
        .info-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .info-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 11px 14px;
            background: var(--card-inner);
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 0.85rem;
        }

        .info-label {
            color: var(--muted);
            font-weight: 600;
        }

        .info-val {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-weight: 600;
            color: var(--fg);
            background: rgba(15, 23, 42, 0.04);
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.8rem;
            max-width: 240px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Routes list card */
        .routes-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.86rem;
        }

        .routes-table th {
            text-align: left;
            padding: 10px 14px;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
        }

        .routes-table td {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        }

        .routes-table tr:last-child td {
            border-bottom: none;
        }

        .route-badge {
            display: inline-block;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-weight: 700;
            font-size: 0.82rem;
            color: var(--blue);
            background: var(--blue-light);
            padding: 3px 8px;
            border-radius: 6px;
        }

        .controller-name {
            font-weight: 600;
        }

        .status-dot-sm {
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--emerald);
            margin-right: 6px;
        }

        /* Footer */
        .dashboard-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: var(--muted);
            font-size: 0.82rem;
            padding: 0 10px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .dashboard-footer a {
            color: var(--blue);
            text-decoration: none;
            font-weight: 600;
        }

        .dashboard-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="ambient-blob blob-1"></div>
    <div class="ambient-blob blob-2"></div>

    <div class="dashboard-shell">
        <!-- Top Navigation -->
        <header class="topbar">
            <a href="<?= $base ?>home/index" class="brand-group">
                <div class="brand-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
                        <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
                        <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
                        <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
                    </svg>
                </div>
                <div>
                    <span class="brand-title"><?= Tools::h($appName) ?></span>
                </div>
                <span class="env-badge"><?= Tools::h($appEnv) ?></span>
            </a>

            <div class="user-nav">
                <div class="user-pill">
                    <div class="user-avatar" aria-hidden="true">
                        <?= mb_substr($loginName, 0, 2) ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?= $loginName ?></span>
                        <span class="user-role">Administrator</span>
                    </div>
                </div>

                <a href="<?= $base ?>home/logout" class="btn-logout" title="Zakończ aktywną sesję">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Wyloguj</span>
                </a>
            </div>
        </header>

        <!-- Hero Card -->
        <section class="hero-card">
            <div class="hero-text">
                <h1>Witaj w panelu, <?= $loginName ?>! 👋</h1>
                <p>Zalogowano pomyślnie do panelu sterowania aplikacji <strong><?= Tools::h($appName) ?></strong>. Wszystkie moduły architektoniczne MVC oraz usługi działają w trybie <?= Tools::h($appEnv) ?>.</p>
            </div>
            <div class="hero-meta">
                <div class="status-pill">
                    <span class="pulse-dot"></span>
                    <span>System operacyjny aktywny</span>
                </div>
                <div class="time-badge">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span id="live-clock"><?= date('Y-m-d H:i:s') ?></span>
                </div>
            </div>
        </section>

        <!-- Metrics Bento Row -->
        <section class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon blue" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="metric-body">
                    <span class="metric-label">Konto użytkownika</span>
                    <span class="metric-value"><?= $loginName ?></span>
                    <span class="metric-sub">Autoryzacja udana</span>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon cyan" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="16 18 22 12 16 6"></polyline>
                        <polyline points="8 6 2 12 8 18"></polyline>
                    </svg>
                </div>
                <div class="metric-body">
                    <span class="metric-label">Środowisko PHP</span>
                    <span class="metric-value">PHP <?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?></span>
                    <span class="metric-sub">Wersja <?= PHP_VERSION ?></span>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon green" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <div class="metric-body">
                    <span class="metric-label">Bezpieczeństwo</span>
                    <span class="metric-value">CSRF & SID</span>
                    <span class="metric-sub">Sesja zabezpieczona</span>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon purple" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                        <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                        <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                    </svg>
                </div>
                <div class="metric-body">
                    <span class="metric-label">Baza danych</span>
                    <span class="metric-value"><?= Tools::h($dbCharset) ?></span>
                    <span class="metric-sub">Sterownik PDO MySQL</span>
                </div>
            </div>
        </section>

        <!-- Main Bento Grid -->
        <section class="bento-grid">
            <!-- Left Column: Quick Actions & Tools -->
            <div class="bento-card">
                <div class="bento-header">
                    <h3 class="bento-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                        </svg>
                        Szybkie akcje i testy MVC
                    </h3>
                    <span class="env-badge">Narzędzia</span>
                </div>

                <div class="action-tiles">
                    <a href="<?= $base ?>home/example/id/42" class="action-tile">
                        <div class="action-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                        </div>
                        <div class="action-info">
                            <h4>Test parametrów URL</h4>
                            <p>Wywołaj akcję testową <code>/home/example/id/42</code></p>
                        </div>
                    </a>

                    <a href="<?= $base ?>docs/index.html" target="_blank" rel="noopener" class="action-tile">
                        <div class="action-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                            </svg>
                        </div>
                        <div class="action-info">
                            <h4>Dokumentacja HTML</h4>
                            <p>Przejrzyj interaktywny opis architektury i stylów</p>
                        </div>
                    </a>

                    <a href="<?= $base ?>home/index" class="action-tile">
                        <div class="action-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="23 4 23 10 17 10"></polyline>
                                <polyline points="1 20 1 14 7 14"></polyline>
                                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                            </svg>
                        </div>
                        <div class="action-info">
                            <h4>Odśwież pulpit</h4>
                            <p>Zaktualizuj bieżący stan sesji i widoku</p>
                        </div>
                    </a>

                    <a href="<?= $base ?>home/logout" class="action-tile">
                        <div class="action-icon" style="color: var(--danger); background: rgba(239, 68, 68, 0.1);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path>
                                <line x1="12" y1="2" x2="12" y2="12"></line>
                            </svg>
                        </div>
                        <div class="action-info">
                            <h4>Zakończ sesję</h4>
                            <p>Bezpieczne wylogowanie z pamięci przeglądarki</p>
                        </div>
                    </a>
                </div>

                <!-- Routes sub-card -->
                <div style="margin-top: 10px;">
                    <h4 style="margin: 0 0 12px; font-size: 0.95rem; font-weight: 700; color: #334155;">Zarejestrowane trasy (Config::$routes)</h4>
                    <div style="background: var(--card-inner); border: 1px solid var(--border); border-radius: var(--radius-inner); overflow: hidden;">
                        <table class="routes-table">
                            <thead>
                                <tr>
                                    <th>Trasa</th>
                                    <th>Prefiks akcji</th>
                                    <th>Kontroler docelowy</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($routes)): ?>
                                    <?php foreach ($routes as $route => $prefix): ?>
                                        <tr>
                                            <td><span class="route-badge">/<?= Tools::h($route) ?></span></td>
                                            <td><code><?= Tools::h($prefix) ?>*()</code></td>
                                            <td class="controller-name"><?= ucfirst(Tools::h($route)) ?>Controller</td>
                                            <td><span class="status-dot-sm"></span>Aktywny</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td><span class="route-badge">/home</span></td>
                                        <td><code>action*()</code></td>
                                        <td class="controller-name">HomeController</td>
                                        <td><span class="status-dot-sm"></span>Aktywny</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column: Environment & Security Details -->
            <div class="bento-card">
                <div class="bento-header">
                    <h3 class="bento-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                            <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                            <line x1="6" y1="6" x2="6.01" y2="6"></line>
                            <line x1="6" y1="18" x2="6.01" y2="18"></line>
                        </svg>
                        Stan aplikacji i sesja
                    </h3>
                    <span class="env-badge">Szczegóły</span>
                </div>

                <div class="info-list">
                    <div class="info-row">
                        <span class="info-label">Aplikacja</span>
                        <span class="info-val"><?= Tools::h($appName) ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Katalog projektu</span>
                        <span class="info-val" title="<?= Tools::h(BASE_PATH) ?>"><?= Tools::h(basename(BASE_PATH)) ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Adres bazowy (Base URL)</span>
                        <span class="info-val"><?= Tools::h($base) ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Plik konfiguracji danych</span>
                        <span class="info-val">program/config/data.php</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Cookie sesji</span>
                        <span class="info-val"><?= Tools::h($sessionName) ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Identyfikator sesji</span>
                        <span class="info-val"><?= substr(Tools::h($sessionId), 0, 12) ?>...</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Adres IP klienta</span>
                        <span class="info-val"><?= Tools::h($clientIp) ?></span>
                    </div>
                </div>

                <div style="background: rgba(37, 99, 235, 0.04); border: 1px dashed rgba(37, 99, 235, 0.3); border-radius: 14px; padding: 14px 16px;">
                    <p style="margin: 0 0 6px; font-size: 0.82rem; font-weight: 700; color: #1e3a8a;">💡 Wskazówka developerska</p>
                    <p style="margin: 0; font-size: 0.78rem; color: #475569; line-height: 1.4;">
                        Dane logowania są skonfigurowane w pliku <code>program/config/data.php</code> (poza repozytorium git). Możesz rozbudowywać aplikację dodając nowe kontrolery do <code>program/script/</code> oraz modele do <code>program/model/</code>.
                    </p>
                </div>
            </div>
        </section>

        <!-- Dashboard Footer -->
        <footer class="dashboard-footer">
            <span>&copy; <?= date('Y') ?> <strong><?= Tools::h($appName) ?></strong> — Mikro-framework PHP MVC.</span>
            <span>Dokumentacja architektury: <a href="<?= $base ?>docs/MVC.md" target="_blank">docs/MVC.md</a></span>
        </footer>
    </div>

    <!-- Live clock script -->
    <script>
        function updateClock() {
            const clockEl = document.getElementById('live-clock');
            if (clockEl) {
                const now = new Date();
                const pad = (n) => String(n).padStart(2, '0');
                const formatted = now.getFullYear() + '-' +
                    pad(now.getMonth() + 1) + '-' +
                    pad(now.getDate()) + ' ' +
                    pad(now.getHours()) + ':' +
                    pad(now.getMinutes()) + ':' +
                    pad(now.getSeconds());
                clockEl.textContent = formatted;
            }
        }
        setInterval(updateClock, 1000);
    </script>
</body>
</html>
