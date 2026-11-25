<?php
declare(strict_types=1);

final class ImageRepository {
  public function __construct(private PDO $pdo) {}

  public function forAlbum(int $albumId): array {
    $st = $this->pdo->prepare('SELECT * FROM images WHERE album = ? ORDER BY position, id');
    $st->execute([$albumId]);
    return $st->fetchAll();
  }

  public function forUser(int $userId): array {
    $st = $this->pdo->prepare(
      'SELECT i.* FROM images i JOIN albums a ON a.id = i.album WHERE a.author = ?'
    );
    $st->execute([$userId]);
    return $st->fetchAll();
  }

  public function create(int $albumId, array $meta, int $position): int {
    $st = $this->pdo->prepare(
      'INSERT INTO images (album, filename, thumb, original_name, mime, width, height, bytes, position)
       VALUES (:album, :filename, :thumb, :orig, :mime, :w, :h, :bytes, :pos)'
    );
    $st->execute([
      ':album' => $albumId, ':filename' => $meta['filename'], ':thumb' => $meta['thumb'],
      ':orig' => $meta['original_name'], ':mime' => $meta['mime'],
      ':w' => $meta['width'], ':h' => $meta['height'], ':bytes' => $meta['bytes'], ':pos' => $position,
    ]);
    return (int) $this->pdo->lastInsertId();
  }

  public function updateMeta(int $id, string $caption, int $position): void {
    $this->pdo->prepare('UPDATE images SET caption = ?, position = ? WHERE id = ?')
      ->execute([$caption, $position, $id]);
  }

  public function findInAlbum(int $id, int $albumId): ?array {
    $st = $this->pdo->prepare('SELECT * FROM images WHERE id = ? AND album = ?');
    $st->execute([$id, $albumId]);
    return $st->fetch() ?: null;
  }

  public function updateThumb(int $id, string $thumb): void {
    $this->pdo->prepare('UPDATE images SET thumb = ? WHERE id = ?')->execute([$thumb, $id]);
  }

  public function delete(int $id): void {
    $this->pdo->prepare('DELETE FROM images WHERE id = ?')->execute([$id]);
  }

  public function maxPosition(int $albumId): int {
    $st = $this->pdo->prepare('SELECT COALESCE(MAX(position), 0) FROM images WHERE album = ?');
    $st->execute([$albumId]);
    return (int) $st->fetchColumn();
  }
  public function findForMedia(string $kind, string $file): ?array {
    $column = $kind === 't' ? 'i.thumb' : 'i.filename';
    $st = $this->pdo->prepare(
      "SELECT i.*, a.visibility, a.author AS owner_id
         FROM images i JOIN albums a ON a.id = i.album
        WHERE $column = ?"
    );
    $st->execute([$file]);
    return $st->fetch() ?: null;
  }
}
