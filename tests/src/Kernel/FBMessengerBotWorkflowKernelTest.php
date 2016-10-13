<?php

namespace Drupal\Tests\fb_messenger_bot\Kernel;

use Drupal\Core\Config\ConfigFactory;
use Drupal\fb_messenger_bot\Entity\BotConversation;
use Drupal\KernelTests\KernelTestBase;
use Drupal\fb_messenger_bot\Workflow\FBMessengerBotWorkflow;

/**
 * Tests for the FBMessengerBotWorkflow class.
 *
 * @group fb_messenger_bot
 */
class FBMessengerBotWorkflowKernelTest extends KernelTestBase {
  use ReflectionTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('fb_messenger_bot');

  /**
   * A converstaion factory.
   *
   * @var \Drupal\fb_messenger_bot\Conversation\ConversationFactory
   */
  protected $conversationFactory;

  protected $existingConversationUid;

  protected $existingConversationLastStep;

  protected $nonExistingConversationUid;

  /**
   * An instantiated Conversation for later use.
   *
   * @var \Drupal\fb_messenger_bot\Conversation\BotConversationInterface
   */
  protected $existingConversation;

  /**
   * @var \Drupal\fb_messenger_bot\FacebookService
   */
  protected $fbService;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * An instantiated Workflow for later use.
   *
   * @var \Drupal\fb_messenger_bot\Workflow\FBMessengerBotWorkflow
   */
  protected $myWorkflow;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('fb_messenger_bot_conversation');
    $this->installConfig(static::$modules);

    // Add a conversation to the db to test the ConversationFactory's ability
    // to load an existing conversation.
    $this->existingConversationUid = '12345';
    $this->nonExistingConversationUid = '23456';
    $this->existingConversationLastStep = 10;
    BotConversation::create([
      'userID' => $this->existingConversationUid,
      'last_step' => $this->existingConversationLastStep,
    ])->save();

    // Instantiate an instance of the ConversationFactory.
    $conversationFactory = $this->container->get('fb_messenger_bot.conversation_factory');
    $this->conversationFactory = $conversationFactory;

    // Get a Conversation instance.
    $this->existingConversation = $this->conversationFactory->getConversation($this->existingConversationUid);

    $configFactory = \Drupal::configFactory();
    $stringTranslation = \Drupal::getContainer()->get('string_translation');

    // Instantiate an instance of the fbService.
    $fbService = $this->container->get('fb_messenger_bot.fb_service');
    $this->fbService = $fbService;

    // Instantiate an instance of the logger.
    $logger = $this->container->get('logger.channel.fb_messenger_bot');
    $this->logger = $logger;

    // Instantiate a workflow instance.
    $this->myWorkflow = new FBMessengerBotWorkflow($configFactory, $conversationFactory, $stringTranslation, $fbService, $logger);
  }

  /**
   * Test the workflow's start over functionality.
   */
  public function testStartOver() {

    $outgoingMessage = $this->invokeMethod($this->myWorkflow, 'startOver', array(&$this->existingConversation));

    // Get default step.
    $defaultStep = $this->invokeMethod($this->myWorkflow, 'getDefaultStep');

    // The returned Message is the questionMessage of the default step.
    $this->assertEquals($outgoingMessage, $this->invokeMethod($this->myWorkflow, 'getStep', array($defaultStep))->getQuestionMessage(),
      "Returned message from startOver does not match expected message");

    // The last step set is the default step.
    $this->assertEquals($defaultStep, $this->existingConversation->getLastStep(),
      "Conversation's lastStep is not 'welcome.'");
  }

  /**
   * Test that our customized preprocessSpecialMessages method works.
   *
   * It should be catching variants of 'Start Over' and invoking their custom
   * method.  We're using a reflection method to allow access to the protected
   * preprocessSpecialMessages method.
   *
   * @param string $content
   *   The text of the message_content to test.
   * @param bool $expectedResult
   *   The expected outcome of the preprocessSpecialMessages invocation.
   *
   * @dataProvider startOverMessageProvider
   */
  public function testpreprocessSpecialMessages($content, $expectedResult) {
    $incomingMessage = array(
      'message_type' => 'text',
      'message_content' => $content,
    );

    $this->assertTrue($expectedResult == $this->invokeMethod($this->myWorkflow, 'preprocessSpecialMessages', array($incomingMessage, &$this->existingConversation)));
  }

  /**
   * Provides data for the testpreprocessSpecialMessages test.
   *
   * @return array
   *   Set of data to be used for testing the start over implementation.
   */
  public function startOverMessageProvider() {
    return array(
      'lower-spaced'       => array('start over', TRUE),
      'wordcase-spaced'    => array('Start Over', TRUE),
      'wordcase-nospace'   => array('StartOver', TRUE),
      'leadingWhitespace'  => array('  Start over', TRUE),
      'trailingWhitespace' => array('sTart over  ', TRUE),
      'notCompleteMessage' => array('I wish we could Start Over', FALSE),
      'rightWordsWrongOrder' => array('Over start', FALSE),
    );
  }

}
