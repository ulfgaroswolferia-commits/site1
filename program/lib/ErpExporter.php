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
     * Wartość do wstawienia między cudzysłowy w formatach tekstowych (EPP, Symfonia).
     * Usuwa znaki sterujące (CR/LF/TAB itd.) — inaczej dane klienta (uwagi, nazwy)
     * mogłyby dopisać do pliku własne sekcje/pozycje — i podwaja cudzysłowy.
     */
    private static function quoteText(string $text): string
    {
        $text = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $text) ?? '';
        return str_replace('"', '""', trim($text));
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

    /**
     * Przygotowuje dane pozycji do eksportu ERP (uwzględnia produkty z cennika oraz spoza cennika).
     *
     * @param array $item Pozycja zamówienia
     * @param int $codeMaxLength Maksymalna długość kodu towaru (np. 20 dla Subiekta, 24 dla innych)
     * @return array ['code' => string, 'name' => string, 'unit' => string, 'price' => float, 'summary' => string, 'is_custom' => bool]
     */
    public static function prepareItemForErp(array $item, int $codeMaxLength = 20): array
    {
        $isCustom = !empty($item['is_custom']);
        $rawName = trim((string)($item['product_name'] ?? $item['name'] ?? 'Towar'));
        $unit = trim((string)($item['unit'] ?? 'kg')) ?: 'kg';
        $price = (float)($item['price'] ?? 0);
        $summary = trim((string)($item['package_summary'] ?? ''));

        if ($isCustom) {
            // Bezpieczny symbol z przedrostkiem SPOZA- (np. SPOZA-KOPER-WLOS, max $codeMaxLength znaków)
            $slug = self::sanitizeSymbol($rawName, null, max(6, $codeMaxLength - 7));
            $code = 'SPOZA-' . $slug;
            if (strlen($code) > $codeMaxLength) {
                $code = substr($code, 0, $codeMaxLength);
            }

            $name = $rawName;
            if (strpos($name, '[SPOZA CENNIKA]') === false) {
                $name .= ' [SPOZA CENNIKA]';
            }
            $price = 0.00;
            if ($summary === '' || $summary === '-') {
                $summary = 'Produkt spoza cennika (do potwierdzenia/wyceny)';
            }
        } else {
            $code = self::sanitizeSymbol($rawName, $item['erp_code'] ?? null, $codeMaxLength);
            $name = $rawName;
        }

        return [
            'code'      => $code,
            'name'      => $name,
            'unit'      => $unit,
            'price'     => $price,
            'summary'   => $summary,
            'is_custom' => $isCustom,
        ];
    }

    /**
     * Formatuje uwagi do zamówienia dla dokumentu ERP (dodaje informację o pozycjach spoza cennika).
     */
    public static function formatOrderNotes(array $order): string
    {
        $notes = trim((string)($order['notes'] ?? ''));
        $hasCustom = false;
        foreach ($order['items'] ?? [] as $it) {
            if (!empty($it['is_custom'])) {
                $hasCustom = true;
                break;
            }
        }
        if ($hasCustom) {
            $notice = 'UWAGA: Zamówienie zawiera pozycje spoza cennika (wymaga potwierdzenia dostawy i wyceny)';
            $notes = $notes !== '' ? ($notes . ' | ' . $notice) : $notice;
        }
        return $notes;
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
                $itemInfo = self::prepareItemForErp($it, 20);
                $pCode = $itemInfo['code'];
                $pName = mb_substr($itemInfo['name'], 0, 50, 'UTF-8');
                if (!isset($productsMap[$pCode])) {
                    $priceNetto = $itemInfo['price'];
                    $productsMap[$pCode] = [
                        'id'          => $prodIdx++,
                        'code'        => $pCode,
                        'name'        => $pName,
                        'unit'        => $itemInfo['unit'],
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
            $nameEsc = self::quoteText($c['name']);
            $addrEsc = self::quoteText($c['address']);
            $lines[] = "{$c['id']},\"" . self::quoteText((string)$c['key']) . "\",\"{$nameEsc}\",\"{$nameEsc}\",\"\",\"\",\"{$addrEsc}\",\"{$c['nip']}\",\"" . self::quoteText((string)$c['phone']) . "\",0";
        }
        $lines[] = '';

        // Sekcja [TOWARY]
        $lines[] = '[TOWARY]';
        foreach ($productsMap as $p) {
            $codeEsc = self::quoteText($p['code']);
            $nameEsc = self::quoteText($p['name']);
            $unitEsc = self::quoteText($p['unit']);
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
            $notesEsc = self::quoteText(self::formatOrderNotes($ord));

            $lines[] = '[DOKUMENT]';
            $lines[] = "{$docId},1,{$cId},\"ZK\",\"" . self::quoteText((string)$num) . "\",\"\",\"\",{$dateCreate},{$dateDelivery},{$totalNettoFmt},{$totalBruttoFmt},\"PLN\",1.0000,\"{$notesEsc}\"";
            $lines[] = '';

            $lines[] = '[ZAWARTOSC]';
            $posLp = 1;
            foreach ($ord['items'] ?? [] as $it) {
                $itemInfo = self::prepareItemForErp($it, 20);
                $pCode = $itemInfo['code'];
                $pId = $productsMap[$pCode]['id'] ?? 1;

                $qty = (float)($it['quantity'] ?? 0);
                $priceNetto = $itemInfo['price'];
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
            $nag->appendChild($xml->createElement('OPIS', htmlspecialchars(self::formatOrderNotes($ord))));

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

                $itemInfo = self::prepareItemForErp($it, 24);
                $pName = $itemInfo['name'];
                $pCode = $itemInfo['code'];
                $qty = (float)($it['quantity'] ?? 0);
                $priceNetto = $itemInfo['price'];
                $totNetto = (float)($it['item_total'] ?? ($qty * $priceNetto));
                $totBrutto = round($totNetto * (1 + self::DEFAULT_VAT_RATE / 100), 2);
                $totVat = round($totBrutto - $totNetto, 2);

                $p->appendChild($xml->createElement('LP', (string)$lp++));
                $p->appendChild($xml->createElement('TOWAR_KOD', htmlspecialchars($pCode)));
                $p->appendChild($xml->createElement('TOWAR_NAZWA', htmlspecialchars($pName)));
                $p->appendChild($xml->createElement('ILOSC', number_format($qty, 3, '.', '')));
                $p->appendChild($xml->createElement('JEDNOSTKA', htmlspecialchars($itemInfo['unit'])));
                $p->appendChild($xml->createElement('CENA_NETTO', number_format($priceNetto, 2, '.', '')));
                $p->appendChild($xml->createElement('WARTOSC_NETTO', number_format($totNetto, 2, '.', '')));
                $p->appendChild($xml->createElement('STAWKA_VAT', (string)(int)self::DEFAULT_VAT_RATE));
                $p->appendChild($xml->createElement('FLAGA_VAT', '1'));
                $p->appendChild($xml->createElement('WARTOSC_VAT', number_format($totVat, 2, '.', '')));
                $p->appendChild($xml->createElement('WARTOSC_BRUTTO', number_format($totBrutto, 2, '.', '')));
                if (!empty($itemInfo['summary'])) {
                    $p->appendChild($xml->createElement('OPIS', htmlspecialchars($itemInfo['summary'])));
                }
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
            $lines[] = "    numer = \"" . self::quoteText((string)$num) . "\"";
            $lines[] = "    data = \"{$dateCreate}\"";
            $lines[] = "    termin = \"{$dateDelivery}\"";
            $lines[] = '    waluta = "PLN"';
            $lines[] = '    kurs = 1.0000';
            $lines[] = '    netto = ' . number_format($totalNetto, 2, '.', '');
            $lines[] = '    brutto = ' . number_format($totalBrutto, 2, '.', '');
            $lines[] = '    opis = "' . self::quoteText(self::formatOrderNotes($ord)) . '"';
            $lines[] = '';
            $lines[] = '    DaneKontrahenta {';
            $lines[] = "        kod = \"{$clientCode}\"";
            $lines[] = "        nip = \"{$clientNip}\"";
            $lines[] = '        nazwa = "' . self::quoteText($ord['client_name_snapshot'] ?? '') . '"';
            $lines[] = '        adres = "' . self::quoteText($ord['delivery_address_snapshot'] ?? '') . '"';
            $lines[] = '        telefon = "' . self::quoteText($ord['client_phone_snapshot'] ?? '') . '"';
            $lines[] = '    }';
            $lines[] = '';

            $lp = 1;
            foreach ($ord['items'] ?? [] as $it) {
                $itemInfo = self::prepareItemForErp($it, 24);
                $pName = $itemInfo['name'];
                $pCode = $itemInfo['code'];
                $qty = (float)($it['quantity'] ?? 0);
                $priceNetto = $itemInfo['price'];
                $totNetto = (float)($it['item_total'] ?? ($qty * $priceNetto));
                $totBrutto = round($totNetto * (1 + self::DEFAULT_VAT_RATE / 100), 2);

                $lines[] = '    Pozycja {';
                $lines[] = "        lp = {$lp}";
                $lines[] = "        kod = \"{$pCode}\"";
                $lines[] = '        nazwa = "' . self::quoteText($pName) . '"';
                $lines[] = '        ilosc = ' . number_format($qty, 3, '.', '');
                $lines[] = '        jm = "' . self::quoteText($itemInfo['unit']) . '"';
                $lines[] = '        cena = ' . number_format($priceNetto, 2, '.', '');
                $lines[] = '        wartosc = ' . number_format($totNetto, 2, '.', '');
                $lines[] = '        stawka = ' . (int)self::DEFAULT_VAT_RATE;
                $lines[] = '        brutto = ' . number_format($totBrutto, 2, '.', '');
                if (!empty($itemInfo['summary'])) {
                    $lines[] = '        opis = "' . self::quoteText($itemInfo['summary']) . '"';
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
            $z->appendChild($xml->createElement('UWAGI', htmlspecialchars(self::formatOrderNotes($ord))));

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

                $itemInfo = self::prepareItemForErp($it, 24);
                $pName = $itemInfo['name'];
                $pCode = $itemInfo['code'];
                $qty = (float)($it['quantity'] ?? 0);
                $priceNetto = $itemInfo['price'];
                $totItemNetto = (float)($it['item_total'] ?? ($qty * $priceNetto));
                $totItemBrutto = round($totItemNetto * (1 + self::DEFAULT_VAT_RATE / 100), 2);

                $poz->appendChild($xml->createElement('LP', (string)$lp++));
                $poz->appendChild($xml->createElement('INDEKS', htmlspecialchars($pCode)));
                $poz->appendChild($xml->createElement('NAZWA', htmlspecialchars($pName)));
                $poz->appendChild($xml->createElement('ILOSC', number_format($qty, 3, '.', '')));
                $poz->appendChild($xml->createElement('JEDNOSTKA', htmlspecialchars($itemInfo['unit'])));
                $poz->appendChild($xml->createElement('CENA_NETTO', number_format($priceNetto, 2, '.', '')));
                $poz->appendChild($xml->createElement('STAWKA_VAT', (string)(int)self::DEFAULT_VAT_RATE));
                $poz->appendChild($xml->createElement('WARTOSC_NETTO', number_format($totItemNetto, 2, '.', '')));
                $poz->appendChild($xml->createElement('WARTOSC_BRUTTO', number_format($totItemBrutto, 2, '.', '')));
                if (!empty($itemInfo['summary'])) {
                    $poz->appendChild($xml->createElement('UWAGI', htmlspecialchars($itemInfo['summary'])));
                }
            }
        }

        return $xml->saveXML();
    }
}
