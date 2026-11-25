<section class="hero panel">
  <div class="panel-body stack">
    <h1><?= e($t->get('hero_title')) ?></h1>
    <p class="text-muted"><?= e($t->get('hero_text')) ?></p>
    <div class="cluster">
      <a class="button button-primary" href="<?= $user ? '/new' : '/register' ?>"><?= e($t->get('start_album')) ?></a>
      <a class="button" href="/gallery"><?= e($t->get('browse_albums')) ?></a>
    </div>
  </div>
</section>

<?php if (!$trending && !$newest): ?>
  <p class="text-muted empty"><?= e($t->get('no_albums')) ?> <a href="<?= $user ? '/new' : '/register' ?>"><?= e($t->get('no_albums_cta')) ?></a></p>
<?php else: ?>
  <section class="section">
    <div class="section-head">
      <h2><?= e($t->get('trending')) ?></h2>
      <a href="/gallery?sort=trending"><?= e($t->get('see_all')) ?> →</a>
    </div>
    <?php $albums = $trending; require ROOT . '/src/views/atoms/album_grid.php'; ?>
  </section>

  <section class="section">
    <div class="section-head">
      <h2><?= e($t->get('newest')) ?></h2>
      <a href="/gallery?sort=new"><?= e($t->get('see_all')) ?> →</a>
    </div>
    <?php $albums = $newest; require ROOT . '/src/views/atoms/album_grid.php'; ?>
  </section>
<?php endif; ?>
