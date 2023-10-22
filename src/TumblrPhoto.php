<?php

namespace Ternaryop\TumTum;

class TumblrPhoto {
  public ?string $caption;
  public array $altSizes = array();
  public TumblrAltSize $originalSize;

  public static function createFromJson(array $json): TumblrPhoto {
    return (new TumblrPhoto())->fromJson($json);
  }

  public function fromJson(array $json): TumblrPhoto {
    $this->caption = $json['caption'];
    $this->originalSize = TumblrAltSize::createFromJson($json['original_size']);

    foreach ($json['alt_sizes'] as $i) {
      $this->altSizes[] = TumblrAltSize::createFromJson($i);
    }
    return $this;
  }
}

