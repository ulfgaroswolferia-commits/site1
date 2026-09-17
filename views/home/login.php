<?php
/**
 * Ekran logowania inspirowany glassmorphism z docs/index.html.
 */
$loginValue = Tools::h($view['login'] ?? '');
$errorText  = Tools::h($view['error'] ?? '');
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
            --card: rgba(255,255,255,0.74);
            --border: rgba(255,255,255,0.9);
            --blue: #2563eb;
            --cyan: #06b6d4;
            --shadow: rgba(15, 23, 42, 0.12);
            --danger: #dc2626;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            min-height: 100%;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg);
            color: var(--fg);
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }

        .bg-grid {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(15, 23, 42, 0.07) 1.2px, transparent 1.2px);
            background-size: 26px 26px;
            opacity: 0.8;
            mask-image: radial-gradient(ellipse at center, black 40%, transparent 80%);
        }

        .blob {
            position: absolute;
            border-radius: 9999px;
            filter: blur(110px);
            opacity: 0.5;
            pointer-events: none;
            animation: drift 15s ease-in-out infinite alternate;
        }

        .blob-1 {
            width: 560px;
            height: 560px;
            background: rgba(147, 197, 253, 0.6);
            left: -120px;
            top: -120px;
        }

        .blob-2 {
            width: 620px;
            height: 620px;
            background: rgba(165, 243, 252, 0.5);
            right: -140px;
            bottom: -150px;
            animation-duration: 12s;
        }

        .blob-3 {
            width: 420px;
            height: 420px;
            background: rgba(196, 181, 253, 0.38);
            left: 50%;
            top: 25%;
            transform: translateX(-50%);
            animation-duration: 18s;
        }

        @keyframes drift {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(22px, -28px) scale(1.08); }
            100% { transform: translate(-18px, 18px) scale(0.96); }
        }

        .login-shell {
            position: relative;
            z-index: 1;
            width: min(100%, 480px);
        }

        .login-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 34px 30px 24px;
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            box-shadow: 0 25px 60px -16px var(--shadow), inset 0 1px 1px rgba(255,255,255,0.9);
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, rgba(255,255,255,0.55), rgba(255,255,255,0.1));
            pointer-events: none;
        }

        .login-content {
            position: relative;
            z-index: 1;
        }

        .brand-mark {
            width: 64px;
            height: 64px;
            margin: 0 auto 18px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.9);
            border: 1px solid rgba(148,163,184,0.5);
            box-shadow: 0 12px 26px -18px rgba(37,99,235,0.45);
            color: var(--blue);
        }

        .brand-mark svg { width: 28px; height: 28px; }

        h1 {
            margin: 0;
            text-align: center;
            font-size: clamp(2rem, 2.7vw, 2.5rem);
            line-height: 1.2;
            letter-spacing: -0.05em;
        }

        .subhead {
            margin: 8px 0 28px;
            text-align: center;
            color: var(--muted);
            font-size: 0.96rem;
        }

        .alert {
            margin: 0 0 18px;
            padding: 12px 14px;
            border-radius: 14px;
            border: 1px solid rgba(220, 38, 38, 0.18);
            background: rgba(254, 242, 242, 0.9);
            color: var(--danger);
            font-size: 0.92rem;
        }

        form {
            display: grid;
            gap: 18px;
        }

        .field {
            display: grid;
            gap: 8px;
        }

        label {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #334155;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            border: 1px solid rgba(203,213,225,0.85);
            border-radius: 14px;
            background: rgba(255,255,255,0.72);
            padding: 14px 15px;
            font-size: 1rem;
            color: var(--fg);
            outline: none;
            transition: 0.2s ease;
            box-shadow: inset 0 1px 1px rgba(255,255,255,0.9);
        }

        input[type="text"]:focus, input[type="password"]:focus {
            border-color: rgba(37,99,235,0.7);
            box-shadow: 0 0 0 4px rgba(37,99,235,0.12);
            background: rgba(255,255,255,0.96);
        }

        .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-top: 6px;
        }

        .check {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 0.87rem;
        }

        .check input {
            accent-color: var(--blue);
            width: 15px;
            height: 15px;
        }

        .link {
            color: var(--blue);
            text-decoration: none;
            font-size: 0.87rem;
            font-weight: 600;
        }

        .button {
            border: 0;
            border-radius: 14px;
            padding: 15px 18px;
            background: linear-gradient(135deg, #2563eb 0%, #0ea5e9 100%);
            color: white;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            box-shadow: 0 16px 24px -18px rgba(37,99,235,0.9);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .button:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 28px -18px rgba(37,99,235,0.9);
        }

        .meta {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 18px;
            font-size: 0.87rem;
            color: var(--muted);
        }

        .meta a {
            color: var(--blue);
            text-decoration: none;
            font-weight: 700;
        }

        @media (max-width: 520px) {
            .login-card {
                padding: 28px 20px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <main class="login-shell">
        <div class="login-card">
            <div class="login-content">
                <div class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="5" y="11" width="14" height="9" rx="2"></rect>
                        <path d="M8 11V8a4 4 0 118 0v3"></path>
                    </svg>
                </div>

                <h1><?= Tools::h($view['title']) ?></h1>
                <p class="subhead">Witaj ponownie! Wprowadź swoje dane dostępowe.</p>

                <?php if ($errorText !== ''): ?>
                    <div class="alert"><?= $errorText ?></div>
                <?php endif; ?>

                <form method="post" action="<?= App::baseUrl() ?>home/login">
                    <?= Tools::csrfField() ?>

                    <div class="field">
                        <label for="login">Login</label>
                        <input id="login" name="login" type="text" value="<?= $loginValue ?>" autocomplete="username" required>
                    </div>

                    <div class="field">
                        <label for="password">Hasło</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required>
                    </div>

                    <div class="row">
                        <label class="check">
                            <input type="checkbox" name="remember" value="1">
                            <span>Zapamiętaj mnie</span>
                        </label>
                        <a class="link" href="<?= App::baseUrl() ?>home/login">Przypomnij hasło</a>
                    </div>

                    <button class="button" type="submit">Zaloguj się</button>
                </form>

                <div class="meta">
                    <span>Nie masz konta?</span>
                    <a href="<?= App::baseUrl() ?>home/login">Skontaktuj się z administratorem</a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
