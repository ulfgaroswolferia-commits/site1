<?php
/**
 * Cienki wrapper na PDO. Konfiguracja w program/config/data.php (DSN, DBLOGIN, DBPASS).
 *
 * Użycie w modelu:  $this->db->dbh->prepare(...)  albo  $this->pdo()->prepare(...)
 *
 * Połączenie jest współdzielone w obrębie żądania — każdy `new Db()` zwraca ten sam
 * uchwyt PDO, więc tworzenie wielu modeli nie otwiera wielu połączeń.
 */
class Db
{
    /** @var PDO */
    public $dbh;

    /** @var PDO|null Współdzielony uchwyt na czas żądania. */
    private static $shared = null;

    public function __construct()
    {
        if (self::$shared === null) {
            $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';

            self::$shared = new \PDO(DSN, DBLOGIN, DBPASS, [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                // Prawdziwe prepared statements po stronie serwera zamiast emulacji.
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            self::$shared->exec('SET NAMES ' . $charset);
        }

        $this->dbh = self::$shared;
    }

    public function getDbh(): \PDO
    {
        return $this->dbh;
    }
}
