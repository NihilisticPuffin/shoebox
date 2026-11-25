<?php
declare(strict_types=1);

final class UserRepository {
  public function __construct(private PDO $pdo) {}

  public function findById(int $id): ?array {
    return $this->one('SELECT * FROM users WHERE id = ?', [$id]);
  }

  public function findByUsername(string $username): ?array {
    return $this->one('SELECT * FROM users WHERE username = ?', [$username]);
  }

  public function findByLogin(string $login): ?array {
    return $this->one('SELECT * FROM users WHERE username = ? OR email = ?', [$login, $login]);
  }

  public function emailTaken(string $email): bool {
    return $this->one('SELECT 1 AS x FROM users WHERE email = ?', [$email]) !== null;
  }

  public function usernameTaken(string $username): bool {
    return $this->one('SELECT 1 AS x FROM users WHERE username = ?', [$username]) !== null;
  }

  public function create(string $username, ?string $email, string $hash): int {
    $st = $this->pdo->prepare('INSERT INTO users (username, email, passhash) VALUES (?, ?, ?)');
    $st->execute([$username, $email, $hash]);
    return (int) $this->pdo->lastInsertId();
  }

  public function setRole(int $id, string $role): void {
    $this->pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
  }

  public function delete(int $id): void {
    $this->pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
  }

  private function one(string $sql, array $args): ?array {
    $st = $this->pdo->prepare($sql);
    $st->execute($args);
    return $st->fetch() ?: null;
  }
}
