<?php
/**
 * Szyfrowanie symetryczne AES-128-CBC — tokeny w linkach (np. link do dokumentu
 * w mailu) i dane w cookie.
 *
 * Klucz i IV pochodzą ze stałych CRYPT_KEY / CRYPT_IV w program/config/data.php.
 * KAŻDY projekt musi mieć własne wartości — wygeneruj:
 *   php -r "echo bin2hex(random_bytes(16));"
 *
 * UWAGA: stały IV oznacza, że ten sam tekst jawny daje zawsze ten sam szyfrogram.
 * To świadomy kompromis na rzecz deterministycznych, powtarzalnych tokenów w URL-ach.
 * Do haseł NIE używaj tej klasy — od tego jest password_hash() / password_verify().
 */
class Crypt
{
    const CIPHER = 'aes-128-cbc';

    private static function key(): string
    {
        return defined('CRYPT_KEY') ? CRYPT_KEY : '';
    }

    /** IV jako bajty (stała trzyma 32 znaki hex = 16 bajtów). */
    private static function iv(): string
    {
        $hex = defined('CRYPT_IV') ? CRYPT_IV : '';
        return @pack('H*', $hex);
    }

    /** Szyfruje string; wynik to bezpieczny w URL-u ciąg szesnastkowy. */
    public static function encrypt(string $data): string
    {
        $ciphertext = openssl_encrypt($data, self::CIPHER, self::key(), OPENSSL_RAW_DATA, self::iv());
        return bin2hex(base64_encode($ciphertext));
    }

    /** Odszyfrowuje ciąg zwrócony przez encrypt(). Zwraca '' przy niepoprawnych danych. */
    public static function decrypt(string $data): string
    {
        if ($data === '' || !ctype_xdigit($data) || strlen($data) % 2 !== 0) {
            return '';
        }

        $raw = base64_decode(hex2bin($data), true);
        if ($raw === false) {
            return '';
        }

        $plain = openssl_decrypt($raw, self::CIPHER, self::key(), OPENSSL_RAW_DATA, self::iv());

        return $plain === false ? '' : trim(rtrim($plain, "\0"));
    }

    /** Pakuje strukturę do stringa (base64 z serialize) — do zaszyfrowania przez encrypt(). */
    public static function serialize($data): string
    {
        return base64_encode(serialize($data));
    }

    /** Rozpakowuje string z serialize(). Blokuje deserializację obiektów. */
    public static function unserialize(string $data)
    {
        $raw = base64_decode($data, true);
        if ($raw === false) {
            return false;
        }
        return unserialize($raw, ['allowed_classes' => false]);
    }
}
