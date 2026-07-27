<?php
namespace App\Core;

/**
 * Plain-PHP template renderer. Views live in app/Views and receive $data keys
 * as local variables. A view is normally wrapped in a layout that echoes
 * $content.
 */
class View
{
    private string $viewPath;

    public function __construct()
    {
        $this->viewPath = dirname(__DIR__) . '/Views';
    }

    public function render(string $view, array $data = [], ?string $layout = 'layouts/app'): string
    {
        $content = $this->partial($view, $data);
        if ($layout === null) {
            return $content;
        }
        return $this->partial($layout, array_merge($data, ['content' => $content]));
    }

    /** Render a view file without a layout and return its output. */
    public function partial(string $view, array $data = []): string
    {
        $file = $this->viewPath . '/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$view} ({$file})");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return ob_get_clean();
    }
}
