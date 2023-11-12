<?php

declare(strict_types=1);

namespace Ternaryop\TumTum;

use Ternaryop\TinyOAuth\OAuthException;

const API_PREFIX = "https://api.tumblr.com/v2";
const MAX_POST_PER_REQUEST = 20;

class Tumblr {
  const POST_STATE_PUBLISHED = 'published';
  const POST_STATE_QUEUED = 'queued';
  const POST_STATE_DRAFT = 'draft';

  private TumblrOAuth $tumblrOAuth;

  function __construct(TumblrOAuth $tumblrOAuth) {
    $this->tumblrOAuth = $tumblrOAuth;
  }

  /**
   * @return array<TumblrBlog>
   * @throws OAuthException
   * @throws TumblrException
   */
  function blogs(): array {
    $apiUrl = API_PREFIX . "/user/info";

    $response = $this->tumblrOAuth->do_logged_request($apiUrl, []);
    $json = $response['result'];
    $jsonArray = $json["response"]["user"]["blogs"];
    $blogs = array();

    foreach ($jsonArray as $b) {
      $blogs[] = TumblrBlog::createFromJson($b);
    }
    return $blogs;
  }

  /**
   * @param string $blogName
   * @param array<string, mixed> $params
   * @return array<TumblrPhotoPost>
   * @throws OAuthException
   * @throws TumblrException
   */
  function getPublicPosts(string $blogName, array $params): array {
    $modifiedParams = $params;
    unset($modifiedParams['type']);

    $response = $this->tumblrOAuth->do_logged_request($this->getApiUrl($blogName, '/posts/photo'), $modifiedParams);
    $json = $response['result'];
    $jsonArray = $json["response"]["posts"];
    $posts = array();

    foreach ($jsonArray as $p) {
      $posts[] = $this->build($p);
    }
    return $posts;
  }

  static function getApiUrl(string $blogName, string $suffix): string {
    return API_PREFIX . '/blog/' . $blogName . '.tumblr.com' . $suffix;
  }

  /**
   * @param array<string, mixed> $json
   * @return TumblrPhotoPost
   * @throws TumblrException if type differs from 'photo'
   */
  static function build(array $json): TumblrPhotoPost {
    $type = $json["type"];

    if ($type != "photo") {
      throw new TumblrException("Unable to build post for type $type", 1);
    }

    return TumblrPhotoPost::createFromJson($json);
  }

  /**
   * @param string $blogName
   * @param array<string, mixed> $params
   * @return array<TumblrPhotoPost>
   * @throws TumblrException
   */
  function getPublicPostsNoOAuth(string $blogName, array $params): array {
    $apiUrl = Tumblr::getApiUrl($blogName, "/posts" . $this->getPostTypeAsUrlPath($params));

    $modifiedParams = $params;
    unset($modifiedParams['type']);

    $json = $this->publicJsonFromGet($apiUrl, $modifiedParams);
    $jsonArray = $json["response"]["posts"];
    $posts = array();

    foreach ($jsonArray as $p) {
      $posts[] = $this->build($p);
    }
    return $posts;
  }

  /**
   * Return the post type contained into params (if any) prepended by "/" url path separator
   * @param array<string, mixed> $params API params
   * @return string the "/" + type or empty string if not present
   */
  function getPostTypeAsUrlPath(array $params): string {
    $type = $params["type"];
    if ($type == null || strlen(trim($type)) == 0) {
      return "";
    }
    return '/' . $type;
  }

  /**
   * Do not involve signed oAuth call, this is used to make public tumblr API requests
   * @param string $url the public url
   * @param array<string, mixed> $params query parameters
   * @return array<string, mixed> the json map
   * @throws TumblrException
   */
  function publicJsonFromGet(string $url, array $params): array {
    $sbUrl = $url . '?api_key=' . $this->tumblrOAuth->getConfig()->getConsumerKey();
    foreach ($params as $key => $value) {
      $sbUrl .= "&" . $key . "=" . $value;
    }
    $contents = file_get_contents($sbUrl);
    if ($contents === false) {
      throw new TumblrException("Unable to read from $url");
    }
    $json = json_decode($contents, true);
    return $this->tumblrOAuth->checkResult($json);
  }

  /**
   * @param string $blogName
   * @param array<string, mixed>|null $params
   * @return array<string, mixed>
   * @throws OAuthException
   * @throws TumblrException
   */
  function createPost(string $blogName, ?array $params): array {
    $all_params = array();
    if (isset($params)) {
      $all_params = array_merge($params, $all_params);
    }
    return $this->tumblrOAuth->do_logged_request($this->getApiUrl($blogName, '/post'), $all_params);
  }

