<?php
/**
 * Lekki parser arkuszy Excel (.xlsx) bez zewnętrznych zależności.
 * Wykorzystuje wbudowane w PHP rozszerzenia ZipArchive oraz SimpleXML.
 *
 * Plik .xlsx jest archiwum ZIP ze strukturą XML. Grafiki i logotypy
 * znajdują się w osobnym podkatalogu (xl/drawings/), dzięki czemu
 * odczyt wyłącznie komórek arkusza sheet1.xml automatycznie pomija
 * wszelkie grafiki, banery i dekoracje.
 */
class XlsxParser
{
    private string $filePath;
    private array $sharedStrings = [];
    private array $rows = [];
    private bool $parsed = false;

    public function __construct(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("Plik nie istnieje: " . $filePath);
        }
        $this->filePath = $filePath;
    }

    public static function open(string $filePath): self
    {
        return new self($filePath);
    }

    /**
     * Konwertuje oznaczenie kolumny (np. "A", "B", "AA") na indeks numeryczny (0-based: 0, 1, 26...).
     */
    public static function columnLetterToIndex(string $letters): int
    {
        $letters = strtoupper(trim($letters));
        $len = strlen($letters);
        $index = 0;
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }
        return $index - 1;
    }

    /**
     * Konwertuje indeks numeryczny (0-based) na literę kolumny (0 -> "A", 1 -> "B").
     */
    public static function indexToColumnLetter(int $index): string
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr(($index % 26) + 65) . $letter;
            $index = intdiv($index, 26) - 1;
        }
        return $letter;
    }

    /**
     * Wczytuje i parsuje zawartość pliku XLSX.
     */
    public function parse(): self
    {
        if ($this->parsed) {
            return $this;
        }

        if (!class_exists('ZipArchive')) {
            throw new RuntimeException("Rozszerzenie PHP ZipArchive nie jest aktywne.");
        }

        $zip = new ZipArchive();
        $res = $zip->open($this->filePath);
        if ($res !== true) {
            throw new RuntimeException("Nie można otworzyć pliku XLSX jako archiwum ZIP (kod błędu: $res).");
        }

        // 1. Wczytanie sharedStrings.xml (współdzielona baza tekstów)
        $this->sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $this->parseSharedStrings($sharedStringsXml);
        }

        // 2. Znalezienie głównego arkusza (domyślnie sheet1.xml lub z workbook.xml)
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            // Spróbuj znaleźć pierwszy dostępny arkusz w xl/worksheets/
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('#^xl/worksheets/sheet\d+\.xml$#i', $name)) {
                    $sheetXml = $zip->getFromName($name);
                    break;
                }
            }
        }

        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException("Nie znaleziono arkusza danych w pliku XLSX.");
        }

        // 3. Parsowanie arkusza i komórek
        $this->parseSheet($sheetXml);
        $this->parsed = true;

        return $this;
    }

    /**
     * Parsowanie tabeli tekstów współdzielonych.
     */
    private function parseSharedStrings(string $xmlContent): void
    {
        $xml = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
        if (!$xml) {
            return;
        }

        foreach ($xml->si as $si) {
            if (isset($si->t)) {
                $this->sharedStrings[] = (string)$si->t;
            } elseif (isset($si->r)) {
                // Sformatowane ciągi tekstowe z fragmentami <r><t>...</t></r>
                $str = '';
                foreach ($si->r as $r) {
                    $str .= (string)$r->t;
                }
                $this->sharedStrings[] = $str;
            } else {
                $this->sharedStrings[] = '';
            }
        }
    }

    /**
     * Parsowanie komórek arkusza z SimpleXML.
     */
    private function parseSheet(string $xmlContent): void
    {
        $xml = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
        if (!$xml || !isset($xml->sheetData)) {
            $this->rows = [];
            return;
        }

        $parsedRows = [];

        foreach ($xml->sheetData->row as $rowNode) {
            $rowNum = (int)$rowNode['r'];
            $rowCells = [];

            foreach ($rowNode->c as $cell) {
                $ref = (string)$cell['r']; // np. "A1", "C4"
                preg_match('#^([A-Z]+)(\d+)$#', $ref, $matches);
                if (!isset($matches[1])) {
                    continue;
                }

                $colLetters = $matches[1];
                $colIndex = self::columnLetterToIndex($colLetters);

                $type = (string)$cell['t'];
                $val = '';

                if ($type === 's') {
                    // Współdzielony string po indeksie
                    $sIndex = (int)$cell->v;
                    $val = $this->sharedStrings[$sIndex] ?? '';
                } elseif ($type === 'inlineStr' && isset($cell->is->t)) {
                    $val = (string)$cell->is->t;
                } elseif (isset($cell->v)) {
                    $val = (string)$cell->v;
                }

                $rowCells[$colIndex] = trim($val);
            }

            // Sprawdź, czy wiersz nie jest całkowicie pusty
            $hasContent = false;
            foreach ($rowCells as $c) {
                if ($c !== '') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent) {
                $parsedRows[$rowNum] = $rowCells;
            }
        }

        ksort($parsedRows);
        $this->rows = $parsedRows;
    }

    /**
     * Zwraca pierwsze N wierszy zawierających dane (jako tablica numerowana [numer_wiersza => kolumny]).
     */
    public function getRawRows(int $limit = 30): array
    {
        $this->parse();
        $result = [];
        $count = 0;

        foreach ($this->rows as $rowNum => $cols) {
            $result[$rowNum] = $cols;
            $count++;
            if ($count >= $limit) {
                break;
            }
        }

        return $result;
    }

    /**
     * Zwraca wszystkie sparsowane wiersze.
     */
    public function getAllRows(): array
    {
        $this->parse();
        return $this->rows;
    }

    /**
     * Analizuje pierwsze wiersze w celu automatycznego zasugerowania:
     * - indeksu wiersza nagłówkowego
     * - kolumny nazwy towaru
     * - kolumny ceny
     * - kolumny jednostki
     */
    public function detectCandidateColumns(array $previewRows): array
    {
        $productKeywords = ['towar', 'produkt', 'asortyment', 'nazwa', 'artykuł', 'warzywo', 'owoc', 'opis'];
        $priceKeywords   = ['cena', 'cena netto', 'cena brutto', 'cena hurtowa', 'zł', 'pln', 'stawka'];
        $unitKeywords    = ['jm', 'jedn', 'jednostka', 'kg', 'szt', 'opakowanie', 'op'];

        $bestHeaderRow = null;
        $bestProductCol = null;
        $bestPriceCol = null;
        $bestUnitCol = null;
        $maxScore = 0;

        foreach ($previewRows as $rowNum => $cols) {
            $score = 0;
            $foundProd = null;
            $foundPrice = null;
            $foundUnit = null;

            foreach ($cols as $colIndex => $val) {
                $valLower = mb_strtolower(trim($val), 'UTF-8');
                if ($valLower === '') {
                    continue;
                }

                foreach ($productKeywords as $kw) {
                    if (str_contains($valLower, $kw)) {
                        $foundProd = $colIndex;
                        $score += 3;
                        break;
                    }
                }

                foreach ($priceKeywords as $kw) {
                    if (str_contains($valLower, $kw)) {
                        $foundPrice = $colIndex;
                        $score += 3;
                        break;
                    }
                }

                foreach ($unitKeywords as $kw) {
                    if (str_contains($valLower, $kw)) {
                        $foundUnit = $colIndex;
                        $score += 2;
                        break;
                    }
                }
            }

            if ($score > $maxScore && ($foundProd !== null || $foundPrice !== null)) {
                $maxScore = $score;
                $bestHeaderRow = $rowNum;
                $bestProductCol = $foundProd;
                $bestPriceCol = $foundPrice;
                $bestUnitCol = $foundUnit;
            }
        }

        // Jeśli nie znaleziono nagłówków po słowach kluczowych, weź pierwszy wiersz
        if ($bestHeaderRow === null && !empty($previewRows)) {
            $firstKey = array_key_first($previewRows);
            $bestHeaderRow = $firstKey;
            $cols = array_keys($previewRows[$firstKey]);
            $bestProductCol = $cols[0] ?? 0;
            $bestPriceCol = $cols[1] ?? 1;
        }

        return [
            'headerRow'  => $bestHeaderRow,
            'productCol' => $bestProductCol ?? 0,
            'priceCol'   => $bestPriceCol ?? 1,
            'unitCol'    => $bestUnitCol,
        ];
    }

    /**
     * Konwertuje ciąg znaków ceny (np. "4,50 zł", "12.30 PLN", "  3,20 ") na float.
     */
    public static function normalizePrice(string $priceStr): float
    {
        $clean = trim($priceStr);
        // Usuń waluty i jednostki
        $clean = preg_replace('/[^\d.,]/', '', $clean);
        // Zamień przecinek na kropkę
        $clean = str_replace(',', '.', $clean);

        // Jeśli jest więcej niż jedna kropka (np. 1.250.00), zachowaj tylko ostatnią
        if (substr_count($clean, '.') > 1) {
            $parts = explode('.', $clean);
            $decimals = array_pop($parts);
            $clean = implode('', $parts) . '.' . $decimals;
        }

        return round((float)$clean, 2);
    }

    /**
     * Wyciąga oczyszczoną listę produktów na podstawie wskazanego mapowania.
     */
    public function extractProducts(int $headerRowIndex, int $productColIndex, int $priceColIndex, ?int $unitColIndex = null): array
    {
        $this->parse();
        $products = [];
        $index = 1;

        foreach ($this->rows as $rowNum => $cols) {
            // Pomijamy wiersz nagłówkowy oraz wiersze przed nim
            if ($rowNum <= $headerRowIndex) {
                continue;
            }

            $rawName  = trim($cols[$productColIndex] ?? '');
            $rawPrice = trim($cols[$priceColIndex] ?? '');
            $rawUnit  = $unitColIndex !== null ? trim($cols[$unitColIndex] ?? '') : '';

            // Pomijamy wiersze bez nazwy towaru
            if ($rawName === '') {
                continue;
            }

            // Pomijamy powtórzenia nagłówków
            $nameLower = mb_strtolower($rawName, 'UTF-8');
            if ($nameLower === 'towar' || $nameLower === 'produkt' || $nameLower === 'nazwa' || $nameLower === 'asortyment') {
                continue;
            }

            // Pomijamy wiersze podsumowań (np. Suma, Razem, Łączna wartość)
            if (str_starts_with($nameLower, 'suma') || str_starts_with($nameLower, 'razem') || str_starts_with($nameLower, 'łączn') || str_starts_with($nameLower, 'laczn')) {
                continue;
            }

            $price = self::normalizePrice($rawPrice);
            $unit = 'kg'; // Domyślna jednostka dla warzyw i owoców

            if ($rawUnit !== '') {
                $uLower = mb_strtolower($rawUnit, 'UTF-8');
                if (str_contains($uLower, 'szt')) {
                    $unit = 'szt.';
                } elseif (str_contains($uLower, 'op') || str_contains($uLower, 'kart')) {
                    $unit = 'op.';
                } elseif (str_contains($uLower, 'pęcz') || str_contains($uLower, 'pecz')) {
                    $unit = 'pęczek';
                } else {
                    $unit = 'kg';
                }
            }

            $products[] = [
                'id'       => $index++,
                'name'     => $rawName,
                'price'    => $price,
                'unit'     => $unit,
                'raw_unit' => $rawUnit,
                'row_num'  => $rowNum,
            ];
        }

        return $products;
    }
}
