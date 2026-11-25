<?php
declare(strict_types=1);

/** Streams files out of storage/ after checking the parent album's visibility. */
final class MediaController {
  public function __construct(private ImageRepository $images) {}

  public function serve(array $p): void {
    $kind = $p['kind'] ?? '';
    $file = $p['file'] ?? '';
    if (!in_array($kind, ['i', 't'], true) || !preg_match('/^[a-f0-9]{32}\.(jpg|png|gif|webp)$/', $file)) {
      abort(404);
    }

    $img = $this->images->findForMedia($kind, $file);
    if ($img === null) {
      abort(404);
    }

    $viewer = current_user();
    $isOwner = $viewer !== null && (int) $viewer['id'] === (int) $img['owner_id'];
    if ($img['visibility'] === 'private' && !$isOwner) {
      abort(404);
    }

    $path = ($kind === 't' ? THUMB_DIR : UPLOAD_DIR) . '/' . $file;
    if (!is_file($path)) {
      abort(404);
    }

    $etag = '"' . md5($file . filesize($path)) . '"';
    header('ETag: ' . $etag);
    header('Cache-Control: ' . ($img['visibility'] === 'private' ? 'private, max-age=3600' : 'public, max-age=31536000, immutable'));
    header('X-Content-Type-Options: nosniff');
    if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
      http_response_code(304);
      exit;
    }

    header('Content-Type: ' . ($kind === 't' ? 'image/jpeg' : $img['mime']));
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
  }
}
