<?php
/**
 * Generator czystych arkuszy Excel (.xlsx) bez formatowania graficznego i zbędnych stylów.
 * Przeznaczony do generowania plików zamówień gotowych do wysyłki e-mailem do hurtowni.
 *
 * Wykorzystuje wyłącznie natywne rozszerzenie PHP ZipArchive.
 */
class XlsxWriter
{
    /**
     * Zapisuje wygenerowane zamówienie bezpośrednio do pliku .xlsx.
     */
    public static function saveToFile(string $filePath, array $items, array $meta = []): bool
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $xlsxContent = self::createOrderWorkbook($items, $meta);
        return file_put_contents($filePath, $xlsxContent) !== false;
    }

    /**
     * Zapisuje kartę kompletacji zamówienia B2B z rozbiciem logistycznym i polem kontrolnym [ ].
     */
    public static function savePackingSheetToFile(string $filePath, array $items, array $meta = []): bool
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $xlsxContent = self::createPackingSheetWorkbook($items, $meta);
        return file_put_contents($filePath, $xlsxContent) !== false;
    }

    /**
     * Tworzy i zwraca zawartość binarną pliku .xlsx jako ciąg znaków.
     *
     * $items = [
     *     ['name' => 'Pomidor malinowy', 'price' => 7.50, 'quantity' => 12.5, 'unit' => 'kg'],
     *     ...
     * ];
     */
    public static function createOrderWorkbook(array $items, array $meta = []): string
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException("Rozszerzenie PHP ZipArchive nie jest aktywne.");
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Nie można utworzyć pliku tymczasowego dla archiwum XLSX.");
        }

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
            '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
            '</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
            '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<bookViews><workbookView xWindow="0" yWindow="0" windowWidth="20480" windowHeight="10240"/></bookViews>' .
            '<sheets>' .
            '<sheet name="Zamówienie" sheetId="1" r:id="rId1"/>' .
            '</sheets>' .
            '</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // 5. xl/styles.xml (proste, schludne formatowanie: pogrubiony nagłówek, formaty liczb)
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<fonts count="2">' .
            '<font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>' .
            '<font><b/><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>' .
            '</fonts>' .
            '<fills count="3">' .
            '<fill><patternFill patternType="none"/></fill>' .
            '<fill><patternFill patternType="gray125"/></fill>' .
            '<fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/></patternFill></fill>' .
            '</fills>' .
            '<borders count="2">' .
            '<border><left/><right/><top/><bottom/><diagonal/></border>' .
            '<border><left style="thin"><color rgb="FFCBD5E1"/></left><right style="thin"><color rgb="FFCBD5E1"/></right><top style="thin"><color rgb="FFCBD5E1"/></top><bottom style="thin"><color rgb="FFCBD5E1"/></bottom></border>' .
            '</borders>' .
            '<cellXfs count="3">' .
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"/>' . // 0 = standard
            '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>' . // 1 = header (bold, gray fill)
            '<xf numFmtId="0" fontId="1" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"/>' . // 2 = bold total
            '</cellXfs>' .
            '</styleSheet>';
        $zip->addFromString('xl/styles.xml', $styles);

        // 6. xl/worksheets/sheet1.xml (tabela zamówienia)
        $sheetXml = self::buildSheetXml($items, $meta);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

        $zip->close();

        $content = file_get_contents($tempFile);
        @unlink($tempFile);

        return $content;
    }

    /**
     * Buduje zawartość XML arkusza z tabelą pozycji zamówienia.
     */
    private static function buildSheetXml(array $items, array $meta): string
    {
        $rowsXml = '';
        $rowNum = 1;

        // Metadane zamówienia u góry (jeśli podane)
        if (!empty($meta['order_number'])) {
            $rowsXml .= '<row r="' . $rowNum . '">';
            $rowsXml .= self::inlineStringCell('A' . $rowNum, 'Zamówienie: ' . $meta['order_number'], 1);
            $rowsXml .= '</row>';
            $rowNum++;
        }

        if (!empty($meta['supplier_name'])) {
            $rowsXml .= '<row r="' . $rowNum . '">';
            $rowsXml .= self::inlineStringCell('A' . $rowNum, 'Hurtownia: ' . $meta['supplier_name'], 0);
            $rowsXml .= '</row>';
            $rowNum++;
        }

        if (!empty($meta['created_at'])) {
            $rowsXml .= '<row r="' . $rowNum . '">';
            $rowsXml .= self::inlineStringCell('A' . $rowNum, 'Data zamówienia: ' . $meta['created_at'], 0);
            $rowsXml .= '</row>';
            $rowNum++;
        }

        // Jeśli były metadane, dodajmy jeden wiersz odstępu
        if ($rowNum > 1) {
            $rowNum++;
        }

        // Wiersz nagłówka tabeli
        $headerRow = $rowNum;
        $rowsXml .= '<row r="' . $headerRow . '">';
        $rowsXml .= self::inlineStringCell('A' . $headerRow, 'Lp.', 1);
        $rowsXml .= self::inlineStringCell('B' . $headerRow, 'Nazwa towaru', 1);
        $rowsXml .= self::inlineStringCell('C' . $headerRow, 'Ilość', 1);
        $rowsXml .= self::inlineStringCell('D' . $headerRow, 'Jednostka', 1);
        $rowsXml .= self::inlineStringCell('E' . $headerRow, 'Cena jedn. (zł)', 1);
        $rowsXml .= self::inlineStringCell('F' . $headerRow, 'Wartość (zł)', 1);
        $rowsXml .= '</row>';
        $rowNum++;

        $totalAmount = 0.0;
        $itemIndex = 1;

        foreach ($items as $item) {
            $qty = (float)($item['quantity'] ?? 0);
            if ($qty <= 0) {
                continue; // Pomijamy pozycje nie zamówione
            }

            $name  = (string)($item['name'] ?? $item['product_name'] ?? 'Produkt');
            $price = (float)($item['price'] ?? $item['unit_price'] ?? 0);
            $unit  = (string)($item['unit'] ?? 'kg');
            $total = round($qty * $price, 2);
            $totalAmount += $total;

            $rowsXml .= '<row r="' . $rowNum . '">';
            $rowsXml .= self::numberCell('A' . $rowNum, $itemIndex++);
            $rowsXml .= self::inlineStringCell('B' . $rowNum, $name);
            $rowsXml .= self::numberCell('C' . $rowNum, $qty);
            $rowsXml .= self::inlineStringCell('D' . $rowNum, $unit);
            $rowsXml .= self::numberCell('E' . $rowNum, $price);
            $rowsXml .= self::numberCell('F' . $rowNum, $total);
            $rowsXml .= '</row>';
            $rowNum++;
        }

        // Wiersz podsumowania
        $rowsXml .= '<row r="' . $rowNum . '">';
        $rowsXml .= self::inlineStringCell('A' . $rowNum, '', 2);
        $rowsXml .= self::inlineStringCell('B' . $rowNum, 'ŁĄCZNA WARTOŚĆ ZAMÓWIENIA:', 2);
        $rowsXml .= self::inlineStringCell('C' . $rowNum, '', 2);
        $rowsXml .= self::inlineStringCell('D' . $rowNum, '', 2);
        $rowsXml .= self::inlineStringCell('E' . $rowNum, '', 2);
        $rowsXml .= self::numberCell('F' . $rowNum, round($totalAmount, 2), 2);
        $rowsXml .= '</row>';

        // Definicja szerokości kolumn
        $colsXml = '<cols>' .
            '<col min="1" max="1" width="6" customWidth="1"/>' .
            '<col min="2" max="2" width="38" customWidth="1"/>' .
            '<col min="3" max="3" width="12" customWidth="1"/>' .
            '<col min="4" max="4" width="12" customWidth="1"/>' .
            '<col min="5" max="5" width="16" customWidth="1"/>' .
            '<col min="6" max="6" width="16" customWidth="1"/>' .
            '</cols>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            $colsXml .
            '<sheetData>' . $rowsXml . '</sheetData>' .
            '</worksheet>';
    }

    /**
     * Tworzy zawartość binarną karty kompletacji zamówienia B2B (.xlsx).
     */
    public static function createPackingSheetWorkbook(array $items, array $meta = []): string
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException("Rozszerzenie PHP ZipArchive nie jest aktywne.");
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_pack_');
        $zip = new ZipArchive();
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Nie można utworzyć pliku tymczasowego dla archiwum XLSX.");
        }

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
            '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
            '</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
            '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<bookViews><workbookView xWindow="0" yWindow="0" windowWidth="20480" windowHeight="10240"/></bookViews>' .
            '<sheets>' .
            '<sheet name="Karta Kompletacji B2B" sheetId="1" r:id="rId1"/>' .
            '</sheets>' .
            '</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // 5. xl/styles.xml
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<fonts count="3">' .
            '<font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>' .
            '<font><b/><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>' .
            '<font><b/><sz val="12"/><color rgb="FF1E3A8A"/><name val="Calibri"/><family val="2"/></font>' .
            '</fonts>' .
            '<fills count="4">' .
            '<fill><patternFill patternType="none"/></fill>' .
            '<fill><patternFill patternType="gray125"/></fill>' .
            '<fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/></patternFill></fill>' .
            '<fill><patternFill patternType="solid"><fgColor rgb="FFE2E8F0"/></patternFill></fill>' .
            '</fills>' .
            '<borders count="2">' .
            '<border><left/><right/><top/><bottom/><diagonal/></border>' .
            '<border><left style="thin"><color rgb="FFCBD5E1"/></left><right style="thin"><color rgb="FFCBD5E1"/></right><top style="thin"><color rgb="FFCBD5E1"/></top><bottom style="thin"><color rgb="FFCBD5E1"/></bottom></border>' .
            '</borders>' .
            '<cellXfs count="4">' .
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"/>' . // 0 = standard
            '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>' . // 1 = header (bold, gray fill)
            '<xf numFmtId="0" fontId="1" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"/>' . // 2 = bold total
            '<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>' . // 3 = title (12pt blue)
            '</cellXfs>' .
            '</styleSheet>';
        $zip->addFromString('xl/styles.xml', $styles);

        // 6. xl/worksheets/sheet1.xml
        $sheetXml = self::buildPackingSheetXml($items, $meta);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

        $zip->close();

        $content = file_get_contents($tempFile);
        @unlink($tempFile);

        return $content;
    }

    /**
     * Buduje XML arkusza karty kompletacji magazynowej B2B z kolumną checkboxów dla rampy.
     */
    private static function buildPackingSheetXml(array $items, array $meta): string
    {
        $rowsXml = '';
        $rowNum = 1;

        // Tytuł
        $rowsXml .= '<row r="' . $rowNum . '">';
        $rowsXml .= self::inlineStringCell('A' . $rowNum, 'HURTOWNIA MAGDY — KARTA KOMPLETACJI TOWARU NA RAMPĘ', 3);
        $rowsXml .= '</row>';
        $rowNum++;

        // Metadane
        $orderNum = !empty($meta['order_number']) ? $meta['order_number'] : 'B2B';
        $clientName = !empty($meta['client_name']) ? $meta['client_name'] : ($meta['company_name'] ?? '');
        $clientPhone = !empty($meta['client_phone']) ? $meta['client_phone'] : ($meta['phone'] ?? '');
        $address = !empty($meta['delivery_address']) ? $meta['delivery_address'] : '';
        $createdAt = !empty($meta['created_at']) ? $meta['created_at'] : date('Y-m-d H:i');
        $notes = !empty($meta['notes']) ? $meta['notes'] : '';

        $rowsXml .= '<row r="' . $rowNum . '">';
        $rowsXml .= self::inlineStringCell('A' . $rowNum, 'Nr zamówienia: ' . $orderNum . ' | Data: ' . $createdAt, 1);
        $rowsXml .= '</row>';
        $rowNum++;

        $rowsXml .= '<row r="' . $rowNum . '">';
        $rowsXml .= self::inlineStringCell('A' . $rowNum, 'Odbiorca: ' . $clientName . ($clientPhone !== '' ? ' (tel: ' . $clientPhone . ')' : ''), 0);
        $rowsXml .= '</row>';
        $rowNum++;

        if ($address !== '') {
            $rowsXml .= '<row r="' . $rowNum . '">';
            $rowsXml .= self::inlineStringCell('A' . $rowNum, 'Adres dostawy: ' . $address, 0);
            $rowsXml .= '</row>';
            $rowNum++;
        }

        if ($notes !== '') {
            $rowsXml .= '<row r="' . $rowNum . '">';
            $rowsXml .= self::inlineStringCell('A' . $rowNum, 'Uwagi do zamówienia: ' . $notes, 0);
            $rowsXml .= '</row>';
            $rowNum++;
        }

        // Pusty wiersz przed tabelą
        $rowNum++;

        // Nagłówek tabeli
        $headerRow = $rowNum;
        $rowsXml .= '<row r="' . $headerRow . '">';
        $rowsXml .= self::inlineStringCell('A' . $headerRow, 'Lp.', 1);
        $rowsXml .= self::inlineStringCell('B' . $headerRow, 'Nazwa towaru', 1);
        $rowsXml .= self::inlineStringCell('C' . $headerRow, 'Ilość', 1);
        $rowsXml .= self::inlineStringCell('D' . $headerRow, 'Jm.', 1);
        $rowsXml .= self::inlineStringCell('E' . $headerRow, 'Rozbicie logistyczne (opakowania)', 1);
        $rowsXml .= self::inlineStringCell('F' . $headerRow, 'Cena jedn.', 1);
        $rowsXml .= self::inlineStringCell('G' . $headerRow, 'Wartość', 1);
        $rowsXml .= self::inlineStringCell('H' . $headerRow, 'Skompletowano [ ]', 1);
        $rowsXml .= '</row>';
        $rowNum++;

        $totalAmount = 0.0;
        $itemIndex = 1;

        foreach ($items as $item) {
            $qty = (float)($item['quantity'] ?? 0);
            if ($qty <= 0) continue;

            $name = (string)($item['product_name'] ?? $item['name'] ?? 'Towar');
            $price = (float)($item['price'] ?? 0);
            $unit = (string)($item['unit'] ?? 'kg');
            $pkgSummary = (string)($item['package_summary'] ?? '-');
            $total = (float)($item['item_total'] ?? round($qty * $price, 2));
            $totalAmount += $total;

            $rowsXml .= '<row r="' . $rowNum . '">';
            $rowsXml .= self::numberCell('A' . $rowNum, $itemIndex++);
            $rowsXml .= self::inlineStringCell('B' . $rowNum, $name);
            $rowsXml .= self::numberCell('C' . $rowNum, $qty);
            $rowsXml .= self::inlineStringCell('D' . $rowNum, $unit);
            $rowsXml .= self::inlineStringCell('E' . $rowNum, $pkgSummary);
            $rowsXml .= self::numberCell('F' . $rowNum, $price);
            $rowsXml .= self::numberCell('G' . $rowNum, $total);
            $rowsXml .= self::inlineStringCell('H' . $rowNum, '[    ]'); // Pusty checkbox dla rampy
            $rowsXml .= '</row>';
            $rowNum++;
        }

        // Wiersz podsumowania
        $rowsXml .= '<row r="' . $rowNum . '">';
        $rowsXml .= self::inlineStringCell('A' . $rowNum, '', 2);
        $rowsXml .= self::inlineStringCell('B' . $rowNum, 'ŁĄCZNA WARTOŚĆ:', 2);
        $rowsXml .= self::inlineStringCell('C' . $rowNum, '', 2);
        $rowsXml .= self::inlineStringCell('D' . $rowNum, '', 2);
        $rowsXml .= self::inlineStringCell('E' . $rowNum, '', 2);
        $rowsXml .= self::inlineStringCell('F' . $rowNum, '', 2);
        $rowsXml .= self::numberCell('G' . $rowNum, round($totalAmount, 2), 2);
        $rowsXml .= self::inlineStringCell('H' . $rowNum, '', 2);
        $rowsXml .= '</row>';
        $rowNum += 2;

        // Podpis
        $rowsXml .= '<row r="' . $rowNum . '">';
        $rowsXml .= self::inlineStringCell('B' . $rowNum, 'Wydano z rampy: .......................................', 0);
        $rowsXml .= self::inlineStringCell('E' . $rowNum, 'Odebrano towar: .......................................', 0);
        $rowsXml .= '</row>';

        // Definicja szerokości kolumn (8 kolumn)
        $colsXml = '<cols>' .
            '<col min="1" max="1" width="6" customWidth="1"/>' .
            '<col min="2" max="2" width="36" customWidth="1"/>' .
            '<col min="3" max="3" width="10" customWidth="1"/>' .
            '<col min="4" max="4" width="8" customWidth="1"/>' .
            '<col min="5" max="5" width="30" customWidth="1"/>' .
            '<col min="6" max="6" width="14" customWidth="1"/>' .
            '<col min="7" max="7" width="14" customWidth="1"/>' .
            '<col min="8" max="8" width="18" customWidth="1"/>' .
            '</cols>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            $colsXml .
            '<sheetData>' . $rowsXml . '</sheetData>' .
            '</worksheet>';
    }

    private static function inlineStringCell(string $ref, string $value, int $style = 0): string
    {
        $safeVal = htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
        return '<c r="' . $ref . '" t="inlineStr" s="' . $style . '"><is><t>' . $safeVal . '</t></is></c>';
    }

    private static function numberCell(string $ref, float|int $value, int $style = 0): string
    {
        return '<c r="' . $ref . '" s="' . $style . '"><v>' . $value . '</v></c>';
    }
}
