<?php
/**
 * Biblioteka generatora plików wymiany danych z systemami ERP:
 * - Subiekt GT / Nexo (format EPP / EDI++)
 * - Comarch ERP Optima (format XML)
 * - Symfonia Handel (format TXT)
 * - Asseco WAPRO Wf-Mag (format XML)
 */
class ErpExporter
{
    /**
     * Główny punkt wejścia do eksportu.
     * Zwraca tablicę: ['content' => string, 'filename' => string, 'mime' => string]
     *
     * @param string $format 'subiekt' | 'optima' | 'symfonia' | 'wfmag'
     * @param array $orders Tablica zamówień (każde z kluczem 'items' oraz danymi nagłówka)
     * @return array
     */
    public static function export(string $format, array $orders): array
    {
        $fmt = strtolower(trim($format));
        $isSingle = count($orders) === 1;
        $orderNumClean = $isSingle ? preg_replace('/[^a-zA-Z0-9_\-]/', '_', $orders[0]['order_number'] ?? 'zam') : 'zbiorczy';
        $timestamp = date('Ymd_His');

        switch ($fmt) {
            case 'subiekt':
            case 'epp':
                $content = self::exportSubiektEpp($orders);
                $filename = "zamowienie_{$orderNumClean}_{$timestamp}.epp";
                $mime = 'text/plain; charset=windows-1250';
                break;

            case 'optima':
            case 'comarch':
                $content = self::exportComarchOptimaXml($orders);
                $filename = "zamowienie_{$orderNumClean}_{$timestamp}.xml";
                $mime = 'application/xml; charset=utf-8';
                break;

            case 'symfonia':
                $content = self::exportSymfoniaTxt($orders);
                $filename = "zamowienie_{$orderNumClean}_{$timestamp}.txt";
                $mime = 'text/plain; charset=windows-1250';
                break;

            case 'wfmag':
            case 'wapro':
                $content = self::exportWfMagXml($orders);
                $filename = "zamowienie_{$orderNumClean}_{$timestamp}.xml";
                $mime = 'application/xml; charset=utf-8';
                break;

            default:
                throw new InvalidArgumentException("Nieobsługiwany format eksportu ERP: {$format}");
        }

        return [
            'content'  => $content,
            'filename' => $filename,
            'mime'     => $mime,
        ];
    }

    /**
     * Konwersja UTF-8 na Windows-1250 dla systemów wymagających kodowania ANSI (Subiekt, Symfonia).
     */
    private static function toWin1250(string $text): string
    {
        return iconv('UTF-8', 'WINDOWS-1250//TRANSLIT', $text) ?: $text;
    }

    // =========================================================================
    // 1. SUBIEKT GT / NEXO (FORMAT EPP / EDI++)
    // =========================================================================

