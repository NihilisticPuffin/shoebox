<?php
declare(strict_types=1);

final class AlbumController {
  public function __construct(
    private PDO $pdo,
    private AlbumRepository $albums,
    private ImageRepository $images,
    private ImageService $imageService,
  ) {}

  /* == Create == */

  public function showNew(): void {
    require_login();
    view('albums/new', $this->newFormData([], [], []));
  }

  public function create(): void {
    $user = require_login();
    if ($this->postTooLarge()) {
      view('albums/new', $this->newFormData([], ['files' => 'err_post_too_large'], []), 413);
      return;
    }
    verify_csrf();

    $old = $this->albumInput();
    $files = $this->uploadedFiles();
    $errors = [];
    if (!$files) {
      $errors['files'] = 'err_no_files';
    } elseif (count($files) > MAX_FILES_PER_UPLOAD) {
      $errors['files'] = 'err_too_many_files';
    }
    if ($errors) {
      view('albums/new', $this->newFormData($old, $errors, []), 422);
      return;
    }

    $stored = [];
    $fileErrors = [];
    $this->pdo->beginTransaction();
    try {
      $album = $this->albums->create((int) $user['id'], $old['title'], $old['description'], $old['visibility']);
      $this->storeFiles($files, $album['id'], 0, $stored, $fileErrors);

      if (!$stored) {
        $this->pdo->rollBack();
        view('albums/new', $this->newFormData($old, ['files' => 'err_no_valid_files'], $fileErrors), 422);
        return;
      }
      $this->pdo->commit();
    } catch (Throwable $e) {
      if ($this->pdo->inTransaction()) {
        $this->pdo->rollBack();
      }
      $this->deleteFiles($stored);
      throw $e;
    }

    flash('success', t('flash_album_created'));
    if ($fileErrors) {
      flash('warning', t('flash_some_failed', ['n' => count($fileErrors)]));
    }
    redirect(album_url($album));
  }

  /* == View == */

  public function show(array $p): void {
    $album = $this->albums->findBySlug($p['slug']);
    $viewer = current_user();
    if ($album === null || !$this->canView($album, $viewer)) {
      abort(404, t('err_album_not_found'));
    }

    $isOwner = $this->isOwner($album, $viewer);
    if (!$isOwner && empty($_SESSION['viewed'][$album['id']])) {
      $_SESSION['viewed'][$album['id']] = true;
      $this->albums->incrementViews((int) $album['id']);
      $album['views']++;
    }

    view('albums/show', [
      'title'       => $album['title'],
      'album'       => $album,
      'images'      => $this->images->forAlbum((int) $album['id']),
      'isOwner'     => $isOwner,
      'isFavorited' => $viewer !== null && $this->albums->isFavorited((int) $viewer['id'], (int) $album['id']),
    ]);
  }

  /* == Edit == */

  public function showEdit(array $p): void {
    $album = $this->ownedAlbum($p['slug']);
    view('albums/edit', $this->editFormData($album, $this->images->forAlbum((int) $album['id']), [], [], []));
  }

