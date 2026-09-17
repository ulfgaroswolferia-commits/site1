<?php

/**
 * Kontroler obsługi zamówień warzyw i owoców ze sklepu spożywczego do hurtowni.
 *
 * Przepływ:
 * 1. Upload cennika .xlsx z hurtowni -> podgląd wierszy
 * 2. Potwierdzenie mapowania kolumn (Towar, Cena, Jednostka)
 * 3. Interaktywna edycja ilości zamówienia z sumowaniem na żywo
 * 4. Eksport do czystego pliku .xlsx bez formatowania oraz zapis w historii SQLite
 */
class OrderController extends AppController
{
    public $layout = ''; // Samodzielny, nowoczesny layout dopasowany do dashboardu

    /**
     * GET /order/ albo /order/index
     * Główny ekran kreatora zamówienia.
     */
    public function actionIndex()
    {
        $this->requireAuth();

        $this->outputData['title']       = 'Nowe zamówienie warzyw i owoców';
        $this->outputData['user']        = Tools::getSessionVar('app_login') ?: 'użytkownik';
        $this->outputData['csrf_token']  = Tools::csrfToken();

        return 'index';
    }

    /**
     * POST /order/upload
     * Odbiera plik .xlsx z cennikiem i zwraca podgląd pierwszych wierszy oraz sugerowane kolumny.
     */
    public function actionUpload()
    {
        $this->requireAuth();
        $this->requireCsrf();

        if (empty($_FILES['price_list']) || $_FILES['price_list']['error'] !== UPLOAD_ERR_OK) {
            App::json(['ok' => false, 'error' => 'Błąd podczas przesyłania pliku.'], 400);
            return;
        }

        $file = $_FILES['price_list'];
        $originalName = $file['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($ext !== 'xlsx') {
            App::json(['ok' => false, 'error' => 'Dozwolony jest wyłącznie format .xlsx (Excel).'], 400);
            return;
        }

        $tmpDir = BASE_PATH . '/tmp';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        $fileId = 'cennik_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.xlsx';
        $destination = $tmpDir . '/' . $fileId;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            App::json(['ok' => false, 'error' => 'Nie udało się zapisać przesłanego pliku na serwerze.'], 500);
            return;
        }

