<?php
namespace App\Core;

/**
 * Base controller: view rendering, redirects, auth guards, flash messages.
 */
abstract class Controller
{
    protected View $view;

    public function __construct()
    {
        $this->view = new View();
    }

    protected function render(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        echo $this->view->render($view, $data, $layout);
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . url($path));
        exit;
    }

    protected function back(string $fallback = ''): void
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        if ($ref !== '') {
            header('Location: ' . $ref);
            exit;
        }
        $this->redirect($fallback);
    }

    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            $this->redirect('login');
        }
    }

    protected function requireAdmin(): void
    {
        $this->requireAuth();
        if (!Auth::isAdmin()) {
            http_response_code(403);
            exit('Forbidden — admin only.');
        }
    }

    protected function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }
}
