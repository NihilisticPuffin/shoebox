<?php
declare(strict_types=1);
define('ROOT', dirname(__DIR__));

if (PHP_SAPI === 'cli-server') {
  $requested = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
  if ($requested !== '/' && is_file(__DIR__ . $requested)) {
    return false;
  }
}

require_once ROOT . '/src/Config.php';
require_once ROOT . '/src/database/Connection.php';
require_once ROOT . '/src/database/Migrator.php';
foreach (['controllers', 'helpers', 'repositories', 'services'] as $dir) {
  foreach (glob(ROOT . "/src/$dir/*.php") as $file) {
    require_once $file;
  }
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

session_set_cookie_params([
  'httponly' => true,
  'samesite' => 'Lax',
  'secure'   => !empty($_SERVER['HTTPS']),
]);
session_cache_limiter('');
session_start();
header('Cache-Control: no-cache');

$lang_root = ROOT . '/src/languages/';
switch ($_SESSION['language'] ?? null) {
  case 'en-US':
    $lang = include $lang_root . 'en-US.php';
    break;
  default:
    $_SESSION["language"] = "en-US";
    $lang = include $lang_root . 'en-US.php';
    break;
}
$t = new LanguageService($lang);

$freshDb = !is_file(ROOT . '/storage/shoebox.sqlite');
$pdo = Connection::create();
if ($freshDb) {
  Migrator::run($pdo);
}

$users        = new UserRepository($pdo);
$albums       = new AlbumRepository($pdo);
$images       = new ImageRepository($pdo);
$auth         = new AuthService($users);
$imageService = new ImageService();

$pages           = new PageController($albums, $users);
$authController  = new AuthController($auth);
$albumController = new AlbumController($pdo, $albums, $images, $imageService);
$media           = new MediaController($images);

$router = new Router();
$router->add('GET',  '/',                      [$pages, 'home']);
$router->add('GET',  '/gallery',               [$pages, 'explore']);
$router->add('GET',  '/favorites',             [$pages, 'favorites']);
$router->add('GET',  '/u/{username}',          [$pages, 'profile']);

$router->add('GET',  '/register',              [$authController, 'showRegister']);
$router->add('POST', '/register',              [$authController, 'register']);
$router->add('GET',  '/login',                 [$authController, 'showLogin']);
$router->add('POST', '/login',                 [$authController, 'login']);
$router->add('POST', '/logout',                [$authController, 'logout']);

$router->add('GET',  '/new',                   [$albumController, 'showNew']);
$router->add('POST', '/new',                   [$albumController, 'create']);
$router->add('GET',  '/a/{slug}',              [$albumController, 'show']);
$router->add('GET',  '/a/{slug}/edit',         [$albumController, 'showEdit']);
$router->add('POST', '/a/{slug}/edit',         [$albumController, 'update']);
$router->add('GET',  '/a/{slug}/crop/{image}', [$albumController, 'showCrop']);
$router->add('POST', '/a/{slug}/crop/{image}', [$albumController, 'crop']);
$router->add('POST', '/a/{slug}/delete',       [$albumController, 'delete']);
$router->add('POST', '/a/{slug}/favorite',     [$albumController, 'favorite']);

$router->add('GET',  '/media/{kind}/{file}',   [$media, 'serve']);

// $router->add('GET',  '/contact',                  [$contact, 'show']);
// $router->add('POST', '/contact',                  [$contact, 'send']);

// $router->add('GET',  '/admin',                    [$adminController, 'dashboard']);
// $router->add('GET',  '/admin/users',              [$adminController, 'users']);
// $router->add('POST', '/admin/users/{id}/role',    [$adminController, 'setRole']);
// $router->add('POST', '/admin/users/{id}/delete',  [$adminController, 'deleteUser']);
// $router->add('GET',  '/admin/albums',             [$adminController, 'albums']);
// $router->add('POST', '/admin/albums/{slug}/delete', [$adminController, 'deleteAlbum']);

// $router->add('GET',  '/admin/messages',           [$adminController, 'messages']);
// $router->add('GET',  '/admin/messages/{id}',      [$adminController, 'showMessage']);
// $router->add('POST', '/admin/messages/{id}/read', [$adminController, 'setMessageRead']);
// $router->add('POST', '/admin/messages/{id}/delete', [$adminController, 'deleteMessage']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
