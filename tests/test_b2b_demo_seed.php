<?php

$sourceRoot = dirname(__DIR__);
$fixtureRoot = null;
$fixtureCreated = false;
$pdo = null;
$failure = null;

$expectedProducts = [
    'Jabłka Gala',
    'Jabłka Ligol',
    'Gruszki Konferencja',
    'Śliwki Węgierki',
    'Winogrona jasne bezpestkowe',
    'Truskawki',
    'Borówki amerykańskie',
    'Maliny',
    'Banany',
    'Pomarańcze Valencia',
    'Mandarynki',
    'Cytryny',
    'Awokado Hass',
    'Pomidory malinowe',
    'Pomidory koktajlowe',
    'Ogórki gruntowe',
    'Papryka czerwona',
    'Cukinia',
    'Brokuły',
    'Kalafior',
    'Marchew',
    'Ziemniaki młode',
    'Cebula czerwona',
    'Sałata masłowa',
    'Bazylia świeża',
];

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$createDirectory = static function (string $path): void {
    if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
        throw new RuntimeException('could not create fixture directory');
    }
};

$startSeeder = static function (string $script, string $database): array {
    $pipes = [];
    $process = proc_open(
        [PHP_BINARY, $script, '--db=' . $database],
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes
    );

    if (!is_resource($process)) {
        throw new RuntimeException('could not start the seeder process');
    }
    fclose($pipes[0]);

    return ['process' => $process, 'pipes' => $pipes];
};

$finishSeeder = static function (array $running): array {
    $stdout = stream_get_contents($running['pipes'][1]);
    $stderr = stream_get_contents($running['pipes'][2]);
    fclose($running['pipes'][1]);
    fclose($running['pipes'][2]);

    return [
        'status' => proc_close($running['process']),
        'stdout' => $stdout,
        'stderr' => $stderr,
    ];
};

$safeOutput = static function (array $result): string {
    $output = trim($result['stderr'] . "\n" . $result['stdout']);
    return preg_replace('/^(Credential demo-sklep-[1-3]: ).+$/m', '$1[redacted]', $output);
};

$runSeeder = static function (string $script, string $database) use ($startSeeder, $finishSeeder): array {
    return $finishSeeder($startSeeder($script, $database));
};

$removeTree = static function (string $path) use (&$removeTree): void {
    if (!is_dir($path)) {
        if (file_exists($path)) {
            unlink($path);
        }
        return;
    }

    foreach (new DirectoryIterator($path) as $entry) {
        if ($entry->isDot()) {
            continue;
        }
        $child = $entry->getPathname();
        if ($entry->isDir() && !$entry->isLink()) {
            $removeTree($child);
        } else {
            if (!@unlink($child)) {
                throw new RuntimeException('could not remove fixture file: ' . $child);
            }
        }
    }
    if (!@rmdir($path)) {
        throw new RuntimeException('could not remove fixture directory: ' . $path);
    }
};

