<?php

declare(strict_types=1);

namespace Ternaryop\TumTum;

class TumblrPhoto {
  public ?string $caption;
  /** @var array<TumblrAltSize> */
  public array $altSizes = array();
  public TumblrAltSize $originalSize;

  /**
   * @param array<string, mixed> $json
   * @return TumblrPhoto
   */
  public static function createFromJson(array $json): TumblrPhoto {
    return (new TumblrPhoto())->fromJson($json);
  }

  /**
   * @param array<string, mixed> $json
   * @return TumblrPhoto
   */
  public function fromJson(array $json): TumblrPhoto {
    $this->caption = $json['caption'];
    $this->originalSize = TumblrAltSize::createFromJson($json['original_size']);

    foreach ($json['alt_sizes'] as $i) {
      $this->altSizes[] = TumblrAltSize::createFromJson($i);
    }
    return $this;
  }
}

