<?php

namespace StoryServer;

class StoryServerClientError extends \Exception { };

/**
 * Class Client
 * @package StoryServer
 */
class Client {
  /** @var \GuzzleHttp\Client client */
  private $guzzle;

  /** @var array formats */
  private $formats;

  /** @var string server */
  private $storyServer;

  /** @var string server */
  private $appServer;

  /** @var string keyId */
  private $keyId;

  /** @var string secretKey */
  private $secretKey;

  /**
   * @param array $options
   */
  public function __construct(array $options) {
    $this->guzzle = new \GuzzleHttp\Client();
    $this->formats = $options["formats"];
    $this->storyServer = $options["storyServer"];
    $this->appServer = $options["appServer"];
    $this->keyId = $options["keyId"];
    $this->secretKey = $options["secretKey"];
  }


  /**
   * Get the index of stories.
   * @param null $storyIds
   * @param string $path
   * @return array
   */
  public function getIndex($storyIds = null, $path = '') {

    $query = '';
    if (!empty($storyIds)) {
      $query = "ids=" . $storyIds;
    }

    $result = $this->guzzleRequest($this->storyServer . '/stories/' . $this->keyId, $query);

    return [
      "data" => $result['data'],
      "appServer" => (empty($path)) ? $this->appServer : $this->appServer . '/' . $path
    ];
  }

  /**
   * Get story
   * @param $storyId
   * @param string $path
   * @return array
   */
  public function getStoryById($storyId, $path = '') {
    $result = $this->guzzleRequest($this->storyServer . '/stories/' . $this->keyId . '/' . $storyId);
    return [
      "storyId" => $storyId,
      "data" => $result['data'],
      "appServer" => (empty($path)) ? $this->appServer : $this->appServer . '/' . $path
    ];
  }

  /**
   * Get story by url
   * @param $url
   * @param string $path
   * @return array
   */
  public function getStoryByUrl($url, $path = '') {
    $result = $this->guzzleRequest($this->storyServer . '/stories/' . $this->keyId . '/url/' . $url);
    return [
      "url" => $url,
      "data" => $result['data'],
      "appServer" => (empty($path)) ? $this->appServer : $this->appServer . '/' . $path
    ];
  }

  /**
   * Create signed authorization header.
   * @return array
   * @throws InvalidAlgorithmError
   * @throws MissingHeaderError
   * @throws \Exception
   */
  private function createAuthHeader() {
    $date = gmdate(DATE_RFC1123);
    $headers = array('date' => $date);

    HTTPSignature::sign($headers, array(
      'secretKey' => $this->secretKey,
      'keyId' => $this->keyId,
      'algorithm' => 'hmac-sha1'
    ));

    return $headers;
  }

  /**
   * Guzzle request
   * @param $url
   * @param string $query
   * @return mixed|\Psr\Http\Message\StreamInterface
   */
  private function guzzleRequest($url, $query = '') {
    $headers = $this->createAuthHeader();
    $headers['formats'] = json_encode($this->formats);

    if(!empty($query)) {
      $response = $this->guzzle->get($url, [
        'headers' => $headers,
        'query' => $query
      ]);
    } else {
      $response = $this->guzzle->get($url, [
        'headers' => $headers
      ]);
    }

    $body = $response->getBody();
    $safeJson = str_replace("\\", "\\\\", $body); //Prepares JSON string for inclusion in JavaScript
    $safeJson = str_replace("'", "\\'",$safeJson);

    $result = [
      "status" => $response->getStatusCode(), // 200 etc.
      "contentType" => $response->getHeader('content-type'), // 'application/json; charset=utf8'
      "body" => $body,
      "data" => json_decode($body), //Parse json to array
      "safeJson" => $safeJson
    ];

    //$data = htmlspecialchars($body , ENT_QUOTES & ~ENT_COMPAT, "UTF-8"); //Encode but leave double quotes in JSON alone.
    //$data = htmlentities($body , ENT_QUOTES & ~ENT_COMPAT, "UTF-8"); //Encode but leave double quotes in JSON alone.
    return $result;

  }
}
