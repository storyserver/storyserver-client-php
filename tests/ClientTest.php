<?php

use StoryServer\Client;

class ClientTest extends \PHPUnit_Framework_TestCase {

  /**
   * @test
   */
  public function hasConfig()
  {
    $client = new Client;
    $this->assertTrue($client->hasConfig());
  }

}