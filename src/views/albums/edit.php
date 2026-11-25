<section class="panel form-page">
  <h1 class="panel-title"><?= e($t->get('edit_album')) ?></h1>
  <form class="panel-body stack" method="post" action="<?= e(album_url($album)) ?>/edit" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php require ROOT . '/src/views/atoms/album_fields.php'; ?>

    <div class="field">
      <span class="label"><?= e($t->get('manage_images')) ?></span>
      <?php $field = 'files'; require ROOT . '/src/views/atoms/field_error.php'; ?>
      <ul class="edit-images">
        <?php foreach ($images as $img):
          $isCover = $album['cover'] !== null ? (int) $album['cover'] === (int) $img['id'] : false; ?>
          <li class="edit-row">
            <img loading="lazy" width="72" height="72" src="<?= e(media_url('t', $img['thumb'])) ?>" alt="">
            <div class="field">
              <label class="label" for="caption-<?= e($img['id']) ?>"><?= e($t->get('caption')) ?></label>
              <input class="input input-sm" id="caption-<?= e($img['id']) ?>" name="caption[<?= e($img['id']) ?>]" maxlength="300" value="<?= e($img['caption']) ?>">
            </div>
            <div class="field edit-order">
              <label class="label" for="order-<?= e($img['id']) ?>"><?= e($t->get('order')) ?></label>
              <input class="input input-sm" id="order-<?= e($img['id']) ?>" name="order[<?= e($img['id']) ?>]" type="number" min="1" value="<?= e($img['position']) ?>">
            </div>
            <a class="button button-sm edit-crop" href="<?= e(album_url($album)) ?>/crop/<?= e($img['id']) ?>"><?= e($t->get('crop')) ?></a>
            <label class="edit-flag"><input type="radio" name="cover" value="<?= e($img['id']) ?>"<?= $isCover ? ' checked' : '' ?>> <?= e($t->get('cover')) ?></label>
            <label class="edit-flag edit-remove"><input type="checkbox" name="remove[]" value="<?= e($img['id']) ?>"> <?= e($t->get('remove')) ?></label>
          </li>
        <?php endforeach; ?>
      </ul>
      <span class="hint"><?= e($t->get('crop_save_first')) ?></span>
    </div>

    <div class="field">
      <label class="label" for="images"><?= e($t->get('add_images')) ?></label>
      <input class="input file-input" id="images" type="file" name="images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple aria-describedby="images-hint">
      <span class="hint" id="images-hint"><?= e($t->get('upload_hint', ['max' => MAX_FILES_PER_UPLOAD])) ?></span>
    </div>
    <?php require ROOT . '/src/views/atoms/file_errors.php'; ?>

    <div class="cluster">
      <button class="button button-primary" type="submit"><?= e($t->get('save_changes')) ?></button>
      <a class="button button-ghost" href="<?= e(album_url($album)) ?>"><?= e($t->get('cancel')) ?></a>
    </div>
  </form>
</section>
