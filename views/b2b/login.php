<?php
$title     = $view['title'] ?? 'Logowanie B2B — Hurtownia Magdy';
$error     = $view['error'] ?? '';
$csrfToken = $view['csrfToken'] ?? Tools::csrfToken();
$base      = $view['base'] ?? App::baseUrl();
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; }
    </style>
</head>
<body class="h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-gradient-to-br from-emerald-50 via-slate-50 to-teal-50 min-h-screen">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <!-- Logo & Branding -->
        <div class="flex justify-center items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-emerald-600 flex items-center justify-center text-white shadow-lg shadow-emerald-600/30">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
            </div>
            <div class="text-left">
                <span class="block text-2xl font-black tracking-tight text-slate-900 leading-none">HURTOWNIA MAGDY</span>
                <span class="text-xs font-bold uppercase tracking-widest text-emerald-600">Platforma zamówień B2B</span>
            </div>
        </div>

        <h2 class="mt-6 text-center text-xl font-bold tracking-tight text-slate-800">
            Logowanie dla sklepów i odbiorców
        </h2>
        <p class="mt-2 text-center text-xs text-slate-500">
            Świeże warzywa i owoce hurtowo z codzienną dostawą
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div class="bg-white py-8 px-6 shadow-xl shadow-slate-200/60 rounded-3xl border border-slate-100 sm:px-10">
            <?php if (!empty($error)): ?>
                <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-center gap-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form class="space-y-5" method="POST" action="<?= $base ?>b2b/login">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

                <div>
                    <label for="login" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">
                        Login lub NIP sklepu
                    </label>
                    <div class="relative">
                        <input id="login" name="login" type="text" required autofocus
                            class="block w-full px-4 py-3 rounded-xl border border-slate-200 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm transition"
                            placeholder="np. sklep@zielony.pl">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">
                        Hasło dostępu
                    </label>
                    <div class="relative">
                        <input id="password" name="password" type="password" required
                            class="block w-full px-4 py-3 rounded-xl border border-slate-200 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm transition"
                            placeholder="••••••••">
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="w-full flex justify-center py-3.5 px-4 rounded-xl shadow-lg shadow-emerald-600/30 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition active:scale-[0.98]">
                        Zaloguj się do katalogu
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-100">
                <div class="rounded-2xl bg-emerald-50/70 p-4 border border-emerald-100/80 text-xs text-emerald-900 leading-relaxed">
                    <p class="font-bold flex items-center gap-1.5 text-emerald-800 mb-1">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Zamawiaj błyskawicznie bez hasła
                    </p>
                    Otrzymałeś od hurtowni link z tokenem? Wystarczy kliknąć link (np. w SMS lub e-mailu), aby wejść od razu do swojego spersonalizowanego cennika.
                </div>
            </div>
        </div>

        <div class="text-center mt-6 text-xs text-slate-400">
            Hurtownia Owoców i Warzyw „Hurtownia Magdy” &bull; Wszystkie prawa zastrzeżone
        </div>
    </div>
</body>
</html>
