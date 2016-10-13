<?php
/**
 * @file
 * Contains Drupal\fb_messenger_bot\FacebookGenericMessageTest.
 */

namespace Drupal\Tests\fb_messenger_bot\Unit\Message;

use Drupal\fb_messenger_bot\Message\FacebookGenericMessage;

/**
 * Unit tests for the FacebookGenericMessage class.
 *
 * @group fb_messenger_bot
 */
class FacebookGenericMessageTest extends \PHPUnit_Framework_TestCase {
  protected $elements;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->elements = array(
      array(
        'title' => 'Check it out',
        'item_url' => "https://www.youtube.com/watch?v=lZ-s3DRZJKY",
        'image_url' => "http://www.gstatic.com/webp/gallery/1.jpg",
        'subtitle' => 'Bubble subtitle',
        'buttons' => array(
          array(
            'type' => 'postback',
            'title' => 'Postback Button',
            'payload' => 'postback_button',
          ),
          array(
            'type' => 'web_url',
            'title' => 'Example website',
            'url' => 'www.example.com',
          ),
        ),
      ),
    );
    $this->FacebookGenericMessage = new FacebookGenericMessage($this->elements);
  }

  /**
   * Test getFormattedMessage() return value.
   */
  public function testGetFormattedMessageReturnsArray() {
    $returned_value = $this->FacebookGenericMessage->getFormattedMessage();
    $this->assertInternalType('array', $returned_value);
    $this->assertNotEmpty($returned_value['attachment']['payload']['elements']);
    $this->assertEquals($this->elements, $returned_value['attachment']['payload']['elements']);
  }

}
