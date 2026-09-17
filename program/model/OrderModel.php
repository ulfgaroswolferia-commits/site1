<?php

namespace App;

/**
 * Model danych zamówień warzyw i owoców.
 * Przechowuje nagłówki i pozycje zamówień w lokalnej bazie SQLite (db/orders.sqlite).
 */
class OrderModel extends \Model
{
    private static ?\PDO $sqlitePdo = null;

    /**
     * Konstruktor — nie wywołuje parent::__construct(), aby nie łączyć się
     * z domyślną bazą MySQL (OrderModel wykorzystuje SQLite w db/orders.sqlite).
     */
    public function __construct()
    {
    }

    /**
     * Dedykowany uchwyt PDO dla bazy SQLite zamówień.
     */
    protected function pdo(): \PDO
    {
        if (self::$sqlitePdo === null) {
            self::$sqlitePdo = self::initDatabase();
        }
        return self::$sqlitePdo;
    }

    /**
     * Inicjalizacja bazy SQLite i utworzenie tabel, jeśli nie istnieją.
     */
    public static function initDatabase(): \PDO
    {
        $dir = defined('BASE_PATH') ? (BASE_PATH . '/db') : (__DIR__ . '/../../db');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $sqliteFile = $dir . '/orders.sqlite';
        $pdo = new \PDO('sqlite:' . $sqliteFile, null, null, [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_number TEXT NOT NULL UNIQUE,
                supplier_name TEXT,
                original_filename TEXT NOT NULL,
                export_filename TEXT NOT NULL,
                total_items INTEGER NOT NULL DEFAULT 0,
                total_amount REAL NOT NULL DEFAULT 0.00,
                created_at DATETIME NOT NULL
            );

            CREATE TABLE IF NOT EXISTS order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                product_name TEXT NOT NULL,
                unit_price REAL NOT NULL,
                quantity REAL NOT NULL,
                unit TEXT NOT NULL DEFAULT "kg",
                item_total REAL NOT NULL,
                FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
            );
        ');

        return $pdo;
    }

    /**
     * Generuje czytelny, unikalny numer zamówienia dla danego dnia, np. ZAM/2026/09/17/01.
     */
    public function generateOrderNumber(): string
    {
        $todayPrefix = 'ZAM/' . date('Y/m/d') . '/';
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM orders WHERE order_number LIKE :prefix');
        $stmt->execute([':prefix' => $todayPrefix . '%']);
        $count = (int)$stmt->fetchColumn();

        $seq = str_pad((string)($count + 1), 2, '0', STR_PAD_LEFT);
        return $todayPrefix . $seq;
    }

    /**
     * Zapisuje nowe zamówienie wraz z pozycjami w transakcji bazy danych.
     */
    public function createOrder(array $orderData, array $items): int
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $orderNumber = $orderData['order_number'] ?? $this->generateOrderNumber();
            $supplierName = trim($orderData['supplier_name'] ?? '');
            $origFilename = $orderData['original_filename'] ?? 'cennik.xlsx';
            $exportFilename = $orderData['export_filename'] ?? ('zamowienie_' . date('Ymd_His') . '.xlsx');
            $createdAt = $orderData['created_at'] ?? date('Y-m-d H:i:s');

            $totalItems = 0;
            $totalAmount = 0.0;

            // Obliczenie sum
            foreach ($items as $item) {
                $qty = (float)($item['quantity'] ?? 0);
                if ($qty > 0) {
                    $totalItems++;
                    $price = (float)($item['price'] ?? $item['unit_price'] ?? 0);
                    $totalAmount += round($qty * $price, 2);
                }
            }

            $stmtOrder = $pdo->prepare('
                INSERT INTO orders (order_number, supplier_name, original_filename, export_filename, total_items, total_amount, created_at)
                VALUES (:num, :supplier, :orig_file, :exp_file, :items_cnt, :total_amt, :created)
            ');

            $stmtOrder->execute([
                ':num'       => $orderNumber,
                ':supplier'  => $supplierName,
                ':orig_file' => $origFilename,
                ':exp_file'  => $exportFilename,
                ':items_cnt' => $totalItems,
                ':total_amt' => round($totalAmount, 2),
                ':created'   => $createdAt,
            ]);

            $orderId = (int)$pdo->lastInsertId();

            $stmtItem = $pdo->prepare('
                INSERT INTO order_items (order_id, product_name, unit_price, quantity, unit, item_total)
                VALUES (:order_id, :prod_name, :price, :qty, :unit, :item_total)
            ');

            foreach ($items as $item) {
                $qty = (float)($item['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue; // Zapisujemy tylko zamówione pozycje
                }

                $name  = trim((string)($item['name'] ?? $item['product_name'] ?? ''));
                $price = (float)($item['price'] ?? $item['unit_price'] ?? 0);
                $unit  = trim((string)($item['unit'] ?? 'kg'));
                $itemTotal = round($qty * $price, 2);

                $stmtItem->execute([
                    ':order_id'   => $orderId,
                    ':prod_name'  => $name,
                    ':price'      => $price,
                    ':qty'        => $qty,
                    ':unit'       => $unit,
                    ':item_total' => $itemTotal,
                ]);
            }

            $pdo->commit();
            return $orderId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Zwraca pojedyncze zamówienie po ID.
     */
    public function getOrderById(int $orderId): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $orderId]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Zwraca listę pozycji dla danego zamówienia.
     */
    public function getOrderItems(int $orderId): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM order_items WHERE order_id = :order_id ORDER BY id ASC');
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll();
    }

    /**
     * Zwraca listę zamówień posortowaną od najnowszych z obsługą paginacji.
     */
    public function getAllOrders(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM orders ORDER BY id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', max(1, $limit), \PDO::PARAM_INT);
        $stmt->bindValue(':offset', max(0, $offset), \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Zwraca całkowitą liczbę zamówień w historii.
     */
    public function getOrdersCount(): int
    {
        return (int)$this->pdo()->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    }

    /**
     * Usuwa zamówienie i kaskadowo jego pozycje.
     */
    public function deleteOrder(int $orderId): bool
    {
        $stmt = $this->pdo()->prepare('DELETE FROM orders WHERE id = :id');
        return $stmt->execute([':id' => $orderId]);
    }
}
