<?php
declare(strict_types=1);

final class PageController {
  private const SORTS = ['trending', 'new', 'top'];

  public function __construct(private AlbumRepository $albums, private UserRepository $users) {}

  public function home(): void {
    [$trending] = $this->albums->list(['sort' => 'trending', 'per_page' => 8]);
    [$newest] = $this->albums->list(['sort' => 'new', 'per_page' => 8]);
    view('home', ['title' => t('site_name') . ' — ' . t('tagline'), 'trending' => $trending, 'newest' => $newest]);
  }

  public function explore(): void {
    $sort = in_array($_GET['sort'] ?? '', self::SORTS, true) ? $_GET['sort'] : 'trending';
    $q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);
    $page = max(1, (int) ($_GET['page'] ?? 1));

    [$albums, $total] = $this->albums->list(['sort' => $sort, 'q' => $q, 'page' => $page]);
    view('gallery', [
      'title'  => t('explore'),
      'albums' => $albums,
      'sort'   => $sort,
      'q'      => $q,
      'page'   => $page,
      'pages'  => max(1, (int) ceil($total / PER_PAGE)),
      'total'  => $total,
    ]);
  }

  public function profile(array $p): void {
    $profile = $this->users->findByUsername($p['username']);
    if ($profile === null) {
      abort(404);
    }
    $viewer = current_user();
    $isSelf = $viewer !== null && (int) $viewer['id'] === (int) $profile['id'];
    $page = max(1, (int) ($_GET['page'] ?? 1));

    [$albums, $total] = $this->albums->list([
      'author'    => $profile['id'],
      'visibility' => $isSelf ? 'all' : 'public',
      'sort'       => 'new',
      'page'       => $page,
    ]);
    view('users/profile', [
      'title'   => $profile['username'],
      'profile' => $profile,
      'isSelf'  => $isSelf,
      'albums'  => $albums,
      'total'   => $total,
      'page'    => $page,
      'pages'   => max(1, (int) ceil($total / PER_PAGE)),
    ]);
  }

  public function favorites(): void {
    $user = require_login();
    $page = max(1, (int) ($_GET['page'] ?? 1));

    [$albums, $total] = $this->albums->list([
      'favorited_by' => $user['id'],
      'visibility'   => 'any_visible',
      'viewer_id'    => $user['id'],
      'sort'         => 'new',
      'page'         => $page,
    ]);
    view('users/favorites', [
      'title'  => t('favorites'),
      'albums' => $albums,
      'total'  => $total,
      'page'   => $page,
      'pages'  => max(1, (int) ceil($total / PER_PAGE)),
    ]);
  }
}
