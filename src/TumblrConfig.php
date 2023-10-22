<?php

namespace Ternaryop\TumTum;

interface TumblrConfig {
  function getConsumerKey(): string;

  function getConsumerSecret(): string;

  function getOauthToken(): string;

  function getOauthTokenSecret(): string;
}