  public function update(array $p): void {
    $album = $this->ownedAlbum($p['slug']);
    $existing = $this->images->forAlbum((int) $album['id']);

    if ($this->postTooLarge()) {
      view('albums/edit', $this->editFormData($album, $existing, [], ['files' => 'err_post_too_large'], []), 413);
      return;
    }
    verify_csrf();

    $input = $this->albumInput();
    $newFiles = $this->uploadedFiles();
    if (count($newFiles) > MAX_FILES_PER_UPLOAD) {
      $errors['files'] = 'err_too_many_files';
    }

    $byId = [];
    foreach ($existing as $img) {
      $byId[(int) $img['id']] = $img;
    }
    $remove = array_values(array_unique(array_filter(
      array_map('intval', (array) ($_POST['remove'] ?? [])),
      static fn(int $id) => isset($byId[$id])
    )));
    $keep = array_values(array_diff(array_keys($byId), $remove));
    $captions = (array) ($_POST['caption'] ?? []);
    $orders = (array) ($_POST['order'] ?? []);
    $coverId = (int) ($_POST['cover'] ?? 0);
    $coverId = in_array($coverId, $keep, true) ? $coverId : null;

    if (!$errors && !$keep && !$newFiles) {
      $errors['files'] = 'err_need_one_image';
    }
    if ($errors) {
      view('albums/edit', $this->editFormData($album, $existing, $input, $errors, []), 422);
      return;
    }

    $stored = [];
    $fileErrors = [];
    $this->pdo->beginTransaction();
    try {
      $maxOrder = 0;
      foreach ($keep as $id) {
        $order = (int) ($orders[$id] ?? $byId[$id]['position']);
        $maxOrder = max($maxOrder, $order);
        $this->images->updateMeta($id, mb_substr(trim((string) ($captions[$id] ?? '')), 0, 300), $order);
      }
      $this->storeFiles($newFiles, (int) $album['id'], $maxOrder, $stored, $fileErrors);

      if (!$keep && !$stored) {
        $this->pdo->rollBack();
        $this->deleteFiles($stored);
        view('albums/edit', $this->editFormData($album, $existing, $input, ['files' => 'err_no_valid_files'], $fileErrors), 422);
        return;
      }

      foreach ($remove as $id) {
        $this->images->delete($id);
      }
      // Re-number positions 1..n so ordering stays tidy.
      foreach ($this->images->forAlbum((int) $album['id']) as $i => $img) {
        $this->images->updateMeta((int) $img['id'], $img['caption'], $i + 1);
      }
      $this->albums->update((int) $album['id'], $input['title'], $input['description'], $input['visibility'], $coverId);
      $this->pdo->commit();
    } catch (Throwable $e) {
      if ($this->pdo->inTransaction()) {
        $this->pdo->rollBack();
      }
      $this->deleteFiles($stored);
      throw $e;
    }

    // Only touch the disk once the DB change is safely committed.
    $this->deleteFiles(array_map(static fn(int $id) => $byId[$id], $remove));

    flash('success', t('flash_album_saved'));
    if ($fileErrors) {
      flash('warning', t('flash_some_failed', ['n' => count($fileErrors)]));
    }
    redirect(album_url($album));
  }

  /* == Crop cover == */

  public function showCrop(array $p): void {
    $album = $this->ownedAlbum($p['slug']);
    $image = $this->albumImage($album, $p['image']);

    $size = @getimagesize(UPLOAD_DIR . '/' . $image['filename']);
    if ($size === false) {
      abort(404, t('err_image_not_found'));
    }

    view('albums/crop', [
      'title'   => t('crop_title'),
      'album'   => $album,
      'image'   => $image,
      'width'   => $size[0], // real pixel size of the stored file (DB values are pre-EXIF-rotation)
      'height'  => $size[1],
      'isCover' => $this->isCover($album, $image),
    ]);
  }

  public function crop(array $p): void {
    $album = $this->ownedAlbum($p['slug']);
    $image = $this->albumImage($album, $p['image']);
    verify_csrf();

    $back = album_url($album) . '/crop/' . (int) $image['id'];
    $size = (int) ($_POST['size'] ?? 0);
    if (!preg_match('/^(\d{1,2}),(\d{1,2})$/', (string) ($_POST['pos'] ?? ''), $m)
        || (int) $m[1] >= GRID || (int) $m[2] >= GRID
        || !in_array($size, SIZES, true)) {
      flash('error', t('err_crop_invalid'));
      redirect($back);
    }

    try {
      $newThumb = $this->imageService->recropThumb($image, (int) $m[1], (int) $m[2], $size);
    } catch (RuntimeException $e) {
      flash('error', t($e->getMessage()));
      redirect($back);
    }

    try {
      $this->images->updateThumb((int) $image['id'], $newThumb);
    } catch (Throwable $e) {
      $this->imageService->deleteThumb($newThumb);
      throw $e;
    }
    // Only drop the old file once the DB points at the new one.
    $this->imageService->deleteThumb($image['thumb']);

    flash('success', t('flash_crop_saved'));
    redirect(album_url($album) . '/edit');
  }

  /* == Delete / favorite == */

  public function delete(array $p): void {
    $album = $this->ownedAlbum($p['slug']);
    verify_csrf();

    $images = $this->images->forAlbum((int) $album['id']);
    $this->albums->delete((int) $album['id']); // images/favorites cascade
    $this->deleteFiles($images);

    flash('success', t('flash_album_deleted'));
    redirect(profile_url(current_user()['username']));
  }

