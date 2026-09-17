<?php
/**
 * Bazowy model. Otwiera połączenie PDO w konstruktorze ($this->db->dbh).
 *
 * Modele aplikacji żyją w program/model/ i mają namespace APP_NAMESPACE:
 *
 *   namespace App;
 *   class User extends \Model { ... }
 *
 * Zapytania ZAWSZE przez prepared statements — nigdy konkatenacja wartości do SQL.
 */
class Model
{
    /** @var Db */
    protected $db;

    public function __construct()
    {
        $this->db = new Db();
    }

    /** Skrót do uchwytu PDO. */
    protected function pdo(): \PDO
    {
        return $this->db->dbh;
    }

    /** Pozostawia w tablicy tylko klucze z białej listy kolumn (ochrona przed mass assignment). */
    protected function filterColumns(array $data, array $allowed): array
    {
        return array_intersect_key($data, array_flip($allowed));
    }

    /** Generyczny INSERT oparty o prepared statement. Zwraca ID wstawionego wiersza. */
    protected function insertRow(string $table, array $data): string
    {
        if (empty($data)) {
            return '0';
        }

        $columns      = array_keys($data);
        $placeholders = array_map(function ($c) { return ':' . $c; }, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->pdo()->prepare($sql)->execute($data);

        return $this->pdo()->lastInsertId();
    }

    /**
     * Generyczny UPDATE oparty o prepared statement. Zwraca liczbę zmienionych wierszy.
     * Placeholdery warunku WHERE dostają prefiks w_, żeby nie kolidować z kolumnami SET.
     */
    protected function updateRow(string $table, array $data, array $where): int
    {
        if (empty($data) || empty($where)) {
            return 0;
        }

        $setParts = [];
        foreach (array_keys($data) as $col) {
            $setParts[] = $col . ' = :' . $col;
        }

        $whereParts = [];
        $params     = $data;
        foreach ($where as $col => $value) {
            $whereParts[]        = $col . ' = :w_' . $col;
            $params['w_' . $col] = $value;
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $setParts),
            implode(' AND ', $whereParts)
        );

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }
}
