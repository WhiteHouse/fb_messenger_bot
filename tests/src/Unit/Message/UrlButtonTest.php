<?php
/**
 * @file
 * Contains Drupal\fb_messenger_bot\Message\UrlButtonTest.
 */

namespace Drupal\Tests\fb_messenger_bot\Unit\Message;

use Drupal\Tests\UnitTestCase;
use Drupal\fb_messenger_bot\Message\UrlButton;

/**
 * Unit tests for the UrlButton Class
 *
 * @group fb_messenger_bot
 */
class UrlButtonTest extends UnitTestCase {
  public $title;
  public $url;
  public $button;

  public function setUp() {
    $this->title = 'URL Button';
    $this->url = 'http://www.example.com';
    $this->button = new UrlButton($this->title, $this->url);
  }

  public function testFormattedButtonContent() {
    $button_array = $this->button->toArray();
    $this->assertEquals($button_array['type'], 'web_url');
    $this->assertEquals($button_array['title'], $this->title);
    $this->assertEquals($button_array['url'], $this->url);
  }

}
