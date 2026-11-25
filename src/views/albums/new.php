<section class="panel form-page">
  <h1 class="panel-title"><?= e($t->get('new_album')) ?></h1>
  <form class="panel-body stack" method="post" action="/new" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php require ROOT . '/src/views/atoms/album_fields.php'; ?>

    <div class="field">
      <label class="label" for="images"><?= e($t->get('images')) ?></label>
      <input class="input file-input" id="images" type="file" name="images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple required aria-describedby="images-hint">
      <span class="hint" id="images-hint"><?= e($t->get('upload_hint', ['max' => MAX_FILES_PER_UPLOAD])) ?></span>
      <?php $field = 'files'; require ROOT . '/src/views/atoms/field_error.php'; ?>
    </div>
    <?php require ROOT . '/src/views/atoms/file_errors.php'; ?>

    <div class="cluster">
      <button class="button button-primary" type="submit"><?= e($t->get('create_album')) ?></button>
      <a class="button button-ghost" href="/"><?= e($t->get('cancel')) ?></a>
    </div>
  </form>
</section>
