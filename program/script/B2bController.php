<?php
/**
 * Kontroler Portalu B2B Hurtownia Magdy
 * Obsługuje strefę hurtownika (/b2b/admin) oraz strefę klienta B2B (/b2b).
 */
class B2bController extends AppController
{
    private \App\B2bRepository $repo;

    public function __construct()
    {
        $this->repo = new \App\B2bRepository();
    }

    /**
     * Pomocnicza metoda sprawdzająca autoryzację klienta B2B.
     */
    private function requireClientAuth(): array
    {
        // Sprawdź czy w URL przekazano token autoryzacyjny
        if (!empty($_GET['token'])) {
            $token = trim((string)$_GET['token']);
            $client = $this->repo->getClientByToken($token);
            if ($client) {
                $_SESSION['b2b_client_id']    = (int)$client['id'];
                $_SESSION['b2b_client_token'] = $client['auth_token'];
                $_SESSION['b2b_company_name'] = $client['company_name'];
                return $client;
            }
        }

        // Sprawdź sesję
        $clientId = (int)($_SESSION['b2b_client_id'] ?? 0);
        if ($clientId > 0) {
            $client = $this->repo->getClientById($clientId);
            if ($client && (int)$client['is_active'] === 1) {
                return $client;
            }
        }

        // Brak autoryzacji — przekieruj do logowania klienta B2B
        App::redirect('b2b/login');
        exit;
    }

    // =========================================================================
    // STREFA KLIENTA B2B
    // =========================================================================

    /**
     * GET /b2b albo GET /b2b/index
     * Główny katalog zamówień dla klienta hurtowni.
     */
    public function actionIndex()
    {
        $this->layout = '';

        // Obsługa wejścia z tokenem autologowania
        if (!empty($_GET['token'])) {
            $token = trim((string)$_GET['token']);
            $client = $this->repo->getClientByToken($token);
            if ($client) {
                $_SESSION['b2b_client_id']    = (int)$client['id'];
                $_SESSION['b2b_client_token'] = $client['auth_token'];
                $_SESSION['b2b_company_name'] = $client['company_name'];
            }
        }

        $clientId = (int)($_SESSION['b2b_client_id'] ?? 0);
        if ($clientId <= 0) {
            // Jeśli zalogowany jest administrator (hurtownik), pozwól na bezpośredni podgląd sklepu B2B
            if ($this->isLoggedIn()) {
                $reqClientId = (int)($_GET['client_id'] ?? 0);
                $client = null;
                if ($reqClientId > 0) {
                    $client = $this->repo->getClientById($reqClientId);
                }
                if (empty($client)) {
                    $client = $this->repo->getClientByLogin('magda');
                }
                if (empty($client)) {
                    $allClients = $this->repo->getAllClients();
                    $client = !empty($allClients) ? $allClients[0] : null;
                }
                if (empty($client)) {
                    $client = [
                        'id'               => 0,
                        'company_name'     => 'Podgląd Hurtownika (Admin)',
                        'delivery_address' => 'Tryb podglądu oferty sklepu B2B',
                        'phone'            => '',
                        'email'            => '',
                        'auth_token'       => '',
                        'is_active'        => 1
                    ];
                }
            } else {
                App::redirect('b2b/login');
                return;
            }
        } else {
            $client = $this->repo->getClientById($clientId);
            if (!$client || (int)$client['is_active'] !== 1) {
                if ($this->isLoggedIn()) {
                    $client = [
                        'id'               => 0,
                        'company_name'     => 'Podgląd Hurtownika (Admin)',
                        'delivery_address' => 'Tryb podglądu oferty sklepu B2B',
                        'phone'            => '',
                        'email'            => '',
                        'auth_token'       => '',
                        'is_active'        => 1
                    ];
                } else {
                    unset($_SESSION['b2b_client_id'], $_SESSION['b2b_client_token'], $_SESSION['b2b_company_name']);
                    App::redirect('b2b/login');
                    return;
                }
            }
        }

        $products = $this->repo->getActiveProducts();
        $categories = array_values(array_unique(array_filter(array_column($products, 'category'))));
        sort($categories);

        $this->outputData['title']            = 'Hurtownia Magdy — Zamówienia B2B';
        $this->outputData['client']           = $client;
        $this->outputData['products']         = $products;
        $this->outputData['categories']       = $categories;
        $this->outputData['deliverySchedule'] = $this->getDeliverySchedule();
        $this->outputData['csrfToken']        = Tools::csrfToken();
        $this->outputData['base']             = App::baseUrl();
        $this->outputData['isAdmin']          = $this->isLoggedIn();

        return 'catalog';
    }

    public function getDeliverySchedule(?int $timestamp = null): array
    {
        return $this->repo->getDeliverySchedule($timestamp);
    }

