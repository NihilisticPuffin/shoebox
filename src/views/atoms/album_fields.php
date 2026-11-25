<div class="field">
  <label class="label" for="title"><?= e($t->get('title')) ?></label>
  <input class="input<?= isset($errors['title']) ? ' is-invalid' : '' ?>" id="title" name="title" maxlength="100" placeholder="Untitled album" value="<?= e($old['title']) ?>">
  <?php $field = 'title'; require ROOT . '/src/views/atoms/field_error.php'; ?>
</div>

<div class="field">
  <label class="label" for="description"><?= e($t->get('description')) ?> <span class="text-muted">(<?= e($t->get('optional')) ?>)</span></label>
  <textarea class="input" id="description" name="description" maxlength="2000" rows="3"><?= e($old['description']) ?></textarea>
</div>

<fieldset class="field visibility">
  <legend class="label"><?= e($t->get('visibility')) ?></legend>
  <?php foreach (VISIBILITIES as $v): ?>
    <label class="visibility-option">
      <input type="radio" name="visibility" value="<?= e($v) ?>"<?= $old['visibility'] === $v ? ' checked' : '' ?>>
      <span><strong><?= e($t->get("vis_$v")) ?></strong> <span class="text-muted">— <?= e($t->get("vis_{$v}_hint")) ?></span></span>
    </label>
  <?php endforeach; ?>
</fieldset>
