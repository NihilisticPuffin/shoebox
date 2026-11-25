<a class="album-card" href="<?= e(album_url($album)) ?>">
  <span class="album-card-cover">
    <?php if ($album['cover_thumb']): ?>
      <img loading="lazy" width="400" height="400" src="<?= e(media_url('t', $album['cover_thumb'])) ?>" alt="">
    <?php endif; ?>
    <span class="badge album-card-count"><?= e($album['image_count']) ?></span>
    <?php if ($album['visibility'] !== 'public'): ?>
      <span class="badge badge-warning album-card-vis"><?= e($t->get('vis_' . $album['visibility'])) ?></span>
    <?php endif; ?>
  </span>
  <span class="album-card-body">
    <strong class="album-card-title"><?= e($album['title']) ?></strong>
    <span class="text-muted album-card-meta cluster" style="gap: 4px;"><?= e($album['username']) ?> · <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-heart"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg> <?= e($album['fav_count']) ?> · <?= e($album['views']) ?> <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg></span>
  </span>
</a>