  public function favorite(array $p): void {
    $user = require_login();
    verify_csrf();

    $album = $this->albums->findBySlug($p['slug']);
    if ($album === null || !$this->canView($album, $user)) {
      abort(404, t('err_album_not_found'));
    }
    $this->albums->toggleFavorite((int) $user['id'], (int) $album['id']);
    redirect(album_url($album));
  }

  /* == Helpers == */

  private function canView(array $album, ?array $viewer): bool {
    return $album['visibility'] !== 'private' || $this->isOwner($album, $viewer);
  }

  private function isOwner(array $album, ?array $viewer): bool {
    return $viewer !== null && (int) $viewer['id'] === (int) $album['author'];
  }

  private function ownedAlbum(string $slug): array {
    $user = require_login();
    $album = $this->albums->findBySlug($slug);
    if ($album === null) {
      abort(404, t('err_album_not_found'));
    }
    if (!$this->isOwner($album, $user)) {
      $album['visibility'] === 'private' ? abort(404, t('err_album_not_found')) : abort(403, t('err_not_owner'));
    }
    return $album;
  }

  /** An image that belongs to this album, or a 404. */
  private function albumImage(array $album, string $rawId): array {
    $image = ctype_digit($rawId) ? $this->images->findInAlbum((int) $rawId, (int) $album['id']) : null;
    if ($image === null) {
      abort(404, t('err_image_not_found'));
    }
    return $image;
  }

  /** Mirrors AlbumRepository's cover rule: the chosen image, else the first one. */
  private function isCover(array $album, array $image): bool {
    if ($album['cover'] !== null && $this->images->findInAlbum((int) $album['cover'], (int) $album['id'])) {
      return (int) $album['cover'] === (int) $image['id'];
    }
    $first = $this->images->forAlbum((int) $album['id'])[0] ?? null;
    return $first !== null && (int) $first['id'] === (int) $image['id'];
  }

  private function albumInput(): array {
    $title = post_str('title', 100);
    $visibility = post_str('visibility');
    return [
      'title'       => $title !== '' ? $title : 'Untitled album',
      'description' => post_str('description', 2000),
      'visibility'  => in_array($visibility, VISIBILITIES, true) ? $visibility : 'public',
    ];
  }

  private function uploadedFiles(): array {
    $f = $_FILES['images'] ?? null;
    if (!$f || !is_array($f['name'])) {
      return [];
    }
    $out = [];
    foreach ($f['name'] as $i => $name) {
      if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) {
        continue;
      }
      $out[] = ['name' => $name, 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $out;
  }

  /** PHP drops $_POST and $_FILES entirely when the body exceeds post_max_size. */
  private function postTooLarge(): bool {
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
      && !$_POST && !$_FILES
      && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
  }

  /** Processes files in order; collects successes in $stored and messages in $fileErrors. */
  private function storeFiles(array $files, int $albumId, int $startPosition, array &$stored, array &$fileErrors): void {
    $position = $startPosition;
    foreach ($files as $file) {
      try {
        $meta = $this->imageService->store($file);
      } catch (RuntimeException $e) {
        $fileErrors[] = ($file['name'] !== '' ? $file['name'] . ': ' : '') . t($e->getMessage());
        continue;
      }
      $stored[] = $meta;
      $this->images->create($albumId, $meta, ++$position);
    }
  }

  private function deleteFiles(array $imageRows): void {
    foreach ($imageRows as $img) {
      $this->imageService->delete($img['filename'], $img['thumb']);
    }
  }

  private function newFormData(array $old, array $errors, array $fileErrors): array {
    return [
      'title'      => t('new_album'),
      'old'        => $old + ['title' => '', 'description' => '', 'visibility' => 'public'],
      'errors'     => $errors,
      'fileErrors' => $fileErrors,
    ];
  }

  private function editFormData(array $album, array $images, array $input, array $errors, array $fileErrors): array {
    return [
      'title'      => t('edit_album') . ': ' . $album['title'],
      'album'      => $album,
      'images'     => $images,
      'old'        => $input + ['title' => $album['title'], 'description' => $album['description'], 'visibility' => $album['visibility']],
      'errors'     => $errors,
      'fileErrors' => $fileErrors,
    ];
  }
}
