<?php
/* · <a href="/contact"><?= e($t->get('contact')) ?></a> */
$page_title = isset($title) && $title !== '' ? $title : $t->get('site_name');
if (!str_contains($page_title, $t->get('site_name'))) {
  $page_title .= ' · ' . $t->get('site_name');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?></title>
  <link rel="stylesheet" href="/css/shoebox.css">
</head>
<body>
  <?php require ROOT . '/src/views/atoms/topbar.php'; ?>

  <main class="page">
    <?php foreach ($flashes as $f): ?>
      <div class="alert alert-<?= e($f['type'] === 'error' ? 'danger' : $f['type']) ?> flash" role="status"><?= e($f['message']) ?></div>
    <?php endforeach; ?>

    <?= $content ?>
  </main>

  <footer class="footer text-muted">
    <?= e($t->get('site_name')) ?> · v1.0.0 · <a href="/gallery"><?= e($t->get('explore')) ?></a>
  </footer>
</body>
</html>