    /**
     * POST /b2b/saveorder
     * Zatwierdzenie zamówienia z poziomu portalu B2B klienta.
     */
    public function actionSaveorder()
    {
        $clientId = (int)($_SESSION['b2b_client_id'] ?? 0);
        $client = null;
        if ($clientId > 0) {
            $client = $this->repo->getClientById($clientId);
        } elseif ($this->isLoggedIn()) {
            // Administrator w trybie podglądu sklepu B2B
            $client = $this->repo->getClientByLogin('magda');
            if (empty($client)) {
                $allClients = $this->repo->getAllClients();
                $client = !empty($allClients) ? $allClients[0] : null;
            }
            if ($client) {
                $clientId = (int)$client['id'];
            }
        }

        if (!$client || ($clientId > 0 && (int)$client['is_active'] !== 1)) {
            App::json(['ok' => false, 'error' => 'Brak aktywnej sesji klienta B2B. Zaloguj się ponownie.'], 401);
            return;
        }

        $this->requireCsrf();

        $rawItems = $_POST['items'] ?? null;
        if (is_string($rawItems)) {
            $items = json_decode($rawItems, true);
        } elseif (is_array($rawItems)) {
            $items = $rawItems;
        } else {
            $items = [];
        }

        if (empty($items) || !is_array($items)) {
            App::json(['ok' => false, 'error' => 'Koszyk zamówienia jest pusty.'], 400);
            return;
        }

        $preparedItems = [];
        $totalAmount = 0.0;

        foreach ($items as $it) {
            $qty = (float)($it['quantity'] ?? 0);
            if ($qty <= 0) continue;

            $name        = trim((string)($it['product_name'] ?? $it['name'] ?? 'Towar'));
            $price       = (float)($it['price'] ?? 0);
            $unit        = trim((string)($it['unit'] ?? 'kg'));
            $pkgSize     = (float)($it['package_size'] ?? 1.0);
            $pkgUnit     = trim((string)($it['package_unit'] ?? 'op.'));
            $itemTotal   = round($qty * $price, 2);
            $pkgSummary  = $this->repo->formatPackageSummary($qty, $pkgSize, $pkgUnit, $unit);

            $preparedItems[] = [
                'product_id'      => isset($it['product_id']) ? (int)$it['product_id'] : null,
                'product_name'    => $name,
                'price'           => $price,
                'quantity'        => $qty,
                'unit'            => $unit,
                'package_size'    => $pkgSize,
                'package_unit'    => $pkgUnit,
                'package_summary' => $pkgSummary,
                'item_total'      => $itemTotal
            ];

            $totalAmount += $itemTotal;
        }

        if (empty($preparedItems)) {
            App::json(['ok' => false, 'error' => 'Brak poprawnych pozycji w zamówieniu.'], 400);
            return;
        }

        $orderNumber = $this->repo->generateOrderNumber();
        $notes = trim((string)($_POST['notes'] ?? ''));

        $deliverySchedule = $this->getDeliverySchedule();
        $rawDeliveryDate = trim((string)($_POST['delivery_date'] ?? ''));
        $deliveryDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDeliveryDate) ? $rawDeliveryDate : ($deliverySchedule['default_date'] ?? date('Y-m-d', strtotime('+1 day')));

        // Generowanie karty kompletacji magazynowej .xlsx
        $safeNumber = str_replace(['/', '\\'], '_', $orderNumber);
        $fileName = 'kompletacja_' . $safeNumber . '.xlsx';
        $filePath = BASE_PATH . '/storage/b2b/orders/' . $fileName;

        $meta = [
            'order_number'     => $orderNumber,
            'client_name'      => $client['company_name'],
            'client_phone'     => $client['phone'] ?? '',
            'delivery_address' => $client['delivery_address'] ?? '',
            'delivery_date'    => $deliveryDate,
            'created_at'       => date('Y-m-d H:i:s'),
            'notes'            => $notes,
        ];

        try {
            XlsxWriter::savePackingSheetToFile($filePath, $preparedItems, $meta);
        } catch (\Throwable $e) {
            error_log('Błąd generowania karty kompletacji Excel: ' . $e->getMessage());
        }

        $orderId = $this->repo->createOrder([
            'order_number'              => $orderNumber,
            'client_id'                 => $clientId,
            'client_name_snapshot'      => $client['company_name'],
            'client_phone_snapshot'     => $client['phone'] ?? '',
            'delivery_address_snapshot' => $client['delivery_address'] ?? '',
            'delivery_date'             => $deliveryDate,
            'status'                    => 'new',
            'export_filename'           => $fileName,
            'total_amount'              => $totalAmount,
            'notes'                     => $notes,
        ], $preparedItems);

        $responseData = [
            'ok'              => true,
            'order_id'        => $orderId,
            'order_number'    => $orderNumber,
            'delivery_date'   => $deliveryDate,
            'total_amount'    => $totalAmount,
            'export_filename' => $fileName
        ];

