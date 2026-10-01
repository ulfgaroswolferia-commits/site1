<?php
/**
 * Ograniczenie liczby nieudanych prób logowania (ochrona przed brute force).
 *
 * Liczniki trzymane są w pliku poza sesją — sesję atakujący może po prostu porzucić.
 * Klucz to zwykle adres IP. Po MAX_ATTEMPTS porażkach w oknie WINDOW sekund
 * kolejne próby są odrzucane aż do wygaśnięcia okna.
 *
 *   if (LoginThrottle::isBlocked($ip)) { ... komunikat ... }
 *   ok ? LoginThrottle::clear($ip) : LoginThrottle::hit($ip);
 */
class LoginThrottle
{
    public const MAX_ATTEMPTS = 5;
    public const WINDOW       = 900; // 15 minut

    /** Czy klucz przekroczył limit nieudanych prób w bieżącym oknie. */
    public static function isBlocked(string $key): bool
    {
        $data = self::read();
        $entry = $data[self::hash($key)] ?? null;
        return $entry !== null
            && $entry['count'] >= self::MAX_ATTEMPTS
            && (time() - $entry['first']) < self::WINDOW;
    }

    /** Zapisuje nieudaną próbę. */
    public static function hit(string $key): void
    {
        self::update(function (array $data) use ($key) {
            $h = self::hash($key);
            $now = time();
            if (!isset($data[$h]) || ($now - $data[$h]['first']) >= self::WINDOW) {
                $data[$h] = ['count' => 0, 'first' => $now];
            }
            $data[$h]['count']++;
            return $data;
        });
    }

    /** Czyści licznik po udanym logowaniu. */
    public static function clear(string $key): void
    {
        self::update(function (array $data) use ($key) {
            unset($data[self::hash($key)]);
            return $data;
        });
    }

    /** Klucz throttlingu dla bieżącego żądania (adres IP klienta). */
    public static function clientKey(): string
    {
        return 'ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'cli');
    }

    private static function file(): string
    {
        return BASE_PATH . '/storage/login_throttle.json';
    }

    private static function hash(string $key): string
    {
        return hash('sha256', $key);
    }

    private static function read(): array
    {
        $file = self::file();
        if (!is_file($file)) {
            return [];
        }
        $data = json_decode((string) @file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }

    /** Odczyt–modyfikacja–zapis pod blokadą pliku; przy okazji usuwa wygasłe wpisy. */
    private static function update(callable $fn): void
    {
        $dir = dirname(self::file());
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            error_log('LoginThrottle: brak katalogu ' . $dir);
            return;
        }

        $fh = @fopen(self::file(), 'c+');
        if ($fh === false) {
            error_log('LoginThrottle: nie można otworzyć ' . self::file());
            return;
        }

        try {
            flock($fh, LOCK_EX);
            $data = json_decode((string) stream_get_contents($fh), true);
            $data = is_array($data) ? $data : [];

            $now = time();
            foreach ($data as $h => $entry) {
                if (($now - (int) ($entry['first'] ?? 0)) >= self::WINDOW) {
                    unset($data[$h]);
                }
            }

            $data = $fn($data);

            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, json_encode($data));
            fflush($fh);
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }
}
