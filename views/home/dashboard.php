<?php
$loginName = Tools::h($view['user'] ?? 'użytkownik');
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Tools::h($view['title']) ?></title>
    <style>
        :root {
            --bg: #f8fafc;
            --fg: #0f172a;
            --muted: #64748b;
            --card: rgba(255,255,255,0.8);
            --border: rgba(148,163,184,0.3);
            --blue: #2563eb;
            --shadow: rgba(15, 23, 42, 0.08);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: linear-gradient(180deg, #eff6ff 0%, #f8fafc 100%);
            color: var(--fg);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        .panel {
            width: min(100%, 640px);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 26px;
            box-shadow: 0 28px 60px -24px var(--shadow);
            padding: 32px 28px;
            backdrop-filter: blur(16px);
        }

        .badge {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(37,99,235,0.08);
            color: var(--blue);
            font-weight: 700;
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        h1 {
            margin: 16px 0 8px;
            font-size: clamp(2rem, 2.5vw, 3rem);
            line-height: 1.1;
        }

        p {
            margin: 0 0 24px;
            color: var(--muted);
            font-size: 1.04rem;
        }

        .info {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            background: rgba(248,250,252,0.9);
            border: 1px solid rgba(148,163,184,0.28);
            border-radius: 18px;
            padding: 16px 18px;
            margin-bottom: 24px;
        }

        .info strong {
            display: block;
            margin-bottom: 5px;
            font-size: 0.7rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .button, .button-secondary {
            border-radius: 12px;
            padding: 12px 18px;
            text-decoration: none;
            font-weight: 700;
            display: inline-block;
        }

        .button {
            background: linear-gradient(135deg, #2563eb 0%, #0ea5e9 100%);
            color: white;
        }

        .button-secondary {
            background: rgba(15,23,42,0.03);
            color: var(--fg);
            border: 1px solid rgba(148,163,184,0.3);
        }
    </style>
</head>
<body>
    <main class="panel">
        <span class="badge">Zalogowany</span>
        <h1>Witaj, <?= $loginName ?>!</h1>
        <p>Udało się zalogować do panelu. To jest prosty ekran po zalogowaniu, wygenerowany w oparciu o szkic frameworku TwiiCoreF.</p>

        <div class="info">
            <div>
                <strong>Login</strong>
                <span><?= $loginName ?></span>
            </div>
            <div>
                <strong>Status</strong>
                <span>Aktywny</span>
            </div>
            <div>
                <strong>Sesja</strong>
                <span>Włączona</span>
            </div>
        </div>

        <div class="actions">
            <a class="button" href="<?= App::baseUrl() ?>home/index">Odśwież</a>
            <a class="button-secondary" href="<?= App::baseUrl() ?>home/logout">Wyloguj się</a>
        </div>
    </main>
</body>
</html>
