<?php

declare(strict_types=1);

namespace Tests;

use Ternaryop\TumTum\TumblrConfig;

class EnvTumblrConfig implements TumblrConfig {

  function getConsumerKey(): string {
    return $_ENV['TUMBLR_CONSUMER_KEY'];
  }

  function getConsumerSecret(): string {
    return $_ENV['TUMBLR_CONSUMER_SECRET'];
  }

  function getOauthToken(): string {
    return $_ENV['TUMBLR_OAUTH_TOKEN'];
  }

  function getOauthTokenSecret(): string {
    return $_ENV['TUMBLR_OAUTH_TOKEN_SECRET'];
  }
}
