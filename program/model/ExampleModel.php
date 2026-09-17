<?php

namespace App;

/**
 * Wzorcowy model — skopiuj ten plik przy tworzeniu nowego modelu.
 *
 * Zasady (pełny opis w docs/MVC.md):
 *   - namespace zgodny z APP_NAMESPACE, klasa dziedziczy po \Model
 *   - CAŁY SQL projektu mieszka tutaj, nigdy w kontrolerze ani w widoku
 *   - wartości zawsze przez prepared statements
 *   - metoda zwraca czyste dane (tablice/skalary), nigdy HTML
 *   - nazwa pliku = nazwa klasy: App\ExampleModel -> program/model/ExampleModel.php
 */
class ExampleModel extends \Model
{
    /** Nazwa tabeli w jednym miejscu — łatwiej zmienić prefiks per projekt. */
    const TABLE = 'example';

    /** Kolumny, które wolno zapisać z danych wejściowych (ochrona przed mass assignment). */
    const FILLABLE = ['name', 'email', 'note'];

    /** Pojedynczy rekord po ID albo null. */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM ' . self::TABLE . ' WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** Strona listy rekordów. LIMIT/OFFSET jako int-y — nie da się ich bindować. */
    public function page(int $limit, int $offset): array
    {
        $sql = sprintf(
            'SELECT id, name, email FROM %s ORDER BY id DESC LIMIT %d OFFSET %d',
            self::TABLE,
            max(1, $limit),
            max(0, $offset)
        );

        return $this->pdo()->query($sql)->fetchAll();
    }

    public function countAll(): int
    {
        return (int) $this->pdo()->query('SELECT COUNT(*) FROM ' . self::TABLE)->fetchColumn();
    }

    /** Zapis nowego rekordu. Zwraca ID. */
    public function create(array $data): int
    {
        return (int) $this->insertRow(self::TABLE, $this->filterColumns($data, self::FILLABLE));
    }

    /** Aktualizacja rekordu. Zwraca liczbę zmienionych wierszy. */
    public function update(int $id, array $data): int
    {
        return $this->updateRow(self::TABLE, $this->filterColumns($data, self::FILLABLE), ['id' => $id]);
    }

    public function delete(int $id): int
    {
        $stmt = $this->pdo()->prepare('DELETE FROM ' . self::TABLE . ' WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount();
    }

    /**
     * Walidacja danych wejściowych — reguły biznesowe należą do modelu, nie do kontrolera.
     * Zwraca tablicę błędów: ['pole' => 'komunikat']. Pusta tablica = dane poprawne.
     */
    public function validate(array $data): array
    {
        $errors = [];

        if (trim($data['name'] ?? '') === '') {
            $errors['name'] = 'Nazwa jest wymagana.';
        }
        if (!filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Nieprawidłowy adres e-mail.';
        }

        return $errors;
    }
}
