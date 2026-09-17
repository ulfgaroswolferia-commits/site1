<?php
/**
 * Bazowy kontroler rdzenia. Kontrolery aplikacji dziedziczą zwykle po AppController
 * (program/script/), który dokłada warstwę sesji, CSRF i autoryzacji.
 *
 * Konwencja: metoda akcji ustawia $this->outputData i zwraca nazwę szablonu widoku.
 */
class Controller
{
    /** Dane przekazywane do widoku — w szablonie dostępne jako $view['klucz']. */
    public $outputData = [];

    /** Nazwa pliku layoutu z views/layout/ (bez .php). Pusty string = render bez layoutu. */
    public $layout = 'main';

    public function getData(): array
    {
        return $this->outputData;
    }

    public function getLayout(): string
    {
        return (string) $this->layout;
    }

    /** Ustawia pojedynczą wartość dla widoku. */
    protected function set(string $key, $value): void
    {
        $this->outputData[$key] = $value;
    }

    /**
     * Renderuje dowolny szablon do stringa (np. fragment, treść maila).
     * $template — ścieżka względem views/, bez .php, np. 'mail/_order'.
     */
    public function render(string $template, array $data = []): string
    {
        return (new View($data, BASE_PATH . '/views/' . $template . '.php'))->render();
    }
}
