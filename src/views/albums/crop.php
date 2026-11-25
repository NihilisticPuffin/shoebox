<?php
/** expects $album, $image, $width, $height, $isCover */
$ratio = round($width / $height, 5);
$mid = intdiv(GRID, 2);
?>
<section class="panel crop-panel" style="--ratio: <?= e($ratio) ?>">
  <h1 class="panel-title cluster">
    <?= e($t->get('crop_title')) ?>
    <span class="badge badge-info"><?= e($width) ?> × <?= e($height) ?> px</span>
    <?php if ($isCover): ?><span class="badge badge-primary"><?= e($t->get('cover')) ?></span><?php endif; ?>
  </h1>

  <form class="panel-body stack" method="post" action="<?= e(album_url($album)) ?>/crop/<?= e($image['id']) ?>">
    <?= csrf_field() ?>
    <p class="text-muted"><?= e($t->get('crop_intro')) ?></p>

    <div class="crop-editor">
      <span class="label"><?= e($t->get('crop_size')) ?></span>
      <?php foreach (SIZES as $s): ?>
        <input type="radio" name="size" id="crop-s<?= $s ?>" value="<?= $s ?>"<?= $s === 50 ? ' checked' : '' ?>>
        <label class="button button-sm" for="crop-s<?= $s ?>"><?= $s ?>%</label>
      <?php endforeach; ?>

      <div class="crop-stage">
        <img src="<?= e(media_url('i', $image['filename'])) ?>" alt="<?= e($image['caption'] !== '' ? $image['caption'] : $album['title']) ?>">
        <div class="crop-cells" style="grid-template: repeat(<?= GRID ?>, 1fr) / repeat(<?= GRID ?>, 1fr)">
          <?php for ($j = 0; $j < GRID; $j++): for ($i = 0; $i < GRID; $i++): ?>
            <label class="crop-cell" style="--cx:<?= round(($i + .5) / GRID, 4) ?>;--cy:<?= round(($j + .5) / GRID, 4) ?>">
              <input type="radio" name="pos" value="<?= $i ?>,<?= $j ?>"<?= ($i === $mid && $j === $mid) ? ' checked' : '' ?>>
              <span class="crop-box"></span>
            </label>
          <?php endfor; endfor; ?>
        </div>
      </div>
    </div>

    <p class="hint"><?= e($t->get('crop_keys_hint')) ?></p>

    <div class="cluster">
      <button class="button button-primary" type="submit"><?= e($t->get('crop_save')) ?></button>
      <a class="button button-ghost" href="<?= e(album_url($album)) ?>/edit"><?= e($t->get('cancel')) ?></a>
    </div>
  </form>
</section>
