<?php

namespace Ternaryop\TumTum;

class TumblrBlog {
  public bool $admin;
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

  public static function createFromJson(array $json): TumblrBlog {
    return (new TumblrBlog())->fromJson($json);
  }

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
