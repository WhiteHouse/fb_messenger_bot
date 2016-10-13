<?php
namespace Drupal\Tests\fb_messenger_bot\Unit\Message;

use Drupal\Tests\UnitTestCase;
use Drupal\fb_messenger_bot\Message\VideoMessage;

/**
 * Unit tests for the VideoMessage class.
 *
 * @group fb_messenger_bot
 */
class VideoMessageTest extends UnitTestCase {

  /**
   * Tests that the constructor rejects non URL input.
   *
   * @dataProvider invalidUrlProvider
   *
   * @expectedException \InvalidArgumentException
   */
  public function testInvalidUrls($url) {
    $videoMessage = new VideoMessage($url);
  }

  /**
   * Tests that the constructor accepts valid URL.
   *
   * @dataProvider validUrlProvider
   */
  public function testValidUrls($url) {
    $videoMessage = new VideoMessage($url);
    $this->assertInstanceOf('\Drupal\fb_messenger_bot\Message\VideoMessage', $videoMessage);
  }

  /**
   * Data provider of invalid Urls.
   *
   * @return array
   *   Bad URLs with which to test the constructor.
   */
  public function invalidUrlProvider() {
    return array(
      'string'          => array('This is a string'),
      'missingProtocol' => array('www.facebook.com'),
    );
  }

  /**
   * Data provider of valid Urls.
   *
   * @return array
   *   Bad URLs with which to test the constructor.
   */
  public function validUrlProvider() {
    return array(
      'sampleVideo'  => array('http://techslides.com/demos/sample-videos/small.mp4'),
      'youtubeVideo' => array('https://www.youtube.com/watch?v=lZ-s3DRZJKY'),
    );
  }

}
