#!/opt/homebrew/bin/php
<?php
declare(strict_types=1);
define('ROOT', dirname(__DIR__));

require ROOT . '/src/database/Connection.php';
require ROOT . '/src/database/Migrator.php';

$ran = Migrator::run(Connection::create());
echo $ran ? 'Applied: ' . implode(', ', $ran) . PHP_EOL : "Nothing to migrate." . PHP_EOL;