        // Jeśli PHP działa pod FastCGI (Nginx/Apache), natychmiast odeślij odpowiedź do przeglądarki klienta,
        // aby modal potwierdzenia pokazał się w kilkanaście milisekund bez oczekiwania na operacje sieciowe.
        $responseSent = false;
        if (function_exists('fastcgi_finish_request')) {
            if (!headers_sent()) {
                http_response_code(200);
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            flush();
            fastcgi_finish_request();
            $responseSent = true;
        }

        // Opcjonalne powiadomienie e-mail z załącznikiem (wysyłane tylko gdy serwer poczty jest skonfigurowany)
        try {
            $isSmtpConfigured = defined('SMTP_HOST') && trim((string)SMTP_HOST) !== '';
            $isTransportMail  = defined('MAIL_TRANSPORT') && MAIL_TRANSPORT === 'mail';
            $toWholesale      = defined('MAIL_ORDER_NOTIFICATION') ? MAIL_ORDER_NOTIFICATION : (defined('MAIL_FROM') ? MAIL_FROM : '');

            if ($toWholesale !== '' && ($isSmtpConfigured || $isTransportMail)) {
                $emailSubject = "Nowe zamówienie B2B: {$orderNumber} — {$client['company_name']}";
                $emailBody = Mailer::buildOrderEmailHtml([
                    'order_number'  => $orderNumber,
                    'supplier_name' => 'Hurtownia Magdy (B2B)',
                    'created_at'    => date('Y-m-d H:i:s'),
                    'total_amount'  => $totalAmount
                ], array_map(function($i) {
                    return [
                        'name'       => $i['product_name'] . ' (' . $i['package_summary'] . ')',
                        'price'      => $i['price'],
                        'quantity'   => $i['quantity'],
                        'unit'       => $i['unit'],
                        'item_total' => $i['item_total']
                    ];
                }, $preparedItems));

                $attachments = file_exists($filePath) ? [$filePath] : [];
                Mailer::send($emailBody, $toWholesale, $emailSubject, [], $attachments);
            }
        } catch (\Throwable $e) {
            error_log('Błąd wysyłki e-maila B2B: ' . $e->getMessage());
        }

        if (!$responseSent) {
            App::json($responseData);
        }
        exit;
    }

    /**
     * GET /b2b/download/id/{id} lub GET /b2b/download?id={id}
     * Pobranie karty kompletacji .xlsx (dostępne dla admina lub odbiorcy zamówienia).
     */
    public function actionDownload()
    {
        $id = (int)($_GET['id'] ?? $this->getParam('id', 0));
        if ($id <= 0) {
            App::error(404, 'Brak identyfikatora zamówienia.');
            return;
        }

        $order = $this->repo->getOrderById($id);
        if (!$order) {
            App::error(404, 'Zamówienie nie istnieje.');
            return;
        }

        // Sprawdź uprawnienia: admin hurtowni LUB klient właściciel
        $isAdmin = $this->isLoggedIn();
        $isOwner = isset($_SESSION['b2b_client_id']) && (int)$_SESSION['b2b_client_id'] === (int)$order['client_id'];

        if (!$isAdmin && !$isOwner) {
            App::error(403, 'Brak uprawnień do pobrania tego zamówienia.');
            return;
        }

        $fileName = $order['export_filename'];
        if (empty($fileName)) {
            $safeNumber = str_replace(['/', '\\'], '_', $order['order_number']);
            $fileName = 'kompletacja_' . $safeNumber . '.xlsx';
        }

        $filePath = BASE_PATH . '/storage/b2b/orders/' . $fileName;

        // Jeśli pliku nie ma na dysku, wygeneruj go w locie
        if (!file_exists($filePath)) {
            $items = $this->repo->getOrderItems($id);
            $meta = [
                'order_number'     => $order['order_number'],
                'client_name'      => $order['client_name_snapshot'],
                'client_phone'     => $order['client_phone_snapshot'],
                'delivery_address' => $order['delivery_address_snapshot'],
                'created_at'       => $order['created_at'],
                'notes'            => $order['notes'],
            ];
            XlsxWriter::savePackingSheetToFile($filePath, $items, $meta);
        }

        if (file_exists($filePath)) {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: max-age=0');
            readfile($filePath);
            exit;
        } else {
            App::error(500, 'Nie udało się odnaleźć ani wygenerować pliku zamówienia.');
        }
    }

