<?php
/**
 * @file
 * Contains Drupal\fb_messenger_bot\Message\TextMessageTest.
 */

namespace Drupal\Tests\fb_messenger_bot\Unit\Message;

use Drupal\Tests\UnitTestCase;
use Drupal\fb_messenger_bot\Message\TextMessage;

/**
 * Unit tests for the TextMessage class.
 *
 * @group fb_messenger_bot
 */
class TextMessageTest extends UnitTestCase {
  protected $text;
  protected $textMessage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->text = 'A sample string for testing.';
    $this->textMessage = new textMessage($this->text);
  }

  /**
   * Test getFormattedMessage() return value.
   */
  public function testGetFormattedMessageReturnsArray() {
    $returned_value = $this->textMessage->getFormattedMessage();
    $this->assertInternalType('array', $returned_value);
    $this->assertNotEmpty($returned_value['text']);
    $this->assertEquals($this->text, $returned_value['text']);
  }

}