  /**
   * @param string $blogName
   * @return array<string, mixed>
   * @throws OAuthException
   * @throws TumblrException
   */
  function draft(string $blogName): array {
    $all_params = array();
    return $this->tumblrOAuth->do_logged_request($this->getApiUrl($blogName, '/posts/draft'), $all_params);
  }

  /**
   * @param string $blogName
   * @param int $maxTimestamp
   * @return array<TumblrPhotoPost>
   * @throws OAuthException
   * @throws TumblrException
   */
  function getDraftPosts(string $blogName, int $maxTimestamp): array {
    $apiUrl = Tumblr::getApiUrl($blogName, "/posts/draft");
    $all_params = [];
    $list = [];

    $json = $this->tumblrOAuth->do_logged_request($apiUrl, $all_params);
    $arr = $json["result"]["response"]["posts"];

    $params = array();
    while (count($arr) > 0 && $this->addNewerPosts($list, $arr, $maxTimestamp)) {
      $beforeId = ($arr[count($arr) - 1])["id"];
      $params["before_id"] = "$beforeId";

      $json = $this->tumblrOAuth->do_logged_request($apiUrl, $params);
      $arr = $json["result"]["response"]["posts"];
    }
    return $list;
  }

  /**
   * Add to list the posts with timestamp greater than maxTimestamp (ie newer posts)
   * @param array<TumblrPhotoPost> $list the destination list
   * @param array<array<string, mixed>> $jsonPosts the json array containing posts
   * @param int $maxTimestamp the max timestamp expressed in seconds
   * @return bool true if all posts in jsonPosts are newer, false otherwise
   * @throws TumblrException
   */
  private function addNewerPosts(array &$list, array $jsonPosts, int $maxTimestamp): bool {
    for ($i = 0; $i < count($jsonPosts); $i++) {
      $post = Tumblr::build($jsonPosts[$i]);

      if ($post->timestamp <= $maxTimestamp) {
        return false;
      }
      $list[] = $post;
    }
    return true;
  }

  /**
   * @param string $blogName
   * @return array<TumblrPhotoPost>
   * @throws OAuthException
   * @throws TumblrException
   */
  function queueAll(string $blogName): array {
    $list = [];
    $params = [];

    do {
      $queue = $this->queue($blogName, $params);
      $readCount = count($queue);
      $list = array_merge($list, $queue);
      $params["offset"] = count($list) . '';
    } while ($readCount == MAX_POST_PER_REQUEST);

    return $list;
  }

  /**
   * @param string $blogName
   * @param array<string, mixed> $params
   * @return array<TumblrPhotoPost>
   * @throws OAuthException
   * @throws TumblrException
   */
  function queue(string $blogName, array $params): array {
    $apiUrl = Tumblr::getApiUrl($blogName, "/posts/queue");
    $list = [];

    $json = $this->tumblrOAuth->do_logged_request($apiUrl, $params, "GET");
    $arr = $json["result"]["response"]["posts"];
    $this->addPostsToList($list, $arr);

    return $list;
  }

  /**
   * @param array<TumblrPhotoPost> $list
   * @param array<string, mixed> $arr
   * @return void
   * @throws TumblrException
   */
  function addPostsToList(array &$list, array $arr): void {
    foreach ($arr as $post) {
      $list[] = Tumblr::build($post);
    }
  }

  /**
   * @param string $blogName
   * @param array<string, mixed> $params
   * @return array<TumblrPhotoPost>
   * @throws OAuthException
   * @throws TumblrException
   */
  function getPhotoPosts(string $blogName, array $params): array {
    $apiUrl = Tumblr::getApiUrl($blogName, "/posts/photo");
    $list = array();

    $json = $this->tumblrOAuth->do_logged_request($apiUrl, $params);
    $arr = $json["result"]["response"]["posts"];
    $totalPosts = $json["response"]["total_posts"] ?? -1;
    foreach ($arr as $i) {
      $post = Tumblr::build($i);
      if ($totalPosts != -1) {
        $post->totalPosts = $totalPosts;
      }
      $list[] = $post;
    }
    return $list;
  }

  /**
   * @param string $blogName
   * @param array<string, mixed> $args
   * @return int
   * @throws OAuthException
   * @throws TumblrException
   */
  function schedulePost(string $blogName, array $args): int {
    $apiUrl = Tumblr::getApiUrl($blogName, "/post/edit");
    $gmtDate = date('c', $args['scheduleTimestamp'] / 1000);

    $params = [
      'id' => $args['postId'],
      'state' => 'queue',
      'publish_on' => $gmtDate,
      'tags' => $args['tags']
    ];

    if ($args['caption']) {
      $params['caption'] = $args['caption'];
    }

    $json = $this->tumblrOAuth->do_logged_request($apiUrl, $params);
    return $json['result']["response"]["id"];
  }

}

