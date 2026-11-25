<?php
declare(strict_types=1);

final class Router {
  private array $routes = [];

  public function add(string $method, string $path, callable $handler): void {
    $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path);
    $this->routes[] = [strtoupper($method), '#^' . $regex . '$#', $handler];
  }

  public function dispatch(string $method, string $uri): void {
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    $path = rtrim($path, '/') ?: '/';

    $method = strtoupper($method);
    if ($method === 'HEAD') {
      $method = 'GET';
    }

    $allowed = [];
    foreach ($this->routes as [$routeMethod, $regex, $handler]) {
      if (!preg_match($regex, $path, $m)) {
        continue;
      }
      if ($routeMethod !== $method) {
        $allowed[] = $routeMethod;
        continue;
      }
      $params = array_map('urldecode', array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY));
      $handler($params);
      return;
    }

    if ($allowed) {
      header('Allow: ' . implode(', ', array_unique($allowed)));
      $this->fail(405, 'Method not allowed');
    }
    $this->fail(404, 'Not found');
  }

  private function fail(int $status, string $message): never {
    if (function_exists('abort')) {
      abort($status, $message);
    }
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit;
  }
}