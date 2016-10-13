<?php
/**
 * @file
 * Contains Drupal\fb_messenger_bot\Message\ButtonMessageTest.
 */

namespace Drupal\Tests\fb_messenger_bot\Unit\Message;

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Drupal\Tests\UnitTestCase;
use Drupal\fb_messenger_bot\Message\ButtonMessage;
use Drupal\fb_messenger_bot\Message\PostbackButton;
use Drupal\fb_messenger_bot\Message\UrlButton;

/**
 * Unit tests for the ButtonMessage class.
 *
 * @group fb_messenger_bot
 */
class ButtonMessageTest extends UnitTestCase {
  protected $text;
  protected $postbackButton;
  protected $urlButton;
  protected $buttonMessage;


  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->text = 'A sample string to accompany button test objects.';
    $this->postbackButton = new PostbackButton('Postback Button', 'POSTBACK_PAYLOAD');
    $this->urlButton = new UrlButton('URL Button', 'http://www.example.com');
    $this->buttonMessage = new ButtonMessage($this->text, array($this->postbackButton, $this->urlButton));
  }

  /**
   * Test getFormattedMessage() returns expected array structure.
   */
  public function testGetFormattedMessageArrayStructure() {
    $returned_value = $this->buttonMessage->getFormattedMessage();
    $this->assertInternalType('array', $returned_value);
    $this->assertNotEmpty($returned_value['attachment']);
    $this->assertNotEmpty($returned_value['attachment']['type']);
    $this->assertNotEmpty($returned_value['attachment']['payload']);
    $this->assertNotEmpty($returned_value['attachment']['payload']['template_type']);
    $this->assertNotEmpty($returned_value['attachment']['payload']['text']);
    $this->assertNotEmpty($returned_value['attachment']['payload']['buttons']);
  }

  /**
   * Test an Empty Button Parameter.
   */
  public function testEmptyButtonParameter() {
    $message = new ButtonMessage($this->text);
    $formattedMessage = $message->getFormattedMessage();
    $this->assertEquals($this->text, $formattedMessage['attachment']['payload']['text']);
    $this->assertEmpty($formattedMessage['attachment']['payload']['buttons']);
  }

  /**
   * Test invalidArgumentException is thrown when the $buttons parameter contains anything other than \Drupal\fb_messenger_bot\Message\ButtonBase buttons.
   *
   * @expectedException InvalidArgumentException
   */
  public function testInvalidButtonObjectThrowsException() {
    $button = ['foo' => 'bar'];
    new ButtonMessage($this->text, $button);
    $this->setExpectedException('invalidArgumentException');
  }

}
