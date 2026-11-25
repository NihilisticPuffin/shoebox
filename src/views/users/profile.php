<header class="profile-head cluster">
  <span class="avatar avatar-lg"><?= e(strtoupper(mb_substr($profile['username'], 0, 1))) ?></span>
  <div class="stack" style="gap:2px">
    <h1><?= e($profile['username']) ?></h1>
    <span class="text-muted">
      <?= e($t->get('joined', ['when' => gmdate('M j, Y', (int) $profile['created'])])) ?>
      · <?= e($total === 1 ? $t->get('album_1') : $t->get('albums_n', ['n' => $total])) ?>
    </span>
  </div>
  <?php if ($isSelf): ?>
    <a class="button button-primary topbar-end" href="/new"><?= e($t->get('new_album')) ?></a>
  <?php endif; ?>
</header>

<section class="section">
  <?php $emptyMessage = $t->get('no_albums_profile'); require ROOT . '/src/views/atoms/album_grid.php'; ?>
  <?php require ROOT . '/src/views/atoms/pagination.php'; ?>
</section>