    public static function exportSubiektEpp(array $orders): string
    {
        $today = date('Ymd');
        $lines = [];

        // Sekcja [INFO]
        $lines[] = '[INFO]';
        $lines[] = '"1.05",3,1250,"Hurtownia Magdy","B2B","","B2B",' . $today . ',' . $today . ',""';
        $lines[] = '';

        $clientsMap = [];
        $productsMap = [];
        $clientIdx = 1;
        $prodIdx = 1;

        foreach ($orders as $ord) {
            $clientNip = preg_replace('/[^0-9]/', '', $ord['nip'] ?? $ord['client_nip'] ?? '');
            $clientKey = $clientNip !== '' ? $clientNip : ($ord['client_name_snapshot'] ?? 'KLIENT');
            if (!isset($clientsMap[$clientKey])) {
                $clientsMap[$clientKey] = [
                    'id'      => $clientIdx++,
                    'key'     => $clientKey,
                    'name'    => $ord['client_name_snapshot'] ?? 'Klient B2B',
                    'nip'     => $clientNip,
                    'address' => $ord['delivery_address_snapshot'] ?? '',
                    'phone'   => $ord['client_phone_snapshot'] ?? '',
                ];
            }

            foreach ($ord['items'] ?? [] as $it) {
                $pName = trim($it['product_name'] ?? $it['name'] ?? 'Towar');
                $pCode = !empty($it['erp_code']) ? trim($it['erp_code']) : $pName;
                if (!isset($productsMap[$pCode])) {
                    $productsMap[$pCode] = [
                        'id'    => $prodIdx++,
                        'code'  => $pCode,
                        'name'  => $pName,
                        'unit'  => $it['unit'] ?? 'kg',
                        'price' => (float)($it['price'] ?? 0),
                    ];
                }
            }
        }

        // Sekcja [NAGLOWEK]
        $lines[] = '[NAGLOWEK]';
        $lines[] = '"DOKUMENTY"';
        $lines[] = '';

        // Sekcja [KONTRAHENCI]
        $lines[] = '[KONTRAHENCI]';
        foreach ($clientsMap as $c) {
            $nameEsc = str_replace('"', '""', $c['name']);
            $addrEsc = str_replace('"', '""', $c['address']);
            $lines[] = "{$c['id']},\"{$c['key']}\",\"{$nameEsc}\",\"{$nameEsc}\",\"\",\"\",\"{$addrEsc}\",\"{$c['nip']}\",\"{$c['phone']}\",0";
        }
        $lines[] = '';

        // Sekcja [TOWARY]
        $lines[] = '[TOWARY]';
        foreach ($productsMap as $p) {
            $codeEsc = str_replace('"', '""', $p['code']);
            $nameEsc = str_replace('"', '""', $p['name']);
            $unitEsc = str_replace('"', '""', $p['unit']);
            $priceFmt = number_format($p['price'], 2, '.', '');
            $lines[] = "{$p['id']},\"{$codeEsc}\",\"{$nameEsc}\",\"{$nameEsc}\",\"{$unitEsc}\",\"\",0.00,{$priceFmt},{$priceFmt},\"\"";
        }
        $lines[] = '';

        // Sekcja dokumentów [DOKUMENT] oraz pozycji [ZAWARTOSC]
        $docId = 1;
        foreach ($orders as $ord) {
            $num = $ord['order_number'] ?? "ZK/{$docId}";
            $clientNip = preg_replace('/[^0-9]/', '', $ord['nip'] ?? $ord['client_nip'] ?? '');
            $clientKey = $clientNip !== '' ? $clientNip : ($ord['client_name_snapshot'] ?? 'KLIENT');
            $cId = $clientsMap[$clientKey]['id'] ?? 1;

            $dateCreate = !empty($ord['created_at']) ? date('Ymd', strtotime($ord['created_at'])) : $today;
            $dateDelivery = !empty($ord['delivery_date']) ? date('Ymd', strtotime($ord['delivery_date'])) : $today;
            $totalAmount = number_format((float)($ord['total_amount'] ?? 0), 2, '.', '');
            $notesEsc = str_replace('"', '""', $ord['notes'] ?? '');

            $lines[] = '[DOKUMENT]';
            $lines[] = "{$docId},1,{$cId},\"ZK\",\"{$num}\",\"\",\"\",{$dateCreate},{$dateDelivery},{$totalAmount},{$totalAmount},\"PLN\",1.0000,\"{$notesEsc}\"";
            $lines[] = '';

            $lines[] = '[ZAWARTOSC]';
            $posLp = 1;
            foreach ($ord['items'] ?? [] as $it) {
                $pName = trim($it['product_name'] ?? $it['name'] ?? 'Towar');
                $pCode = !empty($it['erp_code']) ? trim($it['erp_code']) : $pName;
                $pId = $productsMap[$pCode]['id'] ?? 1;

                $qty = (float)($it['quantity'] ?? 0);
                $price = (float)($it['price'] ?? 0);
                $itemTotal = (float)($it['item_total'] ?? ($qty * $price));

                $qtyFmt   = number_format($qty, 3, '.', '');
                $priceFmt = number_format($price, 2, '.', '');
                $totFmt   = number_format($itemTotal, 2, '.', '');

                $lines[] = "{$docId},{$posLp},{$pId},{$qtyFmt},{$priceFmt},{$priceFmt},{$totFmt},{$totFmt},0.00";
                $posLp++;
            }
            $lines[] = '';
            $docId++;
        }

        $raw = implode("\r\n", $lines) . "\r\n";
        return self::toWin1250($raw);
    }

    // =========================================================================
    // 2. COMARCH ERP OPTIMA (FORMAT XML)
    // =========================================================================

    public static function exportComarchOptimaXml(array $orders): string
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $root = $xml->createElement('ROOT');
        $root->setAttribute('xmlns', 'http://www.comarch.pl/cdn/optima/offline');
        $xml->appendChild($root);

        $dok = $xml->createElement('DOKUMENTY');
        $root->appendChild($dok);

        $zamOdb = $xml->createElement('ZAMOWIENIA_OD_ODBIORCY');
        $dok->appendChild($zamOdb);

