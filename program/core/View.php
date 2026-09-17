<?php
/**
 * Renderer szablonów PHP. Szablon dostaje dane jako tablicę $view.
 *
 * Wewnątrz szablonu:
 *   Tools::h($view['title'])   — zawsze escapuj dane wejściowe przed wypisaniem
 *   $view['content']           — w layoucie: już wyrenderowany widok (nie escapować)
 */
class View
{
    private $dataContent;
    private $viewFile;

    public function __construct(array $dataContent, string $viewFile)
    {
        $this->dataContent = $dataContent;
        $this->viewFile    = $viewFile;
    }

    public function render(): string
    {
        if ($this->viewFile === '') {
            return '';
        }

        if (!is_file($this->viewFile)) {
            error_log('View: brak szablonu ' . $this->viewFile);
            if (defined('APP_ENV') && APP_ENV === 'development') {
                return '<pre style="color:#b00">Brak szablonu: '
                    . htmlspecialchars($this->viewFile, ENT_QUOTES, 'UTF-8') . '</pre>';
            }
            return '';
        }

        $view = &$this->dataContent;

        ob_start();
        include $this->viewFile;
        return ob_get_clean();
    }
}
