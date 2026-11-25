<?php
if ($pages > 1):
  $window = array_unique(array_filter(
    array_merge([1, $pages], range(max(1, $page - 2), min($pages, $page + 2))),
    static fn($n) => $n >= 1 && $n <= $pages
  ));
  sort($window);
?>
<nav aria-label="Pagination" style="display: flex; justify-content: center;">
  <ul class="pagination">
    <li><a class="pagination-link" href="<?= e(query_url(['page' => $page - 1 > 1 ? $page - 1 : null])) ?>"<?= $page <= 1 ? ' aria-disabled="true"' : '' ?>>←</a></li>
    <?php $prev = 0; foreach ($window as $n): ?>
      <?php if ($n - $prev > 1): ?><li class="pagination-gap">…</li><?php endif; ?>
      <li><a class="pagination-link" href="<?= e(query_url(['page' => $n > 1 ? $n : null])) ?>"<?= $n === $page ? ' aria-current="page"' : '' ?>><?= $n ?></a></li>
      <?php $prev = $n; ?>
    <?php endforeach; ?>
    <li><a class="pagination-link" href="<?= e(query_url(['page' => $page + 1])) ?>"<?= $page >= $pages ? ' aria-disabled="true"' : '' ?>>→</a></li>
  </ul>
</nav>
<?php endif; ?>