        try {
            $parser = XlsxParser::open($destination);
            $previewRows = $parser->getRawRows(15);

            if (empty($previewRows)) {
                @unlink($destination);
                App::json(['ok' => false, 'error' => 'Arkusz jest pusty lub nie zawiera czytelnych komórek.'], 400);
                return;
            }

            $candidates = $parser->detectCandidateColumns($previewRows);

            App::json([
                'ok'            => true,
                'file_id'       => $fileId,
                'filename'      => $originalName,
                'preview'       => $previewRows,
                'candidates'    => $candidates,
            ]);
        } catch (\Throwable $e) {
            @unlink($destination);
            App::json(['ok' => false, 'error' => 'Błąd parsowania pliku Excel: ' . $e->getMessage()], 400);
        }
    }

    /**
     * POST /order/process
     * Na podstawie potwierdzonego mapowania wyciąga listę produktów do edycji.
     */
    public function actionProcess()
    {
        $this->requireAuth();
        $this->requireCsrf();

        $fileId     = trim((string)($_POST['file_id'] ?? ''));
        $headerRow  = (int)($_POST['header_row'] ?? 1);
        $prodCol    = (int)($_POST['col_product'] ?? 0);
        $priceCol   = (int)($_POST['col_price'] ?? 1);
        $unitColRaw = $_POST['col_unit'] ?? '';
        $unitCol    = ($unitColRaw !== '' && $unitColRaw !== null) ? (int)$unitColRaw : null;

        // Ochrona przed manipulacją ścieżką
        $fileId = basename($fileId);
        $filePath = BASE_PATH . '/tmp/' . $fileId;

        if (!file_exists($filePath)) {
            App::json(['ok' => false, 'error' => 'Plik sesji wygasł. Wgraj plik ponownie.'], 404);
            return;
        }

        try {
            $parser = XlsxParser::open($filePath);
            $products = $parser->extractProducts($headerRow, $prodCol, $priceCol, $unitCol);

            if (empty($products)) {
                App::json(['ok' => false, 'error' => 'Nie znaleziono pozycji asortymentowych poniżej wskazanego nagłówka.'], 400);
                return;
            }

            App::json([
                'ok'             => true,
                'products'       => $products,
                'total_products' => count($products),
            ]);
        } catch (\Throwable $e) {
            App::json(['ok' => false, 'error' => 'Błąd przetwarzania produktów: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /order/save
     * Zapisuje skompletowane zamówienie w bazie SQLite i generuje czysty plik .xlsx.
     */
    public function actionSave()
    {
        $this->requireAuth();
        $this->requireCsrf();

        $supplierName = trim((string)($_POST['supplier_name'] ?? ''));
        $originalName = trim((string)($_POST['original_filename'] ?? 'cennik.xlsx'));
        $itemsJson    = $_POST['items'] ?? '[]';
        $items        = json_decode($itemsJson, true);

        if (!is_array($items) || empty($items)) {
            App::json(['ok' => false, 'error' => 'Brak pozycji do zamówienia.'], 400);
            return;
        }

        // Filtrowanie wyłącznie zamówionych pozycji (ilość > 0)
        $validItems = [];
        $totalAmount = 0.0;

        foreach ($items as $item) {
            $qty = (float)($item['quantity'] ?? 0);
            if ($qty > 0) {
                $price = (float)($item['price'] ?? 0);
                $unit  = trim((string)($item['unit'] ?? 'kg'));
                $name  = trim((string)($item['name'] ?? ''));

                if ($name === '') {
                    continue;
                }

                $itemTotal = round($qty * $price, 2);
                $totalAmount += $itemTotal;

                $validItems[] = [
                    'name'       => $name,
                    'price'      => $price,
                    'quantity'   => $qty,
                    'unit'       => $unit,
                    'item_total' => $itemTotal,
                ];
            }
        }

        if (empty($validItems)) {
            App::json(['ok' => false, 'error' => 'Nie wprowadzono ilości dla żadnego z produktów (zamówienie jest puste).'], 400);
            return;
        }

        try {
            $model = new \App\OrderModel();
            $orderNumber = $model->generateOrderNumber();
            $safeNum = str_replace('/', '_', $orderNumber);
            $exportFilename = 'zamowienie_' . $safeNum . '.xlsx';

            // 1. Zapis czystego pliku Excela do katalogu storage/orders/
            $storageDir = BASE_PATH . '/storage/orders';
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0777, true);
            }

            $exportPath = $storageDir . '/' . $exportFilename;
            XlsxWriter::saveToFile($exportPath, $validItems, [
                'order_number'  => $orderNumber,
                'supplier_name' => $supplierName,
                'created_at'    => date('Y-m-d H:i'),
            ]);

            // 2. Zapis w bazie SQLite
            $orderId = $model->createOrder([
                'order_number'      => $orderNumber,
                'supplier_name'     => $supplierName,
                'original_filename' => $originalName,
                'export_filename'   => $exportFilename,
            ], $validItems);

            App::json([
                'ok'            => true,
                'order_id'      => $orderId,
                'order_number'  => $orderNumber,
                'download_url'  => App::baseUrl() . 'order/download/id/' . $orderId,
                'total_items'   => count($validItems),
                'total_amount'  => round($totalAmount, 2),
            ]);
        } catch (\Throwable $e) {
            App::json(['ok' => false, 'error' => 'Błąd zapisu zamówienia: ' . $e->getMessage()], 500);
        }
    }

    /**
     * GET /order/history
     * Widok tabeli złożonych zamówień.
     */
    public function actionHistory()
    {
        $this->requireAuth();

        $model = new \App\OrderModel();
        $this->outputData['title']  = 'Historia zamówień warzyw i owoców';
        $this->outputData['user']   = Tools::getSessionVar('app_login') ?: 'użytkownik';
        $this->outputData['orders'] = $model->getAllOrders(100);

        return 'history';
    }

    /**
     * GET /order/view/id/{id}
     * Podgląd szczegółów archiwalnego zamówienia.
     */
    public function actionView()
    {
        $this->requireAuth();

        $orderId = (int)$this->param('id', 0);
        $model = new \App\OrderModel();
        $order = $model->getOrderById($orderId);

        if (!$order) {
            Tools::setFlashMsg('error', 'Nie znaleziono wskazanego zamówienia.');
            App::redirect('order/history');
            return;
        }

        $this->outputData['title'] = 'Zamówienie ' . $order['order_number'];
        $this->outputData['user']  = Tools::getSessionVar('app_login') ?: 'użytkownik';
        $this->outputData['order'] = $order;
        $this->outputData['items'] = $model->getOrderItems($orderId);

        return 'view';
    }

    /**
     * GET /order/download/id/{id}
     * Bezpieczne pobranie wygenerowanego pliku .xlsx dla hurtowni.
     */
    public function actionDownload()
    {
        $this->requireAuth();

        $orderId = (int)$this->param('id', 0);
        $model = new \App\OrderModel();
        $order = $model->getOrderById($orderId);

        if (!$order) {
            http_response_code(404);
            die('Zamówienie nie istnieje.');
        }

        $filePath = BASE_PATH . '/storage/orders/' . $order['export_filename'];

        // Jeśli plik nie istnieje fizycznie, wygeneruj go w locie z bazy danych
        if (!file_exists($filePath)) {
            $items = $model->getOrderItems($orderId);
            XlsxWriter::saveToFile($filePath, $items, [
                'order_number'  => $order['order_number'],
                'supplier_name' => $order['supplier_name'],
                'created_at'    => $order['created_at'],
            ]);
        }

        if (!file_exists($filePath)) {
            http_response_code(500);
            die('Nie udało się wygenerować pliku zamówienia.');
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $order['export_filename'] . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));

        readfile($filePath);
        exit;
    }
}
