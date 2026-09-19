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
        $bestHeaderRow = null;
        $bestProductCol = null;
        $bestPriceCol = null;
        $bestUnitCol = null;
        $maxScore = 0;

        $totalKeywords = ['wartość', 'wartosc', 'kwota', 'razem', 'suma', 'łączna', 'laczna', 'ogółem', 'ogolem', 'zapłaty', 'zaplaty'];
        $productKeywords = ['towar', 'produkt', 'asortyment', 'nazwa', 'artykuł', 'artykul', 'warzywo', 'owoc', 'opis'];

        foreach ($previewRows as $rowNum => $cols) {
            $rowScore = 0;
            $foundProd = null;
            $prodScore = 0;
            $foundPrice = null;
            $priceScore = 0;
            $foundUnit = null;
            $unitScore = 0;

            foreach ($cols as $colIndex => $val) {
                $valClean = trim((string)$val);
                if ($valClean === '') {
                    continue;
                }
                $valLower = mb_strtolower($valClean, 'UTF-8');
                $valNoPunct = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $valLower);
                $words = array_filter(explode(' ', $valNoPunct));

                // 1. Sprawdź czy kolumna to podsumowanie / wartość łączna
                $isTotal = false;
                foreach ($totalKeywords as $tKw) {
                    if (str_contains($valLower, $tKw)) {
                        $isTotal = true;
                        break;
                    }
                }

                // 2. Detekcja kolumny ceny (wykluczamy kolumny wartości/sumy)
                $colIsPrice = false;
                if (!$isTotal) {
                    if (str_contains($valLower, 'cena') || str_contains($valLower, 'stawka') || str_contains($valLower, 'taryfa')) {
                        $colIsPrice = true;
                        if ($priceScore < 10) {
                            $foundPrice = $colIndex;
                            $priceScore = 10;
                        }
                    } elseif (in_array('zł', $words) || in_array('pln', $words)) {
                        if ($priceScore < 3) {
                            $foundPrice = $colIndex;
                            $priceScore = 3;
                        }
                    }
                }

                // 3. Detekcja kolumny jednostki (kolumny z ceną/wartością NIE MOGĄ być jednostką!)
                if (!$colIsPrice && !$isTotal && !str_contains($valLower, 'cena') && !str_contains($valLower, 'stawka')) {
                    $isUnitHeader = false;
                    // Precyzyjne nagłówki jednostki
                    if (str_contains($valLower, 'jednostka') || str_contains($valNoPunct, 'jm') || str_contains($valNoPunct, 'j m') || str_contains($valLower, 'miara') || in_array('uom', $words)) {
                        $isUnitHeader = true;
                        if ($unitScore < 10) {
                            $foundUnit = $colIndex;
                            $unitScore = 10;
                        }
                    } elseif (in_array('jedn', $words) || in_array('szt', $words) || in_array('kg', $words) || in_array('op', $words)) {
                        if ($unitScore < 5) {
                            $foundUnit = $colIndex;
                            $unitScore = 5;
                        }
                    }
                }

                // 4. Detekcja kolumny produktu
                if (!$colIsPrice && !$isTotal && $foundUnit !== $colIndex) {
                    foreach ($productKeywords as $pKw) {
                        if (str_contains($valLower, $pKw)) {
                            if ($prodScore < 10) {
                                $foundProd = $colIndex;
                                $prodScore = 10;
                            }
                            break;
                        }
                    }
                }
            }

            $rowScore = $prodScore + $priceScore + $unitScore;

            if ($rowScore > $maxScore && ($foundProd !== null || $foundPrice !== null)) {
                $maxScore = $rowScore;
                $bestHeaderRow  = $rowNum;
                $bestProductCol = $foundProd;
                $bestPriceCol   = $foundPrice;
                $bestUnitCol    = $foundUnit;
            }
        }

        // Jeśli nie znaleziono nagłówków po słowach kluczowych, weź pierwszy wiersz
        if ($bestHeaderRow === null && !empty($previewRows)) {
            $firstKey = array_key_first($previewRows);
            $bestHeaderRow = $firstKey;
            $cols = array_keys($previewRows[$firstKey]);
            $bestProductCol = $cols[0] ?? 0;
            $bestPriceCol   = $cols[1] ?? 1;
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
     * Inteligentnie określa jednostkę miary (szt. / pęczek / op. / kg)
     * na podstawie sparsowanej komórki jednostki lub nazwy towaru.
     */
    public static function detectProductUnit(string $productName, string $rawUnit = ''): string
    {
        // 1. Jeśli w kolumnie jednostki podano wartość, znormalizuj ją
        $cleanRaw = mb_strtolower(trim($rawUnit), 'UTF-8');
        if ($cleanRaw !== '') {
            if (str_contains($cleanRaw, 'szt') || str_contains($cleanRaw, 'don') || str_contains($cleanRaw, 'kpl')) {
                return 'szt.';
            }
            if (str_contains($cleanRaw, 'pęcz') || str_contains($cleanRaw, 'pecz')) {
                return 'pęczek';
            }
            if (str_contains($cleanRaw, 'op') || str_contains($cleanRaw, 'kart') || str_contains($cleanRaw, 'skrz') || str_contains($cleanRaw, 'klat') || str_contains($cleanRaw, 'wor') || str_contains($cleanRaw, 'tack') || str_contains($cleanRaw, 'wiad')) {
                return 'op.';
            }
            if (str_contains($cleanRaw, 'kg') || str_contains($cleanRaw, 'kilo')) {
                return 'kg';
            }
        }

        // 2. Jeśli brak kolumny jednostki lub jest pusta — wykryj z nazwy towaru
        $nameLower = mb_strtolower(trim($productName), 'UTF-8');

        // Warzywa i zioła pęczkowe
        $bunchKeywords = [
            'koperek', 'koper', 'szczypiorek', 'szczypior', 'natka', 'pietruszka nać', 'nać pietruszki',
            'rzodkiewka', 'mięta', 'mieta', 'bazylia', 'kolendra', 'rozmaryn', 'tymianek',
            'lubczyk', 'melisa', 'botwina', 'botwinka', 'rabarbar', 'pęczek', 'peczek'
        ];
        foreach ($bunchKeywords as $bKw) {
            if (str_contains($nameLower, $bKw)) {
                if (!preg_match('/\bkg\b/i', $nameLower) && !str_contains($nameLower, 'luzem')) {
                    return 'pęczek';
                }
            }
        }

        // Towary typowo sztukowe
        $pieceKeywords = [
            'sałata', 'salata', 'kalafior', 'brokuł', 'brokul', 'kapusta pekińska', 'kapusta pekinska',
            'kapusta młoda', 'kapusta mloda', 'kapusta stożkowa', 'kapusta stozkowa', 'kapusta biała szt',
            'kapusta czerwona szt', 'kapusta włoska', 'kapusta wloska', 'seler naciowy', 'por', 'czosnek',
            'kukurydza', 'arbuz', 'melon', 'ananas', 'mango', 'awokado', 'papaja', 'granat', 'kokos',
            'pomelo', 'ogórek szklarniowy', 'ogorek szklarniowy', 'ogórek długi', 'ogorek dlugi',
            'dynia hokkaido', 'dynia piżmowa', 'dynia pizmowa', 'bakłażan szt', 'baklazan szt',
            'cukinia szt', 'szt.', 'szt'
        ];
        foreach ($pieceKeywords as $pKw) {
            if (str_contains($nameLower, $pKw)) {
                if (!preg_match('/\bkg\b/i', $nameLower)) {
                    return 'szt.';
                }
            }
        }

        // Opakowania zbiorcze
        if (preg_match('/\b(op|op\.|opak|opak\.|karton|kart\.|worek|skrzynka|klatka|tacka)\b/u', $nameLower)) {
            return 'op.';
        }

        // Domyślna jednostka dla owoców i warzyw wagowych
        return 'kg';
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
            $unit  = self::detectProductUnit($rawName, $rawUnit);

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
