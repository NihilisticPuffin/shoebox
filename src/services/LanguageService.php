<?php
declare(strict_types=1);

final class LanguageService {
  public function __construct (private array $translations) {}

  public function get(string $key, array $replace = []): string {
    $text = $this->translations[$key] ?? $key;

    foreach ($replace as $name => $value) {
      $text = str_replace(":$name", (string) $value, $text);
    }

    return $text;
  }
}