        foreach ($orders as $ord) {
            $z = $xml->createElement('ZAMOWIENIE');
            $zamOdb->appendChild($z);

            $nag = $xml->createElement('NAGLOWEK');
            $z->appendChild($nag);

            $dateCreate = !empty($ord['created_at']) ? date('Y-m-d', strtotime($ord['created_at'])) : date('Y-m-d');
            $dateDelivery = !empty($ord['delivery_date']) ? date('Y-m-d', strtotime($ord['delivery_date'])) : $dateCreate;

            $nag->appendChild($xml->createElement('SERIA', 'RO'));
            $nag->appendChild($xml->createElement('NUMER_PELNY', htmlspecialchars($ord['order_number'] ?? '')));
            $nag->appendChild($xml->createElement('DATA_WYSTAWIENIA', $dateCreate));
            $nag->appendChild($xml->createElement('DATA_REALIZACJI', $dateDelivery));
            $nag->appendChild($xml->createElement('WARTOSC_NETTO', number_format((float)($ord['total_amount'] ?? 0), 2, '.', '')));
            $nag->appendChild($xml->createElement('WARTOSC_BRUTTO', number_format((float)($ord['total_amount'] ?? 0), 2, '.', '')));
            $nag->appendChild($xml->createElement('OPIS', htmlspecialchars($ord['notes'] ?? '')));

            $podmiot = $xml->createElement('PODMIOT');
            $nag->appendChild($podmiot);

            $clientNip = preg_replace('/[^0-9]/', '', $ord['nip'] ?? $ord['client_nip'] ?? '');
            $podmiot->appendChild($xml->createElement('TYP', '1'));
            $podmiot->appendChild($xml->createElement('KOD', htmlspecialchars($clientNip ?: ($ord['client_name_snapshot'] ?? 'KONTRAHENT'))));
            $podmiot->appendChild($xml->createElement('NAZWA1', htmlspecialchars($ord['client_name_snapshot'] ?? '')));
            $podmiot->appendChild($xml->createElement('NIP', htmlspecialchars($clientNip)));
            $podmiot->appendChild($xml->createElement('ADRES', htmlspecialchars($ord['delivery_address_snapshot'] ?? '')));
            $podmiot->appendChild($xml->createElement('TELEFON', htmlspecialchars($ord['client_phone_snapshot'] ?? '')));

            $pozWrapper = $xml->createElement('POZYCJE');
            $z->appendChild($pozWrapper);

            $lp = 1;
            foreach ($ord['items'] ?? [] as $it) {
                $p = $xml->createElement('POZYCJA');
                $pozWrapper->appendChild($p);

                $pName = trim($it['product_name'] ?? $it['name'] ?? 'Towar');
                $pCode = !empty($it['erp_code']) ? trim($it['erp_code']) : $pName;
                $qty = (float)($it['quantity'] ?? 0);
                $price = (float)($it['price'] ?? 0);
                $tot = (float)($it['item_total'] ?? ($qty * $price));

                $p->appendChild($xml->createElement('LP', (string)$lp++));
                $p->appendChild($xml->createElement('TOWAR_KOD', htmlspecialchars($pCode)));
                $p->appendChild($xml->createElement('TOWAR_NAZWA', htmlspecialchars($pName)));
                $p->appendChild($xml->createElement('ILOSC', number_format($qty, 2, '.', '')));
                $p->appendChild($xml->createElement('JEDNOSTKA', htmlspecialchars($it['unit'] ?? 'kg')));
                $p->appendChild($xml->createElement('CENA_NETTO', number_format($price, 2, '.', '')));
                $p->appendChild($xml->createElement('WARTOSC_NETTO', number_format($tot, 2, '.', '')));
                $p->appendChild($xml->createElement('OPIS', htmlspecialchars($it['package_summary'] ?? '')));
            }
        }

