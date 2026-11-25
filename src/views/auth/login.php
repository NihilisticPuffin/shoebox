<section class="panel narrow">
  <h1 class="panel-title"><?= e($t->get('login')) ?></h1>
  <form class="panel-body stack" method="post" action="/login">
    <?= csrf_field() ?>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>

    <div class="field">
      <label class="label" for="login"><?= e($t->get('login_id')) ?></label>
      <input class="input" id="login" name="login" autocomplete="username" required autofocus value="<?= e($old['login'] ?? '') ?>">
    </div>
    <div class="field">
      <label class="label" for="password"><?= e($t->get('password')) ?></label>
      <input class="input" id="password" name="password" type="password" autocomplete="current-password" required>
    </div>

    <button class="button button-primary" type="submit"><?= e($t->get('login')) ?></button>
    <p class="text-muted"><?= e($t->get('need_account')) ?> <a href="/register"><?= e($t->get('register')) ?></a></p>
  </form>
</section>