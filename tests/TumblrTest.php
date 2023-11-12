<?php

declare(strict_types=1);

namespace Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use Ternaryop\TumTum\Tumblr;
use Ternaryop\TumTum\TumblrOAuth;
use function PHPUnit\Framework\assertTrue;

class TumblrTest extends TestCase {
  private Tumblr $tumblr;

  protected function setUp(): void {
    parent::setUp();
    $this->tumblr = new Tumblr(new TumblrOAuth(new EnvTumblrConfig()));
  }

  public function testBlogs(): void
  {
    try {
      $blogs = $this->tumblr->blogs();
      $this->assertCount(2, $blogs);
    } catch (Exception $e) {
      $this->fail($e->getMessage());
    }
  }

  public function testDraft(): void
  {
    try {
      $blogName = $_ENV['BLOG_NAME'];
      $posts = $this->tumblr->getDraftPosts($blogName, -1);
      $this->assertCount(1, $posts);
      $tags = $posts[0]->tags;
      $this->assertCount(1, $tags);
      self::assertEquals('Chloe Goodman', $tags[0]);
    } catch (Exception $e) {
      $this->fail($e->getMessage());
    }
  }
}
