<?php

function e(string|int|float|null $s): string {
  return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

function t(string $key, array $replace = []): string {
  return $GLOBALS['t']->get($key, $replace);
}

function db(): PDO {
  return $GLOBALS['pdo'];
}

function current_user(): ?array {
  return $GLOBALS['auth']->user();
}

/* == Responses == */
function redirect(string $to, int $status = 302): never {
  header('Location: ' . $to, true, $status);
  exit;
}

function view(string $name, array $data = [], int $status = 200): void {
  http_response_code($status);
  $t = $GLOBALS['t'];
  $user = current_user();
  $flashes = pull_flashes();
  extract($data, EXTR_SKIP);

  ob_start();
  require ROOT . "/src/views/$name.php";
  $content = ob_get_clean();

  require ROOT . '/src/views/layout.php';
}

function abort(int $status, string $message = ''): never {
  $titles = [403 => 'Forbidden', 404 => 'Not found', 405 => 'Method not allowed', 419 => 'Session expired', 500 => 'Server error'];
  view('error', [
    'title'   => $titles[$status] ?? 'Error',
    'status'  => $status,
    'message' => $message !== '' ? $message : ($titles[$status] ?? 'Error'),
  ], $status);
  exit;
}

/* == Flash messages == */

function flash(string $type, string $message): void {
  $_SESSION['flashes'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array {
  $f = $_SESSION['flashes'] ?? [];
  unset($_SESSION['flashes']);
  return $f;
}

/* == CSRF == */

function csrf_token(): string {
  return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string {
  return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void {
  $sent = (string) ($_POST['_csrf'] ?? '');
  if ($sent === '' || !hash_equals(csrf_token(), $sent)) {
    abort(419, t('err_csrf'));
  }
}

/* == Auth guards == */

function require_login(): array {
  $user = current_user();
  if ($user === null) {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
      $_SESSION['next'] = $_SERVER['REQUEST_URI'] ?? '/';
    }
    flash('info', t('flash_login_required'));
    redirect('/login');
  }
  return $user;
}

function safe_next(?string $next): string {
  if ($next !== null && preg_match('#^/(?![/\\\\])#', $next)) {
    return $next;
  }
  return '/';
}

/* == Input == */

function post_str(string $key, int $max = 0): string {
  $v = trim((string) ($_POST[$key] ?? ''));
  return $max > 0 ? mb_substr($v, 0, $max) : $v;
}

/* == URLs == */

function album_url(array $album): string {
  return '/a/' . rawurlencode($album['slug']);
}

function media_url(string $kind, string $file): string {
  return '/media/' . $kind . '/' . rawurlencode($file);
}

function profile_url(string $username): string {
  return '/u/' . rawurlencode($username);
}

function query_url(array $overrides): string {
  $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
  $q = array_merge($_GET, $overrides);
  $q = array_filter($q, static fn($v) => $v !== null && $v !== '');
  return $path . ($q ? '?' . http_build_query($q) : '');
}

function is_current(string $prefix): bool {
  $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
  return $prefix === '/' ? $path === '/' : str_starts_with($path, $prefix);
}

/* == Formatting == */

function time_ago(int $ts): string {
  $diff = max(0, time() - (int) $ts);
  return match (true) {
    $diff < 60      => t('time_just_now'),
    $diff < 3600    => t('time_minutes', ['n' => intdiv($diff, 60)]),
    $diff < 86400   => t('time_hours',   ['n' => intdiv($diff, 3600)]),
    $diff < 2592000 => t('time_days',    ['n' => intdiv($diff, 86400)]),
    default         => gmdate('M j, Y', (int) $ts),
  };
}

function format_bytes(int $bytes): string {
  if ($bytes >= 1073741824) return round($bytes / 1048576, 1) . ' GB';
  if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
  if ($bytes >= 1024)    return round($bytes / 1024) . ' KB';
  return $bytes . ' B';
}
