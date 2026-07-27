<?php
namespace App\Core;

/**
 * Minimal path-info router. Routes are registered as
 *   $r->get('admin/contracts', [ContractController::class, 'index']);
 *
 * URLs use PATH_INFO (e.g. /AIFIN/index.php/admin/contracts) so the app works
 * on any Apache/XAMPP setup without mod_rewrite. An optional .htaccess can hide
 * the "index.php/" segment.
 */
class Router
{
    /** @var array<string, array<string, array{0:string,1:string}>> */
    private array $routes = ['GET' => [], 'POST' => []];
    private string $base;

    public function __construct()
    {
        // Directory the front controller lives in, e.g. "/AIFIN".
        $this->base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    }

    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$this->norm($path)] = $handler;
    }

    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$this->norm($path)] = $handler;
    }

    private function norm(string $path): string
    {
        return trim($path, '/');
    }

    /** The current route path derived from PATH_INFO (falls back to ?r=). */
    public function currentPath(): string
    {
        $pi = $_SERVER['PATH_INFO'] ?? '';
        if ($pi === '' && isset($_GET['r'])) {
            $pi = $_GET['r'];
        }
        return $this->norm($pi);
    }

    public function base(): string
    {
        return $this->base;
    }

    /** Build an app URL from a route path. */
    public function url(string $path = ''): string
    {
        $path = $this->norm($path);
        return $this->base . '/index.php' . ($path === '' ? '' : '/' . $path);
    }

    /** Dispatch the current request; returns the controller's output. */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        $path = $this->currentPath();
        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            http_response_code(404);
            echo (new View())->render('errors/404', ['path' => $path], 'layouts/plain');
            return;
        }

        [$class, $action] = $handler;
        (new $class())->$action();
    }
}
