<?php

namespace Ternaryop\TumTum;

class TumblrPhotoPost extends TumblrPost {
  public array $photos = array();
  public string $caption = "";

  public static function createFromJson(array $json): TumblrPhotoPost {
    return (new TumblrPhotoPost())->fromJson($json);
  }

  public function fromJson(array $json): TumblrPhotoPost {
    parent::fromJson($json);
    $this->caption = $json['caption'];

    foreach ($json['photos'] as $i) {
      $this->photos[] = TumblrPhoto::createFromJson($i);
    }
    return $this;
  }

  /**
   * @throws TumblrException
   */
  public function fromPost(TumblrPost $post): TumblrPhotoPost {
    parent::fromPost($post);
    if (!$post instanceof TumblrPhotoPost) {
      throw new TumblrException("Expected TumblrPhotoPost found TumblrPost");
    }
    $this->photos = $post->photos;
    $this->caption = $post->caption;
    return $this;
  }

  function getClosestPhotoByWidth(int $width): ?int {
    // some images don't have the exact (==) width, so we get closest width (<=)
    $fas = $this->firstPhotoAltSize();

    if ($fas == null) {
      return null;
    }
    foreach ($fas as $v) {
      if ($v->width <= $width) {
        return $v->width;
      }
    }
    return null;
  }

  public function firstPhotoAltSize(): ?array {
    if (empty($this->photos)) {
      return null;
    }
    return $this->photos[0]->altSizes;
  }
}