try {
    $temporaryDirectory = sys_get_temp_dir();
    for ($attempt = 0; $attempt < 10; $attempt++) {
        $candidate = $temporaryDirectory . DIRECTORY_SEPARATOR
            . 'b2b-demo-fixture-' . bin2hex(random_bytes(16));
        if (@mkdir($candidate, 0700)) {
            $fixtureRoot = $candidate;
            $fixtureCreated = true;
            break;
        }
        if (file_exists($candidate) || is_link($candidate)) {
            continue;
        }
        throw new RuntimeException('could not create private fixture root');
    }
    if (!$fixtureCreated) {
        throw new RuntimeException('could not allocate a unique fixture root');
    }

    $programPath = $fixtureRoot . DIRECTORY_SEPARATOR . 'program';
    $configPath = $programPath . DIRECTORY_SEPARATOR . 'config';
    $modelPath = $programPath . DIRECTORY_SEPARATOR . 'model';
    $corePath = $programPath . DIRECTORY_SEPARATOR . 'core';
    $testsPath = $fixtureRoot . DIRECTORY_SEPARATOR . 'tests';
    $databaseDirectory = $fixtureRoot . DIRECTORY_SEPARATOR . 'db';
    foreach ([$configPath, $modelPath, $corePath, $testsPath, $databaseDirectory] as $directory) {
        $createDirectory($directory);
    }
    $assert(
        DIRECTORY_SEPARATOR === '\\' || (fileperms($fixtureRoot) & 0777) === 0700,
        'fixture root is not a privately created directory'
    );

    $fixtureFiles = [
        $sourceRoot . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'seed_b2b_demo.php'
            => $testsPath . DIRECTORY_SEPARATOR . 'seed_b2b_demo.php',
        $sourceRoot . DIRECTORY_SEPARATOR . 'program' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'autoload.php'
            => $configPath . DIRECTORY_SEPARATOR . 'autoload.php',
        $sourceRoot . DIRECTORY_SEPARATOR . 'program' . DIRECTORY_SEPARATOR . 'model' . DIRECTORY_SEPARATOR . 'B2bRepository.php'
            => $modelPath . DIRECTORY_SEPARATOR . 'B2bRepository.php',
        $sourceRoot . DIRECTORY_SEPARATOR . 'program' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'Model.php'
            => $corePath . DIRECTORY_SEPARATOR . 'Model.php',
    ];
    foreach ($fixtureFiles as $source => $destination) {
        if (!copy($source, $destination)) {
            throw new RuntimeException('could not copy required fixture file');
        }
    }

    $configContents = "<?php\n"
        . "define('APP_ENV', 'development');\n"
        . "define('APP_NAMESPACE', 'App\\\\');\n";
    if (file_put_contents($configPath . DIRECTORY_SEPARATOR . 'data.php', $configContents) === false) {
        throw new RuntimeException('could not create fixture config');
    }

    $seederPath = $testsPath . DIRECTORY_SEPARATOR . 'seed_b2b_demo.php';
    $sortedExpectedProducts = $expectedProducts;
    sort($sortedExpectedProducts, SORT_STRING);

    $idempotencyDatabase = $databaseDirectory . DIRECTORY_SEPARATOR . 'idempotency.sqlite';
    $firstRun = $runSeeder($seederPath, $idempotencyDatabase);
    $assert($firstRun['status'] === 0, 'first seeder run failed: ' . $safeOutput($firstRun));
    $assert(
        strpos($firstRun['stdout'], 'Products: added 25, skipped 0; clients: added 3, skipped 0') !== false,
        'first run summary did not report all sample records as added'
    );

    $credentials = [];
    preg_match_all(
        '/^Credential (demo-sklep-[1-3]): ([a-f0-9]{32})\r?$/m',
        $firstRun['stdout'],
        $credentialMatches,
        PREG_SET_ORDER
    );
    foreach ($credentialMatches as $match) {
        $credentials[$match[1]] = $match[2];
    }
    $assert(count($credentials) === 3, 'first run did not print exactly three new credentials');

    $pdo = new PDO('sqlite:' . $idempotencyDatabase, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $productRows = $pdo->query(
        'SELECT name, category, unit, price, package_size, package_unit, is_available FROM b2b_products'
    )->fetchAll();
    $productNames = array_column($productRows, 'name');
    sort($productNames, SORT_STRING);
    $assert($productNames === $sortedExpectedProducts, 'database does not contain exactly the 25 expected products');
    foreach ($productRows as $product) {
        $assert($product['category'] !== '', 'product category is empty for ' . $product['name']);
        $assert($product['unit'] !== '', 'product unit is empty for ' . $product['name']);
        $assert((float) $product['price'] > 0, 'product price is not positive for ' . $product['name']);
        $assert((float) $product['package_size'] > 0, 'product package size is not positive for ' . $product['name']);
        $assert($product['package_unit'] !== '', 'product package unit is empty for ' . $product['name']);
        $assert((int) $product['is_available'] === 1, 'product is not available: ' . $product['name']);
    }

    $clientRows = $pdo->query(
        "SELECT login, password_hash, is_active FROM b2b_clients WHERE login LIKE 'demo-sklep-%' ORDER BY login"
    )->fetchAll();
    $assert(count($clientRows) === 3, 'database does not contain exactly three demo logins');
    $initialHashes = [];
    foreach ($clientRows as $client) {
        $login = $client['login'];
        $assert(isset($credentials[$login]), 'missing first-run password for ' . $login);
        $assert($client['password_hash'] !== '', 'missing password hash for ' . $login);
        $assert($client['password_hash'] !== $credentials[$login], 'plaintext password was stored for ' . $login);
        $assert((int) $client['is_active'] === 1, 'demo client is not active: ' . $login);
        $assert(password_verify($credentials[$login], $client['password_hash']), 'password verification failed for ' . $login);
        $initialHashes[$login] = $client['password_hash'];
    }

    $secondRun = $runSeeder($seederPath, $idempotencyDatabase);
    $assert($secondRun['status'] === 0, 'second seeder run failed: ' . $safeOutput($secondRun));
    $assert(
        strpos($secondRun['stdout'], 'Products: added 0, skipped 25; clients: added 0, skipped 3') !== false,
        'second run summary did not report all sample records as skipped'
    );
    $assert(!preg_match('/^Credential /m', $secondRun['stdout']), 'second run printed existing credentials');
    $finalProductNames = $pdo->query('SELECT name FROM b2b_products')->fetchAll(PDO::FETCH_COLUMN);
    sort($finalProductNames, SORT_STRING);
    $assert($finalProductNames === $sortedExpectedProducts, 'second run changed the product records');
    $finalClientRows = $pdo->query(
        "SELECT login, password_hash FROM b2b_clients WHERE login LIKE 'demo-sklep-%' ORDER BY login"
    )->fetchAll();
    $assert(count($finalClientRows) === 3, 'second run changed the demo client record count');
    foreach ($finalClientRows as $client) {
        $assert(
            $client['password_hash'] === $initialHashes[$client['login']],
            'second run changed the password hash for ' . $client['login']
        );
    }
    $pdo = null;

    $concurrentDatabase = $databaseDirectory . DIRECTORY_SEPARATOR . 'concurrent.sqlite';
    $firstProcess = $startSeeder($seederPath, $concurrentDatabase);
    $secondProcess = $startSeeder($seederPath, $concurrentDatabase);
    $concurrentResults = [$finishSeeder($firstProcess), $finishSeeder($secondProcess)];
    foreach ($concurrentResults as $index => $result) {
        $assert(
            $result['status'] === 0,
            'concurrent seeder process ' . ($index + 1) . ' failed: ' . $safeOutput($result)
        );
    }

    $pdo = new PDO('sqlite:' . $concurrentDatabase, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $concurrentNames = $pdo->query('SELECT name FROM b2b_products')->fetchAll(PDO::FETCH_COLUMN);
    sort($concurrentNames, SORT_STRING);
    $assert($concurrentNames === $sortedExpectedProducts, 'concurrent seeding produced missing or duplicate products');
    $concurrentClients = $pdo->query(
        "SELECT login FROM b2b_clients WHERE login IN ('demo-sklep-1', 'demo-sklep-2', 'demo-sklep-3') ORDER BY login"
    )->fetchAll(PDO::FETCH_COLUMN);
    $assert($concurrentClients === ['demo-sklep-1', 'demo-sklep-2', 'demo-sklep-3'], 'concurrent seeding produced incorrect demo clients');
    $totalConcurrentCredentials = 0;
    foreach ($concurrentResults as $result) {
        $totalConcurrentCredentials += preg_match_all('/^Credential demo-sklep-[1-3]: /m', $result['stdout']);
    }
    $assert($totalConcurrentCredentials === 3, 'concurrent seeding printed duplicate or missing credentials');
    $pdo = null;

    $preservationDatabase = $databaseDirectory . DIRECTORY_SEPARATOR . 'preservation.sqlite';
    $pdo = new PDO('sqlite:' . $preservationDatabase, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec(
        "CREATE TABLE b2b_products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
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
        )"
    );
    $preservedFields = [
        'name' => 'Jabłka—Gala!!',
        'category' => 'Fixture Preserve',
        'unit' => 'skrzynka',
        'price' => 123.45,
        'package_size' => 77.0,
        'package_unit' => 'opakowanie-testowe',
        'is_available' => 0,
        'is_new' => 0,
        'sort_order' => 99,
        'updated_at' => '2020-01-02 03:04:05',
    ];
    $insert = $pdo->prepare(
        'INSERT INTO b2b_products '
        . '(name, category, unit, price, package_size, package_unit, is_available, is_new, sort_order, updated_at) '
        . 'VALUES (:name, :category, :unit, :price, :package_size, :package_unit, :is_available, :is_new, :sort_order, :updated_at)'
    );
    $insert->execute($preservedFields);
    $insert = null;
    $pdo = null;

    $preservationRun = $runSeeder($seederPath, $preservationDatabase);
    $assert($preservationRun['status'] === 0, 'normalized-name fixture run failed: ' . $safeOutput($preservationRun));
    $assert(
        strpos($preservationRun['stdout'], 'Products: added 24, skipped 1; clients: added 3, skipped 0') !== false,
        'normalized-name fixture summary did not report the existing product as skipped'
    );

    $pdo = new PDO('sqlite:' . $preservationDatabase, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $preservedRow = $pdo->query("SELECT * FROM b2b_products WHERE name = 'Jabłka—Gala!!'")->fetch();
    $assert(is_array($preservedRow), 'normalized existing product was not preserved');
    foreach ($preservedFields as $field => $value) {
        $assert((string) $preservedRow[$field] === (string) $value, 'existing product field changed: ' . $field);
    }
    $preservedProductNames = $pdo->query('SELECT name FROM b2b_products')->fetchAll(PDO::FETCH_COLUMN);
    $expectedPreservationNames = $expectedProducts;
    $expectedPreservationNames[0] = $preservedFields['name'];
    sort($expectedPreservationNames, SORT_STRING);
    sort($preservedProductNames, SORT_STRING);
    $assert(
        $preservedProductNames === $expectedPreservationNames,
        'normalized-name fixture did not insert every other sample product exactly once'
    );
    $assert(
        count(array_filter($preservedProductNames, static function (string $name): bool {
            return mb_strtolower(trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $name))), 'UTF-8')
                === 'jabłka gala';
        })) === 1,
        'normalized-name fixture contains duplicate normalized sample products'
    );
    $pdo = null;
} catch (Throwable $exception) {
    $failure = $exception->getMessage();
} finally {
    $pdo = null;
    if ($fixtureCreated) {
        try {
            $removeTree($fixtureRoot);
        } catch (Throwable $cleanupException) {
            $failure = ($failure === null ? '' : $failure . '; ')
                . 'fixture cleanup failed: ' . $cleanupException->getMessage();
        }
        if (file_exists($fixtureRoot)) {
            $failure = ($failure === null ? '' : $failure . '; ') . 'fixture scratch files remain';
        }
    }
}

if ($failure !== null) {
    fwrite(STDERR, "FAIL: " . $failure . "\n");
    exit(1);
}

echo "PASS: fixture-isolated B2B seeding is idempotent, concurrency-safe, and preserves normalized existing products.\n";
