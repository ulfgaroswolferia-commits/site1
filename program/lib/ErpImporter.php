<?php
/**
 * Biblioteka parsera plików importu asortymentu z systemów ERP:
 * - Subiekt GT / Nexo (format EPP / EDI++ – Windows-1250)
 * - Comarch ERP Optima (format XML UTF-8)
 * - Symfonia Handel (format TXT Windows-1250)
 * - Asseco WAPRO Wf-Mag (format XML UTF-8)
 *
 * Każdy parser zwraca tablicę produktów:
 *   [['name' => string, 'price' => float, 'unit' => string, 'erp_code' => string], ...]
 */
class ErpImporter
{
    /** Maksymalna cena akceptowana podczas importu (filtr śmieci) */
    private const MAX_PRICE = 100000.0;

    /**
     * Główny punkt wejścia.
     *
     * @param string $format  'subiekt' | 'optima' | 'symfonia' | 'wfmag'
     * @param string $content Zawartość pliku (binarna / UTF-8)
     * @return array          Znormalizowane produkty gotowe do saveProductsBatch()
     * @throws InvalidArgumentException Gdy format nieobsługiwany
     * @throws RuntimeException         Gdy parsowanie się nie powiedzie
     */
    public static function import(string $format, string $content): array
    {
        $fmt = strtolower(trim($format));

        switch ($fmt) {
            case 'subiekt':
            case 'epp':
                return self::parseSubiektEpp($content);

            case 'optima':
            case 'comarch':
                return self::parseComarchOptimaXml($content);

            case 'symfonia':
                return self::parseSymfoniaTxt($content);

            case 'wfmag':
            case 'wapro':
                return self::parseWfMagXml($content);

            default:
                throw new InvalidArgumentException("Nieobsługiwany format importu ERP: {$format}");
        }
    }

    /**
     * Wykrywa format na podstawie zawartości pliku (magia bajtów / sygnatura).
     * Używane gdy użytkownik nie wybrał formatu.
     */
    public static function detectFormat(string $content): ?string
    {
        // Konwersja do UTF-8 do analizy
        $utf = @iconv('WINDOWS-1250', 'UTF-8//TRANSLIT//IGNORE', $content) ?: $content;

        $utf = self::ensureUtf8($content);

        // XML – sprawdź korzeń
        if (strpos($utf, '<?xml') !== false) {
            if (strpos($utf, 'WAPRO_MAG') !== false || strpos($utf, 'DOKUMENTY_MAGAZYNOWE') !== false) {
                return 'wfmag';
            }
            if (strpos($utf, 'comarch.pl') !== false || strpos($utf, 'ZAMOWIENIA_OD_ODBIORCY') !== false) {
                return 'optima';
            }
            // Próba ogólna XML
            return 'optima';
        }

        // Subiekt EPP – zawiera sekcję [INFO] lub [TOWARY]
        if (strpos($utf, '[INFO]') !== false || strpos($utf, '[TOWARY]') !== false) {
            return 'subiekt';
        }

        // Symfonia – INFO { lub Dokument {
        if (strpos($utf, 'INFO {') !== false || strpos($utf, 'Dokument {') !== false
            || strpos($utf, 'format = "SymfoniaHandel"') !== false) {
            return 'symfonia';
        }

        return null;
    }

    // =========================================================================
    // 1. SUBIEKT GT / NEXO (EPP / EDI++)
    // =========================================================================

    public static function parseSubiektEpp(string $raw): array
    {
        // Subiekt standardowo używa Windows-1250, ale nowsze wersje/eksporty mogą być UTF-8
        $content = self::ensureUtf8($raw);
        $lines   = preg_split('/\r?\n/', $content);

        $products    = [];
        $inTowary    = false;
        $inNaglowek  = false;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            // Zmiana sekcji
            if (preg_match('/^\[([A-Z_]+)\]$/', $line, $m)) {
                $section    = $m[1];
                $inTowary   = ($section === 'TOWARY');
                $inNaglowek = ($section === 'NAGLOWEK');
                continue;
            }

            if (!$inTowary) continue;

            // Format wiersza towaru EPP:
            // id,"symbol","nazwa","nazwa fiskalna","jm","pkwiu",stawka_vat,cena_netto,cena_brutto,"barcode"
            $fields = self::parseCsvLine($line);
            if (count($fields) < 5) continue;

            $code       = trim($fields[1] ?? '', " \t\"");
            $name       = trim($fields[2] ?? '', " \t\"");
            $unit       = trim($fields[4] ?? 'kg', " \t\"");
            $rawPrice   = trim($fields[7] ?? '0', " \t\"");
            $priceNetto = (float)str_replace([' ', ','], ['', '.'], $rawPrice);

            if ($name === '' || $priceNetto < 0 || $priceNetto > self::MAX_PRICE) continue;

            $products[] = self::normalize($name, $priceNetto, $unit, $code);
        }

