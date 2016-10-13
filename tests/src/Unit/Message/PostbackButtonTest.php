<?php
/**
 * @file
 * Contains Drupal\fb_messenger_bot\Message\PostbackButtonTest.
 */

namespace Drupal\Tests\fb_messenger_bot\Unit\Message;

use Drupal\Tests\UnitTestCase;
use Drupal\fb_messenger_bot\Message\PostbackButton;

/**
 * Unit tests for the PostbackButton Class
 *
 * @group fb_messenger_bot
 */
class PostbackButtonTest extends UnitTestCase {
  public $title;
  public $payload;
  public $button;

  public function setUp() {
    $this->title = 'URL Button';
    $this->postback = 'http://www.example.com';
    $this->button = new PostbackButton($this->title, $this->payload);
  }

  public function testFormattedButtonContent() {
    $button_array = $this->button->toArray();
    $this->assertEquals($button_array['type'], 'postback');
    $this->assertEquals($button_array['title'], $this->title);
    $this->assertEquals($button_array['payload'], $this->payload);
  }

}
