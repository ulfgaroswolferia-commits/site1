<?php
namespace App;

use PDO;
use PDOException;

/**
 * Repozytorium danych dla modułu B2B Hurtownia Magdy.
 * Obsługuje uniwersalną warstwę PDO (domyślnie SQLite db/b2b.sqlite, z gotowością na MySQL).
 */
class B2bRepository
{
    private PDO $pdo;
    private string $driver;

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo !== null) {
            $this->pdo = $pdo;
            $this->driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            return;
        }

        $this->driver = defined('B2B_DB_DRIVER') ? strtolower((string)B2B_DB_DRIVER) : 'sqlite';

        if ($this->driver === 'mysql') {
            $host = defined('B2B_MYSQL_HOST') ? B2B_MYSQL_HOST : '127.0.0.1';
            $port = defined('B2B_MYSQL_PORT') ? (int)B2B_MYSQL_PORT : 3306;
            $name = defined('B2B_MYSQL_NAME') ? B2B_MYSQL_NAME : 'hurtownia_magdy';
            $user = defined('B2B_MYSQL_USER') ? B2B_MYSQL_USER : 'root';
            $pass = defined('B2B_MYSQL_PASS') ? B2B_MYSQL_PASS : '';

            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } else {
            // Domyślny SQLite
            $dbDir = BASE_PATH . '/db';
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0777, true);
            }
            $dbPath = $dbDir . '/b2b.sqlite';
            $this->pdo = new PDO('sqlite:' . $dbPath, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $this->pdo->exec('PRAGMA foreign_keys = ON;');
            $this->pdo->exec('PRAGMA journal_mode = WAL;');
            $this->pdo->exec('PRAGMA busy_timeout = 5000;');
            $this->pdo->exec('PRAGMA synchronous = NORMAL;');
        }

        $this->initDatabase();
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Inicjalizuje tabele bazy danych, jeśli nie istnieją.
     */
    public function initDatabase(): void
    {
        $isSqlite = ($this->driver === 'sqlite');
        $pk = $isSqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS b2b_clients (
                id {$pk},
                company_name VARCHAR(150) NOT NULL,
                nip VARCHAR(20) DEFAULT NULL,
                phone VARCHAR(50) DEFAULT NULL,
                email VARCHAR(120) DEFAULT NULL,
                delivery_address TEXT DEFAULT NULL,
                auth_token VARCHAR(64) NOT NULL UNIQUE,
                login VARCHAR(50) DEFAULT NULL UNIQUE,
                password_hash VARCHAR(255) DEFAULT NULL,
                price_group_id INT NOT NULL DEFAULT 1,
                is_active INT NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL
            );

            CREATE TABLE IF NOT EXISTS b2b_products (
                id {$pk},
                name VARCHAR(150) NOT NULL,
                category VARCHAR(50) NOT NULL DEFAULT 'Inne',
                unit VARCHAR(20) NOT NULL DEFAULT 'kg',
                price REAL NOT NULL DEFAULT 0.00,
                package_size REAL NOT NULL DEFAULT 1.0,
                package_unit VARCHAR(30) NOT NULL DEFAULT 'op.',
                is_available INT NOT NULL DEFAULT 1,
                is_new INT NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                updated_at DATETIME NOT NULL
            );

            CREATE TABLE IF NOT EXISTS b2b_package_rules (
                id {$pk},
                name_pattern VARCHAR(150) NOT NULL UNIQUE,
                package_size REAL NOT NULL DEFAULT 1.0,
                package_unit VARCHAR(30) NOT NULL DEFAULT 'op.',
                unit VARCHAR(20) NOT NULL DEFAULT 'kg'
            );

            CREATE TABLE IF NOT EXISTS b2b_orders (
                id {$pk},
                order_number VARCHAR(40) NOT NULL UNIQUE,
                client_id INT NOT NULL,
                client_name_snapshot VARCHAR(150) NOT NULL,
                client_phone_snapshot VARCHAR(50) DEFAULT NULL,
                delivery_address_snapshot TEXT DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'new',
                export_filename VARCHAR(150) DEFAULT NULL,
                total_items INT NOT NULL DEFAULT 0,
                total_amount REAL NOT NULL DEFAULT 0.00,
                notes TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL
            );

            CREATE TABLE IF NOT EXISTS b2b_order_items (
                id {$pk},
                order_id INT NOT NULL,
                product_id INT DEFAULT NULL,
                product_name VARCHAR(150) NOT NULL,
                price REAL NOT NULL,
                quantity REAL NOT NULL,
                unit VARCHAR(20) NOT NULL,
                package_size REAL NOT NULL DEFAULT 1.0,
                package_unit VARCHAR(30) NOT NULL DEFAULT 'op.',
                package_summary VARCHAR(100) DEFAULT NULL,
                item_total REAL NOT NULL
            );

            CREATE TABLE IF NOT EXISTS b2b_settings (
                setting_key VARCHAR(50) PRIMARY KEY,
                setting_value TEXT NOT NULL
            );
        ");

        try {
            $this->pdo->exec("ALTER TABLE b2b_products ADD COLUMN is_new INT NOT NULL DEFAULT 0");
        } catch (\Throwable $e) {}
        try {
            $this->pdo->exec("ALTER TABLE b2b_products ADD COLUMN erp_code VARCHAR(50) DEFAULT NULL");
        } catch (\Throwable $e) {}
        try {
            $this->pdo->exec("ALTER TABLE b2b_orders ADD COLUMN delivery_date DATE DEFAULT NULL");
        } catch (\Throwable $e) {}

        // Domyślne konfiguracje hurtowni (cut-off, dni dostaw, format ERP)
        $defaultSettings = [
            'cutoff_time'        => '21:30',
            'delivery_days'      => 'mon,tue,wed,thu,fri,sat',
            'default_erp_format' => 'subiekt'
        ];
        foreach ($defaultSettings as $sk => $sv) {
            try {
                $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO b2b_settings (setting_key, setting_value) VALUES (:k, :v)");
                $stmt->execute([':k' => $sk, ':v' => $sv]);
            } catch (\Throwable $e) {}
        }
    }

    // =========================================================================
    // USTAWIENIA HURTOWNI (B2B SETTINGS)
    // =========================================================================

    public function getSetting(string $key, ?string $default = null): ?string
    {
        $stmt = $this->pdo->prepare("SELECT setting_value FROM b2b_settings WHERE setting_key = :k LIMIT 1");
        $stmt->execute([':k' => trim($key)]);
        $val = $stmt->fetchColumn();
        return ($val !== false && $val !== null) ? (string)$val : $default;
    }

    public function setSetting(string $key, string $value): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO b2b_settings (setting_key, setting_value)
            VALUES (:k, :v)
            ON CONFLICT(setting_key) DO UPDATE SET setting_value = :v2
        ");
        return $stmt->execute([':k' => trim($key), ':v' => $value, ':v2' => $value]);
    }

    public function getAllSettings(): array
    {
        $rows = $this->pdo->query("SELECT setting_key, setting_value FROM b2b_settings")->fetchAll();
        $res = [];
        foreach ($rows as $r) {
            $res[$r['setting_key']] = $r['setting_value'];
        }
        return $res;
    }

    /**
     * Oblicza dostępne dni dostawy na podstawie godziny granicznej (cut-off) i aktywnych dni pracy hurtowni.
     * Zwraca tablicę z flagą is_cutoff_passed, komunikatem oraz opcjami dostawy (np. najbliższe 2-3 dni).
     */
    public function getDeliverySchedule(?int $timestamp = null): array
    {
        $cutoffStr = $this->getSetting('cutoff_time', '21:30');
        $daysSetting = $this->getSetting('delivery_days', 'mon,tue,wed,thu,fri,sat');
        $activeDays = array_map('trim', explode(',', strtolower($daysSetting)));

        $now = new \DateTime();
        if ($timestamp !== null) {
            $now->setTimestamp($timestamp);
        }

        // Sprawdź czy godzina graniczna minęła
        $parts = explode(':', $cutoffStr);
        $cHour = (int)($parts[0] ?? 21);
        $cMinute = (int)($parts[1] ?? 30);
        $cutoffToday = clone $now;
        $cutoffToday->setTime($cHour, $cMinute, 0);

        $isCutoffPassed = ($now > $cutoffToday);

        // Mapowanie nazw dni tygodnia po polsku
        $daysPl = [
            'mon' => 'Poniedziałek',
            'tue' => 'Wtorek',
            'wed' => 'Środa',
            'thu' => 'Czwartek',
            'fri' => 'Piątek',
            'sat' => 'Sobota',
            'sun' => 'Niedziela'
        ];

        // Wyznacz kandydata na pierwszy dostępny dzień dostawy:
        // Jeśli cut-off minął -> zaczynamy poszukiwanie od +2 dni (pojutrze).
        // Jeśli przed cut-off -> zaczynamy od +1 dnia (jutro).
        $startOffset = $isCutoffPassed ? 2 : 1;

        $options = [];
        for ($i = $startOffset; $i <= $startOffset + 7; $i++) {
            $cand = (clone $now)->modify("+{$i} days");
            $dayCode = strtolower($cand->format('D'));

            if (in_array($dayCode, $activeDays, true)) {
                $dateYmd = $cand->format('Y-m-d');
                $dayName = $daysPl[$dayCode] ?? $cand->format('l');

                // Relatywne określenie dnia
                $diffDays = (int)$now->diff($cand)->format('%a');
                if ($diffDays === 1) {
                    $prefix = 'Jutro';
                } elseif ($diffDays === 2) {
                    $prefix = 'Pojutrze';
                } else {
                    $prefix = $dayName;
                }

                $shortLabel = ($prefix === $dayName) ? $dayName : "{$prefix} ({$dayName})";
                $subLabel = "Dostawa: " . $cand->format('d.m.Y');

                $options[] = [
                    'date'        => $dateYmd,
                    'short_label' => $shortLabel,
                    'sub_label'   => $subLabel,
                    'is_default'  => count($options) === 0,
                ];

                if (count($options) >= 2) {
                    break;
                }
            }
        }

        return [
            'cutoff_time'      => $cutoffStr,
            'is_cutoff_passed' => $isCutoffPassed,
            'options'          => $options,
            'default_date'     => $options[0]['date'] ?? (clone $now)->modify('+1 day')->format('Y-m-d'),
        ];
    }

    // =========================================================================
    // KLIENCI B2B
    // =========================================================================

    public function createClient(array $data): int
    {
        $token = !empty($data['auth_token']) ? trim($data['auth_token']) : bin2hex(random_bytes(16));
        $passHash = !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;

        $stmt = $this->pdo->prepare("
            INSERT INTO b2b_clients (company_name, nip, phone, email, delivery_address, auth_token, login, password_hash, price_group_id, is_active, created_at)
            VALUES (:company_name, :nip, :phone, :email, :delivery_address, :auth_token, :login, :password_hash, :price_group_id, :is_active, :created_at)
        ");

        $stmt->execute([
            ':company_name'     => trim($data['company_name'] ?? ''),
            ':nip'              => trim($data['nip'] ?? ''),
            ':phone'            => trim($data['phone'] ?? ''),
            ':email'            => trim($data['email'] ?? ''),
            ':delivery_address' => trim($data['delivery_address'] ?? ''),
            ':auth_token'       => $token,
            ':login'            => !empty($data['login']) ? trim($data['login']) : null,
            ':password_hash'    => $passHash,
            ':price_group_id'   => (int)($data['price_group_id'] ?? 1),
            ':is_active'        => isset($data['is_active']) ? (int)$data['is_active'] : 1,
            ':created_at'       => date('Y-m-d H:i:s')
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function getClientByToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM b2b_clients WHERE auth_token = :token AND is_active = 1 LIMIT 1");
        $stmt->execute([':token' => trim($token)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getClientByLogin(string $login): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM b2b_clients WHERE LOWER(login) = LOWER(:login) AND is_active = 1 LIMIT 1");
        $stmt->execute([':login' => trim($login)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getClientById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM b2b_clients WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getAllClients(string $orderBy = 'created_at DESC, id DESC'): array
    {
        $allowed = [
            'created_at DESC, id DESC' => 'created_at DESC, id DESC',
            'id DESC'                  => 'id DESC',
            'company_name ASC'         => 'company_name ASC',
            'company_name DESC'        => 'company_name DESC',
        ];
        $orderSql = $allowed[$orderBy] ?? 'created_at DESC, id DESC';

        $stmt = $this->pdo->query("SELECT * FROM b2b_clients ORDER BY {$orderSql}");
        return $stmt->fetchAll();
    }

    public function updateClient(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        foreach (['company_name', 'nip', 'phone', 'email', 'delivery_address', 'login', 'price_group_id', 'is_active', 'auth_token'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "{$f} = :{$f}";
                $params[":{$f}"] = $data[$f];
            }
        }

        if (!empty($data['password'])) {
            $fields[] = "password_hash = :password_hash";
            $params[':password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE b2b_clients SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function toggleClientStatus(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE b2b_clients SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function deleteClient(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM b2b_clients WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // =========================================================================
    // REGUŁY OPAKOWAŃ (Inteligentna pamięć klatek/skrzynek)
    // =========================================================================

    public function normalizePattern(string $name): string
    {
        $str = mb_strtolower(trim($name), 'UTF-8');
        // Usuń zbędne znaki interpunkcyjne
        $str = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $str);
        $str = preg_replace('/\s+/', ' ', $str);
        return trim($str);
    }

    public function savePackageRule(string $namePattern, float $packageSize, string $packageUnit = 'op.', string $unit = 'kg'): void
    {
        $norm = $this->normalizePattern($namePattern);
        if ($norm === '') return;

        $stmt = $this->pdo->prepare("
            INSERT INTO b2b_package_rules (name_pattern, package_size, package_unit, unit)
            VALUES (:name_pattern, :package_size, :package_unit, :unit)
            ON CONFLICT(name_pattern) DO UPDATE SET
                package_size = :package_size,
                package_unit = :package_unit,
                unit         = :unit
        ");
        $stmt->execute([
            ':name_pattern' => $norm,
            ':package_size' => $packageSize,
            ':package_unit' => trim($packageUnit),
            ':unit'         => trim($unit),
        ]);
    }

    public function getPackageRule(string $productName): ?array
    {
        $norm = $this->normalizePattern($productName);
        if ($norm === '') return null;

        // Dokładne dopasowanie lub fragment
        $stmt = $this->pdo->query("SELECT * FROM b2b_package_rules");
        $rules = $stmt->fetchAll();

        foreach ($rules as $r) {
            $pat = $r['name_pattern'];
            if ($norm === $pat || mb_stripos($norm, $pat, 0, 'UTF-8') !== false || mb_stripos($pat, $norm, 0, 'UTF-8') !== false) {
                return $r;
            }
        }
        return null;
    }

    // =========================================================================
    // KATALOG PRODUKTÓW
    // =========================================================================

    public function saveProductsBatch(array $products, bool $clearOld = true): int
    {
        $this->pdo->beginTransaction();
        try {
            // Pobierz aktualny asortyment, aby ZACHOWAĆ wcześniej skonfigurowane jednostki i opakowania zbiorcze
            $existingMap = [];
            try {
                $existingProducts = $this->pdo->query("SELECT * FROM b2b_products")->fetchAll();
                foreach ($existingProducts as $ep) {
                    $norm = $this->normalizePattern($ep['name']);
                    if ($norm !== '') {
                        $existingMap[$norm] = $ep;
                    }
                }
            } catch (\Throwable $e) {}

            if ($clearOld) {
                $this->pdo->exec("DELETE FROM b2b_products");
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO b2b_products (name, category, unit, price, package_size, package_unit, is_available, is_new, erp_code, sort_order, updated_at)
                VALUES (:name, :category, :unit, :price, :package_size, :package_unit, :is_available, :is_new, :erp_code, :sort_order, :updated_at)
            ");

            $now = date('Y-m-d H:i:s');
            $count = 0;

            foreach ($products as $idx => $p) {
                $name = trim($p['name'] ?? '');
                if ($name === '') continue;

                $norm = $this->normalizePattern($name);
                $isExisting = isset($existingMap[$norm]);

                if ($isExisting) {
                    // Towar już wcześniej istniał w ofercie — zachowaj jednostkę, opakowanie zbiorcze i kategorię
                    $old = $existingMap[$norm];
                    $unit = !empty($old['unit']) ? $old['unit'] : trim($p['unit'] ?? 'kg');
                    $pkgSize = (float)($old['package_size'] ?? 1.0);
                    $pkgUnit = !empty($old['package_unit']) ? $old['package_unit'] : trim($p['package_unit'] ?? 'op.');
                    $cat = !empty($old['category']) ? $old['category'] : trim($p['category'] ?? $this->detectCategory($name));
                    $price = (float)($p['price'] ?? 0);
                    $isAvail = isset($p['is_available']) ? (int)$p['is_available'] : (int)($old['is_available'] ?? 1);
                    $erpCode = !empty($p['erp_code']) ? trim($p['erp_code']) : ($old['erp_code'] ?? null);
                    $isNew = 0;
                } else {
                    // Nowy artykuł, którego wcześniej nie było w bazie
                    $isNew = 1;
                    $unit = trim($p['unit'] ?? 'kg');
                    $price = (float)($p['price'] ?? 0);
                    $cat = trim($p['category'] ?? $this->detectCategory($name));
                    $pkgSize = (float)($p['package_size'] ?? 1.0);
                    $pkgUnit = trim($p['package_unit'] ?? 'op.');
                    $isAvail = isset($p['is_available']) ? (int)$p['is_available'] : 1;
                    $erpCode = !empty($p['erp_code']) ? trim($p['erp_code']) : null;

                    // Sprawdź czy pasuje do inteligentnych reguł opakowań
                    $rule = $this->getPackageRule($name);
                    if ($rule) {
                        $pkgSize = (float)$rule['package_size'];
                        $pkgUnit = $rule['package_unit'];
                        if (!empty($rule['unit'])) {
                            $unit = $rule['unit'];
                        }
                    } elseif ($pkgSize > 1.0 || $unit !== 'kg') {
                        $this->savePackageRule($name, $pkgSize, $pkgUnit, $unit);
                    }
                }

                $stmt->execute([
                    ':name'         => $name,
                    ':category'     => $cat,
                    ':unit'         => $unit,
                    ':price'        => $price,
                    ':package_size' => $pkgSize,
                    ':package_unit' => $pkgUnit,
                    ':is_available' => $isAvail,
                    ':is_new'       => $isNew,
                    ':erp_code'     => $erpCode,
                    ':sort_order'   => $idx,
                    ':updated_at'   => $now,
                ]);
                $count++;
            }

            $this->pdo->commit();
            return $count;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function detectCategory(string $name): string
    {
        $nameLower = mb_strtolower($name, 'UTF-8');

        $fruits = ['jabłko', 'jablko', 'gruszka', 'śliwka', 'sliwka', 'banan', 'truskawka', 'borówka', 'borowka', 'malina', 'winogrono', 'brzoskwinia', 'morela', 'arbuz', 'melon', 'czereśnia', 'czeresnia', 'wiśnia', 'wisnia'];
        $citrus = ['pomarańcza', 'pomarancza', 'mandarynka', 'cytryna', 'limonka', 'grejpfrut', 'grapefruit', 'pomelo'];
        $herbs  = ['koperek', 'pietruszka nać', 'szczypiorek', 'bazylia', 'mięta', 'mieta', 'sałata', 'salata', 'rukola', 'roszponka', 'szpinak'];

        foreach ($fruits as $f) {
            if (mb_strpos($nameLower, $f) !== false) return 'Owoce';
        }
        foreach ($citrus as $c) {
            if (mb_strpos($nameLower, $c) !== false) return 'Cytrusy';
        }
        foreach ($herbs as $h) {
            if (mb_strpos($nameLower, $h) !== false) return 'Zioła i sałaty';
        }

        return 'Warzywa';
    }

    public function getActiveProducts(?string $category = null): array
    {
        $sql = "SELECT * FROM b2b_products WHERE is_available = 1";
        $params = [];

        if ($category !== null && $category !== '' && $category !== 'Wszystkie') {
            $sql .= " AND category = :cat";
            $params[':cat'] = $category;
        }

        $sql .= " ORDER BY sort_order ASC, name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAllProductsAdmin(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM b2b_products ORDER BY sort_order ASC, name ASC");
        return $stmt->fetchAll();
    }

    public function getProductById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM b2b_products WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateProduct(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        foreach (['name', 'category', 'unit', 'price', 'package_size', 'package_unit', 'is_available', 'is_new', 'erp_code'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "{$f} = :{$f}";
                $params[":{$f}"] = $data[$f];
            }
        }

        if (empty($fields)) {
            return false;
        }

        // Gdy operator edytuje towar, domyślnie zdejmij flagę is_new (chyba że podano explicite)
        if (!array_key_exists('is_new', $data)) {
            $fields[] = "is_new = 0";
        }

        $fields[] = "updated_at = :updated_at";
        $params[':updated_at'] = date('Y-m-d H:i:s');

        $sql = "UPDATE b2b_products SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute($params);

        // Jeśli zaktualizowano opakowanie lub jednostkę, zapisz w regułach
        if ($ok) {
            $prod = $this->pdo->query("SELECT name, unit, package_size, package_unit FROM b2b_products WHERE id = " . (int)$id)->fetch();
            if ($prod) {
                $pkgUnit = $data['package_unit'] ?? $prod['package_unit'];
                $unit    = $data['unit'] ?? $prod['unit'];
                $pkgSize = isset($data['package_size']) ? (float)$data['package_size'] : (float)$prod['package_size'];
                $this->savePackageRule($prod['name'], $pkgSize, $pkgUnit, $unit);
            }
        }

        return $ok;
    }

    public function toggleProductAvailability(int $id, ?int $forceStatus = null): bool
    {
        if ($forceStatus !== null) {
            $stmt = $this->pdo->prepare("UPDATE b2b_products SET is_available = :st, updated_at = :now WHERE id = :id");
            return $stmt->execute([':st' => $forceStatus, ':now' => date('Y-m-d H:i:s'), ':id' => $id]);
        }

        $stmt = $this->pdo->prepare("UPDATE b2b_products SET is_available = CASE WHEN is_available = 1 THEN 0 ELSE 1 END, updated_at = :now WHERE id = :id");
        return $stmt->execute([':now' => date('Y-m-d H:i:s'), ':id' => $id]);
    }

    public function clearAllProducts(): void
    {
        $this->pdo->exec("DELETE FROM b2b_products");
    }

    // =========================================================================
    // ZAMÓWIENIA B2B
    // =========================================================================

    public function generateOrderNumber(): string
    {
        $today = date('Y/m/d');
        $prefix = 'B2B/' . $today . '/';

        $stmt = $this->pdo->prepare("SELECT order_number FROM b2b_orders WHERE order_number LIKE :prefix ORDER BY id DESC LIMIT 1");
        $stmt->execute([':prefix' => $prefix . '%']);
        $lastOrder = $stmt->fetchColumn();

        if ($lastOrder) {
            $parts = explode('/', $lastOrder);
            $seq = (int)end($parts);
            $nextSeq = $seq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad((string)$nextSeq, 2, '0', STR_PAD_LEFT);
    }

    public function createOrder(array $orderData, array $items): int
    {
        $this->pdo->beginTransaction();
        try {
            $orderNumber = !empty($orderData['order_number']) ? $orderData['order_number'] : $this->generateOrderNumber();

            $stmt = $this->pdo->prepare("
                INSERT INTO b2b_orders (
                    order_number, client_id, client_name_snapshot, client_phone_snapshot,
                    delivery_address_snapshot, delivery_date, status, export_filename, total_items, total_amount, notes, created_at
                ) VALUES (
                    :order_number, :client_id, :client_name_snapshot, :client_phone_snapshot,
                    :delivery_address_snapshot, :delivery_date, :status, :export_filename, :total_items, :total_amount, :notes, :created_at
                )
            ");

            $stmt->execute([
                ':order_number'               => $orderNumber,
                ':client_id'                  => (int)($orderData['client_id'] ?? 0),
                ':client_name_snapshot'       => trim($orderData['client_name_snapshot'] ?? ''),
                ':client_phone_snapshot'      => trim($orderData['client_phone_snapshot'] ?? ''),
                ':delivery_address_snapshot'  => trim($orderData['delivery_address_snapshot'] ?? ''),
                ':delivery_date'              => !empty($orderData['delivery_date']) ? trim($orderData['delivery_date']) : null,
                ':status'                     => trim($orderData['status'] ?? 'new'),
                ':export_filename'            => trim($orderData['export_filename'] ?? ''),
                ':total_items'                => count($items),
                ':total_amount'               => (float)($orderData['total_amount'] ?? 0.0),
                ':notes'                      => trim($orderData['notes'] ?? ''),
                ':created_at'                 => date('Y-m-d H:i:s'),
            ]);

            $orderId = (int)$this->pdo->lastInsertId();

            $stmtItem = $this->pdo->prepare("
                INSERT INTO b2b_order_items (
                    order_id, product_id, product_name, price, quantity, unit,
                    package_size, package_unit, package_summary, item_total
                ) VALUES (
                    :order_id, :product_id, :product_name, :price, :quantity, :unit,
                    :package_size, :package_unit, :package_summary, :item_total
                )
            ");

            foreach ($items as $item) {
                $qty = (float)($item['quantity'] ?? 0);
                if ($qty <= 0) continue;

                $price = (float)($item['price'] ?? 0);
                $pkgSize = (float)($item['package_size'] ?? 1.0);
                $pkgUnit = trim($item['package_unit'] ?? 'op.');
                $unit    = trim($item['unit'] ?? 'kg');

                // Wyliczenie rozbicia na opakowania z poprawną odmianą gramatyczną
                if (!empty($item['package_summary'])) {
                    $pkgSummary = self::inflectSummaryString((string)$item['package_summary']);
                } else {
                    $pkgSummary = $this->formatPackageSummary($qty, $pkgSize, $pkgUnit, $unit);
                }

                $stmtItem->execute([
                    ':order_id'        => $orderId,
                    ':product_id'      => isset($item['product_id']) ? (int)$item['product_id'] : null,
                    ':product_name'    => trim($item['product_name'] ?? $item['name'] ?? ''),
                    ':price'           => $price,
                    ':quantity'        => $qty,
                    ':unit'            => $unit,
                    ':package_size'    => $pkgSize,
                    ':package_unit'    => $pkgUnit,
                    ':package_summary' => $pkgSummary,
                    ':item_total'      => (float)($item['item_total'] ?? ($qty * $price)),
                ]);
            }

            $this->pdo->commit();
            return $orderId;

        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Odmiana rzeczowników i jednostek przez przypadki w języku polskim.
     * Zwraca poprawną gramatycznie formę dla liczby (np. 1 klatka, 2 klatki, 5 klatek, 2 worki, 5 worków).
     */
    public static function inflectPolish(float|int $number, string $unit): string
    {
        $unitTrim = trim($unit);
        $unitLower = mb_strtolower($unitTrim, 'UTF-8');

        $formsMap = [
            'klatka'      => ['klatka', 'klatki', 'klatek'],
            'klatki'      => ['klatka', 'klatki', 'klatek'],
            'klatek'      => ['klatka', 'klatki', 'klatek'],

            'worek'       => ['worek', 'worki', 'worków'],
            'worki'       => ['worek', 'worki', 'worków'],
            'worków'      => ['worek', 'worki', 'worków'],

            'skrzynka'    => ['skrzynka', 'skrzynki', 'skrzynek'],
            'skrzynki'    => ['skrzynka', 'skrzynki', 'skrzynek'],
            'skrzynek'    => ['skrzynka', 'skrzynki', 'skrzynek'],

            'karton'      => ['karton', 'kartony', 'kartonów'],
            'kartony'     => ['karton', 'kartony', 'kartonów'],
            'kartonów'    => ['karton', 'kartony', 'kartonów'],

            'pudełko'     => ['pudełko', 'pudełka', 'pudełek'],
            'pudełka'     => ['pudełko', 'pudełka', 'pudełek'],
            'pudełek'     => ['pudełko', 'pudełka', 'pudełek'],

            'pęczek'      => ['pęczek', 'pęczki', 'pęczków'],
            'pęczki'      => ['pęczek', 'pęczki', 'pęczków'],
            'pęczków'     => ['pęczek', 'pęczki', 'pęczków'],

            'zgrzewka'    => ['zgrzewka', 'zgrzewki', 'zgrzewek'],
            'zgrzewki'    => ['zgrzewka', 'zgrzewki', 'zgrzewek'],
            'zgrzewek'    => ['zgrzewka', 'zgrzewki', 'zgrzewek'],

            'paleta'      => ['paleta', 'palety', 'palet'],
            'palety'      => ['paleta', 'palety', 'palet'],
            'palet'       => ['paleta', 'palety', 'palet'],

            'paczka'      => ['paczka', 'paczki', 'paczek'],
            'paczki'      => ['paczka', 'paczki', 'paczek'],
            'paczek'      => ['paczka', 'paczki', 'paczek'],

            'wytłaczanka' => ['wytłaczanka', 'wytłaczanki', 'wytłaczanek'],
            'wytłaczanki' => ['wytłaczanka', 'wytłaczanki', 'wytłaczanek'],
            'wytłaczanek' => ['wytłaczanka', 'wytłaczanki', 'wytłaczanek'],

            'koszyk'      => ['koszyk', 'koszyki', 'koszyków'],
            'koszyki'     => ['koszyk', 'koszyki', 'koszyków'],
            'koszyków'    => ['koszyk', 'koszyki', 'koszyków'],

            'wiązka'      => ['wiązka', 'wiązki', 'wiązek'],
            'wiązki'      => ['wiązka', 'wiązki', 'wiązek'],
            'wiązek'      => ['wiązka', 'wiązki', 'wiązek'],

            'opakowanie'  => ['opakowanie', 'opakowania', 'opakowań'],
            'opakowania'  => ['opakowanie', 'opakowania', 'opakowań'],
            'opakowań'    => ['opakowanie', 'opakowania', 'opakowań'],

            'sztuka'      => ['sztuka', 'sztuki', 'sztuk'],
            'sztuki'      => ['sztuka', 'sztuki', 'sztuk'],
            'sztuk'       => ['sztuka', 'sztuki', 'sztuk'],

            'szt'         => ['szt.', 'szt.', 'szt.'],
            'szt.'        => ['szt.', 'szt.', 'szt.'],
            'op'          => ['op.', 'op.', 'op.'],
            'op.'         => ['op.', 'op.', 'op.'],
            'kg'          => ['kg', 'kg', 'kg'],
            'g'           => ['g', 'g', 'g'],
            'l'           => ['l', 'l', 'l'],
            'litr'        => ['litr', 'litry', 'litrów'],
            'litry'       => ['litr', 'litry', 'litrów'],
            'litrów'      => ['litr', 'litry', 'litrów'],
        ];

        $forms = $formsMap[$unitLower] ?? null;
        if (!$forms) {
            if (str_ends_with($unitLower, '.') || mb_strlen($unitLower, 'UTF-8') <= 3) {
                return $unitTrim;
            }
            if (preg_match('/^(.*)ka$/u', $unitLower, $m)) {
                $forms = [$unitLower, $m[1] . 'ki', $m[1] . 'ek'];
            } else {
                return $unitTrim;
            }
        }

        $isInteger = (floor($number) == $number);
        if ($isInteger) {
            $abs = abs((int)$number);
            if ($abs === 1) {
                return $forms[0];
            }
            $mod10 = $abs % 10;
            $mod100 = $abs % 100;
            if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
                return $forms[1];
            }
            return $forms[2];
        }

        return $forms[1];
    }

    /**
     * Koryguje ciąg podsumowania opakowań (np. "2 klatka" -> "2 klatki", "2 worek + 5 kg" -> "2 worki + 5 kg")
     */
    public static function inflectSummaryString(string $summary): string
    {
        return preg_replace_callback('/(\d+(?:\.\d+)?)\s+([a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ.]+)/u', function($m) {
            $val = (float)$m[1];
            $unit = $m[2];
            $inflected = self::inflectPolish($val, $unit);
            return $m[1] . ' ' . $inflected;
        }, $summary);
    }

    public function formatPackageSummary(float $quantity, float $packageSize, string $packageUnit, string $unit): string
    {
        if ($packageSize <= 1.0) {
            $unitInflected = self::inflectPolish($quantity, $unit);
            return "{$quantity} {$unitInflected}";
        }

        $fullBoxes = (int)floor($quantity / $packageSize);
        $remainder = round(fmod($quantity, $packageSize), 2);

        $parts = [];
        if ($fullBoxes > 0) {
            $boxInflected = self::inflectPolish($fullBoxes, $packageUnit);
            $parts[] = "{$fullBoxes} {$boxInflected}";
        }
        if ($remainder > 0.001) {
            $unitInflected = self::inflectPolish($remainder, $unit);
            $parts[] = "{$remainder} {$unitInflected}";
        }

        return !empty($parts) ? implode(' + ', $parts) : "{$quantity} {$unit}";
    }

    public function getOrderById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM b2b_orders WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getOrderItems(int $orderId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM b2b_order_items WHERE order_id = :oid ORDER BY id ASC");
        $stmt->execute([':oid' => $orderId]);
        return $stmt->fetchAll();
    }

    public function getAllOrders(int $limit = 100, ?string $status = null): array
    {
        $sql = "SELECT * FROM b2b_orders";
        $params = [];

        if ($status !== null && $status !== '' && $status !== 'all') {
            $sql .= " WHERE status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY id DESC LIMIT " . (int)$limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getClientOrders(int $clientId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM b2b_orders WHERE client_id = :cid ORDER BY id DESC LIMIT " . (int)$limit);
        $stmt->execute([':cid' => $clientId]);
        return $stmt->fetchAll();
    }

    public function updateOrderStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare("UPDATE b2b_orders SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => trim($status), ':id' => $id]);
    }

    public function updateOrderExportFile(int $id, string $filename): bool
    {
        $stmt = $this->pdo->prepare("UPDATE b2b_orders SET export_filename = :fn WHERE id = :id");
        return $stmt->execute([':fn' => trim($filename), ':id' => $id]);
    }

    public function getOrderForErpExport(int $orderId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT o.*, c.nip as client_nip, c.company_name as client_company, c.phone as client_phone, c.delivery_address as client_address
            FROM b2b_orders o
            LEFT JOIN b2b_clients c ON o.client_id = c.id
            WHERE o.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $orderId]);
        $order = $stmt->fetch();
        if (!$order) return null;

        $stmtItems = $this->pdo->prepare("
            SELECT oi.*, p.erp_code
            FROM b2b_order_items oi
            LEFT JOIN b2b_products p ON oi.product_id = p.id
            WHERE oi.order_id = :oid
            ORDER BY oi.id ASC
        ");
        $stmtItems->execute([':oid' => $orderId]);
        $order['items'] = $stmtItems->fetchAll();

        return $order;
    }

    public function getOrdersBatchForErpExport(?string $deliveryDate = null, ?string $status = null, int $limit = 200): array
    {
        $sql = "
            SELECT o.*, c.nip as client_nip, c.company_name as client_company, c.phone as client_phone, c.delivery_address as client_address
            FROM b2b_orders o
            LEFT JOIN b2b_clients c ON o.client_id = c.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($deliveryDate)) {
            $sql .= " AND o.delivery_date = :deliv_date";
            $params[':deliv_date'] = $deliveryDate;
        }

        if (!empty($status) && $status !== 'all') {
            $sql .= " AND o.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY o.id ASC LIMIT " . (int)$limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        foreach ($orders as &$ord) {
            $stmtItems = $this->pdo->prepare("
                SELECT oi.*, p.erp_code
                FROM b2b_order_items oi
                LEFT JOIN b2b_products p ON oi.product_id = p.id
                WHERE oi.order_id = :oid
                ORDER BY oi.id ASC
            ");
            $stmtItems->execute([':oid' => (int)$ord['id']]);
            $ord['items'] = $stmtItems->fetchAll();
        }

        return $orders;
    }
}
