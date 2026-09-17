<?php
/**
 * Pomocnik paginacji — budowa listy numerów stron i obliczanie offsetu.
 */
class PaginationHelper
{
    /**
     * Lista stron do wyświetlenia, skrócona elipsami przy dużej liczbie stron
     * (np. 1 … 4 5 [6] 7 8 … 43).
     *
     * Zawsze pokazuje $edge pierwszych i ostatnich stron oraz $around stron
     * przed i po bieżącej.
     *
     * @return array lista elementów: int (numer strony) lub null (placeholder "…")
     */
    public static function pages(int $current, int $total, int $edge = 1, int $around = 2): array
    {
        if ($total <= 1) {
            return [1];
        }

        $keep = [];
        for ($i = 1; $i <= $total; $i++) {
            if ($i <= $edge || $i > $total - $edge || abs($i - $current) <= $around) {
                $keep[] = $i;
            }
        }

        $pages = [];
        $prev  = null;
        foreach ($keep as $p) {
            if ($prev !== null && $p - $prev > 1) {
                $pages[] = null;
            }
            $pages[] = $p;
            $prev = $p;
        }

        return $pages;
    }

    /** Bieżący numer strony z $_GET, przycięty do zakresu 1..$totalPages. */
    public static function currentPage(array $get, int $totalPages = PHP_INT_MAX, string $key = 'page'): int
    {
        $page = (int) ($get[$key] ?? 1);
        return max(1, min($page, max(1, $totalPages)));
    }

    /** Offset do klauzuli LIMIT. */
    public static function offset(int $page, int $perPage): int
    {
        return max(0, ($page - 1) * $perPage);
    }

    /** Liczba stron dla podanej liczby rekordów. */
    public static function totalPages(int $totalRows, int $perPage): int
    {
        return $perPage > 0 ? max(1, (int) ceil($totalRows / $perPage)) : 1;
    }
}
