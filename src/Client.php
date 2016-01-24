<?php

namespace StoryServer;

class StoryServerClientError extends \Exception { };

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

  public function __construct(array $options) {
    $this->guzzle = new \GuzzleHttp\Client();
    $this->formats = $options["formats"];
    $this->storyServer = $options["storyServer"];
    $this->appServer = $options["appServer"];
    $this->secretKeyId = $options["secretKeyId"];
    $this->secretKey = $options["secretKey"];
  }


  /**
   * Get the index of stories.
   *
   * @param string $storyIds optional group by storyIds
   *
   * @param string $path
   *
   * @return array
   */
  public function getIndex($storyIds = null, $path = '') {

    $query = '';
    if (!empty($storyIds)) {
      $query = "ids=" . $storyIds;
    }

    $data = $this->guzzleRequest($this->storyServer . '/stories/' . $this->keyId, $query);

    return [
      "data" => $data,
      "appServer" => (empty($path)) ? $this->appServer : $this->appServer . '/' . $path
    ];
  }

  /**
   * Get story
   *
   * @param string $storyId
   *
   * @param string $path
   *
   * @return array
   */
  public function getStoryById($storyId, $path = '') {
    $data = $this->guzzleRequest($this->storyServer . '/stories/' . $this->keyId . '/' . $storyId);
    return [
      "storyId" => $storyId,
      "data" => $data,
      "appServer" => (empty($path)) ? $this->appServer : $this->appServer . '/' . $path
    ];
  }

  /**
   * Get story by url
   *
   * @param string $url
   *
   * @param string $path
   *
   * @return array
   */
  public function getStoryByUrl($url, $path = '') {
    $data = $this->guzzleRequest($this->storyServer . '/stories/' . $this->keyId . '/url/' . $url);
    return [
      "url" => $url,
      "data" => $data,
      "appServer" => (empty($path)) ? $this->appServer : $this->appServer . '/' . $path
    ];
  }

  /**
   * Create signed authorization header.
   *
   * @return string
   */
  private function createAuthHeader() {
    $date = gmdate(DATE_RFC1123);
    $headers = array('date' => $date);

    \StoryServer\HTTPSignature::sign($headers, array(
      'secretKey' => $this->secretKey,
      'keyId' => $this->keyId,
      'algorithm' => 'hmac-sha1'
    ));

    return $headers;
  }

  /**
   * Guzzle request
   *
   * @param string $url api url
   *
   * @param string $query optional query string arguments
   *
   * @return string json result
   */
  private function guzzleRequest($url, $query = '') {
    $headers = $this->createAuthHeader();
    $headers['formats'] = json_encode($this->formats);

    if(!empty($query)) {
      $res = $this->guzzle->get($url, [
        'headers' => $headers,
        'query' => $query
      ]);
    } else {
      $res = $this->guzzle->get($url, [
        'headers' => $headers
      ]);
    }

    $status = $res->getStatusCode(); // 200
    $contentType = $res->getHeader('content-type'); // 'application/json; charset=utf8'
    $body = $res->getBody();
    //$data = htmlspecialchars($body , ENT_QUOTES & ~ENT_COMPAT, "UTF-8"); //Encode but leave double quotes in JSON alone.
    //$data = htmlentities($body , ENT_QUOTES & ~ENT_COMPAT, "UTF-8"); //Encode but leave double quotes in JSON alone.
    $body = str_replace("\\", "\\\\", $body); //Prepares JSON string for inclusion in JavaScript
    $body = str_replace("'", "\\'",$body);
    return $body;

  }
}