        return $xml->saveXML();
    }

    // =========================================================================
    // 3. SYMFONIA HANDEL (FORMAT TXT / CSV)
    // =========================================================================

    public static function exportSymfoniaTxt(array $orders): string
    {
        $lines = [];
        $lines[] = 'Baza;Dokumenty';
        $lines[] = 'Format;SymfoniaHandel;1.0';

        foreach ($orders as $ord) {
            $num = $ord['order_number'] ?? 'ZO';
            $dateCreate = !empty($ord['created_at']) ? date('Y-m-d', strtotime($ord['created_at'])) : date('Y-m-d');
            $dateDelivery = !empty($ord['delivery_date']) ? date('Y-m-d', strtotime($ord['delivery_date'])) : $dateCreate;
            $clientNip = preg_replace('/[^0-9]/', '', $ord['nip'] ?? $ord['client_nip'] ?? '');

            $lines[] = '';
            $lines[] = 'Sekcja;Zamowienie';
            $lines[] = "Typ;ZO";
            $lines[] = "Numer;{$num}";
            $lines[] = "Data;{$dateCreate}";
            $lines[] = "Termin;{$dateDelivery}";
            $lines[] = "Kontrahent;{$clientNip};" . ($ord['client_name_snapshot'] ?? '') . ';' . ($ord['delivery_address_snapshot'] ?? '');
            $lines[] = "Uwagi;" . ($ord['notes'] ?? '');
            $lines[] = 'Pozycje;Lp;KodTowaru;Nazwa;Ilosc;Jm;CenaNetto;WartoscNetto;Opakowanie';

            $lp = 1;
            foreach ($ord['items'] ?? [] as $it) {
                $pName = trim($it['product_name'] ?? $it['name'] ?? 'Towar');
                $pCode = !empty($it['erp_code']) ? trim($it['erp_code']) : $pName;
                $qty = number_format((float)($it['quantity'] ?? 0), 2, '.', '');
                $unit = $it['unit'] ?? 'kg';
                $price = number_format((float)($it['price'] ?? 0), 2, '.', '');
                $tot = number_format((float)($it['item_total'] ?? 0), 2, '.', '');
                $pkg = $it['package_summary'] ?? '';

                $lines[] = "Pozycja;{$lp};{$pCode};{$pName};{$qty};{$unit};{$price};{$tot};{$pkg}";
                $lp++;
            }
        }

        $raw = implode("\r\n", $lines) . "\r\n";
        return self::toWin1250($raw);
    }

    // =========================================================================
    // 4. ASSECO WAPRO / WF-MAG (FORMAT XML)
    // =========================================================================

    public static function exportWfMagXml(array $orders): string
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $root = $xml->createElement('DOKUMENTY_MAGAZYNOWE');
        $root->setAttribute('system', 'WAPRO_MAG');
        $xml->appendChild($root);

        foreach ($orders as $ord) {
            $z = $xml->createElement('ZAMOWIENIE_ODBIORCY');
            $root->appendChild($z);

            $dateCreate = !empty($ord['created_at']) ? date('Y-m-d', strtotime($ord['created_at'])) : date('Y-m-d');
            $dateDelivery = !empty($ord['delivery_date']) ? date('Y-m-d', strtotime($ord['delivery_date'])) : $dateCreate;
            $clientNip = preg_replace('/[^0-9]/', '', $ord['nip'] ?? $ord['client_nip'] ?? '');

            $z->appendChild($xml->createElement('NUMER', htmlspecialchars($ord['order_number'] ?? '')));
            $z->appendChild($xml->createElement('TYP_DOKUMENTU', 'ZO'));
            $z->appendChild($xml->createElement('DATA_WYSTAWIENIA', $dateCreate));
            $z->appendChild($xml->createElement('TERMIN_REALIZACJI', $dateDelivery));
            $z->appendChild($xml->createElement('UWAGI', htmlspecialchars($ord['notes'] ?? '')));

            $kontrahent = $xml->createElement('KONTRAHENT');
            $z->appendChild($kontrahent);
            $kontrahent->appendChild($xml->createElement('NIP', htmlspecialchars($clientNip)));
            $kontrahent->appendChild($xml->createElement('NAZWA', htmlspecialchars($ord['client_name_snapshot'] ?? '')));
            $kontrahent->appendChild($xml->createElement('ADRES', htmlspecialchars($ord['delivery_address_snapshot'] ?? '')));
            $kontrahent->appendChild($xml->createElement('TELEFON', htmlspecialchars($ord['client_phone_snapshot'] ?? '')));

            $pozycje = $xml->createElement('POZYCJE');
            $z->appendChild($pozycje);

            $lp = 1;
            foreach ($ord['items'] ?? [] as $it) {
                $poz = $xml->createElement('POZYCJA');
                $pozycje->appendChild($poz);

                $pName = trim($it['product_name'] ?? $it['name'] ?? 'Towar');
                $pCode = !empty($it['erp_code']) ? trim($it['erp_code']) : $pName;
                $qty = (float)($it['quantity'] ?? 0);
                $price = (float)($it['price'] ?? 0);
                $tot = (float)($it['item_total'] ?? ($qty * $price));

                $poz->appendChild($xml->createElement('LP', (string)$lp++));
                $poz->appendChild($xml->createElement('INDEKS', htmlspecialchars($pCode)));
                $poz->appendChild($xml->createElement('NAZWA', htmlspecialchars($pName)));
                $poz->appendChild($xml->createElement('ILOSC', number_format($qty, 2, '.', '')));
                $poz->appendChild($xml->createElement('JEDNOSTKA', htmlspecialchars($it['unit'] ?? 'kg')));
                $poz->appendChild($xml->createElement('CENA_NETTO', number_format($price, 2, '.', '')));
                $poz->appendChild($xml->createElement('WARTOSC_NETTO', number_format($tot, 2, '.', '')));
            }
        }

        return $xml->saveXML();
    }
}
