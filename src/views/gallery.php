<div class="section-head">
  <h1><?= e($t->get('explore')) ?></h1>
  <span class="text-muted"><?= e($t->get('results_count', ['n' => $total])) ?></span>
</div>

<form class="explore-bar" method="get" action="/gallery">
  <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e($t->get('search_placeholder')) ?>" aria-label="<?= e($t->get('search')) ?>">
  <select class="input" name="sort" aria-label="Sort">
    <?php foreach (['trending' => 'trending', 'new' => 'newest', 'top' => 'top'] as $value => $key): ?>
      <option value="<?= e($value) ?>"<?= $sort === $value ? ' selected' : '' ?>><?= e($t->get($key)) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="button button-primary" type="submit"><?= e($t->get('search')) ?></button>
</form>

<?php $emptyMessage = $q !== '' ? $t->get('no_results') : $t->get('no_albums'); require ROOT . '/src/views/atoms/album_grid.php'; ?>
<?php require ROOT . '/src/views/atoms/pagination.php'; ?>
