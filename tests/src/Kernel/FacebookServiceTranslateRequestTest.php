<?php

namespace Drupal\Tests\fb_messenger_bot\Kernel;

use Drupal\fb_messenger_bot\FacebookService;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for the Conversation Factory class.
 *
 * @group fb_messenger_bot
 */
class FacebookServiceTranslateRequestTest extends KernelTestBase {

  use MessageDataProviderTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('fb_messenger_bot');

  public $service;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->service = $this->container->get('fb_messenger_bot.fb_service');
  }

  /**
   * Test ConversationFactory instantiation.
   */
  public function testServiceShouldInstantiate() {
    $this->assertInstanceOf('\Drupal\fb_messenger_bot\FacebookService', $this->service, 'FacebookService instantiated is not an instance of \Drupal\fb_messenger_bot\FacebookService');
  }

  /**
   * Test decoding message data.
   *
   * @dataProvider multipleRequestDataProvider
   */
  public function testServiceShouldTranslateValidMessageRequests($json) {
    $original = $this->getTestTextMessage();
    $uid = $original['entry'][0]['messaging'][0]['sender']['id'];
    $translated = $this->service->translateRequest($json);
    $this->assertInternalType('array', $translated);
    $this->assertNotNull($translated[$uid]);
  }

  /**
   * Test decoding invalid message data.
   *
   * @dataProvider invalidMessageDataProvider
   */
  public function testServiceShouldNotTranslateInvalidMessageRequests($json) {
    $translated = $this->service->translateRequest($json);
    $this->assertInternalType('array', $translated);
    $this->assertEmpty($translated);
  }

  /**
   * Test decoding multiple messages from a single request.
   *
   * @dataProvider mixedMessageDataProvider
   */
  public function testServiceShouldTranslateMultipleMessagesFromSingleRequest($json) {
    $original = $this->getMultipleTestMessages();

    // Account for the mixedMessageDataProvider's single invalid message
    // included in the request data.
    $expectedCount = count($original['entry'][0]['messaging']) - 1;

    $translated = $this->service->translateRequest($json);
    $this->assertInternalType('array', $translated);
    $this->assertEquals($expectedCount, count($translated));
  }

  /**
   * Test assignment of text message type.
   *
   * @dataProvider textMessageDataProvider
   */
  public function testServiceShouldAssignTextMessageType($json) {
    $original = $this->getTestTextMessage();
    $uid = $original['entry'][0]['messaging'][0]['sender']['id'];
    $translated = $this->service->translateRequest($json);
    $this->assertEquals(FacebookService::MESSAGE_TYPE_TEXT, $translated[$uid][0]['message_type']);
  }

  /**
   * Test assignment of postback message type.
   *
   * @dataProvider postbackMessageDataProvider
   */
  public function testServiceShouldAssignPostbackMessageType($json) {
    $original = $this->getTestTextMessage();
    $uid = $original['entry'][0]['messaging'][0]['sender']['id'];
    $translated = $this->service->translateRequest($json);
    $this->assertEquals(FacebookService::MESSAGE_TYPE_POSTBACK, $translated[$uid][0]['message_type']);
  }

  /**
   * Test assignment of attachment message type.
   *
   * @dataProvider attachmentMessageDataProvider
   */
  public function testServiceShouldAssignAttachentMessageType($json) {
    $original = $this->getTestTextMessage();
    $uid = $original['entry'][0]['messaging'][0]['sender']['id'];
    $translated = $this->service->translateRequest($json);
    $this->assertEquals(FacebookService::MESSAGE_TYPE_ATTACHMENT, $translated[$uid][0]['message_type']);
  }

}
