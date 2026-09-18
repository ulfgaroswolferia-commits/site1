<?php
/**
 * Kreator zamówienia warzyw i owoców ze sklepu spożywczego do hurtowni.
 */
$pageTitle     = Tools::h($view['title'] ?? 'Kreator zamówienia');
$userName      = Tools::h($view['user'] ?? 'użytkownik');
$csrfToken     = Tools::h($view['csrf_token'] ?? Tools::csrfToken());
$recentOrders  = $view['recent_orders'] ?? [];
$base          = App::baseUrl();
$appName       = 'Zamawiarka Magdy';
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

        .btn-download-order {
            background: #e0f2fe;
            border: 1px solid #bae6fd;
            color: #0284c7;
            border-radius: 10px;
            padding: 7px 16px;
            font-size: 0.84rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .btn-download-order:hover {
            background: #bae6fd;
            border-color: #7dd3fc;
            color: #0369a1;
            transform: translateY(-1px);
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

        .divider-or {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 28px 0 20px;
            color: var(--muted);
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .divider-or::before, .divider-or::after {
            content: '';
            flex: 1;
            border-bottom: 1px dashed var(--border);
        }

        .divider-or:not(:empty)::before {
            margin-right: 16px;
        }

        .divider-or:not(:empty)::after {
            margin-left: 16px;
        }

        .history-card-box {
            background: rgba(248, 250, 252, 0.85);
            border: 1px solid var(--border);
            border-radius: var(--radius-inner);
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .history-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .history-card-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 0.98rem;
            color: #1e293b;
            margin: 0;
        }

        .history-icon-badge {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--blue);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Tło dekoracyjne z owocami i warzywami */
        .bg-decorations {
            position: fixed;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        .bg-glow {
            position: absolute;
            border-radius: 50%;
            filter: blur(95px);
            opacity: 0.55;
            pointer-events: none;
        }

        .bg-glow-tomato {
            top: -60px;
            left: 3%;
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, rgba(239, 68, 68, 0.16) 0%, transparent 70%);
        }

        .bg-glow-lime {
            top: 28%;
            right: -80px;
            width: 360px;
            height: 360px;
            background: radial-gradient(circle, rgba(34, 197, 94, 0.14) 0%, transparent 70%);
        }

        .bg-glow-amber {
            bottom: 12%;
            left: -80px;
            width: 380px;
            height: 380px;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.14) 0%, transparent 70%);
        }

        .bg-glow-purple {
            bottom: -80px;
            right: 8%;
            width: 340px;
            height: 340px;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.13) 0%, transparent 70%);
        }

        .bg-deco-item {
            position: absolute;
            pointer-events: none;
            filter: drop-shadow(0 14px 24px rgba(15, 23, 42, 0.08));
            opacity: 0.85;
            transition: opacity 0.3s ease;
            will-change: transform;
        }

        /* Pozycje i animacje poszczególnych owoców i warzyw */
        .deco-tomato {
            top: 75px;
            left: 2%;
            width: 72px;
            height: 72px;
            animation: floatSlow1 12s ease-in-out infinite;
        }

        .deco-carrot {
            top: 110px;
            right: 2.5%;
            width: 80px;
            height: 80px;
            animation: floatSlow2 14s ease-in-out infinite;
        }

        .deco-lemon {
            top: 38%;
            left: 1.5%;
            width: 64px;
            height: 64px;
            animation: floatSlow3 11s ease-in-out infinite;
        }

        .deco-avocado {
            top: 45%;
            right: 1.8%;
            width: 72px;
            height: 72px;
            animation: floatSlow1 15s ease-in-out infinite reverse;
        }

        .deco-eggplant {
            bottom: 70px;
            left: 2.5%;
            width: 76px;
            height: 76px;
            animation: floatSlow2 13s ease-in-out infinite;
        }

        .deco-apple {
            bottom: 80px;
            right: 2.2%;
            width: 68px;
            height: 68px;
            animation: floatSlow3 16s ease-in-out infinite reverse;
        }

        .deco-leaf-1 {
            top: 24%;
            left: 4.5%;
            width: 36px;
            height: 36px;
            animation: floatSlow2 9s ease-in-out infinite;
            opacity: 0.7;
        }

        .deco-leaf-2 {
            bottom: 30%;
            right: 4%;
            width: 36px;
            height: 36px;
            animation: floatSlow1 10s ease-in-out infinite;
            opacity: 0.7;
        }

        @keyframes floatSlow1 {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-12px) rotate(5deg); }
        }

        @keyframes floatSlow2 {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(14px) rotate(-6deg); }
        }

        @keyframes floatSlow3 {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(-4deg); }
        }

        @media (max-width: 1400px) {
            .bg-deco-item { opacity: 0.55; }
            .deco-leaf-1, .deco-leaf-2 { display: none; }
        }

        @media (max-width: 1100px) {
            .bg-deco-item {
                display: none;
            }
            .bg-glow {
                opacity: 0.35;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .bg-deco-item { animation: none !important; }
        }

        .hidden { display: none !important; }
    </style>
</head>
<body>
    <div class="bg-grid"></div>

    <!-- Tło dekoracyjne z owocami i warzywami -->
    <div class="bg-decorations" aria-hidden="true">
        <!-- Miękkie poświaty gradientowe -->
        <div class="bg-glow bg-glow-tomato"></div>
        <div class="bg-glow bg-glow-lime"></div>
        <div class="bg-glow bg-glow-amber"></div>
        <div class="bg-glow bg-glow-purple"></div>

        <!-- 1. Dojrzały pomidor (lewy górny róg) -->
        <div class="bg-deco-item deco-tomato">
            <svg viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="36" cy="40" r="28" fill="url(#grad-tomato)"/>
                <ellipse cx="27" cy="27" rx="8" ry="5" transform="rotate(-30 27 27)" fill="#ffffff" fill-opacity="0.32"/>
                <path d="M36 18V8C36 8 39 10 42 8" stroke="#15803d" stroke-width="3" stroke-linecap="round"/>
                <path d="M36 18C33 13 25 15 22 17C26 20 32 20 36 20C40 20 46 20 50 17C47 15 39 13 36 18Z" fill="#16a34a"/>
                <path d="M36 19C37 24 43 27 46 27C44 24 40 21 36 19Z" fill="#15803d"/>
                <path d="M36 19C35 24 29 27 26 27C28 24 32 21 36 19Z" fill="#15803d"/>
                <defs>
                    <radialGradient id="grad-tomato" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(28 32) rotate(52) scale(34)">
                        <stop offset="0%" stop-color="#f87171"/>
                        <stop offset="55%" stop-color="#ef4444"/>
                        <stop offset="100%" stop-color="#b91c1c"/>
                    </radialGradient>
                </defs>
            </svg>
        </div>

        <!-- 2. Świeża marchewka (prawy górny róg) -->
        <div class="bg-deco-item deco-carrot">
            <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M52 28C56 22 62 16 68 12C63 18 63 24 57 29" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M50 25C52 17 55 10 59 6C56 12 55 18 53 26" stroke="#22c55e" stroke-width="2.5" stroke-linecap="round"/>
                <path d="M47 27C42 21 38 15 35 11C39 17 42 22 47 28" stroke="#15803d" stroke-width="2.5" stroke-linecap="round"/>
                <path d="M55 27C58 31 56 35 52 38L22 68C19 71 16 71 14 69C12 67 12 64 15 61L45 31C48 27 52 25 55 27Z" fill="url(#grad-carrot)"/>
                <path d="M43 36C40 37 38 39 39 40" stroke="#c2410c" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M33 46C30 47 28 49 29 50" stroke="#c2410c" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M25 54C23 55 21 57 22 58" stroke="#c2410c" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M48 30L23 57" stroke="#fdba74" stroke-width="2" stroke-linecap="round" stroke-opacity="0.6"/>
                <defs>
                    <linearGradient id="grad-carrot" x1="56" y1="26" x2="14" y2="70" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#fb923c"/>
                        <stop offset="50%" stop-color="#f97316"/>
                        <stop offset="100%" stop-color="#ea580c"/>
                    </linearGradient>
                </defs>
            </svg>
        </div>

        <!-- 3. Plasterek soczystej cytryny (środek z lewej) -->
        <div class="bg-deco-item deco-lemon">
            <svg viewBox="0 0 70 70" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="35" cy="35" r="28" fill="#facc15" stroke="#eab308" stroke-width="2.5"/>
                <circle cx="35" cy="35" r="24.5" fill="#fef08a"/>
                <circle cx="35" cy="35" r="22" fill="#eab308" fill-opacity="0.25"/>
                <path d="M35 35L35 15C39 15 43 17 46 20L35 35Z" fill="#fde047"/>
                <path d="M35 35L48 22C51 25 53 29 54 33L35 35Z" fill="#facc15"/>
                <path d="M35 35L54 37C53 41 51 45 48 48L35 35Z" fill="#fde047"/>
                <path d="M35 35L46 50C43 53 39 55 35 55L35 35Z" fill="#facc15"/>
                <path d="M35 35L35 55C31 55 27 53 24 50L35 35Z" fill="#fde047"/>
                <path d="M35 35L22 48C19 45 17 41 16 37L35 35Z" fill="#facc15"/>
                <path d="M35 35L16 33C17 29 19 25 22 22L35 35Z" fill="#fde047"/>
                <path d="M35 35L24 20C27 17 31 15 35 15L35 35Z" fill="#facc15"/>
                <circle cx="35" cy="35" r="3.5" fill="#ffffff"/>
            </svg>
        </div>

        <!-- 4. Awokado z pestką (środek z prawej) -->
        <div class="bg-deco-item deco-avocado">
            <svg viewBox="0 0 74 74" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M37 10C27 10 23 21 21 31C18 43 20 62 37 62C54 62 56 43 53 31C51 21 47 10 37 10Z" fill="#14532d"/>
                <path d="M37 14C29 14 26 23 24 32C22 42 23 58 37 58C51 58 52 42 50 32C48 23 45 14 37 14Z" fill="url(#grad-avocado-flesh)"/>
                <circle cx="37" cy="42" r="10.5" fill="url(#grad-avocado-pit)"/>
                <ellipse cx="34" cy="39" rx="3" ry="2" transform="rotate(-30 34 39)" fill="#ffffff" fill-opacity="0.25"/>
                <defs>
                    <radialGradient id="grad-avocado-flesh" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(37 40) rotate(90) scale(22)">
                        <stop offset="40%" stop-color="#fef08a"/>
                        <stop offset="85%" stop-color="#86efac"/>
                        <stop offset="100%" stop-color="#22c55e"/>
                    </radialGradient>
                    <radialGradient id="grad-avocado-pit" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(34 39) rotate(45) scale(12)">
                        <stop offset="0%" stop-color="#92400e"/>
                        <stop offset="70%" stop-color="#78350f"/>
                        <stop offset="100%" stop-color="#451a03"/>
                    </radialGradient>
                </defs>
            </svg>
        </div>

        <!-- 5. Dojrzały bakłażan (dół po lewej) -->
        <div class="bg-deco-item deco-eggplant">
            <svg viewBox="0 0 76 76" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M34 22C28 26 22 36 21 46C20 57 26 66 38 66C50 66 56 57 55 45C54 33 46 25 40 21C38 19 36 20 34 22Z" fill="url(#grad-eggplant)"/>
                <path d="M28 38C26 44 26 52 28 58" stroke="#c084fc" stroke-width="2.5" stroke-linecap="round" stroke-opacity="0.45"/>
                <path d="M37 20V11C37 11 39 11 41 9" stroke="#15803d" stroke-width="3" stroke-linecap="round"/>
                <path d="M37 19C33 16 26 19 24 23C27 24 33 22 37 22C41 22 47 24 50 23C48 19 41 16 37 19Z" fill="#16a34a"/>
                <path d="M37 21C38 25 44 28 47 27C44 24 41 22 37 21Z" fill="#15803d"/>
                <path d="M37 21C36 25 30 28 27 27C30 24 33 22 37 21Z" fill="#15803d"/>
                <defs>
                    <linearGradient id="grad-eggplant" x1="28" y1="21" x2="48" y2="66" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#9333ea"/>
                        <stop offset="45%" stop-color="#6b21a8"/>
                        <stop offset="100%" stop-color="#3b0764"/>
                    </linearGradient>
                </defs>
            </svg>
        </div>

        <!-- 6. Chrupiące zielone jabłko (dół po prawej) -->
        <div class="bg-deco-item deco-apple">
            <svg viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M36 24C32 20 22 20 16 26C10 33 11 49 19 59C24 65 31 66 36 63C41 66 48 65 53 59C61 49 62 33 56 26C50 20 40 20 36 24Z" fill="url(#grad-apple)"/>
                <ellipse cx="26" cy="33" rx="6" ry="3.5" transform="rotate(-40 26 33)" fill="#ffffff" fill-opacity="0.3"/>
                <path d="M36 22C36 17 38 12 41 9" stroke="#78350f" stroke-width="2.5" stroke-linecap="round"/>
                <path d="M38 15C44 12 50 14 51 17C46 19 40 18 38 15Z" fill="#16a34a"/>
                <defs>
                    <radialGradient id="grad-apple" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(28 32) rotate(45) scale(34)">
                        <stop offset="0%" stop-color="#86efac"/>
                        <stop offset="60%" stop-color="#22c55e"/>
                        <stop offset="100%" stop-color="#15803d"/>
                    </radialGradient>
                </defs>
            </svg>
        </div>

        <!-- 7. Świeży liść bazylii (akcent górny lewy) -->
        <div class="bg-deco-item deco-leaf-1">
            <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 31C9 31 11 19 23 11C29 7 35 7 35 7C35 7 35 13 31 19C23 31 9 31 9 31Z" fill="#22c55e"/>
                <path d="M9 31C16 25 24 18 35 7" stroke="#15803d" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M19 22C22 22 25 20 27 18" stroke="#15803d" stroke-width="1.2" stroke-linecap="round"/>
            </svg>
        </div>

        <!-- 8. Świeży liść mięty (akcent dolny prawy) -->
        <div class="bg-deco-item deco-leaf-2">
            <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M31 31C31 31 29 19 17 11C11 7 5 7 5 7C5 7 5 13 9 19C17 31 31 31 31 31Z" fill="#10b981"/>
                <path d="M31 31C24 25 16 18 5 7" stroke="#047857" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M21 22C18 22 15 20 13 18" stroke="#047857" stroke-width="1.2" stroke-linecap="round"/>
            </svg>
        </div>
    </div>

    <div class="shell">
        <!-- Top Navigation -->
        <header class="topbar">
            <a href="<?= $base ?>order/index" class="brand-group">
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

            <!-- Opcja wczytania cennika z historii -->
            <div class="divider-or">LUB WCZYTAJ Z HISTORII ZAMÓWIEŃ</div>

            <div class="history-card-box">
                <div class="history-card-header">
                    <h3 class="history-card-title">
                        <span class="history-icon-badge">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </span>
                        Wczytaj cennik z historii (ostatnie 10 zamówień)
                    </h3>
                    <span style="font-size: 0.82rem; color: var(--muted);">Wczytuje asortyment bez konieczności ponownego uploadu pliku</span>
                </div>

                <?php if (!empty($recentOrders)): ?>
                    <div class="form-row" style="align-items: flex-end;">
                        <div class="form-col" style="flex: 2; min-width: 280px;">
                            <label class="form-label" for="history-order-select">Wybierz wcześniejsze zamówienie / cennik</label>
                            <select id="history-order-select" class="form-select">
                                <?php foreach ($recentOrders as $ro): ?>
                                    <?php
                                        $label = Tools::h($ro['order_number']);
                                        if (!empty($ro['supplier_name'])) {
                                            $label .= ' — ' . Tools::h($ro['supplier_name']);
                                        }
                                        $label .= ' (' . date('d.m.Y', strtotime($ro['created_at'])) . ', ' . (int)$ro['total_items'] . ' poz.)';
                                    ?>
                                    <option value="<?= (int)$ro['id'] ?>">
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-col" style="flex: 0 0 auto; justify-content: flex-end;">
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 0.86rem; font-weight: 600; cursor: pointer; color: #334155; margin-bottom: 8px;">
                                <input id="history-keep-qty" type="checkbox" style="width: 16px; height: 16px; accent-color: var(--blue);">
                                <span>Zachowaj poprzednie ilości</span>
                            </label>
                            <button type="button" id="btn-load-history" class="btn-secondary" style="padding: 11px 20px; font-weight: 700;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                                <span>Wczytaj ten cennik</span>
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <p style="margin: 0; font-size: 0.88rem; color: var(--muted);">Brak wcześniejszych zamówień w historii. Wgraj plik Excela powyżej, aby utworzyć pierwsze zamówienie.</p>
                <?php endif; ?>
            </div>
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

                <button type="button" class="btn-secondary" style="padding: 11px 16px; font-size: 0.88rem; font-weight: 600;" onclick="resetToStep1()">
                    ← Zmień cennik
                </button>

                <div class="submit-actions-wrap" style="display: flex; flex-direction: column; gap: 6px; align-items: stretch;">
                    <button type="button" id="btn-submit-send-order" class="btn-primary" style="padding: 11px 22px; background: linear-gradient(135deg, #059669 0%, #10b981 100%); box-shadow: 0 10px 20px -8px rgba(16, 185, 129, 0.6); justify-content: center;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        <span>Zatwierdź i wyślij</span>
                    </button>

                    <button type="button" id="btn-submit-order" class="btn-download-order">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        <span>Zatwierdź i pobierz Excel</span>
                    </button>
                </div>
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

                <!-- Status wysyłki na e-mail -->
                <div id="email-status-box" class="hidden" style="margin: 4px auto 0; padding: 14px 22px; border-radius: 12px; font-size: 0.95rem; font-weight: 600; display: flex; align-items: center; gap: 12px; max-width: 600px; text-align: left; box-sizing: border-box;">
                    <div id="email-status-icon" style="flex-shrink: 0; display: flex; align-items: center;"></div>
                    <div id="email-status-text" style="line-height: 1.45;"></div>
                </div>

                <div style="background: var(--card-inner); border: 1px solid var(--border); border-radius: 16px; padding: 18px 28px; display: flex; gap: 32px; margin-top: 10px;">
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

        // Pomocniczy wrapper AJAX wymuszający nagłówki i czysty JSON
        function apiFetch(url, options = {}) {
            options.headers = Object.assign({
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }, options.headers || {});

            return fetch(url, options).then(async res => {
                const contentType = res.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    if (res.status === 401 || res.status === 403) {
                        throw new Error('Sesja wygasła. Odśwież stronę i zaloguj się ponownie.');
                    }
                    throw new Error('Serwer zwrócił odpowiedź inną niż JSON (kod ' + res.status + '). Sprawdź uprawnienia.');
                }
                return await res.json();
            });
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
            formData.append('_csrf', CSRF_TOKEN);
            formData.append('csrf_token', CSRF_TOKEN);

            apiFetch(BASE_URL + 'order/upload', {
                method: 'POST',
                body: formData
            })
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
                showStatus('Błąd: ' + err.message, true);
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

        // 1b. Obsługa wczytywania cennika z historii
        const btnLoadHistory = document.getElementById('btn-load-history');
        if (btnLoadHistory) {
            btnLoadHistory.addEventListener('click', () => {
                const select = document.getElementById('history-order-select');
                const orderId = select ? select.value : null;
                if (!orderId) {
                    alert('Proszę wybrać zamówienie z listy.');
                    return;
                }

                const keepQty = document.getElementById('history-keep-qty')?.checked || false;
                const originalText = btnLoadHistory.innerHTML;
                btnLoadHistory.disabled = true;
                btnLoadHistory.innerHTML = '<span>Wczytywanie...</span>';

                const formData = new FormData();
                formData.append('order_id', orderId);
                formData.append('_csrf', CSRF_TOKEN);
                formData.append('csrf_token', CSRF_TOKEN);

                apiFetch(BASE_URL + 'order/loadhistory', {
                    method: 'POST',
                    body: formData
                })
                .then(data => {
                    btnLoadHistory.disabled = false;
                    btnLoadHistory.innerHTML = originalText;

                    if (!data.ok) {
                        alert(data.error || 'Nie udało się wczytać cennika z historii.');
                        return;
                    }

                    if (!data.products || data.products.length === 0) {
                        alert('Wybrane zamówienie nie zawiera pozycji asortymentowych.');
                        return;
                    }

                    // Ustawienie dostawcy i pliku źródłowego
                    if (data.supplier_name) {
                        const supplierInput = document.getElementById('supplier-name');
                        if (supplierInput) supplierInput.value = data.supplier_name;
                    }
                    originalFileName = data.original_filename || 'cennik.xlsx';

                    // Przypisanie listy produktów
                    productsList = data.products.map(p => ({
                        name: p.name,
                        price: parseFloat(p.price) || 0,
                        unit: p.unit || 'kg',
                        quantity: keepQty ? (parseFloat(p.prev_quantity) || 0) : 0
                    }));

                    // Przejście od razu do Kroku 3
                    step1.classList.add('hidden');
                    step2.classList.add('hidden');
                    step4.classList.add('hidden');
                    step3.classList.remove('hidden');
                    renderProductsTable();
                })
                .catch(err => {
                    btnLoadHistory.disabled = false;
                    btnLoadHistory.innerHTML = originalText;
                    alert('Błąd wczytywania cennika z historii: ' + err.message);
                });
            });
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
            formData.append('_csrf', CSRF_TOKEN);
            formData.append('csrf_token', CSRF_TOKEN);

            apiFetch(BASE_URL + 'order/process', {
                method: 'POST',
                body: formData
            })
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

        // 4. Zatwierdzenie i generowanie zamówienia (oraz opcjonalna wysyłka e-mail)
        function submitOrder(sendEmail = false) {
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
            formData.append('_csrf', CSRF_TOKEN);
            formData.append('csrf_token', CSRF_TOKEN);
            if (sendEmail) {
                formData.append('send_email', '1');
            }

            const btnSave = document.getElementById('btn-submit-order');
            const btnSend = document.getElementById('btn-submit-send-order');
            btnSave.disabled = true;
            btnSend.disabled = true;

            const activeBtn = sendEmail ? btnSend : btnSave;
            const origHtml = activeBtn.innerHTML;
            activeBtn.innerHTML = sendEmail ? '<span>Wysyłanie e-mail...</span>' : '<span>Generowanie pliku...</span>';

            apiFetch(BASE_URL + 'order/save', {
                method: 'POST',
                body: formData
            })
            .then(data => {
                btnSave.disabled = false;
                btnSend.disabled = false;
                activeBtn.innerHTML = origHtml;

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

                // Prezentacja statusu wysyłki e-mail
                const emailBox = document.getElementById('email-status-box');
                const emailIcon = document.getElementById('email-status-icon');
                const emailText = document.getElementById('email-status-text');

                if (data.email_status) {
                    emailBox.classList.remove('hidden');
                    emailText.textContent = data.email_message || '';

                    if (data.email_status === 'sent') {
                        emailBox.style.background = '#ecfdf5';
                        emailBox.style.border = '1px solid #a7f3d0';
                        emailBox.style.color = '#065f46';
                        emailIcon.innerHTML = `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
                    } else if (data.email_status === 'not_configured') {
                        emailBox.style.background = '#fffbeb';
                        emailBox.style.border = '1px solid #fde68a';
                        emailBox.style.color = '#92400e';
                        emailIcon.innerHTML = `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;
                    } else {
                        emailBox.style.background = '#fef2f2';
                        emailBox.style.border = '1px solid #fecaca';
                        emailBox.style.color = '#991b1b';
                        emailIcon.innerHTML = `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;
                    }
                } else {
                    emailBox.classList.add('hidden');
                }

                // Automatyczne pobranie pliku Excela
                window.location.href = data.download_url;
            })
            .catch(err => {
                btnSave.disabled = false;
                btnSend.disabled = false;
                activeBtn.innerHTML = origHtml;
                alert('Błąd połączenia: ' + err.message);
            });
        }

        document.getElementById('btn-submit-order').addEventListener('click', () => submitOrder(false));
        document.getElementById('btn-submit-send-order').addEventListener('click', () => submitOrder(true));

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
