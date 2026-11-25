<?php
declare(strict_types=1);

final class Connection {
  public static function create(): PDO {
    $PDO = new PDO('sqlite:' . ROOT . '/storage/shoebox.sqlite', options: [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $PDO->exec('PRAGMA foreign_keys = ON');
    $PDO->exec('PRAGMA busy_timeout = 3000');

    return $PDO;
  }
}
