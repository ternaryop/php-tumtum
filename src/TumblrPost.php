<?php

namespace Ternaryop\TumTum;

function optBoolean(array $json, string $v, bool $defaultValue): bool {
  if (isset($json[$v])) {
    return $json[$v];
  }
  return $defaultValue;
}

function optString(array $json, string $v, ?string $defaultValue): ?string {
  if (isset($json[$v])) {
    return $json[$v];
  }
  return $defaultValue;
}

function optLong(array $json, string $v, int $defaultValue): int {
  if (isset($json[$v])) {
    return $json[$v];
  }
  return $defaultValue;
}

class TumblrPost {
  public string $blogName = "";
  public int $postId = 0;
  public string $postUrl = "";
  public string $type = "";
  public int $timestamp = 0;
  public string $date = "";
  public string $format = "";
  public string $reblogKey = "";
  public bool $isBookmarklet = false;
  public bool $isMobile = false;
  public ?string $sourceUrl = null;
  public ?string $sourceTitle = null;
  public bool $isLiked = false;
  public string $state = "";
  public int $totalPosts = 0;
  public int $noteCount = 0;

  // queue posts
  public int $scheduledPublishTime = 0;

  public array $tags = array();

  public static function createFromJson(array $json): TumblrPost {
    return (new TumblrPost())->fromJson($json);
  }

  public function fromJson(array $json): TumblrPost {
    $this->blogName = $json['blog_name'];
    $this->postId = $json['id'];
    $this->postUrl = $json['post_url'];
    $this->type = $json['type'];
    $this->timestamp = $json['timestamp'];
    $this->date = $json['date'];
    $this->format = $json['format'];
    $this->reblogKey = $json['reblog_key'];
    $this->isBookmarklet = optBoolean($json, 'bookmarklet', false);
    $this->isMobile = optBoolean($json, 'mobile', false);
    $this->sourceUrl = optString($json, 'source_url', null);
    $this->sourceTitle = optString($json, 'source_title', null);
    $this->isLiked = optBoolean($json, 'liked', false);
    $this->state = $json['state'];
    $this->totalPosts = optLong($json, 'total_posts', 0);
    $this->noteCount = optLong($json, 'note_count', 0);

    foreach ($json['tags'] as $i) {
      $this->tags[] = $i;
    }

    $this->scheduledPublishTime = optLong($json, 'scheduled_publish_time', 0);

    return $this;
  }

  public static function createFromPost(TumblrPost $post): TumblrPost {
    return (new TumblrPost())->fromPost($post);
  }

  public function fromPost(TumblrPost $post): TumblrPost {
    $this->blogName = $post->blogName;
    $this->postId = $post->postId;
    $this->postUrl = $post->postUrl;
    $this->type = $post->type;
    $this->timestamp = $post->timestamp;
    $this->date = $post->date;
    $this->format = $post->format;
    $this->reblogKey = $post->reblogKey;
    $this->isBookmarklet = $post->isBookmarklet;
    $this->isMobile = $post->isMobile;
    $this->sourceUrl = $post->sourceUrl;
    $this->sourceTitle = $post->sourceTitle;
    $this->isLiked = $post->isLiked;
    $this->state = $post->state;
    $this->totalPosts = $post->totalPosts;
    $this->noteCount = $post->noteCount;

    $this->tags = $post->tags;
    $this->scheduledPublishTime = $post->scheduledPublishTime;

    return $this;
  }

  function tagsAsString(): string {
    if (empty($this->tags)) {
      return "";
    }
    return join(',', $this->tags);
  }

  /**
   * Protect against IndexOutOfBoundsException returning an empty string
   * @return string the first tag or an empty string
   */
  function firstTag(): string {
    if (empty($this->tags)) {
      return "";
    }
    return $this->tags[0];
  }

  public function setTagsFromString(string $str): void {
    $this->tags = TumblrPost::tagsFromString($str);
  }

  static function tagsFromString(string $str): array {
    $arr = array();
    foreach (explode(",", $str) as $s) {
      $t = trim($s);
      if (!empty($t)) {
        $arr[] = $t;
      }
    }

    return $arr;
  }
}
