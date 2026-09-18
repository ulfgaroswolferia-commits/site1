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
        $this->redirect(App::baseUrl() . 'b2b/login');
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
            $this->redirect(App::baseUrl() . 'b2b/login');
            return;
        }

        $client = $this->repo->getClientById($clientId);
        if (!$client || (int)$client['is_active'] !== 1) {
            unset($_SESSION['b2b_client_id'], $_SESSION['b2b_client_token'], $_SESSION['b2b_company_name']);
            $this->redirect(App::baseUrl() . 'b2b/login');
            return;
        }

        $this->outputData['title']     = 'Hurtownia Magdy — Zamówienia B2B';
        $this->outputData['client']    = $client;
        $this->outputData['products']  = $this->repo->getActiveProducts();
        $this->outputData['csrfToken'] = Tools::generateCsrf();
        $this->outputData['base']      = App::baseUrl();

        return 'catalog';
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

        if (Tools::isPost()) {
            $this->requireCsrf();
            $login = trim((string)($_POST['login'] ?? ''));
            $pass  = (string)($_POST['password'] ?? '');

            $client = $this->repo->getClientByLogin($login);
            if ($client && !empty($client['password_hash']) && password_verify($pass, $client['password_hash'])) {
                $_SESSION['b2b_client_id']    = (int)$client['id'];
                $_SESSION['b2b_client_token'] = $client['auth_token'];
                $_SESSION['b2b_company_name'] = $client['company_name'];
                $this->redirect(App::baseUrl() . 'b2b/index');
                return;
            } else {
                $this->outputData['error'] = 'Nieprawidłowy login lub hasło dostępu.';
            }
        }

        $this->outputData['csrfToken'] = Tools::generateCsrf();
        return 'login';
    }

    /**
     * GET /b2b/logout
     */
    public function actionLogout()
    {
        unset($_SESSION['b2b_client_id'], $_SESSION['b2b_client_token'], $_SESSION['b2b_company_name']);
        $this->redirect(App::baseUrl() . 'b2b/login');
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
        $this->outputData['csrfToken']    = Tools::generateCsrf();
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
            $candidates = $parser->detectCandidateColumns($previewRows);

            App::json([
                'ok'                => true,
                'file_id'           => $fileId,
                'original_name'     => $file['name'],
                'preview_rows'      => $previewRows,
                'candidate_columns' => $candidates,
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

        $ok = $this->repo->updateProduct($id, $data);
        App::json(['ok' => $ok]);
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
        App::json(['ok' => $ok]);
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
        $this->requireAuth();

        $id = (int)($_REQUEST['id'] ?? 0);
        $order = $this->repo->getOrderById($id);
        if (!$order) {
            App::json(['ok' => false, 'error' => 'Zamówienie nie istnieje.'], 404);
            return;
        }

        $items = $this->repo->getOrderItems($id);
        App::json([
            'ok'    => true,
            'order' => $order,
            'items' => $items
        ]);
    }
}
