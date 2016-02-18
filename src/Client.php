<?php

namespace StoryServer;

/**
 * Class StoryServerClientError
 * @package StoryServer
 */
class StoryServerClientError extends \Exception { };

/**
 * Class Client
 * @package StoryServer
 */
class Client {
  /** @var \GuzzleHttp\Client client */
  private $client;

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
    $this->client       = new \GuzzleHttp\Client();
    $this->formats      = $options["formats"];
    $this->storyServer  = $options["storyServer"];
    $this->appServer    = $options["appServer"];
    $this->keyId        = $options["keyId"];
    $this->secretKey    = $options["secretKey"];
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

    $result = $this->clientRequest($this->storyServer . '/stories/' . $this->keyId, $query);

    return [
      "data" => $result['data'],
      "raw" => $result['raw'],
      "safeJson" => $result['safeJson'],
      "appServer" => (empty($path)) ? $this->appServer : $this->appServer . '/' . $path
    ];
  }

  /**
   * Get Story by Id
   * @param $storyId
   * @param string $path
   * @return array
   */
  public function getStoryById($storyId, $path = '') {
    $result = $this->clientRequest($this->storyServer . '/stories/' . $this->keyId . '/' . $storyId);
    return [
      "storyId" => $storyId,
      "data" => $result['data'],
      "raw" => $result['raw'],
      "safeJson" => $result['safeJson'],
      "appServer" => (empty($path)) ? $this->appServer : $this->appServer . '/' . $path
    ];
  }


  /**
   * Get Story by Url
   * @param $url
   * @param string $path
   * @return array
   */
  public function getStoryByUrl($url, $path = '') {
    $result = $this->clientRequest($this->storyServer . '/stories/' . $this->keyId . '/url/' . $url);
    return [
      "url" => $url,
      "data" => $result['data'],
      "raw" => $result['raw'],
      "safeJson" => $result['safeJson'],
      "appServer" => (empty($path)) ? $this->appServer : $this->appServer . '/' . $path
    ];
  }

  /**
   * Get Story Names
   * @param query
   * @return array
   */
  public function getStoryNames($query) {

    $query = "storyname=" . $query;

    $result = $this->clientRequest($this->storyServer . '/stories/' . $this->keyId . '/names', $query);
    return [
      "data" => $result['data'],
      "body" => $result['body']
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
   * @return array
   * @throws StoryServerClientError
   */
  private function clientRequest($url, $query = '') {

    try {
      $headers = $this->createAuthHeader();
      if($this->formats) {
        $headers['formats'] = json_encode($this->formats);
      }
      $headers['accept'] = 'application/vnd.storyserver+json';

      $params = ['headers' => $headers];
      if(!empty($query)) {
        $params['query'] = $query;
      }

      $response = $this->client->get($url, $params);

      $body = $response->getBody();
      $safeJson = str_replace("\\", "\\\\", $body); //Prepares JSON string for inclusion in JavaScript
      $safeJson = str_replace("'", "\\'",$safeJson);

      $result = [
        "status" => $response->getStatusCode(), // 200 etc.
        "contentType" => $response->getHeader('content-type'), // 'application/json; charset=utf8'
        "raw" => (string)$body,
        "data" => json_decode($body), //Parse json to array
        "safeJson" => $safeJson
      ];

      //$data = htmlspecialchars($body , ENT_QUOTES & ~ENT_COMPAT, "UTF-8"); //Encode but leave double quotes in JSON alone.
      //$data = htmlentities($body , ENT_QUOTES & ~ENT_COMPAT, "UTF-8"); //Encode but leave double quotes in JSON alone.
      return $result;
    }
    catch (\Exception $e) {
      throw new StoryServerClientError('StoryServer\StoryServerClientError: ' . $e->getMessage(), $e->getCode(), $e);
    }
  }
}
