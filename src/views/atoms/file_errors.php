<?php if (!empty($fileErrors)): ?>
  <div class="alert alert-warning" role="alert">
    <ul class="stack" style="gap:4px">
      <?php foreach ($fileErrors as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