        return $products;
    }

    // =========================================================================
    // 2. COMARCH ERP OPTIMA (XML OPT021)
    // =========================================================================

    public static function parseComarchOptimaXml(string $raw): array
    {
        $content = self::ensureUtf8($raw);

        // Usuń domyślne przestrzenie nazw — SimpleXML XPath nie radzi sobie
        // z domyślnym xmlns bez rejestracji prefixu (xpath('//POZYCJA') zwróciłoby 0).
        $content = preg_replace('/\sxmlns(?::\w+)?="[^"]*"/', '', $content);

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        if ($xml === false) {
            throw new RuntimeException('Nie udało się sparsować XML Optima: ' . self::xmlErrors());
        }

        $products = [];

        // Próba sekcji słownika towarów (eksport zawiera TOWARY/TOWAR lub ARTYKULY/ARTYKUL)
        $towaryNodes = $xml->xpath('//TOWAR | //ARTYKUL | //towar | //artykul');
        if (!empty($towaryNodes)) {
            foreach ($towaryNodes as $t) {
                $code  = (string)($t->KOD ?? $t->SYMBOL ?? $t->INDEKS ?? $t->kod ?? $t->symbol ?? '');
                $name  = (string)($t->NAZWA ?? $t->NAZWA1 ?? $t->nazwa ?? '');
                $unit  = (string)($t->JEDNOSTKA ?? $t->JM ?? $t->jednostka ?? 'kg');
                $rawPrice = '0';
                if (!empty($t->CENA_NETTO)) {
                    $rawPrice = (string)$t->CENA_NETTO;
                } elseif (!empty($t->cena_netto)) {
                    $rawPrice = (string)$t->cena_netto;
                } elseif (isset($t->CENY->CENA->NETTO)) {
                    $rawPrice = (string)$t->CENY->CENA->NETTO;
                } elseif (isset($t->ceny->cena->netto)) {
                    $rawPrice = (string)$t->ceny->cena->netto;
                } elseif (isset($t->CENY->CENA) && count($t->CENY->CENA->children()) === 0) {
                    $rawPrice = (string)$t->CENY->CENA;
                } elseif (isset($t->CENA) && count($t->CENA->children()) === 0) {
                    $rawPrice = (string)$t->CENA;
                } elseif (isset($t->cena) && count($t->cena->children()) === 0) {
                    $rawPrice = (string)$t->cena;
                }
                $price = (float)str_replace([' ', ','], ['', '.'], trim($rawPrice, " \t\""));
                if ($name === '') continue;
                $products[] = self::normalize($name, $price, $unit, $code);
            }
            if (!empty($products)) return $products;
        }

        // Fallback: wyciągaj towary z pozycji zamówień (deduplikacja po kodzie)
        $seen = [];
        $pozycjeNodes = $xml->xpath('//POZYCJA | //pozycja');
        foreach ($pozycjeNodes as $p) {
            $code  = (string)($p->TOWAR_KOD ?? $p->KOD ?? $p->INDEKS ?? $p->towar_kod ?? '');
            $name  = (string)($p->TOWAR_NAZWA ?? $p->NAZWA ?? $p->towar_nazwa ?? '');
            $unit  = (string)($p->JEDNOSTKA ?? $p->JM ?? $p->jednostka ?? 'kg');
            $rawPrice = '0';
            if (!empty($p->CENA_NETTO)) {
                $rawPrice = (string)$p->CENA_NETTO;
            } elseif (!empty($p->cena_netto)) {
                $rawPrice = (string)$p->cena_netto;
            } elseif (!empty($p->CENA_KATALOGOWA)) {
                $rawPrice = (string)$p->CENA_KATALOGOWA;
            } elseif (!empty($p->cena_katalogowa)) {
                $rawPrice = (string)$p->cena_katalogowa;
            } elseif (isset($p->CENA) && count($p->CENA->children()) === 0) {
                $rawPrice = (string)$p->CENA;
            } elseif (isset($p->cena) && count($p->cena->children()) === 0) {
                $rawPrice = (string)$p->cena;
            }
            $price = (float)str_replace([' ', ','], ['', '.'], trim($rawPrice, " \t\""));
            if ($name === '') continue;
            $key = $code ?: $name;
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $products[] = self::normalize($name, $price, $unit, $code);
        }

        return $products;
    }

    // =========================================================================
    // 3. SYMFONIA HANDEL (Format 3.0 HMF)
    // =========================================================================

    public static function parseSymfoniaTxt(string $raw): array
    {
        $content = self::ensureUtf8($raw);
        $lines   = preg_split('/\r?\n/', $content);

        $products   = [];
        $seen       = [];
        $inPozycja  = false;
        $curName    = '';
        $curCode    = '';
        $curUnit    = 'kg';
        $curPrice   = 0.0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            // Blok Pozycja { ... }
            if (preg_match('/^Pozycja\s*\{/i', $line)) {
                $inPozycja = true;
                $curName = $curCode = '';
                $curUnit = 'kg';
                $curPrice = 0.0;
                continue;
            }

            if ($line === '}' && $inPozycja) {
                $inPozycja = false;
                if ($curName !== '') {
                    $key = $curCode ?: $curName;
                    if (!isset($seen[$key])) {
                        $seen[$key] = true;
                        $products[] = self::normalize($curName, $curPrice, $curUnit, $curCode);
                    }
                }
                continue;
            }

            if (!$inPozycja) continue;

            // Pary klucz = wartość
            if (preg_match('/^(\w+)\s*=\s*(.+)$/', $line, $m)) {
                $key = strtolower($m[1]);
                $val = trim($m[2], " \t\"");
                switch ($key) {
                    case 'kod':    $curCode  = $val; break;
                    case 'nazwa':  $curName  = $val; break;
                    case 'jm':     $curUnit  = $val; break;
                    case 'cena':   $curPrice = (float)str_replace([' ', ','], ['', '.'], $val); break;
                }
            }
        }

        // Jeśli nie znaleziono przez Pozycja{}, szukaj przez Dokument{} wzorzec starszy
        if (empty($products)) {
            $products = self::parseSymfoniaLegacy($lines);
        }

        return $products;
    }

    /** Alternatywny parser Symfonii — starsze pliki bez bloku Pozycja{} */
    private static function parseSymfoniaLegacy(array $lines): array
    {
        $products = [];
        $seen     = [];

        foreach ($lines as $line) {
            $line = trim($line);
            // Linie towarów: kod;nazwa;jm;cena_netto lub tabulatorami
            if (substr_count($line, ';') >= 2) {
                $parts = explode(';', $line);
            } elseif (substr_count($line, "\t") >= 2) {
                $parts = explode("\t", $line);
            } else {
                continue;
            }

            $code  = trim($parts[0] ?? '');
            $name  = trim($parts[1] ?? '');
            $unit  = trim($parts[2] ?? 'kg');
            $price = (float)str_replace([' ', ','], ['', '.'], trim($parts[3] ?? '0', " \t\""));

            if ($name === '' || $price < 0 || $price > self::MAX_PRICE) continue;
            $key = $code ?: $name;
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $products[] = self::normalize($name, $price, $unit, $code);
        }

        return $products;
    }

    // =========================================================================
    // 4. ASSECO WAPRO WF-MAG (XML)
    // =========================================================================

    public static function parseWfMagXml(string $raw): array
    {
        $content = self::ensureUtf8($raw);
        $content = preg_replace('/\sxmlns(?::\w+)?="[^"]*"/', '', $content);

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        if ($xml === false) {
            throw new RuntimeException('Nie udało się sparsować XML Wf-Mag: ' . self::xmlErrors());
        }

        $products = [];
        $seen     = [];

        // Obsługa zarówno zamówień (POZYCJA), jak i słowników kartotek (ARTYKUL, TOWAR)
        $nodes = $xml->xpath('//POZYCJA | //ARTYKUL | //TOWAR | //pozycja | //artykul | //towar');
        foreach ($nodes as $p) {
            $code  = (string)($p->INDEKS ?? $p->INDEKS_KATALOGOWY ?? $p->INDEKS_HANDLOWY ?? $p->KOD ?? $p->SYMBOL ?? '');
            $name  = (string)($p->NAZWA ?? $p->NAZWA_ARTYKULU ?? $p->NAZWA1 ?? '');
            $unit  = (string)($p->JEDNOSTKA ?? $p->JM ?? 'kg');
            $price = (float)str_replace([' ', ','], ['', '.'], trim((string)($p->CENA_NETTO ?? $p->CENA_SPRZEDAZY_NETTO ?? $p->CENA ?? '0'), " \t\""));

            if ($name === '') continue;
            $key = $code ?: $name;
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $products[] = self::normalize($name, $price, $unit, $code);
        }

        return $products;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Normalizuje produkt do formatu zgodnego z saveProductsBatch().
     */
    private static function normalize(string $name, float $price, string $unit, string $code): array
    {
        $name  = mb_substr(trim($name), 0, 200, 'UTF-8');
        $unit  = self::normalizeUnit($unit);
        $price = max(0.0, min($price, self::MAX_PRICE));
        $code  = mb_substr(trim($code), 0, 50, 'UTF-8');

        return [
            'name'     => $name,
            'price'    => round($price, 2),
            'unit'     => $unit,
            'erp_code' => $code,
        ];
    }

    /**
     * Normalizuje jednostki miary do wartości akceptowanych przez system.
     */
    public static function normalizeUnit(string $raw): string
    {
        $u = mb_strtolower(trim(str_replace('.', '', $raw)), 'UTF-8');
        if (in_array($u, ['szt', 'sztuka', 'sztuki', 'pcs', 'pc'], true)) return 'szt.';
        if (in_array($u, ['op', 'opak', 'kart', 'skrz', 'karton', 'skrzynka', 'klatka'], true)) return 'op.';
        if (in_array($u, ['peczek', 'pęczek', 'pecz', 'bunch'], true)) return 'pęczek';
        return 'kg'; // domyślnie
    }

    /**
     * Prosta tokenizacja linii CSV z cudzysłowami (zgodna z EPP).
     */
    private static function parseCsvLine(string $line): array
    {
        $fields = [];
        $i      = 0;
        $len    = strlen($line);

        while ($i < $len) {
            if ($line[$i] === '"') {
                // Pole w cudzysłowie
                $i++;
                $field = '';
                while ($i < $len) {
                    if ($line[$i] === '"') {
                        if ($i + 1 < $len && $line[$i + 1] === '"') {
                            $field .= '"';
                            $i += 2;
                        } else {
                            $i++;
                            break;
                        }
                    } else {
                        $field .= $line[$i++];
                    }
                }
                $fields[] = $field;
                if ($i < $len && $line[$i] === ',') $i++;
            } else {
                // Pole bez cudzysłowów — do następnego przecinka
                $end = strpos($line, ',', $i);
                if ($end === false) {
                    $fields[] = substr($line, $i);
                    break;
                }
                $fields[] = substr($line, $i, $end - $i);
                $i = $end + 1;
            }
        }

        return $fields;
    }

    /**
     * Zapewnia UTF-8 — usuwa BOM oraz konwertuje z Windows-1250 jeśli potrzeba.
     */
    private static function ensureUtf8(string $raw): string
    {
        // Usuń ewentualny UTF-8 BOM
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        if (mb_detect_encoding($raw, 'UTF-8', true) === 'UTF-8') return $raw;
        return @iconv('WINDOWS-1250', 'UTF-8//TRANSLIT//IGNORE', $raw) ?: $raw;
    }

    /**
     * Formatuje błędy libxml do jednego stringa.
     */
    private static function xmlErrors(): string
    {
        $msgs = array_map(fn($e) => trim($e->message), libxml_get_errors());
        libxml_clear_errors();
        return implode('; ', $msgs);
    }
}
