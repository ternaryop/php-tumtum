<?php

declare(strict_types=1);

namespace Ternaryop\TumTum;

use Ternaryop\TinyOAuth\OAuthConsumer;
use Ternaryop\TinyOAuth\OAuthException;
use Ternaryop\TinyOAuth\OAuthRequest;
use Ternaryop\TinyOAuth\OAuthSignatureMethod;
use Ternaryop\TinyOAuth\OAuthSignatureMethod_HMAC_SHA1;
use Ternaryop\TinyOAuth\OAuthToken;

define('OAUTH_CONSUMER_KEY', '');
define('OAUTH_SECRET_KEY', '');
define('REQUEST_TOKEN_URL', 'https://www.tumblr.com/oauth/request_token');
define('AUTHORIZE_URL', 'https://www.tumblr.com/oauth/authorize');
define('ACCESS_TOKEN_URL', 'https://www.tumblr.com/oauth/access_token');
define("ACCESS_TOKEN", "ACCESS_TOKEN");
define("ACCESS_TOKEN_SECRET", "ACCESS_TOKEN_SECRET");
define("REQUEST_TOKEN", "REQUEST_TOKEN");
define("REQUEST_TOKEN_SECRET", "REQUEST_TOKEN_SECRET");

class TumblrOAuth {
  private OAuthConsumer $consumer;
  private OAuthToken $access_token;
  private string $oauth_token;
  private OAuthSignatureMethod $sig_method;

  /** @var array<string, mixed> */
  private array $oauth_params; // array containing parameters to pass to oauth request
  private TumblrConfig $config;

  public function __construct(TumblrConfig $config) {
    $this->sig_method = new OAuthSignatureMethod_HMAC_SHA1();
    $this->config = $config;

    $this->consumer = new OAuthConsumer($config->getConsumerKey(), $config->getConsumerSecret(), NULL);
    $this->access_token = new OAuthToken($config->getOauthToken(), $config->getOauthTokenSecret());
    $this->oauth_token = $config->getOauthToken();

    $this->oauth_params = array('oauth_token' => $this->oauth_token);
  }

  /**
   * @param string $url
   * @param array<string, mixed> $params
   * @return array{'status': int, 'result': string} 'status' (HTTP code) and result (the result)
   * /*
   * @throws TumblrException
   */
  public static function do_request(string $url, array $params): array {
    $request_data = http_build_query($params);

    // Send the POST request (with cURL)
    $c = curl_init();
    curl_setopt($c, CURLOPT_URL, $url);
    curl_setopt($c, CURLOPT_POST, true);
    curl_setopt($c, CURLOPT_POSTFIELDS, $request_data);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    /** @var string|false $result */
    $result = curl_exec($c);
    $status = curl_getinfo($c, CURLINFO_HTTP_CODE);
    curl_close($c);

    if ($result === false) {
      throw new TumblrException("Unable to obtain result from $url");
    }

    return array(
      'status' => $status,
      'result' => $result
    );
  }

  /**
   * Make an authorize oAuth request
   * @param array<string, mixed> $params containing parameters to pass to authorize request
   * @return string the authorized url to call
   * @throws TumblrException
   * @throws OAuthException
   */
  static function authorize(array $params): string {
    $test_consumer = new OAuthConsumer(OAUTH_CONSUMER_KEY, OAUTH_SECRET_KEY, NULL);
    $result = self::oauth_request(REQUEST_TOKEN_URL, $test_consumer, NULL, $params);

    $oauth_token = $result['oauth_token'];
    $oauth_token_secret = $result['oauth_token_secret'];
    $_SESSION[REQUEST_TOKEN] = $oauth_token;
    $_SESSION[REQUEST_TOKEN_SECRET] = $oauth_token_secret;

    return AUTHORIZE_URL . '?oauth_token=' . $oauth_token;
  }

