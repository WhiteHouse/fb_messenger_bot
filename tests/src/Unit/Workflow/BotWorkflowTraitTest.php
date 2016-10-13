<?php

namespace Drupal\Tests\fb_messenger_bot\Unit\Workflow;

use Drupal\fb_messenger_bot\FacebookService;
use Drupal\fb_messenger_bot\Message\TextMessage;
use Drupal\fb_messenger_bot\Step\BotWorkflowStep;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\fb_messenger_bot\Kernel\ReflectionTrait;
use Psr\Log\LoggerInterface;

/**
 * Class BotworkflowTraitTest.
 *
 * @package Drupal\Tests\fb_messenger_bot\Unit
 *
 * @group fb_messenger_bot
 */

class BotWorkflowTraitTest extends UnitTestCase {
  use ReflectionTrait;

  /**
   * Stores a workflow mock for later use.
   *
   * @var $workflow
   */
  public $workflow;

  /**
   * Set up a trait mock so we don't have to rebuild it in each step.
   *
   * We stub the preprocessSpecialMessages method so that it does not return the
   * workflow's full implementation and is closer to the method defined in the
   * trait.
   */
  public function setUp() {
    // Instantiate a mock ConfigFactory.
    $configFactory = $this->getConfigFactoryStub([
      'fb_messenger_bot.settings' => [
        'trolling_threshold' => 3,
      ],
    ]);

    // Instantiate mock immuatableConfig for configFactory stub's get() method.
    // @see Drupal\fb_messenger_bot\Workflow\FBMessengerBotWorkflow::buildSteps().
    $immutableConfig = $this->getMockBuilder('\Drupal\Core\Config\ImmutableConfig')
      ->disableOriginalConstructor()
      ->getMock();

    $configFactory->method('get')
      ->willReturn($immutableConfig);

    $conversationFactory = $this->getMockBuilder('\Drupal\fb_messenger_bot\Conversation\ConversationFactory')
      ->disableOriginalConstructor()
      ->getMock();

    $stringTranslation = $this->getStringTranslationStub();

    $mockFbService = $this->getMockBuilder('Drupal\fb_messenger_bot\FacebookService')
      ->disableOriginalConstructor()
      ->getMock();

    $mockLogger = $this->getMockBuilder('Drupal\Core\Logger\LoggerChannelInterface')
      ->getMock();
    $mockLogger->method('debug')
      ->will($this->returnCallback(function($input) {
        print $input;
      }));

    $workflow = $this->getMockBuilder('Drupal\fb_messenger_bot\Workflow\FBMessengerBotWorkflow')
      ->setMethods(array(
        'preprocessSpecialMessages',
        'checkDisallowedMessageType',
      ))
      ->setConstructorArgs(array(
        $configFactory,
        $conversationFactory,
        $stringTranslation,
        $mockFbService,
        $mockLogger,
      ))
      ->getMock();

    $this->workflow = $workflow;
  }

  /**
   * Test that setSteps() accepts an array as input.
   */
  public function testSetStepsAcceptsArray() {

    $mockMessage = $this->getMockBuilder('Drupal\fb_messenger_bot\Message\MessageInterface')
      ->getMock();

    $inputArray = array();

    for ($i = 1; $i <= 5; $i++) {
      /* @noinspection PhpParamsInspection */
      $inputArray[] = new BotWorkflowStep("StepName $i", "step$i", $mockMessage);
    };

    $this->assertTrue($this->workflow->setSteps($inputArray));

    $this->assertTrue($inputArray === $this->workflow->steps);
  }

  /**
   * Test that setSteps rejects back input types.
   *
   * @dataProvider badDataProvider
   *
   * @parameter array $input array of bad data types
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetStepsRejectsBadTypes($input) {

    $this->workflow->setSteps($input);

  }

  /**
   * PHPUnit data provider for bad input types.
   *
   * @return array
   *   Array of bad data types.
   */
  public function badDataProvider() {

    return array(
      'string'                       => array('This is a string'),
      'array of unexpected contents' => array(array(1, 2, 3)),
      'non-iterateable object'       => array(new \stdClass()),
    );

  }

