<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int,array{method:string,pattern:string,handler:array,middlewares:array}> */
    private array $routes = [];

    public function add(string $method, string $pattern, array $handler, array $middlewares = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function get(string $p, array $h, array $mw = []): void { $this->add('GET', $p, $h, $mw); }
    public function post(string $p, array $h, array $mw = []): void { $this->add('POST', $p, $h, $mw); }

    public function dispatch(Request $req): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $req->method && !($route['method'] === 'GET' && $req->method === 'HEAD')) {
                continue;
            }
            $params = [];
            if ($this->match($route['pattern'], $req->path, $params)) {
                // run middlewares
                foreach ($route['middlewares'] as $mwClass) {
                    $mw = new $mwClass();
                    $mw->handle($req);
                }
                [$class, $method] = $route['handler'];
                $controller = new $class();
                $controller->{$method}($req, $params);
                return;
            }
        }
        $this->notFound();
    }

    private function match(string $pattern, string $path, array &$params): bool
    {
        $regex = '#^' . preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        if (preg_match($regex, $path, $m)) {
            foreach ($m as $k => $v) {
                if (!is_int($k)) $params[$k] = $v;
            }
            return true;
        }
        return false;
    }

    private function notFound(): void
    {
        http_response_code(404);
        if (is_file(__DIR__ . '/../../templates/errors/404.php')) {
            View::render('errors/404');
        } else {
            echo '<h1>404 Not Found</h1>';
        }
        exit;
    }
}
