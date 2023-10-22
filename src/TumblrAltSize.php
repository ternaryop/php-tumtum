<?php

namespace Ternaryop\TumTum;

const IMAGE_WIDTH_1280 = 1280;
const IMAGE_WIDTH_400 = 400;
const IMAGE_WIDTH_250 = 250;
const IMAGE_WIDTH_75 = 75;
const IMAGE_AVATAR_WIDTH = 96;

class TumblrAltSize {
  public int $width;
  public int $height;
  public string $url;

  public static function createFromJson(array $json): TumblrAltSize {
    return (new TumblrAltSize())->fromJson($json);
  }

  public function fromJson(array $json): TumblrAltSize {
    $this->width = $json['width'];
    $this->height = $json['height'];
    $this->url = $json['url'];

    return $this;
  }
}
