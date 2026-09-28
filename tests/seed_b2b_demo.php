<?php

if (PHP_SAPI !== 'cli') {
    $message = "Error: this seeder can only run from the command line.\n";
    if (defined('STDERR')) {
        fwrite(STDERR, $message);
    } else {
        error_log(rtrim($message));
    }
    exit(1);
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

$lockHandle = null;
$exitCode = 0;

try {
    $databasePath = BASE_PATH . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'b2b.sqlite';
    $databaseOptionSeen = false;

    // Optional --db=<path> target is intended for isolated integration tests.
    foreach (array_slice($argv, 1) as $argument) {
        if (strncmp($argument, '--db=', 5) === 0) {
            if ($databaseOptionSeen) {
                throw new InvalidArgumentException('option --db may only be specified once');
            }
            $databaseOptionSeen = true;
            $databasePath = substr($argument, 5);
            if ($databasePath === '' || strpos($databasePath, "\0") !== false) {
                throw new InvalidArgumentException('option --db requires a valid path');
            }
        } else {
            throw new InvalidArgumentException('unknown or malformed option: ' . $argument);
        }
    }

    $configPath = BASE_PATH . DIRECTORY_SEPARATOR . 'program' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'data.php';
    if (!is_file($configPath)) {
        throw new RuntimeException('local program/config/data.php is required');
    }
    require_once $configPath;

    if (!defined('APP_ENV') || APP_ENV !== 'development') {
        throw new RuntimeException('APP_ENV must be development');
    }

    $driver = defined('B2B_DB_DRIVER') ? strtolower((string) B2B_DB_DRIVER) : 'sqlite';
    if ($driver !== 'sqlite') {
        throw new RuntimeException('B2B_DB_DRIVER must be sqlite');
    }

    require_once BASE_PATH . DIRECTORY_SEPARATOR . 'program' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'autoload.php';

    $databaseDirectory = dirname($databasePath);
    if (!is_dir($databaseDirectory)) {
        throw new RuntimeException('database directory does not exist: ' . $databaseDirectory);
    }

    $canonicalDatabasePath = realpath($databasePath);
    if ($canonicalDatabasePath === false) {
        $canonicalDatabaseDirectory = realpath($databaseDirectory);
        if ($canonicalDatabaseDirectory === false) {
            throw new RuntimeException('could not resolve database directory: ' . $databaseDirectory);
        }
        $canonicalDatabasePath = $canonicalDatabaseDirectory . DIRECTORY_SEPARATOR . basename($databasePath);
    }

    $lockPath = $canonicalDatabasePath . '.seed.lock';
    $lockHandle = fopen($lockPath, 'c');
    if ($lockHandle === false) {
        throw new RuntimeException('could not open database seed lock: ' . $lockPath);
    }
    if (!flock($lockHandle, LOCK_EX)) {
        throw new RuntimeException('could not acquire database seed lock: ' . $lockPath);
    }

    $pdo = new PDO('sqlite:' . $databasePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');

    $repository = new App\B2bRepository($pdo);
    $repository->initDatabase();

    $products = [
        ['name' => 'Jabłka Gala', 'category' => 'Owoce', 'unit' => 'kg', 'price' => 4.80, 'package_size' => 12, 'package_unit' => 'skrz.'],
        ['name' => 'Jabłka Ligol', 'category' => 'Owoce', 'unit' => 'kg', 'price' => 4.40, 'package_size' => 12, 'package_unit' => 'skrz.'],
        ['name' => 'Gruszki Konferencja', 'category' => 'Owoce', 'unit' => 'kg', 'price' => 7.90, 'package_size' => 8, 'package_unit' => 'skrz.'],
        ['name' => 'Śliwki Węgierki', 'category' => 'Owoce', 'unit' => 'kg', 'price' => 8.50, 'package_size' => 6, 'package_unit' => 'skrz.'],
        ['name' => 'Winogrona jasne bezpestkowe', 'category' => 'Owoce', 'unit' => 'kg', 'price' => 13.90, 'package_size' => 5, 'package_unit' => 'karton'],
        ['name' => 'Truskawki', 'category' => 'Owoce', 'unit' => 'kg', 'price' => 18.00, 'package_size' => 2, 'package_unit' => 'łubianka'],
        ['name' => 'Borówki amerykańskie', 'category' => 'Owoce', 'unit' => 'kg', 'price' => 24.00, 'package_size' => 2, 'package_unit' => 'karton'],
        ['name' => 'Maliny', 'category' => 'Owoce', 'unit' => 'kg', 'price' => 22.00, 'package_size' => 2, 'package_unit' => 'łubianka'],
        ['name' => 'Banany', 'category' => 'Owoce', 'unit' => 'kg', 'price' => 6.90, 'package_size' => 18, 'package_unit' => 'karton'],
        ['name' => 'Pomarańcze Valencia', 'category' => 'Cytrusy', 'unit' => 'kg', 'price' => 8.20, 'package_size' => 10, 'package_unit' => 'skrz.'],
        ['name' => 'Mandarynki', 'category' => 'Cytrusy', 'unit' => 'kg', 'price' => 9.50, 'package_size' => 10, 'package_unit' => 'skrz.'],
        ['name' => 'Cytryny', 'category' => 'Cytrusy', 'unit' => 'kg', 'price' => 7.80, 'package_size' => 10, 'package_unit' => 'skrz.'],
        ['name' => 'Awokado Hass', 'category' => 'Owoce', 'unit' => 'kg', 'price' => 19.90, 'package_size' => 4, 'package_unit' => 'karton'],
        ['name' => 'Pomidory malinowe', 'category' => 'Warzywa', 'unit' => 'kg', 'price' => 12.50, 'package_size' => 6, 'package_unit' => 'skrz.'],
        ['name' => 'Pomidory koktajlowe', 'category' => 'Warzywa', 'unit' => 'kg', 'price' => 16.00, 'package_size' => 3, 'package_unit' => 'karton'],
        ['name' => 'Ogórki gruntowe', 'category' => 'Warzywa', 'unit' => 'kg', 'price' => 6.50, 'package_size' => 5, 'package_unit' => 'skrz.'],
        ['name' => 'Papryka czerwona', 'category' => 'Warzywa', 'unit' => 'kg', 'price' => 13.00, 'package_size' => 5, 'package_unit' => 'skrz.'],
        ['name' => 'Cukinia', 'category' => 'Warzywa', 'unit' => 'kg', 'price' => 7.20, 'package_size' => 6, 'package_unit' => 'skrz.'],
        ['name' => 'Brokuły', 'category' => 'Warzywa', 'unit' => 'szt.', 'price' => 4.20, 'package_size' => 8, 'package_unit' => 'karton'],
        ['name' => 'Kalafior', 'category' => 'Warzywa', 'unit' => 'szt.', 'price' => 5.50, 'package_size' => 8, 'package_unit' => 'karton'],
        ['name' => 'Marchew', 'category' => 'Warzywa', 'unit' => 'kg', 'price' => 3.20, 'package_size' => 10, 'package_unit' => 'worek'],
        ['name' => 'Ziemniaki młode', 'category' => 'Warzywa', 'unit' => 'kg', 'price' => 2.90, 'package_size' => 15, 'package_unit' => 'worek'],
        ['name' => 'Cebula czerwona', 'category' => 'Warzywa', 'unit' => 'kg', 'price' => 5.80, 'package_size' => 5, 'package_unit' => 'worek'],
        ['name' => 'Sałata masłowa', 'category' => 'Zioła i sałaty', 'unit' => 'szt.', 'price' => 3.20, 'package_size' => 10, 'package_unit' => 'karton'],
        ['name' => 'Bazylia świeża', 'category' => 'Zioła i sałaty', 'unit' => 'szt.', 'price' => 4.50, 'package_size' => 12, 'package_unit' => 'doniczka'],
    ];

    foreach ($products as &$product) {
        $product['is_available'] = 1;
    }
    unset($product);

    $existingProducts = [];
    foreach ($repository->getAllProductsAdmin() as $existingProduct) {
        $normalizedName = $repository->normalizePattern((string) $existingProduct['name']);
        if ($normalizedName !== '') {
            $existingProducts[$normalizedName] = true;
        }
    }

    $missingProducts = [];
    $skippedProducts = 0;
    foreach ($products as $product) {
        $normalizedName = $repository->normalizePattern($product['name']);
        if (isset($existingProducts[$normalizedName])) {
            $skippedProducts++;
            continue;
        }

        $existingProducts[$normalizedName] = true;
        $missingProducts[] = $product;
    }

    $addedProducts = $missingProducts === [] ? 0 : $repository->saveProductsBatch($missingProducts, false);
    $skippedProducts += count($missingProducts) - $addedProducts;

    $clients = [
        [
            'login' => 'demo-sklep-1',
            'company_name' => 'Sklep Owocowy Pod Jabłonią',
            'phone' => '+48 500 100 101',
            'email' => 'kontakt@podjablonia.example.test',
            'delivery_address' => 'ul. Słoneczna 12, 00-101 Warszawa',
        ],
        [
            'login' => 'demo-sklep-2',
            'company_name' => 'Warzywniak Zielony Koszyk',
            'phone' => '+48 500 100 102',
            'email' => 'zamowienia@zielonykoszyk.example.test',
            'delivery_address' => 'ul. Ogrodowa 8, 30-102 Kraków',
        ],
        [
            'login' => 'demo-sklep-3',
            'company_name' => 'Delikatesy Cztery Pory Roku',
            'phone' => '+48 500 100 103',
            'email' => 'biuro@czteryporyroku.example.test',
            'delivery_address' => 'ul. Rzeczna 21, 60-103 Poznań',
        ],
    ];

    $existingLogins = [];
    foreach ($repository->getAllClients() as $existingClient) {
        $login = strtolower(trim((string) ($existingClient['login'] ?? '')));
        if ($login !== '') {
            $existingLogins[$login] = true;
        }
    }

    $addedClients = 0;
    $skippedClients = 0;
    foreach ($clients as $client) {
        if (isset($existingLogins[$client['login']])) {
            $skippedClients++;
            continue;
        }

        $password = bin2hex(random_bytes(16));
        $repository->createClient($client + [
            'password' => $password,
            'is_active' => 1,
        ]);
        $existingLogins[$client['login']] = true;
        $addedClients++;
        fwrite(STDOUT, 'Credential ' . $client['login'] . ': ' . $password . PHP_EOL);
    }

    fwrite(
        STDOUT,
        'Products: added ' . $addedProducts . ', skipped ' . $skippedProducts
        . '; clients: added ' . $addedClients . ', skipped ' . $skippedClients . PHP_EOL
    );
} catch (Throwable $exception) {
    fwrite(STDERR, 'Error: ' . $exception->getMessage() . PHP_EOL);
    $exitCode = 1;
} finally {
    if (is_resource($lockHandle)) {
        if (!flock($lockHandle, LOCK_UN)) {
            fwrite(STDERR, "Error: could not release database seed lock.\n");
            $exitCode = 1;
        }
        if (!fclose($lockHandle)) {
            fwrite(STDERR, "Error: could not close database seed lock.\n");
            $exitCode = 1;
        }
    }
}
exit($exitCode);
