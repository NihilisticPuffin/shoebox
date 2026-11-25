<?php
declare(strict_types=1);

final class AlbumRepository {
  private const SELECT = "SELECT a.*, u.username,
      (SELECT COUNT(*) FROM images i WHERE i.album = a.id) AS image_count,
      (SELECT COUNT(*) FROM favorites f WHERE f.album = a.id) AS fav_count,
      COALESCE(
        (SELECT thumb FROM images WHERE id = a.cover AND album = a.id),
        (SELECT thumb FROM images WHERE album = a.id ORDER BY position, id LIMIT 1)
      ) AS cover_thumb
    FROM albums a JOIN users u ON u.id = a.author";

  private const SORTS = [
    'new'      => 'a.created DESC, a.id DESC',
    'trending' => '(a.views + 5 * fav_count) DESC, a.id DESC',
    'top'      => 'fav_count DESC, a.views DESC, a.id DESC',
  ];

  public function __construct(private PDO $pdo) {}

  public function create(int $userId, string $title, string $description, string $visibility): array {
    $st = $this->pdo->prepare(
      'INSERT INTO albums (slug, author, title, description, visibility) VALUES (?, ?, ?, ?, ?)'
    );
    do {
      $slug = $this->newSlug();
      try {
        $st->execute([$slug, $userId, $title, $description, $visibility]);
        break;
      } catch (PDOException $e) {
        if (!str_contains($e->getMessage(), 'UNIQUE')) {
          throw $e;
        }
      }
    } while (true);

    return ['id' => (int) $this->pdo->lastInsertId(), 'slug' => $slug];
  }

  public function findBySlug(string $slug): ?array {
    $st = $this->pdo->prepare(self::SELECT . ' WHERE a.slug = ?');
    $st->execute([$slug]);
    return $st->fetch() ?: null;
  }

  public function update(int $id, string $title, string $description, string $visibility, ?int $coverImageId): void {
    $this->pdo->prepare(
      "UPDATE albums SET title = ?, description = ?, visibility = ?, cover = ?,
              updated = unixepoch() WHERE id = ?"
    )->execute([$title, $description, $visibility, $coverImageId, $id]);
  }

  public function delete(int $id): void {
    $this->pdo->prepare('DELETE FROM albums WHERE id = ?')->execute([$id]);
  }

  public function incrementViews(int $id): void {
    $this->pdo->prepare('UPDATE albums SET views = views + 1 WHERE id = ?')->execute([$id]);
  }

  public function isFavorited(int $userId, int $albumId): bool {
    $st = $this->pdo->prepare('SELECT 1 FROM favorites WHERE user = ? AND album = ?');
    $st->execute([$userId, $albumId]);
    return (bool) $st->fetchColumn();
  }

  public function toggleFavorite(int $userId, int $albumId): bool {
    if ($this->isFavorited($userId, $albumId)) {
      $this->pdo->prepare('DELETE FROM favorites WHERE user = ? AND album = ?')->execute([$userId, $albumId]);
      return false;
    }
    $this->pdo->prepare('INSERT OR IGNORE INTO favorites (user, album) VALUES (?, ?)')->execute([$userId, $albumId]);
    return true;
  }

  public function list(array $o = []): array {
    $where = empty($o['include_empty']) ? ['EXISTS (SELECT 1 FROM images i WHERE i.album = a.id)'] : [];
    $args = [];

    switch ($o['visibility'] ?? 'public') {
      case 'public':
        $where[] = "a.visibility = 'public'";
        break;
      case 'any_visible':
        $where[] = "(a.visibility != 'private' OR a.author = :viewer)";
        $args[':viewer'] = (int) ($o['viewer_id'] ?? 0);
        break;
      case 'all':
        break;
      default:
        if (in_array($o['visibility'], VISIBILITIES, true)) {
          $where[] = 'a.visibility = :vis';
          $args[':vis'] = $o['visibility'];
        }
    }
    if (!empty($o['author'])) {
      $where[] = 'a.author = :author';
      $args[':author'] = (int) $o['author'];
    }
    if (!empty($o['favorited_by'])) {
      $where[] = 'EXISTS (SELECT 1 FROM favorites fv WHERE fv.album = a.id AND fv.user = :favby)';
      $args[':favby'] = (int) $o['favorited_by'];
    }
    $q = trim((string) ($o['q'] ?? ''));
    if ($q !== '') {
      $where[] = "(a.title LIKE :q ESCAPE '\\' OR a.description LIKE :q ESCAPE '\\' OR u.username LIKE :q ESCAPE '\\')";
      $args[':q'] = '%' . addcslashes($q, '%_\\') . '%';
    }

    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $order = self::SORTS[$o['sort'] ?? 'new'] ?? self::SORTS['new'];
    $perPage = max(1, (int) ($o['per_page'] ?? PER_PAGE));
    $offset = (max(1, (int) ($o['page'] ?? 1)) - 1) * $perPage;

    $count = $this->pdo->prepare('SELECT COUNT(*) FROM albums a JOIN users u ON u.id = a.author' . $whereSql);
    $count->execute($args);

    $st = $this->pdo->prepare(self::SELECT . $whereSql . " ORDER BY $order LIMIT $perPage OFFSET $offset");
    $st->execute($args);

    return [$st->fetchAll(), (int) $count->fetchColumn()];
  }

  private function newSlug(): string {
    $alphabet = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $slug = '';
    for ($i = 0; $i < 8; $i++) {
      $slug .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $slug;
  }
}
