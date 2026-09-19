<?php
/**
 * Biblioteka generatora plików wymiany danych z systemami ERP:
 * - Subiekt GT / Nexo (format EPP / EDI++)
 * - Comarch ERP Optima (format XML zgodny z biuletynem OPT021)
 * - Symfonia Handel (format 3.0 HMF)
 * - Asseco WAPRO Wf-Mag (format XML)
 */
class ErpExporter
{
    /**
     * Domyślna stawka VAT dla produktów hurtowni owocowo-warzywnej (5%).
     */
    public const DEFAULT_VAT_RATE = 5.0;

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

    /**
     * Bezpieczny symbol/kod towaru dla programów ERP (max 24 znaki, bez spacji i znaków specjalnych).
     */
    public static function sanitizeSymbol(string $name, ?string $code = null, int $maxLength = 24): string
    {
        if (!empty($code)) {
            $cleaned = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', trim($code));
            if ($cleaned !== '') {
                return mb_substr($cleaned, 0, $maxLength, 'UTF-8');
            }
        }

        // Brak kodu: tworzenie czytelnego sluga z nazwy towaru
        $translit = @iconv('UTF-8', 'ASCII//TRANSLIT', $name) ?: $name;
        $slug = preg_replace('/[^a-zA-Z0-9]+/', '-', strtoupper(trim($translit)));
        $slug = trim($slug, '-');
        if (strlen($slug) > $maxLength) {
            $slug = substr($slug, 0, $maxLength);
            $slug = rtrim($slug, '-');
        }
        return $slug ?: 'TOWAR';
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
                $pCode = self::sanitizeSymbol($pName, $it['erp_code'] ?? null, 24);
                if (!isset($productsMap[$pCode])) {
                    $priceNetto = (float)($it['price'] ?? 0);
                    $productsMap[$pCode] = [
                        'id'          => $prodIdx++,
                        'code'        => $pCode,
                        'name'        => $pName,
                        'unit'        => $it['unit'] ?? 'kg',
                        'price_netto' => $priceNetto,
                        'price_brutto'=> round($priceNetto * (1 + self::DEFAULT_VAT_RATE / 100), 2),
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
            $priceNettoFmt  = number_format($p['price_netto'], 2, '.', '');
            $priceBruttoFmt = number_format($p['price_brutto'], 2, '.', '');
            $vatRateFmt     = number_format(self::DEFAULT_VAT_RATE, 2, '.', '');
            // Format: id,"symbol","nazwa","nazwa fiskalna","jm","pkwiu",stawka_vat,cena_netto,cena_brutto,"barcode"
            $lines[] = "{$p['id']},\"{$codeEsc}\",\"{$nameEsc}\",\"{$nameEsc}\",\"{$unitEsc}\",\"\",{$vatRateFmt},{$priceNettoFmt},{$priceBruttoFmt},\"\"";
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
            
            $totalNetto = (float)($ord['total_amount'] ?? 0);
            $totalBrutto = round($totalNetto * (1 + self::DEFAULT_VAT_RATE / 100), 2);
            $totalNettoFmt = number_format($totalNetto, 2, '.', '');
            $totalBruttoFmt = number_format($totalBrutto, 2, '.', '');
            $notesEsc = str_replace('"', '""', $ord['notes'] ?? '');

            $lines[] = '[DOKUMENT]';
            $lines[] = "{$docId},1,{$cId},\"ZK\",\"{$num}\",\"\",\"\",{$dateCreate},{$dateDelivery},{$totalNettoFmt},{$totalBruttoFmt},\"PLN\",1.0000,\"{$notesEsc}\"";
            $lines[] = '';

            $lines[] = '[ZAWARTOSC]';
            $posLp = 1;
            foreach ($ord['items'] ?? [] as $it) {
                $pName = trim($it['product_name'] ?? $it['name'] ?? 'Towar');
                $pCode = self::sanitizeSymbol($pName, $it['erp_code'] ?? null, 24);
                $pId = $productsMap[$pCode]['id'] ?? 1;

                $qty = (float)($it['quantity'] ?? 0);
                $priceNetto = (float)($it['price'] ?? 0);
                $itemNetto = (float)($it['item_total'] ?? ($qty * $priceNetto));
                $itemBrutto = round($itemNetto * (1 + self::DEFAULT_VAT_RATE / 100), 2);

                $qtyFmt         = number_format($qty, 3, '.', '');
                $priceNettoFmt  = number_format($priceNetto, 2, '.', '');
                $itemNettoFmt   = number_format($itemNetto, 2, '.', '');
                $itemBruttoFmt  = number_format($itemBrutto, 2, '.', '');
                $vatRateFmt     = number_format(self::DEFAULT_VAT_RATE, 2, '.', '');

                $lines[] = "{$docId},{$posLp},{$pId},{$qtyFmt},{$priceNettoFmt},{$priceNettoFmt},{$itemNettoFmt},{$itemBruttoFmt},{$vatRateFmt}";
                $posLp++;
            }
            $lines[] = '';
            $docId++;
        }

        $raw = implode("\r\n", $lines) . "\r\n";
        return self::toWin1250($raw);
    }

    // =========================================================================
    // 2. COMARCH ERP OPTIMA (FORMAT XML ZGODNY Z OPT021)
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

            $totalNetto = (float)($ord['total_amount'] ?? 0);
            $totalBrutto = round($totalNetto * (1 + self::DEFAULT_VAT_RATE / 100), 2);
            $totalVat = round($totalBrutto - $totalNetto, 2);

            $nag->appendChild($xml->createElement('SERIA', 'RO'));
            $nag->appendChild($xml->createElement('NUMER_PELNY', htmlspecialchars($ord['order_number'] ?? '')));
            $nag->appendChild($xml->createElement('DATA_WYSTAWIENIA', $dateCreate));
            $nag->appendChild($xml->createElement('DATA_REALIZACJI', $dateDelivery));
            $nag->appendChild($xml->createElement('MAGAZYN', 'MAG'));
            $nag->appendChild($xml->createElement('FORMA_PLATNOSCI', 'przelew'));
            $nag->appendChild($xml->createElement('TERMIN_PLATNOSCI', $dateDelivery));
            $nag->appendChild($xml->createElement('WALUTA', 'PLN'));
            $nag->appendChild($xml->createElement('KURS_WALUTY', '1.0000'));
            $nag->appendChild($xml->createElement('WARTOSC_NETTO', number_format($totalNetto, 2, '.', '')));
            $nag->appendChild($xml->createElement('WARTOSC_VAT', number_format($totalVat, 2, '.', '')));
            $nag->appendChild($xml->createElement('WARTOSC_BRUTTO', number_format($totalBrutto, 2, '.', '')));
            $nag->appendChild($xml->createElement('OPIS', htmlspecialchars($ord['notes'] ?? '')));

            $podmiot = $xml->createElement('PODMIOT');
            $nag->appendChild($podmiot);

            $clientNip = preg_replace('/[^0-9]/', '', $ord['nip'] ?? $ord['client_nip'] ?? '');
            $podmiot->appendChild($xml->createElement('TYP', '1'));
            $podmiot->appendChild($xml->createElement('KOD', htmlspecialchars($clientNip ?: preg_replace('/[^a-zA-Z0-9]/', '', $ord['client_name_snapshot'] ?? 'KONTRAHENT'))));
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
                $pCode = self::sanitizeSymbol($pName, $it['erp_code'] ?? null, 24);
                $qty = (float)($it['quantity'] ?? 0);
                $priceNetto = (float)($it['price'] ?? 0);
                $totNetto = (float)($it['item_total'] ?? ($qty * $priceNetto));
                $totBrutto = round($totNetto * (1 + self::DEFAULT_VAT_RATE / 100), 2);
                $totVat = round($totBrutto - $totNetto, 2);

                $p->appendChild($xml->createElement('LP', (string)$lp++));
                $p->appendChild($xml->createElement('TOWAR_KOD', htmlspecialchars($pCode)));
                $p->appendChild($xml->createElement('TOWAR_NAZWA', htmlspecialchars($pName)));
                $p->appendChild($xml->createElement('ILOSC', number_format($qty, 3, '.', '')));
                $p->appendChild($xml->createElement('JEDNOSTKA', htmlspecialchars($it['unit'] ?? 'kg')));
                $p->appendChild($xml->createElement('CENA_NETTO', number_format($priceNetto, 2, '.', '')));
                $p->appendChild($xml->createElement('WARTOSC_NETTO', number_format($totNetto, 2, '.', '')));
                $p->appendChild($xml->createElement('STAWKA_VAT', (string)(int)self::DEFAULT_VAT_RATE));
                $p->appendChild($xml->createElement('FLAGA_VAT', '1'));
                $p->appendChild($xml->createElement('WARTOSC_VAT', number_format($totVat, 2, '.', '')));
                $p->appendChild($xml->createElement('WARTOSC_BRUTTO', number_format($totBrutto, 2, '.', '')));
                $p->appendChild($xml->createElement('OPIS', htmlspecialchars($it['package_summary'] ?? '')));
            }
        }

        return $xml->saveXML();
    }

    // =========================================================================
    // 3. SYMFONIA HANDEL (FORMAT 3.0 / HMF)
    // =========================================================================

    public static function exportSymfoniaTxt(array $orders): string
    {
        $lines = [];
        $lines[] = 'INFO {';
        $lines[] = '    format = "SymfoniaHandel"';
        $lines[] = '    wersja = 3.0';
        $lines[] = '    program = "B2B Hurtownia Warzyw i Owocow"';
        $lines[] = '    baza = "Dokumenty"';
        $lines[] = '}';
        $lines[] = '';

        foreach ($orders as $ord) {
            $num = $ord['order_number'] ?? 'ZO';
            $dateCreate = !empty($ord['created_at']) ? date('Y-m-d', strtotime($ord['created_at'])) : date('Y-m-d');
            $dateDelivery = !empty($ord['delivery_date']) ? date('Y-m-d', strtotime($ord['delivery_date'])) : $dateCreate;
            $clientNip = preg_replace('/[^0-9]/', '', $ord['nip'] ?? $ord['client_nip'] ?? '');
            $clientCode = $clientNip ?: preg_replace('/[^a-zA-Z0-9]/', '', $ord['client_name_snapshot'] ?? 'KLIENT');

            $totalNetto = (float)($ord['total_amount'] ?? 0);
            $totalBrutto = round($totalNetto * (1 + self::DEFAULT_VAT_RATE / 100), 2);

            $lines[] = 'Dokument {';
            $lines[] = '    kod = "ZO"';
            $lines[] = '    rodzaj = "ZO"';
            $lines[] = "    numer = \"{$num}\"";
            $lines[] = "    data = \"{$dateCreate}\"";
            $lines[] = "    termin = \"{$dateDelivery}\"";
            $lines[] = '    waluta = "PLN"';
            $lines[] = '    kurs = 1.0000';
            $lines[] = '    netto = ' . number_format($totalNetto, 2, '.', '');
            $lines[] = '    brutto = ' . number_format($totalBrutto, 2, '.', '');
            $lines[] = '    opis = "' . str_replace('"', '""', $ord['notes'] ?? '') . '"';
            $lines[] = '';
            $lines[] = '    DaneKontrahenta {';
            $lines[] = "        kod = \"{$clientCode}\"";
            $lines[] = "        nip = \"{$clientNip}\"";
            $lines[] = '        nazwa = "' . str_replace('"', '""', $ord['client_name_snapshot'] ?? '') . '"';
            $lines[] = '        adres = "' . str_replace('"', '""', $ord['delivery_address_snapshot'] ?? '') . '"';
            $lines[] = '        telefon = "' . str_replace('"', '""', $ord['client_phone_snapshot'] ?? '') . '"';
            $lines[] = '    }';
            $lines[] = '';

            $lp = 1;
            foreach ($ord['items'] ?? [] as $it) {
                $pName = trim($it['product_name'] ?? $it['name'] ?? 'Towar');
                $pCode = self::sanitizeSymbol($pName, $it['erp_code'] ?? null, 24);
                $qty = (float)($it['quantity'] ?? 0);
                $priceNetto = (float)($it['price'] ?? 0);
                $totNetto = (float)($it['item_total'] ?? ($qty * $priceNetto));
                $totBrutto = round($totNetto * (1 + self::DEFAULT_VAT_RATE / 100), 2);

                $lines[] = '    Pozycja {';
                $lines[] = "        lp = {$lp}";
                $lines[] = "        kod = \"{$pCode}\"";
                $lines[] = '        nazwa = "' . str_replace('"', '""', $pName) . '"';
                $lines[] = '        ilosc = ' . number_format($qty, 3, '.', '');
                $lines[] = '        jm = "' . str_replace('"', '""', $it['unit'] ?? 'kg') . '"';
                $lines[] = '        cena = ' . number_format($priceNetto, 2, '.', '');
                $lines[] = '        wartosc = ' . number_format($totNetto, 2, '.', '');
                $lines[] = '        stawka = ' . (int)self::DEFAULT_VAT_RATE;
                $lines[] = '        brutto = ' . number_format($totBrutto, 2, '.', '');
                if (!empty($it['package_summary'])) {
                    $lines[] = '        opis = "' . str_replace('"', '""', $it['package_summary']) . '"';
                }
                $lines[] = '    }';
                $lp++;
            }
            $lines[] = '}';
            $lines[] = '';
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

            $totalNetto = (float)($ord['total_amount'] ?? 0);
            $totalBrutto = round($totalNetto * (1 + self::DEFAULT_VAT_RATE / 100), 2);
            $totalVat = round($totalBrutto - $totalNetto, 2);

            $z->appendChild($xml->createElement('NUMER', htmlspecialchars($ord['order_number'] ?? '')));
            $z->appendChild($xml->createElement('TYP_DOKUMENTU', 'ZO'));
            $z->appendChild($xml->createElement('MAGAZYN', 'MAG'));
            $z->appendChild($xml->createElement('STATUS_ZAMOWIENIA', '1'));
            $z->appendChild($xml->createElement('DATA_WYSTAWIENIA', $dateCreate));
            $z->appendChild($xml->createElement('TERMIN_REALIZACJI', $dateDelivery));
            $z->appendChild($xml->createElement('WALUTA', 'PLN'));
            $z->appendChild($xml->createElement('WARTOSC_NETTO', number_format($totalNetto, 2, '.', '')));
            $z->appendChild($xml->createElement('WARTOSC_VAT', number_format($totalVat, 2, '.', '')));
            $z->appendChild($xml->createElement('WARTOSC_BRUTTO', number_format($totalBrutto, 2, '.', '')));
            $z->appendChild($xml->createElement('UWAGI', htmlspecialchars($ord['notes'] ?? '')));

            $kontrahent = $xml->createElement('KONTRAHENT');
            $z->appendChild($kontrahent);
            $kontrahent->appendChild($xml->createElement('NIP', htmlspecialchars($clientNip)));
            $kontrahent->appendChild($xml->createElement('KOD', htmlspecialchars($clientNip ?: preg_replace('/[^a-zA-Z0-9]/', '', $ord['client_name_snapshot'] ?? 'KLIENT'))));
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
                $pCode = self::sanitizeSymbol($pName, $it['erp_code'] ?? null, 24);
                $qty = (float)($it['quantity'] ?? 0);
                $priceNetto = (float)($it['price'] ?? 0);
                $totItemNetto = (float)($it['item_total'] ?? ($qty * $priceNetto));
                $totItemBrutto = round($totItemNetto * (1 + self::DEFAULT_VAT_RATE / 100), 2);

                $poz->appendChild($xml->createElement('LP', (string)$lp++));
                $poz->appendChild($xml->createElement('INDEKS', htmlspecialchars($pCode)));
                $poz->appendChild($xml->createElement('NAZWA', htmlspecialchars($pName)));
                $poz->appendChild($xml->createElement('ILOSC', number_format($qty, 3, '.', '')));
                $poz->appendChild($xml->createElement('JEDNOSTKA', htmlspecialchars($it['unit'] ?? 'kg')));
                $poz->appendChild($xml->createElement('CENA_NETTO', number_format($priceNetto, 2, '.', '')));
                $poz->appendChild($xml->createElement('STAWKA_VAT', (string)(int)self::DEFAULT_VAT_RATE));
                $poz->appendChild($xml->createElement('WARTOSC_NETTO', number_format($totItemNetto, 2, '.', '')));
                $poz->appendChild($xml->createElement('WARTOSC_BRUTTO', number_format($totItemBrutto, 2, '.', '')));
                if (!empty($it['package_summary'])) {
                    $poz->appendChild($xml->createElement('UWAGI', htmlspecialchars($it['package_summary'])));
                }
            }
        }

        return $xml->saveXML();
    }
}
