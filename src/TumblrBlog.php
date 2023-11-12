<?php

declare(strict_types=1);

namespace Ternaryop\TumTum;

class TumblrBlog {
  public bool $admin;
  /** @var array<TumblrAltSize> */
  public array $avatar;
  public string $description;
  public int $drafts;
  public string $name;
  public int $posts;
  public bool $primary;
  public int $queue;
  public string $title;
  public int $total_posts;
  public string $url;

  /**
   * @param array<string, mixed> $json
   * @return TumblrBlog
   */
  public static function createFromJson(array $json): TumblrBlog {
    return (new TumblrBlog())->fromJson($json);
  }

  /**
   * @param array<string, mixed> $json
   * @return TumblrBlog
   */
  public function fromJson(array $json): TumblrBlog {
    $this->admin = $json['admin'];
    $this->description = $json['description'];
    $this->drafts = $json['drafts'];
    $this->name = $json['name'];
    $this->posts = $json['posts'];
    $this->primary = $json['primary'];
    $this->queue = $json['queue'];
    $this->title = $json['title'];
    $this->total_posts = $json['total_posts'];
    $this->url = $json['url'];

    $this->avatar = [];
    foreach ($json['avatar'] as $i) {
      $this->avatar[] = TumblrAltSize::createFromJson($i);
    }

    return $this;
  }

}