    /**
     * GET /b2b/exporterp?id={id}&format={subiekt|optima|symfonia|wfmag}
     * Eksport pojedynczego zamówienia do pliku programu handlowo-magazynowego (ERP).
     */
    public function actionExporterp()
    {
        $id = (int)($_GET['id'] ?? $this->getParam('id', 0));
        if ($id <= 0) {
            App::error(404, 'Brak identyfikatora zamówienia.');
            return;
        }

        $order = $this->repo->getOrderForErpExport($id);
        if (!$order) {
            App::error(404, 'Zamówienie nie istnieje.');
            return;
        }

        $isAdmin = $this->isLoggedIn();
        $isOwner = isset($_SESSION['b2b_client_id']) && (int)$_SESSION['b2b_client_id'] === (int)$order['client_id'];
        if (!$isAdmin && !$isOwner) {
            App::error(403, 'Brak uprawnień do pobrania tego zamówienia.');
            return;
        }

        $defaultFmt = $this->repo->getSetting('default_erp_format', 'subiekt');
        $format = trim((string)($_GET['format'] ?? $defaultFmt));

        try {
            $exported = ErpExporter::export($format, [$order]);
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $exported['mime']);
            header('Content-Disposition: attachment; filename="' . $exported['filename'] . '"');
            header('Content-Length: ' . strlen($exported['content']));
            header('Cache-Control: max-age=0');
            echo $exported['content'];
            exit;
        } catch (\Throwable $e) {
            App::error(400, 'Błąd generowania eksportu ERP: ' . $e->getMessage());
        }
    }

    /**
     * GET /b2b/exportbatch?format={subiekt|optima|symfonia|wfmag}&date={YYYY-MM-DD}&status={new|all...}
     * Zbiorczy eksport paczki zamówień do wybranego formatu ERP.
     */
    public function actionExportbatch()
    {
        $this->requireAuth();

        $defaultFmt = $this->repo->getSetting('default_erp_format', 'subiekt');
        $format = trim((string)($_GET['format'] ?? $defaultFmt));
        $date   = !empty($_GET['date']) ? trim((string)$_GET['date']) : null;
        $status = !empty($_GET['status']) ? trim((string)$_GET['status']) : null;

        $orders = $this->repo->getOrdersBatchForErpExport($date, $status);
        if (empty($orders)) {
            App::error(404, 'Brak zamówień spełniających kryteria eksportu.');
            return;
        }

        try {
            $exported = ErpExporter::export($format, $orders);
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $exported['mime']);
            header('Content-Disposition: attachment; filename="' . $exported['filename'] . '"');
            header('Content-Length: ' . strlen($exported['content']));
            header('Cache-Control: max-age=0');
            echo $exported['content'];
            exit;
        } catch (\Throwable $e) {
            App::error(400, 'Błąd generowania paczki ERP: ' . $e->getMessage());
        }
    }

    /**
     * POST /b2b/savesettings
     * Zapis konfiguracji hurtowni (cut-off time, dni dostaw, domyślny format ERP).
     */
    public function actionSavesettings()
    {
        $this->requireAuth();
        $this->requireCsrf();

        if (isset($_POST['cutoff_time'])) {
            $cutoff = trim((string)$_POST['cutoff_time']);
            if (preg_match('/^\d{1,2}:\d{2}$/', $cutoff)) {
                $this->repo->setSetting('cutoff_time', $cutoff);
            }
        }

        if (isset($_POST['delivery_days'])) {
            $days = trim((string)$_POST['delivery_days']);
            $this->repo->setSetting('delivery_days', $days);
        }

        if (isset($_POST['default_erp_format'])) {
            $fmt = strtolower(trim((string)$_POST['default_erp_format']));
            if (in_array($fmt, ['subiekt', 'optima', 'symfonia', 'wfmag'], true)) {
                $this->repo->setSetting('default_erp_format', $fmt);
            }
        }

        App::json(['ok' => true, 'message' => 'Ustawienia hurtowni zostały zaktualizowane.']);
    }

    /**
     * GET /b2b/history
     * Historia zamówień zalogowanego klienta B2B.
     */
    public function actionHistory()
    {
        $clientId = (int)($_SESSION['b2b_client_id'] ?? 0);
        if ($clientId <= 0) {
            if ($this->isLoggedIn()) {
                App::redirect('b2b/admin?tab=orders');
                return;
            }
            App::redirect('b2b/login');
            return;
        }

        $client = $this->repo->getClientById($clientId);
        if (!$client) {
            App::redirect('b2b/login');
            return;
        }

        $orders = $this->repo->getClientOrders($clientId);

        if ($this->isAjax()) {
            App::json(['ok' => true, 'orders' => $orders]);
            return;
        }

        $this->layout = '';
        $this->outputData['title']   = 'Historia Zamówień — ' . $client['company_name'];
        $this->outputData['client']  = $client;
        $this->outputData['orders']  = $orders;
        $this->outputData['base']    = App::baseUrl();

        return 'history';
    }

    /**
     * GET /b2b/login, POST /b2b/login
     * Tradycyjne logowanie odbiorcy B2B.
     */
    public function actionLogin()
    {
        $this->layout = '';
        $this->outputData['title'] = 'Logowanie B2B — Hurtownia Magdy';
        $this->outputData['error'] = '';
        $this->outputData['base']  = App::baseUrl();

        // Jeśli admin jest już zalogowany, przekieruj do panelu hurtownika
        if ($this->isLoggedIn()) {
            App::redirect('b2b/admin');
            return;
        }

        // Jeśli klient B2B jest już zalogowany, przekieruj do katalogu
        if (!empty($_SESSION['b2b_client_id'])) {
            App::redirect('b2b/index');
            return;
        }

        if (Tools::isPost()) {
            $this->requireCsrf();
            $login = trim((string)($_POST['login'] ?? ''));
            $pass  = (string)($_POST['password'] ?? '');

            // 1. Sprawdź czy to administrator (hurtownik)
            $adminLogin = defined('APP_LOGIN') ? APP_LOGIN : '';
            $adminPass  = defined('APP_PASSWORD') ? APP_PASSWORD : '';

            if ($adminLogin !== '' && $login === $adminLogin && $pass === $adminPass) {
                $this->startUserSession(1, ['app_login' => $login]);
                App::redirect('b2b/admin');
                return;
            }

            // 2. Sprawdź czy to odbiorca / sklep B2B
            $client = $this->repo->getClientByLogin($login);
            if ($client && !empty($client['password_hash']) && password_verify($pass, $client['password_hash'])) {
                $_SESSION['b2b_client_id']    = (int)$client['id'];
                $_SESSION['b2b_client_token'] = $client['auth_token'];
                $_SESSION['b2b_company_name'] = $client['company_name'];
                App::redirect('b2b/index');
                return;
            } else {
                $this->outputData['error'] = 'Nieprawidłowy login lub hasło dostępu.';
            }
        }

        $this->outputData['csrfToken'] = Tools::csrfToken();
        return 'login';
    }

    /**
     * GET /b2b/logout
     */
    public function actionLogout()
    {
        unset($_SESSION['b2b_client_id'], $_SESSION['b2b_client_token'], $_SESSION['b2b_company_name']);
        App::redirect('b2b/login');
    }

    // =========================================================================
    // PANEL HURTOWNIKA (ADMINISTRACJA)
    // =========================================================================

    /**
     * GET /b2b/admin
     * Główny dashboard zarządzania hurtowni (Cennik, Zamówienia, Klienci).
     */
    public function actionAdmin()
    {
        $this->requireAuth();
        $this->layout = '';

        $this->outputData['title']        = 'Panel Hurtownika — Hurtownia Magdy';
        $this->outputData['products']     = $this->repo->getAllProductsAdmin();
        $this->outputData['orders']       = $this->repo->getAllOrders(100);
        $this->outputData['clients']      = $this->repo->getAllClients();
        $this->outputData['settings']     = $this->repo->getAllSettings();
        $this->outputData['csrfToken']    = Tools::csrfToken();
        $this->outputData['base']         = App::baseUrl();
        $this->outputData['activeTab']    = $_GET['tab'] ?? 'products';

        return 'admin';
    }

    /**
     * POST /b2b/upload
     * Upload pliku .xlsx i autodetekcja kandydatów kolumn.
     */
    public function actionUpload()
    {
        $this->requireAuth();
        $this->requireCsrf();

        if (empty($_FILES['cennik']) || $_FILES['cennik']['error'] !== UPLOAD_ERR_OK) {
            App::json(['ok' => false, 'error' => 'Nie przesłano pliku lub wystąpił błąd uploadu.'], 400);
            return;
        }

        $file = $_FILES['cennik'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'xlsx') {
            App::json(['ok' => false, 'error' => 'Obsługiwany jest wyłącznie format .xlsx (Excel).'], 400);
            return;
        }

        $tmpDir = BASE_PATH . '/tmp';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        $fileId = 'cennik_b2b_' . time() . '_' . bin2hex(random_bytes(6)) . '.xlsx';
        $target = $tmpDir . '/' . $fileId;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            App::json(['ok' => false, 'error' => 'Nie udało się zapisać pliku w katalogu tymczasowym.'], 500);
            return;
        }

        try {
            $parser = XlsxParser::open($target);
            $previewRows = $parser->getRawRows(30);

            if (empty($previewRows)) {
                @unlink($target);
                App::json(['ok' => false, 'error' => 'Arkusz jest pusty lub nie zawiera czytelnych komórek.'], 400);
                return;
            }

            $candidates = $parser->detectCandidateColumns($previewRows);

            App::json([
                'ok'                => true,
                'file_id'           => $fileId,
                'original_name'     => $file['name'],
                'preview_rows'      => $previewRows,
                'candidate_columns' => [
                    'header_row_index'  => $candidates['headerRow'] ?? 1,
                    'product_col_index' => $candidates['productCol'] ?? 0,
                    'price_col_index'   => $candidates['priceCol'] ?? 1,
                    'unit_col_index'    => $candidates['unitCol'] ?? null,
                    'headerRow'         => $candidates['headerRow'] ?? 1,
                    'productCol'        => $candidates['productCol'] ?? 0,
                    'priceCol'          => $candidates['priceCol'] ?? 1,
                    'unitCol'           => $candidates['unitCol'] ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            @unlink($target);
            App::json(['ok' => false, 'error' => 'Błąd parsowania pliku Excel: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /b2b/processimport
     * Przetworzenie zmapowanych kolumn i wdrożenie cennika do oferty.
     */
    public function actionProcessimport()
    {
        $this->requireAuth();
        $this->requireCsrf();

        $fileId = basename((string)($_POST['file_id'] ?? ''));
        $target = BASE_PATH . '/tmp/' . $fileId;

        if (!file_exists($target)) {
            App::json(['ok' => false, 'error' => 'Plik cennika wygasł lub nie został znaleziony.'], 400);
            return;
        }

        $headerRow = (int)($_POST['header_row'] ?? 1);
        $colProd   = (int)($_POST['col_product'] ?? 0);
        $colPrice  = (int)($_POST['col_price'] ?? 1);
        $colUnit   = isset($_POST['col_unit']) && $_POST['col_unit'] !== '' ? (int)$_POST['col_unit'] : null;

        try {
            $parser = XlsxParser::open($target);
            $extracted = $parser->extractProducts($headerRow, $colProd, $colPrice, $colUnit);

            if (empty($extracted)) {
                App::json(['ok' => false, 'error' => 'Nie znaleziono pozycji towarowych w arkuszu.'], 400);
                return;
            }

            $count = $this->repo->saveProductsBatch($extracted, true);
            @unlink($target);

            App::json([
                'ok'             => true,
                'total_imported' => $count
            ]);
        } catch (\Throwable $e) {
            App::json(['ok' => false, 'error' => 'Błąd importu cennika: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /b2b/updateproduct
     * Szybka edycja pojedynczego produktu (cena, opakowanie, kategoria).
     */
    public function actionUpdateproduct()
    {
        $this->requireAuth();
        $this->requireCsrf();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            App::json(['ok' => false, 'error' => 'Brak identyfikatora produktu.'], 400);
            return;
        }

        $data = [];
        if (isset($_POST['name']))         $data['name']         = trim($_POST['name']);
        if (isset($_POST['category']))     $data['category']     = trim($_POST['category']);
        if (isset($_POST['unit']))         $data['unit']         = trim($_POST['unit']);
        if (isset($_POST['price']))        $data['price']        = (float)$_POST['price'];
        if (isset($_POST['package_size'])) $data['package_size'] = (float)$_POST['package_size'];
        if (isset($_POST['package_unit'])) $data['package_unit'] = trim($_POST['package_unit']);
        if (isset($_POST['erp_code']))     $data['erp_code']     = trim($_POST['erp_code']);

        $ok = $this->repo->updateProduct($id, $data);
        App::json(['ok' => $ok]);
    }

    /**
     * POST /b2b/updateproductsbatch
     * Hurtowy zapis wielu zmodyfikowanych pozycji asortymentu w jednej operacji.
     */
    public function actionUpdateproductsbatch()
    {
        $this->requireAuth();
        $this->requireCsrf();

        $rawProducts = $_POST['products'] ?? null;
        if (is_string($rawProducts)) {
            $products = json_decode($rawProducts, true);
        } elseif (is_array($rawProducts)) {
            $products = $rawProducts;
        } else {
            $products = [];
        }

        if (empty($products) || !is_array($products)) {
            App::json(['ok' => false, 'error' => 'Brak danych produktów do hurtowego zapisu.'], 400);
            return;
        }

        $savedCount = 0;
        $errors = [];

        foreach ($products as $p) {
            $id = (int)($p['id'] ?? 0);
            if ($id <= 0) continue;

            $data = [];
            if (isset($p['name']))         $data['name']         = trim((string)$p['name']);
            if (isset($p['category']))     $data['category']     = trim((string)$p['category']);
            if (isset($p['unit']))         $data['unit']         = trim((string)$p['unit']);
            if (isset($p['price']))        $data['price']        = (float)$p['price'];
            if (isset($p['package_size'])) $data['package_size'] = (float)$p['package_size'];
            if (isset($p['package_unit'])) $data['package_unit'] = trim((string)$p['package_unit']);
            if (isset($p['erp_code']))     $data['erp_code']     = trim((string)$p['erp_code']);

            if (!empty($data['name'])) {
                if ($this->repo->updateProduct($id, $data)) {
                    $savedCount++;
                } else {
                    $errors[] = "Nie udało się zaktualizować pozycji ID {$id}";
                }
            }
        }

        App::json([
            'ok'          => true,
            'saved_count' => $savedCount,
            'errors'      => $errors
        ]);
    }

    /**
     * POST /b2b/toggleproduct
     * Błyskawiczne włączenie/wyłączenie towaru z oferty na dziś.
     */
    public function actionToggleproduct()
    {
        $this->requireAuth();
        $this->requireCsrf();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            App::json(['ok' => false, 'error' => 'Brak identyfikatora produktu.'], 400);
            return;
        }

        $force = isset($_POST['is_available']) ? (int)$_POST['is_available'] : null;
        $ok = $this->repo->toggleProductAvailability($id, $force);

        App::json(['ok' => $ok]);
    }

    /**
     * POST /b2b/createclient
     * Rejestracja nowego klienta B2B z generowaniem tokenu.
     */
    public function actionCreateclient()
    {
        $this->requireAuth();
        $this->requireCsrf();

        $companyName = trim((string)($_POST['company_name'] ?? ''));
        if ($companyName === '') {
            App::json(['ok' => false, 'error' => 'Nazwa firmy/sklepu jest wymagana.'], 400);
            return;
        }

        $token = bin2hex(random_bytes(16));
        $clientId = $this->repo->createClient([
            'company_name'     => $companyName,
            'nip'              => trim((string)($_POST['nip'] ?? '')),
            'phone'            => trim((string)($_POST['phone'] ?? '')),
            'email'            => trim((string)($_POST['email'] ?? '')),
            'delivery_address' => trim((string)($_POST['delivery_address'] ?? '')),
            'auth_token'       => $token,
            'login'            => !empty($_POST['login']) ? trim((string)$_POST['login']) : null,
            'password'         => !empty($_POST['password']) ? (string)$_POST['password'] : null,
        ]);

        $tokenUrl = App::baseUrl() . 'b2b?token=' . $token;

        App::json([
            'ok'          => true,
            'client_id'   => $clientId,
            'auth_token'  => $token,
            'token_url'   => $tokenUrl
        ]);
    }

    /**
     * POST /b2b/toggleclient
     */
    public function actionToggleclient()
    {
        $this->requireAuth();
        $this->requireCsrf();

        $id = (int)($_POST['id'] ?? 0);
        $ok = $this->repo->toggleClientStatus($id);
        $client = $this->repo->getClientById($id);
        $isActive = $client ? (int)$client['is_active'] : null;

        App::json([
            'ok'        => $ok,
            'is_active' => $isActive,
            'message'   => ($isActive === 1) ? 'Konto klienta zostało aktywowane.' : 'Konto klienta zostało zablokowane.'
        ]);
    }

    /**
     * POST /b2b/updateclient
     * Aktualizacja danych istniejącego odbiorcy B2B.
     */
    public function actionUpdateclient()
    {
        $this->requireAuth();
        $this->requireCsrf();

        $id = (int)($_POST['id'] ?? 0);
        $client = $this->repo->getClientById($id);
        if (!$client) {
            App::json(['ok' => false, 'error' => 'Klient nie istnieje.'], 404);
            return;
        }

        $companyName = trim((string)($_POST['company_name'] ?? ''));
        if ($companyName === '') {
            App::json(['ok' => false, 'error' => 'Nazwa firmy/sklepu jest wymagana.'], 400);
            return;
        }

        $updateData = [
            'company_name'     => $companyName,
            'nip'              => trim((string)($_POST['nip'] ?? '')),
            'phone'            => trim((string)($_POST['phone'] ?? '')),
            'email'            => trim((string)($_POST['email'] ?? '')),
            'delivery_address' => trim((string)($_POST['delivery_address'] ?? '')),
        ];

        if (isset($_POST['is_active'])) {
            $updateData['is_active'] = ((int)$_POST['is_active'] === 1) ? 1 : 0;
        }

        if (isset($_POST['login'])) {
            $updateData['login'] = trim((string)$_POST['login']) ?: null;
        }

        if (!empty($_POST['password'])) {
            $updateData['password'] = (string)$_POST['password'];
        }

        if (!empty($_POST['regenerate_token'])) {
            $updateData['auth_token'] = bin2hex(random_bytes(16));
        }

        $ok = $this->repo->updateClient($id, $updateData);
        if (!$ok) {
            App::json(['ok' => false, 'error' => 'Nie udało się zaktualizować danych klienta.'], 500);
            return;
        }

        $updatedClient = $this->repo->getClientById($id);
        $tokenUrl = App::baseUrl() . 'b2b?token=' . $updatedClient['auth_token'];

        App::json([
            'ok'        => true,
            'client'    => $updatedClient,
            'token_url' => $tokenUrl,
            'message'   => 'Dane odbiorcy zostały pomyślnie zaktualizowane.'
        ]);
    }

    /**
     * POST /b2b/deleteclient
     * Trwałe usunięcie odbiorcy B2B z bazy danych.
     */
    public function actionDeleteclient()
    {
        $this->requireAuth();
        $this->requireCsrf();

        $id = (int)($_POST['id'] ?? 0);
        $client = $this->repo->getClientById($id);
        if (!$client) {
            App::json(['ok' => false, 'error' => 'Klient nie istnieje.'], 404);
            return;
        }

        $ok = $this->repo->deleteClient($id);
        if (!$ok) {
            App::json(['ok' => false, 'error' => 'Nie udało się usunąć klienta z bazy.'], 500);
            return;
        }

        App::json([
            'ok'      => true,
            'message' => 'Odbiorca "' . $client['company_name'] . '" został trwale usunięty z bazy.'
        ]);
    }

    /**
     * POST /b2b/sendtoken
     * Wysyłka linku dostępowego z unikalnym tokenem na e-mail klienta.
     */
    public function actionSendtoken()
    {
        $this->requireAuth();
        $this->requireCsrf();

        $id = (int)($_POST['id'] ?? 0);
        $client = $this->repo->getClientById($id);
        if (!$client) {
            App::json(['ok' => false, 'error' => 'Klient nie istnieje.'], 404);
            return;
        }

        $channel = trim((string)($_POST['channel'] ?? 'email'));
        $tokenUrl = App::baseUrl() . 'b2b?token=' . $client['auth_token'];
        $companyName = $client['company_name'];
        $email = trim((string)($client['email'] ?? ''));

        if ($channel === 'email') {
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                App::json(['ok' => false, 'error' => 'Klient nie posiada poprawnego adresu e-mail.'], 400);
                return;
            }

            $html = self::buildTokenEmailHtml($client, $tokenUrl);
            $subject = 'Dostęp do panelu zamówień hurtowych B2B — ' . $companyName;
            $sent = Mailer::send($html, $email, $subject);

            if (!$sent) {
                $err = Mailer::$lastError ?: 'Nieznany błąd serwera pocztowego.';
                App::json([
                    'ok'    => false,
                    'error' => 'Błąd wysyłki e-mail: ' . $err,
                    'fallback_mailto' => 'mailto:' . rawurlencode($email) . '?subject=' . rawurlencode($subject) . '&body=' . rawurlencode("Dzień dobry,\n\nOto Twój bezpośredni link do składania zamówień hurtowych w Hurtowni:\n" . $tokenUrl . "\n\nPozdrawiamy,\nHurtownia Warzyw i Owoców")
                ], 500);
                return;
            }

            App::json([
                'ok'      => true,
                'message' => 'Wiadomość z linkiem dostępowym została pomyślnie wysłana na adres: ' . $email
            ]);
            return;
        }

        App::json(['ok' => false, 'error' => 'Nieobsługiwany kanał wysyłki.'], 400);
    }

    /**
     * Generuje treść HTML wiadomości e-mail z tokenem dostępowym dla klienta.
     */
    public static function buildTokenEmailHtml(array $client, string $tokenUrl): string
    {
        $company = Tools::h($client['company_name']);
        $address = Tools::h($client['delivery_address'] ?: 'Brak zdefiniowanego adresu');
        $phone   = Tools::h($client['phone'] ?: '—');
        $safeUrl = htmlspecialchars($tokenUrl, ENT_QUOTES, 'UTF-8');

        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dostęp do zamówień B2B</title>
</head>
<body style="margin: 0; padding: 24px; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; color: #1e293b;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">
        <div style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); padding: 32px 28px; color: #ffffff; text-align: center;">
            <h1 style="margin: 0 0 8px; font-size: 22px; font-weight: 800; letter-spacing: -0.02em;">Panel Zamówień Hurtowych B2B</h1>
            <p style="margin: 0; font-size: 15px; opacity: 0.95;">Hurtownia Warzyw i Owoców</p>
        </div>
        <div style="padding: 28px;">
            <p style="font-size: 16px; margin: 0 0 16px; color: #0f172a;">Dzień dobry, <strong>' . $company . '</strong>!</p>
            <p style="font-size: 14px; line-height: 1.6; color: #334155; margin: 0 0 24px;">
                Przygotowaliśmy dla Ciebie bezpośredni, bezpieczny dostęp do naszego katalogu hurtowego świeżych warzyw i owoców. 
                Nie musisz pamiętać haseł ani loginu — wystarczy kliknąć poniższy przycisk, aby od razu rozpocząć składanie zamówienia z Twoimi cenami i rabatami hurtowymi.
            </p>
            <div style="text-align: center; margin: 32px 0;">
                <a href="' . $safeUrl . '" style="background-color: #059669; color: #ffffff; font-size: 15px; font-weight: 700; text-decoration: none; padding: 14px 32px; border-radius: 12px; display: inline-block; box-shadow: 0 4px 12px rgba(5,150,105,0.3);">
                    Przejdź do składania zamówienia &rarr;
                </a>
            </div>
            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 20px; font-size: 13px; margin: 24px 0;">
                <p style="margin: 0 0 8px; font-weight: 700; color: #0f172a;">Twoje dane rejestracyjne:</p>
                <p style="margin: 4px 0; color: #475569;">Telefon kontaktowy: <strong>' . $phone . '</strong></p>
                <p style="margin: 4px 0; color: #475569;">Adres dostawy: <strong>' . $address . '</strong></p>
            </div>
            <p style="font-size: 12px; line-height: 1.5; color: #64748b; margin: 24px 0 0;">
                Jeśli przycisk nie działa, skopiuj i wklej poniższy link w oknie przeglądarki:<br>
                <a href="' . $safeUrl . '" style="color: #059669; word-break: break-all;">' . $safeUrl . '</a>
            </p>
        </div>
        <div style="padding: 16px 28px; background: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; text-align: center;">
            Wiadomość wygenerowana automatycznie przez Hurtownię Warzyw i Owoców.
        </div>
    </div>
</body>
</html>';
    }

    /**
     * POST /b2b/updateorderstatus
     */
    public function actionUpdateorderstatus()
    {
        $this->requireAuth();
        $this->requireCsrf();

        $id = (int)($_POST['id'] ?? 0);
        $status = trim((string)($_POST['status'] ?? ''));

        if (!in_array($status, ['new', 'processing', 'completed', 'cancelled'])) {
            App::json(['ok' => false, 'error' => 'Nieprawidłowy status zamówienia.'], 400);
            return;
        }

        $ok = $this->repo->updateOrderStatus($id, $status);
        App::json(['ok' => $ok]);
    }

    /**
     * GET/POST /b2b/orderdetails
     */
    public function actionOrderdetails()
    {
        $id = (int)($_REQUEST['id'] ?? $this->getParam('id', 0));
        $order = $this->repo->getOrderById($id);
        if (!$order) {
            App::json(['ok' => false, 'error' => 'Zamówienie nie istnieje.'], 404);
            return;
        }

        // Sprawdź uprawnienia: admin hurtowni LUB klient właściciel
        $isAdmin = $this->isLoggedIn();
        $isOwner = isset($_SESSION['b2b_client_id']) && (int)$_SESSION['b2b_client_id'] === (int)$order['client_id'];

        if (!$isAdmin && !$isOwner) {
            App::json(['ok' => false, 'error' => 'Brak uprawnień do podglądu tego zamówienia.'], 403);
            return;
        }

        $items = $this->repo->getOrderItems($id);
        foreach ($items as &$it) {
            if (!empty($it['package_summary'])) {
                $it['package_summary'] = \App\B2bRepository::inflectSummaryString((string)$it['package_summary']);
            } elseif (!empty($it['package_size']) && (float)$it['package_size'] > 1.0) {
                $it['package_summary'] = $this->repo->formatPackageSummary(
                    (float)$it['quantity'],
                    (float)$it['package_size'],
                    (string)($it['package_unit'] ?? 'op.'),
                    (string)($it['unit'] ?? 'kg')
                );
            }
        }
        unset($it);

        App::json([
            'ok'    => true,
            'order' => $order,
            'items' => $items
        ]);
    }
}
