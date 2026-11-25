<header class="topbar">
  <a class="topbar-brand" href="/"><?= e($t->get('site_name')) ?></a>

  <nav>
    <ul class="cluster no-gap">
      <li><a class="topbar-link" href="/gallery"<?= is_current('/gallery') ? ' aria-current="page"' : '' ?>><?= e($t->get('explore')) ?></a></li>
      <?php if ($user): ?>
        <li><a class="topbar-link" href="/new"<?= is_current('/new') ? ' aria-current="page"' : '' ?>><?= e($t->get('new_album')) ?></a></li>
        <li><a class="topbar-link" href="/favorites"<?= is_current('/favorites') ? ' aria-current="page"' : '' ?>><?= e($t->get('favorites')) ?></a></li>
      <?php endif; ?>
    </ul>
  </nav>

  <div class="topbar-end cluster">
    <?php if ($user): ?>
      <a class="topbar-link" href="<?= e(profile_url($user['username'])) ?>"><?= e($user['username']) ?></a>
      <form method="post" action="/logout">
        <?= csrf_field() ?>
        <button class="button button-sm button-ghost" type="submit"><?= e($t->get('logout')) ?></button>
      </form>
    <?php else: ?>
      <a class="button button-sm button-ghost" href="/login"><?= e($t->get('login')) ?></a>
      <a class="button button-sm button-primary" href="/register"><?= e($t->get('register')) ?></a>
    <?php endif; ?>
  </div>
</header>