  /**
   * Test that the workflow tests for special message handlers.
   */
  public function testSpecialMessages() {

    // Trait has the expected method to check for special messages.
    $this->assertTrue(method_exists($this->workflow, 'preprocessSpecialMessages'),
      'No preprocessSpecialMessages method found on the BotWorkflowTrait.'
    );

    // Set preprocessSpecialMessages to return TRUE since we don't care about testing
    // the whole processConversation method.
    $this->workflow
      ->method('preprocessSpecialMessages')
      ->willReturn(TRUE);

    // Set checkDisallowedMessageType to return FALSE since we don't care about
    // testing the whole processConversation method.
    $this->workflow
      ->method('checkDisallowedMessageType')
      ->willReturn(FALSE);

    $incoming = array(
      'message_type' => 'text',
      'message_content' => 'start over',
    );

    $mockConversation = $this->getMockBuilder('\Drupal\fb_messenger_bot\Conversation\BotConversationInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue($this->invokeMethod($this->workflow, 'preprocessSpecialMessages', array($incoming, &$mockConversation)));

    $this->assertTrue($this->workflow->processConversation($mockConversation, $incoming));
  }

  /**
   * Test that the workflow tests for disallowed message types.
   */
  public function testCheckDisallowedMessageTypeInvocation() {

    // Trait has the expected method to check for allowed messages.
    $this->assertTrue(method_exists($this->workflow, 'checkDisallowedMessageType'),
      'No checkDisallowedMessageType method found on the BotWorkflowTrait.'
    );

    // Set preprocessSpecialMessages to return FALSE since the message isn't special.
    $this->workflow
      ->method('preprocessSpecialMessages')
      ->willReturn(FALSE);

    // Set up the checkDisallowedMessageType method and it's return value so
    // that we don't actually pass the call through to the Step to validate.
    $expectedResult = array(
      new TextMessage('How about NO'),
    );

    $this->workflow
      ->method('checkDisallowedMessageType')
      ->willReturn($expectedResult);

    // Set up a simulated incoming message from the user.
    $incoming = array(
      'message_type' => FacebookService::MESSAGE_TYPE_ATTACHMENT,
      'message_content' => 'placeholder message text',
    );

    // Set up a Conversation mock for the Workflow to process.
    $mockConversation = $this->getMockBuilder('\Drupal\fb_messenger_bot\Conversation\BotConversationInterface')
      ->disableOriginalConstructor()
      ->getMock();

    // Check that the return value is the array we set above when mocking the
    // checkDisallowedMessageType method.
    $processConversationResult = $this->workflow->processConversation($mockConversation, $incoming);
    $this->assertEquals(
      $expectedResult,
      $processConversationResult
    );
  }

  /**
   * Test the processConversation method's return value.
   *
   * We expect it will return an array of arrays representing outgoing
   * messages to send back to the user.
   */
  public function testProcessConversationReturnValue() {

    $mockConversation = $this->getMockBuilder('\Drupal\fb_messenger_bot\Conversation\BotConversationInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $mockConversation->method('getLastStep')->willReturn('welcome');

    $incomingMessage = array(
      'message_type' => FacebookService::MESSAGE_TYPE_ATTACHMENT,
      'message_content' => 'placeholder message text',
    );

    $this->assertTrue(is_array($this->workflow->processConversation(
      $mockConversation,
      $incomingMessage
    )));
  }

  /**
   * Test the getTrollingMessage method's return value.
   *
   * We expect it will return an array of MessageInterface objects representing
   * outgoing messages to send back to the user.
   */
  public function testGetTrollingMessageReturnValue() {

    $trollingMessages = $this->invokeMethod($this->workflow, 'getTrollingMessage');
    $this->assertTrue(is_array($trollingMessages));

    // Make sure we have at least one trolling message.
    $this->assertTrue(count($trollingMessages) >= 1);

    // Make sure all contents of the array are actually MessagInterfaces.
    foreach ($trollingMessages as $message) {
      $this->assertInstanceOf('\Drupal\fb_messenger_bot\Message\MessageInterface', $message);
    }

  }

}
