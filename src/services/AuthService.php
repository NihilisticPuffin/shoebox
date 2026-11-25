<?php
declare(strict_types=1);

final class AuthService {
  private const DUMMY_HASH = '$2y$10$fdw8WgLKUz4pE0SUNmU6ye7BH0NOwfxMDHaZKGTD5VxADA4azbffi';
  private ?array $user = null;
  private bool $loaded = false;

  public function __construct(private UserRepository $users) {}

  public function user(): ?array {
    if (!$this->loaded) {
      $this->loaded = true;
      $id = (int) ($_SESSION['uid'] ?? 0);
      $this->user = $id > 0 ? $this->users->findById($id) : null;
      if ($id > 0 && $this->user === null) {
        unset($_SESSION['uid']);
      }
    }
    return $this->user;
  }
  public function validateRegistration(string $username, string $email, string $password, string $confirm): array {
    $errors = [];
    if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
      $errors['username'] = 'err_username_format';
    } elseif ($this->users->usernameTaken($username)) {
      $errors['username'] = 'err_username_taken';
    }
    if ($email !== '') {
      if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $errors['email'] = 'err_email_invalid';
      } elseif ($this->users->emailTaken($email)) {
        $errors['email'] = 'err_email_taken';
      }
    }
    if (strlen($password) < 8) {
      $errors['password'] = 'err_password_short';
    } elseif ($password !== $confirm) {
      $errors['password_confirm'] = 'err_password_mismatch';
    }
    return $errors;
  }

  public function register(string $username, string $email, string $password): int {
    $id = $this->users->create($username, $email !== '' ? $email : null, password_hash($password, PASSWORD_DEFAULT));
    $this->login($id);
    return $id;
  }

  public function attempt(string $login, string $password): bool {
    $row = $this->users->findByLogin($login);
    $hash = $row['passhash'] ?? self::DUMMY_HASH;
    $ok = password_verify($password, $hash);
    if ($row === null || !$ok) {
      return false;
    }
    $this->login((int) $row['id']);
    return true;
  }

  public function login(int $userId): void {
    session_regenerate_id(true);
    $_SESSION['uid'] = $userId;
    $this->loaded = false;
  }

  public function logout(): void {
    unset($_SESSION['uid']);
    session_regenerate_id(true);
    $this->user = null;
    $this->loaded = true;
  }
}
