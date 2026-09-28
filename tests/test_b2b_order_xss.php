<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/program/model/B2bRepository.php';

use App\B2bRepository;

function assertSecurity(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$pdo = new PDO('sqlite::memory:');
$repo = new B2bRepository($pdo);
$repo->initDatabase();
$pdo->exec("
    INSERT INTO b2b_products (name, category, unit, price, package_size, package_unit, is_available, updated_at)
    VALUES ('Jabłka', 'Owoce', 'kg', 4.25, 5, 'skrzynka', 1, CURRENT_TIMESTAMP)
");
$productId = (int)$pdo->lastInsertId();

assertSecurity(
    method_exists($repo, 'prepareOrderItems'),
    'B2bRepository must expose server-side order item preparation'
);

$prepared = $repo->prepareOrderItems([[
    'product_id' => $productId,
    'product_name' => '<img src=x onerror=alert(1)>',
    'price' => 0.01,
    'quantity' => 10,
    'unit' => 'script',
    'package_size' => 1,
    'package_unit' => 'x',
]]);

assertSecurity(count($prepared) === 1, 'The valid catalog product should remain orderable');
assertSecurity($prepared[0]['product_name'] === 'Jabłka', 'Product name must come from the catalog');
assertSecurity($prepared[0]['price'] === 4.25, 'Price must come from the catalog');
assertSecurity($prepared[0]['unit'] === 'kg', 'Unit must come from the catalog');
assertSecurity($prepared[0]['package_size'] === 5.0, 'Package size must come from the catalog');
assertSecurity($prepared[0]['package_unit'] === 'skrzynka', 'Package unit must come from the catalog');
assertSecurity($prepared[0]['item_total'] === 42.5, 'Item total must use the catalog price');

$legacyPrepared = $repo->prepareOrderItems([[
    'product_name' => 'Jabłka',
    'price' => 0.01,
    'quantity' => 2,
]]);
assertSecurity($legacyPrepared[0]['product_name'] === 'Jabłka', 'Legacy exact-name orders should remain supported');
assertSecurity($legacyPrepared[0]['price'] === 4.25, 'Legacy orders must also use the catalog price');

$rejected = false;
try {
    $repo->prepareOrderItems([[
        'product_id' => 99999,
        'product_name' => '<img src=x onerror=alert(1)>',
        'quantity' => 1,
    ]]);
} catch (InvalidArgumentException $e) {
    $rejected = true;
}
assertSecurity($rejected, 'Unknown products must be rejected instead of stored');

$rejected = false;
try {
    $repo->prepareOrderItems([[
        'product_name' => '<img src=x onerror=alert(1)>',
        'quantity' => 1,
    ]]);
} catch (InvalidArgumentException $e) {
    $rejected = true;
}
assertSecurity($rejected, 'Unknown legacy product names must be rejected');

$pdo->exec("UPDATE b2b_products SET is_available = 0 WHERE id = {$productId}");
$rejected = false;
try {
    $repo->prepareOrderItems([['product_id' => $productId, 'quantity' => 1]]);
} catch (InvalidArgumentException $e) {
    $rejected = true;
}
assertSecurity($rejected, 'Unavailable products must be rejected');

$catalog = file_get_contents(BASE_PATH . '/views/b2b/catalog.php');
$history = file_get_contents(BASE_PATH . '/views/b2b/history.php');
$admin = file_get_contents(BASE_PATH . '/views/b2b/admin.php');
$controller = file_get_contents(BASE_PATH . '/program/script/B2bController.php');

assertSecurity(strpos($controller, '$this->repo->prepareOrderItems($items)') !== false, 'Order endpoint must use server-side catalog resolution');
assertSecurity(strpos($catalog, '${it.product_name}') === false, 'Catalog preview must not interpolate untrusted data into HTML');
assertSecurity(strpos($history, '${it.product_name}') === false, 'Client history must not interpolate order data into HTML');
assertSecurity(strpos($history, '${st}') === false, 'Unknown status must not be interpolated into HTML');
assertSecurity(strpos($admin, '${i.product_name}') === false, 'Admin order modal must not interpolate order data into HTML');
assertSecurity(strpos($admin, '${it.product_name}') === false, 'Admin print view must escape order data');
assertSecurity(strpos($admin, 'escapeHtml(it.product_name)') !== false, 'Admin print view must HTML-escape order item names');

echo "B2B order XSS regression tests passed.\n";
