<?php if (!empty($errors[$field])): ?>
  <span class="error" id="<?= e($field) ?>-error"><?= e($t->get($errors[$field])) ?></span>
<?php endif; ?>
