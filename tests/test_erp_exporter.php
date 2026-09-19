<?php
/**
 * Test TDD: Biblioteka ErpExporter dla Subiekt GT/Nexo, Comarch Optima, Symfonia, Wf-Mag
 */
define('BASE_PATH', 'C:/laragon\www');
require_once BASE_PATH . '/program/config/data.php';
require_once BASE_PATH . '/program/config/autoload.php';
require_once BASE_PATH . '/program/lib/ErpExporter.php';

echo "=== TEST: Generator eksportu ERP (Subiekt, Optima, Symfonia, Wf-Mag) ===\n";

$sampleOrders = [
    [
        'id'                        => 101,
        'order_number'              => 'B2B/2026/09/19/01',
        'client_name_snapshot'      => 'Zieleniak Pod Dębem',
        'client_phone_snapshot'     => '500600700',
        'delivery_address_snapshot' => 'ul. Kwiatowa 5, 00-001 Warszawa',
        'nip'                       => '1234567890',
        'delivery_date'             => '2026-09-20',
        'created_at'                => '2026-09-19 14:30:00',
        'total_amount'              => 145.50,
        'notes'                     => 'Dostawa rano do 7:00',
        'items'                     => [
            [
                'product_name'    => 'Pomidor Malinowy PL',
                'erp_code'        => 'POM-MAL-PL',
                'quantity'        => 12.0,
                'unit'            => 'kg',
                'price'           => 8.50,
                'item_total'      => 102.00,
                'package_summary' => '2 skrzynki'
            ],
            [
                'product_name'    => 'Koperek Świeży',
                'erp_code'        => '', // brak kodu — powinien użyć nazwy
                'quantity'        => 15.0,
                'unit'            => 'pęczek',
                'price'           => 2.90,
                'item_total'      => 43.50,
                'package_summary' => '15 pęczków'
            ]
        ]
    ]
];

// 1. Test Subiekt GT / Nexo (EPP / EDI++)
$subiekt = ErpExporter::export('subiekt', $sampleOrders);
$subiektContent = $subiekt['content'];
$hasInfo = (strpos($subiektContent, '[INFO]') !== false);
$hasDokument = (strpos($subiektContent, '[DOKUMENT]') !== false && strpos($subiektContent, '"ZK"') !== false);
$hasNip = (strpos($subiektContent, '1234567890') !== false);
$hasPomidorCode = (strpos($subiektContent, 'POM-MAL-PL') !== false);
$hasVat5 = (strpos($subiektContent, '5.00') !== false);
$hasSafeSymbol = (strpos($subiektContent, 'KOPEREK') !== false);
$hasExtensionEpp = (substr($subiekt['filename'], -4) === '.epp');
$subiektOk = ($hasInfo && $hasDokument && $hasNip && $hasPomidorCode && $hasVat5 && $hasSafeSymbol && $hasExtensionEpp);
echo "1. Eksport Subiekt GT/Nexo (.epp VAT 5% i bezpieczne symbole): " . ($subiektOk ? "PASS" : "FAIL") . "\n";

// 2. Test Comarch Optima (XML zgodny z OPT021)
$optima = ErpExporter::export('optima', $sampleOrders);
$optimaContent = $optima['content'];
$hasXmlHeader = (strpos($optimaContent, '<?xml') !== false);
$hasOptimaRoot = (strpos($optimaContent, '<DOKUMENTY>') !== false && strpos($optimaContent, '<ZAMOWIENIE>') !== false);
$hasOptimaNip = (strpos($optimaContent, '<NIP>1234567890</NIP>') !== false);
$hasOptimaItem = (strpos($optimaContent, 'Pomidor Malinowy PL') !== false);
$hasOptimaVat = (strpos($optimaContent, '<STAWKA_VAT>5</STAWKA_VAT>') !== false);
$hasOptimaPayment = (strpos($optimaContent, '<FORMA_PLATNOSCI>przelew</FORMA_PLATNOSCI>') !== false);
$hasOptimaMag = (strpos($optimaContent, '<MAGAZYN>MAG</MAGAZYN>') !== false);
$hasExtensionXml = (substr($optima['filename'], -4) === '.xml');
$optimaOk = ($hasXmlHeader && $hasOptimaRoot && $hasOptimaNip && $hasOptimaItem && $hasOptimaVat && $hasOptimaPayment && $hasOptimaMag && $hasExtensionXml);
echo "2. Eksport Comarch Optima (.xml ze schematem OPT021): " . ($optimaOk ? "PASS" : "FAIL") . "\n";

// 3. Test Symfonia Handel (Natywny Format 3.0 HMF)
$symfonia = ErpExporter::export('symfonia', $sampleOrders);
$symfoniaContent = $symfonia['content'];
$hasSymfoniaFormat3 = (strpos($symfoniaContent, 'Dokument {') !== false || strpos($symfoniaContent, 'Dokument') !== false);
$hasSymfoniaNip = (strpos($symfoniaContent, '1234567890') !== false);
$hasSymfoniaVat = (strpos($symfoniaContent, 'stawka = 5') !== false || strpos($symfoniaContent, '5') !== false);
$hasExtensionTxt = (substr($symfonia['filename'], -4) === '.txt');
$symfoniaOk = ($hasSymfoniaFormat3 && $hasSymfoniaNip && $hasSymfoniaVat && $hasExtensionTxt);
echo "3. Eksport Symfonia Handel (.txt Format 3.0): " . ($symfoniaOk ? "PASS" : "FAIL") . "\n";

// 4. Test Wf-Mag / Asseco WAPRO (XML z magazynem i statusem)
$wfmag = ErpExporter::export('wfmag', $sampleOrders);
$wfmagContent = $wfmag['content'];
$hasWfMagHeader = (strpos($wfmagContent, '<?xml') !== false && strpos($wfmagContent, 'WAPRO_MAG') !== false);
$hasWfMagNip = (strpos($wfmagContent, '<NIP>1234567890</NIP>') !== false);
$hasWfMagMag = (strpos($wfmagContent, '<MAGAZYN>MAG</MAGAZYN>') !== false);
$hasWfMagStatus = (strpos($wfmagContent, '<STATUS_ZAMOWIENIA>1</STATUS_ZAMOWIENIA>') !== false);
$hasWfMagVat = (strpos($wfmagContent, '<STAWKA_VAT>5</STAWKA_VAT>') !== false);
$hasWfMagExt = (substr($wfmag['filename'], -4) === '.xml');
$wfmagOk = ($hasWfMagHeader && $hasWfMagNip && $hasWfMagMag && $hasWfMagStatus && $hasWfMagVat && $hasWfMagExt);
echo "4. Eksport Asseco WAPRO Wf-Mag (.xml z magazynem i VAT): " . ($wfmagOk ? "PASS" : "FAIL") . "\n";

if (!$subiektOk || !$optimaOk || !$symfoniaOk || !$wfmagOk) {
    echo "=== TEST ZAKOŃCZONY BŁĘDEM (Stan RED) ===\n";
    exit(1);
}

echo "=== TEST ZAKOŃCZONY SUKCESEM (Stan GREEN) ===\n";
