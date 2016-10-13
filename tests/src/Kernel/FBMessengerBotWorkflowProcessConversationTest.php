<?php

namespace Drupal\Tests\fb_messenger_bot\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Serialization\Json;
use Drupal\fb_messenger_bot\FacebookService;
use Psr\Log\LoggerInterface;

/**
 * Tests for the FBMessengerBotWorkflow ProcessConversation method.
 *
 * @group fb_messenger_bot
 */
class FBMessengerBotWorkflowProcessConversationTest extends KernelTestBase {

  use MessageDataProviderTrait, reflectionTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('system', 'fb_messenger_bot');

  /**
   * The Bot Workflow.
   *
   * @var \Drupal\fb_messenger_bot\Workflow\BotWorkflowInterface
   */
  protected $workflow;

  /**
   * The Conversation factory.
   *
   * @var \Drupal\fb_messenger_bot\Conversation\ConversationFactoryInterface
   */
  protected $conversationFactory;

  /**
   * A reusable conversation uid.
   *
   * @var string
   */
  protected $conversationUid;

  /**
   * An instantiated Conversation, using the reusable uid.
   *
   * @var \Drupal\fb_messenger_bot\Conversation\BotConversationInterface
   */
  protected $conversation;

  /**
   * An instantiated Conversation for later use.
   *
   * @var \Drupal\fb_messenger_bot\Conversation\BotConversationInterface
   */
  protected $newConversation;

  /**
   * The Facebook Service.
   *
   * @var \Drupal\fb_messenger_bot\FacebookService
   */
  protected $fbService;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The Bot instance.
   *
   * @var \Drupal\fb_messenger_bot\Bot\BotInterface
   */
  protected $bot;

  /**
   * The trolling threshold, loaded from the module's default config.
   *
   * @var int
   */
  protected $trollingThreshold;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('fb_messenger_bot_conversation');
    $this->installConfig(static::$modules);

    $this->workflow = $this->container->get('fb_messenger_bot.workflow');
    $this->fbService = $this->container->get('fb_messenger_bot.fb_service');
    $this->bot = $this->container->get('fb_messenger_bot.bot');
    $this->logger = $this->container->get('logger.channel.fb_messenger_bot');
    $this->conversationUid = 123;
    $this->conversationFactory = $this->container->get('fb_messenger_bot.conversation_factory');
    $this->conversation = $this->conversationFactory->getConversation($this->conversationUid)->resetErrorCount();
    $this->trollingThreshold = $this->config('fb_messenger_bot.settings')->get('trolling_threshold');

    $this->conversation->setLastStep('welcome');
  }

  /**
   * Test incrementing and resetting a conversation's error counter.
   */
  public function testShouldIncrementAndResetErrorCount() {
    $this->conversation->resetErrorCount();
    $this->assertEquals(0, $this->conversation->getErrorCount());
    $this->conversation->incrementErrorCount();
    $this->assertEquals(1, $this->conversation->getErrorCount());
    $this->conversation->resetErrorCount();
    $this->assertEquals(0, $this->conversation->getErrorCount());
  }

  /**
   * Test that new conversations are instantiated with an error count of 0.
   */
  public function testShouldCreateNewConversationWithEmptyErrorCount() {
    $conversation = $this->conversationFactory->getConversation('1234567890');
    $this->assertEquals(0, $conversation->getErrorCount());
  }

  /**
   * Test that conversations under the threshold do not get a trolling message.
   *
   * @dataProvider translatedPostbackMessageDataProvider
   */
  public function testShouldNotReturnTrollingMessageBeforeThresholdReached($invalidMessage) {
    $i = 0;
    do {
      $response = $this->workflow->processConversation($this->conversation, $invalidMessage);
      $this->assertEquals(++$i, $this->conversation->getErrorCount());
    } while ($i < $this->trollingThreshold - 1);

    $this->assertNotEquals($this->invokeMethod($this->workflow, 'getTrollingMessage'), $response);
  }

  /**
   * Test conversations reaching the trolling threshold get a trolling message.
   *
   * @dataProvider translatedPostbackMessageDataProvider
   */
  public function testShouldReturnTrollingMessageOnThresholdReached($invalidMessage) {
    $i = 0;
    do {
      $response = $this->workflow->processConversation($this->conversation, $invalidMessage);
      $this->assertEquals(++$i, $this->conversation->getErrorCount());
    } while ($i < $this->trollingThreshold);

    $this->assertEquals($this->invokeMethod($this->workflow, 'getTrollingMessage'), $response);

    // Troll some more and ensure the trolling message is still received.
    $response = $this->workflow->processConversation($this->conversation, $invalidMessage);
    $this->assertEquals($this->invokeMethod($this->workflow, 'getTrollingMessage'), $response);
  }

  /**
   * Test that a valid response resets a conversation's error count.
   */
  public function testShouldResetErrorCountOnValidResponse() {
    $invalidMessage = $this->getTestTranslatedPostbackMessage();
    $validMessage = $this->getTestTranslatedTextMessage();
    $this->conversation->resetErrorCount();
    $this->assertEquals(0, $this->conversation->getErrorCount());
    $this->workflow->processConversation($this->conversation, $invalidMessage);
    $this->assertEquals(1, $this->conversation->getErrorCount());
    $this->workflow->processConversation($this->conversation, $validMessage);
    $this->assertEquals(0, $this->conversation->getErrorCount());
  }

  /**
   * Test the workflow's start over functionality.
   */
  public function testNewConversation() {

    // Get step new conversations should default to.
    $defaultStep = $this->invokeMethod($this->workflow, 'getDefaultStep');

    $newConversation = $this->conversationFactory->getConversation('9349172393');
    $incomingMessage = array(
      'message_type' => FacebookService::MESSAGE_TYPE_TEXT,
      'message_content' => 'hello from the other side',
    );
    $this->invokeMethod($this->workflow, 'processConversation', array(&$newConversation, $incomingMessage));
    $this->assertEquals($defaultStep, $newConversation->getLastStep(),
      "Conversation's lastStep is not 'welcome.'");

  }

}
