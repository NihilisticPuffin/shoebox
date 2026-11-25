<?php

final class Migrator {
  public static function run(PDO $pdo): array {
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
      version TEXT PRIMARY KEY,
      applied INTEGER NOT NULL DEFAULT (unixepoch())
    )");
    
    $applied = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    $files = glob(ROOT . '/src/database/migrations/*.sql') ?: [];
    sort($files);
    
    $ran = [];
    foreach ($files as $file) {
      $version = basename($file);
      if (in_array($version, $applied, true)) {
        continue;
      }
      $pdo->beginTransaction();
      try {
        $pdo->exec((string) file_get_contents($file));
        $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)')->execute([$version]);
        $pdo->commit();
      } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
      }
      $ran[] = $version;
    }
    return $ran;
  }
}
