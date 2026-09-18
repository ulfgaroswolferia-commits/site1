<?php
$client = $view['client'] ?? [];
$orders = $view['orders'] ?? [];
$base   = $view['base'] ?? App::baseUrl();
$title  = $view['title'] ?? 'Historia Zamówień — Hurtownia Magdy';
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-100">
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
<body class="min-h-full flex flex-col bg-slate-100 text-slate-800">

    <!-- Top Navigation Header -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 flex items-center justify-center text-white shadow-md shadow-emerald-600/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-lg font-black tracking-tight text-slate-900 block leading-tight">HURTOWNIA MAGDY</span>
                        <span class="text-[11px] font-bold tracking-wider uppercase text-emerald-600">Portal Zamówień B2B</span>
                    </div>
                </div>

                <!-- Client Info & Actions -->
                <div class="flex items-center gap-4">
                    <a href="<?= $base ?>b2b" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-xs font-bold text-white shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Wróć do katalogu
                    </a>

                    <a href="<?= $base ?>b2b/logout" class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Wyloguj">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
        <div class="mb-6">
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Historia zamówień</h1>
            <p class="text-sm text-slate-500 mt-1">Zestawienie Twoich wcześniejszych zamówień w Hurtowni Magdy.</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-4">Numer zamówienia</th>
                            <th class="py-3.5 px-4">Data złożenia</th>
                            <th class="py-3.5 px-4 text-center">Pozycje</th>
                            <th class="py-3.5 px-4 text-right">Wartość</th>
                            <th class="py-3.5 px-4 text-center">Status</th>
                            <th class="py-3.5 px-4 text-right">Dokumenty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    Nie złożyłeś jeszcze żadnego zamówienia.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $o): 
                                $st = $o['status'] ?? 'new';
                                $badgeClass = match($st) {
                                    'new' => 'bg-sky-50 text-sky-700 border-sky-200',
                                    'processing' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-slate-50 text-slate-700 border-slate-200'
                                };
                                $statusLabel = match($st) {
                                    'new' => 'Nowe',
                                    'processing' => 'W kompletacji',
                                    'completed' => 'Zrealizowane',
                                    'cancelled' => 'Anulowane',
                                    default => $st
                                };
                            ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-4 px-4 font-black text-slate-900">
                                        <?= htmlspecialchars($o['order_number']) ?>
                                    </td>
                                    <td class="py-4 px-4 text-slate-500 text-xs">
                                        <?= htmlspecialchars($o['created_at']) ?>
                                    </td>
                                    <td class="py-4 px-4 text-center font-bold text-slate-700">
                                        <?= (int)$o['total_items'] ?>
                                    </td>
                                    <td class="py-4 px-4 text-right font-black text-slate-900">
                                        <?= number_format((float)$o['total_amount'], 2, '.', ' ') ?> zł
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border <?= $badgeClass ?>">
                                            <?= $statusLabel ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <a href="<?= $base ?>b2b/download?id=<?= (int)$o['id'] ?>"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-100 hover:text-emerald-700 transition">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            Excel (.xlsx)
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
