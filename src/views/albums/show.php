<article class="album">
  <header class="album-head stack">
    <div class="cluster">
      <h1><?= e($album['title']) ?></h1>
      <?php if ($album['visibility'] !== 'public'): ?>
        <span class="badge badge-warning"><?= e($t->get('vis_' . $album['visibility'])) ?></span>
      <?php endif; ?>
    </div>

    <p class="text-muted">
      <?= e($t->get('by')) ?> <a href="<?= e(profile_url($album['username'])) ?>"><?= e($album['username']) ?></a>
      · <?= e(time_ago($album['created'])) ?>
      · <?= e((int) $album['image_count'] === 1 ? $t->get('image_1') : $t->get('images_n', ['n' => $album['image_count']])) ?>
      · <?= e((int) $album['views'] === 1 ? $t->get('view_1') : $t->get('views_n', ['n' => $album['views']])) ?>
    </p>

    <?php if ($album['description'] !== ''): ?>
      <p class="album-description"><?= nl2br(e($album['description'])) ?></p>
    <?php endif; ?>

    <div class="cluster">
      <form method="post" action="<?= e(album_url($album)) ?>/favorite">
        <?= csrf_field() ?>
        <button class="button<?= $isFavorited ? ' button-primary' : '' ?>" type="submit">
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-heart"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg> <?= e($isFavorited ? $t->get('unfavorite') : $t->get('favorite')) ?> · <?= e($album['fav_count']) ?>
        </button>
      </form>
      <?php if ($isOwner): ?>
        <a class="button" href="<?= e(album_url($album)) ?>/edit"><?= e($t->get('edit')) ?></a>
        <details class="confirm">
          <summary class="button button-danger"><?= e($t->get('delete')) ?></summary>
          <form class="confirm-body panel" method="post" action="<?= e(album_url($album)) ?>/delete">
            <?= csrf_field() ?>
            <p><?= e($t->get('confirm_delete_album')) ?></p>
            <button class="button button-danger" type="submit"><?= e($t->get('confirm_yes_delete')) ?></button>
          </form>
        </details>
      <?php endif; ?>
    </div>
  </header>

  <ol class="album-images">
    <?php foreach ($images as $img): ?>
      <li class="album-image">
        <a href="<?= e(media_url('i', $img['filename'])) ?>" target="_blank" rel="noopener" title="<?= e($t->get('open_original')) ?>">
          <img loading="lazy" width="<?= e($img['width']) ?>" height="<?= e($img['height']) ?>" src="<?= e(media_url('i', $img['filename'])) ?>" alt="<?= e($img['caption'] !== '' ? $img['caption'] : $album['title']) ?>">
        </a>
        <?php if ($img['caption'] !== ''): ?>
          <p class="album-image-caption"><?= nl2br(e($img['caption'])) ?></p>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
</article>
