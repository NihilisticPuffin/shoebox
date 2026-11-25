<section class="panel narrow">
  <h1 class="panel-title"><?= e($t->get('register')) ?></h1>
  <form class="panel-body stack" method="post" action="/register">
    <?= csrf_field() ?>

    <div class="field">
      <label class="label" for="username"><?= e($t->get('username')) ?></label>
      <input class="input<?= isset($errors['username']) ? ' is-invalid' : '' ?>" id="username" name="username" autocomplete="username" required minlength="3" maxlength="20" pattern="[A-Za-z0-9_]+" value="<?= e($old['username'] ?? '') ?>">
      <?php $field = 'username'; require ROOT . '/src/views/atoms/field_error.php'; ?>
      <span class="hint"><?= e($t->get('username_hint')) ?></span>
    </div>
    <div class="field">
      <label class="label" for="email"><?= e($t->get('email')) ?> <span class="text-muted">(<?= e($t->get('optional')) ?>)</span></label>
      <input class="input<?= isset($errors['email']) ? ' is-invalid' : '' ?>" id="email" name="email" type="email" autocomplete="email" maxlength="254" value="<?= e($old['email'] ?? '') ?>">
      <?php $field = 'email'; require ROOT . '/src/views/atoms/field_error.php'; ?>
      <span class="hint"><?= e($t->get('email_optional_hint')) ?></span>
    </div>
    <div class="field">
      <label class="label" for="password"><?= e($t->get('password')) ?></label>
      <input class="input<?= isset($errors['password']) ? ' is-invalid' : '' ?>" id="password" name="password" type="password" autocomplete="new-password" required minlength="8">
      <?php $field = 'password'; require ROOT . '/src/views/atoms/field_error.php'; ?>
      <span class="hint"><?= e($t->get('password_hint')) ?></span>
    </div>
    <div class="field">
      <label class="label" for="password_confirm"><?= e($t->get('password_confirm')) ?></label>
      <input class="input<?= isset($errors['password_confirm']) ? ' is-invalid' : '' ?>" id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required>
      <?php $field = 'password_confirm'; require ROOT . '/src/views/atoms/field_error.php'; ?>
    </div>

    <button class="button button-primary" type="submit"><?= e($t->get('create_account')) ?></button>
    <p class="text-muted"><?= e($t->get('have_account')) ?> <a href="/login"><?= e($t->get('login')) ?></a></p>
  </form>
</section>
