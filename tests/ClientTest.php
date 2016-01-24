<?php

use StoryServer\Client;

class ClientTest extends \PHPUnit_Framework_TestCase {

  protected $client;

  public function setUp()
  {
    $options = [
      "formats"       => ["thumbnail" => "450x300", "preview" => "900l", "large" => "1024l", "special" => "175x55"],
      "storyServer"   => "http://0.0.0.0:9233/api/v1",
      "appServer"     => "http://www.somewebsite.com",
      "keyId"         => '23u73reqmzh4x4y0ujyanv0r',
      "secretKey"     => '1ce48e640809f8622647ae8b75f7970ad8733c207b31c901e8134c5c652bce6c'
    ];

    $this->client = new Client($options);
  }

  /**
   * @test
   */
  public function getIndex()
  {
    $result = $this->client->getIndex();
    $this->assertArrayHasKey('data', $result);
    $this->assertArrayHasKey('appServer', $result);
    //fwrite(STDERR, print_r($result, TRUE));
  }

  /**
   * @test
   */
  public function getStory()
  {
    $result = $this->client->getStoryById(21);
    $this->assertArrayHasKey('data', $result);
    $this->assertArrayHasKey('appServer', $result);
    //fwrite(STDERR, print_r($result, TRUE));
  }
}