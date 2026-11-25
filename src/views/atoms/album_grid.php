<?php if (!$albums): ?>
  <p class="text-muted empty"><?= e($emptyMessage ?? $t->get('no_albums')) ?></p>
<?php else: ?>
  <div class="album-grid">
    <?php foreach ($albums as $album): ?>
      <?php require ROOT . '/src/views/atoms/album_card.php'; ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
