<div class="section-head">
  <h1><?= e($t->get('favorites')) ?></h1>
</div>
<?php $emptyMessage = $t->get('no_favorites'); require ROOT . '/src/views/atoms/album_grid.php'; ?>
<?php require ROOT . '/src/views/atoms/pagination.php'; ?>
