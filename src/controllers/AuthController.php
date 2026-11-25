<?php
declare(strict_types=1);

final class AuthController {
  public function __construct(private AuthService $auth) {}

  public function showRegister(): void {
    $this->guestOnly();
    view('auth/register', ['title' => t('register'), 'old' => [], 'errors' => []]);
  }

  public function register(): void {
    $this->guestOnly();
    verify_csrf();

    $username = post_str('username');
    $email = post_str('email');
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirm'] ?? '');

    $errors = $this->auth->validateRegistration($username, $email, $password, $confirm);
    if ($errors) {
      view('auth/register', [
        'title'  => t('register'),
        'old'    => ['username' => $username, 'email' => $email],
        'errors' => $errors,
      ], 422);
      return;
    }

    $this->auth->register($username, $email, $password);
    flash('success', t('flash_welcome', ['name' => $username]));
    redirect('/');
  }

  public function showLogin(): void {
    $this->guestOnly();
    view('auth/login', ['title' => t('login'), 'old' => [], 'error' => null]);
  }

  public function login(): void {
    $this->guestOnly();
    verify_csrf();

    $login = post_str('login');
    $password = (string) ($_POST['password'] ?? '');

    if (!$this->auth->attempt($login, $password)) {
      view('auth/login', ['title' => t('login'), 'old' => ['login' => $login], 'error' => t('err_login_failed')], 422);
      return;
    }

    $next = safe_next($_SESSION['next'] ?? null);
    unset($_SESSION['next']);
    redirect($next);
  }

  public function logout(): void {
    verify_csrf();
    $this->auth->logout();
    flash('info', t('flash_logged_out'));
    redirect('/');
  }

  private function guestOnly(): void {
    if (current_user() !== null) {
      redirect('/');
    }
  }
}