  /**
   * @param string $url
   * @param OAuthConsumer $consumer
   * @param OAuthToken|null $token
   * @param array<string, mixed> $params
   * @param bool $parse_response
   * @param string $http_method
   * @return array<string, mixed>
   * @throws OAuthException
   * @throws TumblrException
   */
  protected static function oauth_request(
    string        $url,
    OAuthConsumer $consumer,
    ?OAuthToken   $token,
    array         $params,
    bool          $parse_response = true,
    string        $http_method = 'POST'
  ): array {
    $sig_method = new OAuthSignatureMethod_HMAC_SHA1();

    $req = OAuthRequest::from_consumer_and_token($consumer, $token, $http_method, $url, $params);
    $req->sign_request($sig_method, $consumer, $token);

    $response = self::executeOAuthRequest($req, $http_method);

    if ($parse_response) {
      $result = array();
      parse_str($response['result'], $result);

      return $result;
    }

    return $response['result'];
  }

  /**
   * @param array<string, mixed> $params
   * @return array<string, mixed>
   * @throws OAuthException
   * @throws TumblrException
   */
  static function access(array $params): array {
    $request_token = $_SESSION[REQUEST_TOKEN];
    $request_token_secret = $_SESSION[REQUEST_TOKEN_SECRET];

    $test_consumer = new OAuthConsumer(OAUTH_CONSUMER_KEY, OAUTH_SECRET_KEY, NULL);
    $test_token = new OAuthToken($request_token, $request_token_secret);

    return self::oauth_request(ACCESS_TOKEN_URL, $test_consumer, $test_token, $params);
  }

  public function getConfig(): TumblrConfig {
    return $this->config;
  }

  /**
   * @param string $url
   * @param array<string, mixed> $params
   * @param string $http_method
   * @return array<string, mixed>
   * @throws OAuthException
   * @throws TumblrException
   */
  function do_logged_request(string $url, array $params, string $http_method = 'POST'): array {
    $params = array_merge($params, $this->oauth_params);

    $req = OAuthRequest::from_consumer_and_token($this->consumer, $this->access_token, $http_method, $url, $params);
    $req->sign_request($this->sig_method, $this->consumer, $this->access_token);

    return self::executeOAuthRequest($req, $http_method);
  }

  /**
   * @param OAuthRequest $oauth_req
   * @param string $http_method
   * @return array<string, mixed>
   * @throws OAuthException
   * @throws TumblrException
   */
  protected static function executeOAuthRequest(OAuthRequest $oauth_req, string $http_method): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $oauth_req->to_url());
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, explode(',', $oauth_req->to_header()));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    if ($http_method == 'POST') {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $oauth_req->to_postdata());
    }

    $res = curl_exec($ch);
    if ($res === false) {
      echo 'Curl error: ' . curl_error($ch) . "\n";
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return self::checkResult(array('status' => $status, 'result' => json_decode((string)$res, true)));
  }

  /**
   * @param array<string, mixed> $json
   * @return array<string, mixed>
   * @throws TumblrException
   */
  static function checkResult(array $json): array {
    if (!isset($json['result']['meta'])) {
      throw new TumblrException("Invalid tumblr response, meta not found");
    }
    $result = $json['result'];
    $status = $result["meta"]["status"];

    if ($status != 200 && $status != 201) {
      $errorMessage = self::getErrorFromResponse($result);
      if ($errorMessage == null) {
        $errorMessage = $result["meta"]["msg"];
      }
      throw new TumblrException($errorMessage, $status);
    }
    return $json;
  }

  /**
   * @param array<string, mixed> $json
   * @return string|null
   */
  static function getErrorFromResponse(array $json): string|null {
    if (isset($json["response"])) {
      $arr = $json["response"];
      // for example when an invalid id is passed the returned response contains an empty array
      if ($arr != null && count($arr) > 0) {
        $response = $json["response"];
        if (isset($response["errors"])) {
          $errors = $response["errors"];
          return join(",", $errors);
        }
      }
    }
    return null;
  }

}
