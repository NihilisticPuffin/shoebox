<?php
declare(strict_types=1);

final class ImageService {
  public function store(array $file): array {
    @ini_set('memory_limit', '512M');

    $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
      throw new RuntimeException('err_file_too_big');
    }
    if ($err !== UPLOAD_ERR_OK) {
      throw new RuntimeException('err_upload_failed');
    }

    $tmp = (string) $file['tmp_name'];
    if (!is_uploaded_file($tmp)) {
      throw new RuntimeException('err_upload_failed');
    }
    if (filesize($tmp) > MAX_BYTES) {
      throw new RuntimeException('err_file_too_big');
    }

    $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    if (!isset(ALLOWED_MIMES[$mime])) {
      throw new RuntimeException('err_file_type');
    }

    $info = @getimagesize($tmp);
    if ($info === false || $info[0] < 1 || $info[1] < 1) {
      throw new RuntimeException('err_file_type');
    }
    if ($info[0] * $info[1] > MAX_PIXELS) {
      throw new RuntimeException('err_file_pixels');
    }

    $src = $this->decode($tmp, $mime);
    if (!$src) {
      throw new RuntimeException('err_file_type');
    }

    if ($mime === 'image/jpeg') {
      $src = $this->applyExifOrientation($src, $tmp);
    }

    $base = bin2hex(random_bytes(16));
    $filename = $base . '.' . ALLOWED_MIMES[$mime];
    $thumb = $base . '.jpg';
    $dest = UPLOAD_DIR . '/' . $filename;
    $thumbDest = THUMB_DIR . '/' . $thumb;

    try {
      if ($mime === 'image/gif') {
        if (!move_uploaded_file($tmp, $dest)) {
          throw new RuntimeException('err_upload_failed');
        }
      } else {
        $this->encode($src, $mime, $dest);
      }
      $this->writeThumb($src, $thumbDest);
    } catch (Throwable $e) {
      @unlink($dest);
      @unlink($thumbDest);
      throw $e;
    }

    return [
      'filename'      => $filename,
      'thumb'         => $thumb,
      'original_name' => mb_substr(basename((string) ($file['name'] ?? '')), 0, 120),
      'mime'          => $mime,
      'width'         => $info[0],
      'height'        => $info[1],
      'bytes'         => (int) filesize($dest),
    ];
  }

  public function delete(string $filename, string $thumb): void {
    @unlink(UPLOAD_DIR . '/' . basename($filename));
    @unlink(THUMB_DIR . '/' . basename($thumb));
  }

  public function deleteThumb(string $thumb): void {
    @unlink(THUMB_DIR . '/' . basename($thumb));
  }

  public static function cropRect(int $w, int $h, int $col, int $row, int $pct): array {
    $side = max(1, (int) round($pct / 100 * min($w, $h)));
    $x = (int) round(max(0, min($w - $side, ($col + 0.5) / GRID * $w - $side / 2)));
    $y = (int) round(max(0, min($h - $side, ($row + 0.5) / GRID * $h - $side / 2)));
    return [$x, $y, $side];
  }

  public function recropThumb(array $image, int $col, int $row, int $pct): string {
    @ini_set('memory_limit', '512M');

    $path = UPLOAD_DIR . '/' . basename((string) $image['filename']);
    if (!is_file($path)) {
      throw new RuntimeException('err_crop_failed');
    }
    $src = $this->decode($path, (string) $image['mime']);
    if (!$src) {
      throw new RuntimeException('err_crop_failed');
    }

    $thumb = bin2hex(random_bytes(16)) . '.jpg';
    $dest = THUMB_DIR . '/' . $thumb;
    try {
      $rect = self::cropRect(imagesx($src), imagesy($src), $col, $row, $pct);
      $this->writeThumb($src, $dest, $rect);
    } catch (Throwable $e) {
      @unlink($dest);
      throw new RuntimeException('err_crop_failed', 0, $e);
    }
    return $thumb;
  }

  private function decode(string $path, string $mime): GdImage|false {
    return match ($mime) {
      'image/jpeg' => @imagecreatefromjpeg($path),
      'image/png'  => @imagecreatefrompng($path),
      'image/gif'  => @imagecreatefromgif($path),
      'image/webp' => @imagecreatefromwebp($path),
      default      => false,
    };
  }

  private function encode(GdImage $img, string $mime, string $dest): void {
    $ok = match ($mime) {
      'image/jpeg' => imagejpeg($img, $dest, 88),
      'image/png'  => $this->savePng($img, $dest),
      'image/webp' => $this->saveAlpha($img, fn() => imagewebp($img, $dest, 88)),
    };
    if (!$ok) {
      throw new RuntimeException('err_upload_failed');
    }
  }

  private function savePng(GdImage $img, string $dest): bool {
    return $this->saveAlpha($img, fn() => imagepng($img, $dest, 6));
  }

  private function saveAlpha(GdImage $img, callable $write): bool {
    imagealphablending($img, false);
    imagesavealpha($img, true);
    return (bool) $write();
  }

  private function writeThumb(GdImage $src, string $dest, ?array $rect = null): void {
    if ($rect === null) {
      $w = imagesx($src);
      $h = imagesy($src);
      $side = min($w, $h);
      $rect = [intdiv($w - $side, 2), intdiv($h - $side, 2), $side];
    }
    [$sx, $sy, $side] = $rect;

    $thumb = imagecreatetruecolor(THUMB_SIZE, THUMB_SIZE);
    imagefill($thumb, 0, 0, imagecolorallocate($thumb, 0x26, 0x28, 0x36)); // theme --c-base behind transparency
    imagealphablending($thumb, true);
    imagecopyresampled($thumb, $src, 0, 0, $sx, $sy, THUMB_SIZE, THUMB_SIZE, $side, $side);

    $ok = imagejpeg($thumb, $dest, 82);
    if (!$ok) {
      throw new RuntimeException('err_upload_failed');
    }
  }

  private function applyExifOrientation(GdImage $img, string $path): GdImage {
    if (!function_exists('exif_read_data')) {
      return $img;
    }
    $exif = @exif_read_data($path);
    $angle = match ((int) ($exif['Orientation'] ?? 1)) {
      3 => 180, 6 => -90, 8 => 90, default => 0,
    };
    if ($angle === 0) {
      return $img;
    }
    $rotated = imagerotate($img, $angle, 0);
    if ($rotated === false) {
      return $img;
    }
    return $rotated;
  }
}
