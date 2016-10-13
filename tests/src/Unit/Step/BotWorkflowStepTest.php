<?php

namespace Drupal\Tests\fb_messenger_bot\Unit\Step;

use Drupal\Tests\UnitTestCase;
use Drupal\fb_messenger_bot\Message\Message;
use Drupal\fb_messenger_bot\Message\TextMessage;
use Drupal\fb_messenger_bot\Step\BotWorkflowStep;

/**
 * Tests the BotWorkflowStep.
 *
 * @group fb_messenger_bot
 */
class BotWorkflowStepTest extends UnitTestCase {

  /**
   * An instance of MessageInterface.
   *
   * @var \Drupal\fb_messenger_bot\Message\MessageInterface
   */
  public $mockMessage;

  /**
   * The callback used to instantiate the $questionCallbackStep.
   *
   * @var callable
   */
  public $mockMessageCallback;

  /**
   * A step instantiated with a mock message and the default message callback.
   *
   * @var \Drupal\fb_messenger_bot\Step\BotWorkflowStepInterface
   */
  public $questionMessageStep;

  /**
   * A step instantiated with a custom question message callback.
   *
   * @var \Drupal\fb_messenger_bot\Step\BotWorkflowStepInterface
   */
  public $questionCallbackStep;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->mockMessage = $this->getMockBuilder('\Drupal\fb_messenger_bot\Message\MessageInterface')
      ->getMock();

    $this->mockMessageCallback = function($arg) {
      return $arg;
    };

    // Set up instances of BotWorkflowStep using an instance of MessageInterface
    // and a callable callback in each constructor, respectively.
    $this->questionMessageStep = new BotWorkflowStep('Question Message Step', 'questionMessageStep', $this->mockMessage);
    $this->questionCallbackStep = new BotWorkflowStep('Question Callback Step', 'questionCallbackStep', $this->mockMessageCallback);

    // Set response handlers for the question message step.
    $this->questionMessageStep->setResponseHandlers(
      array(
        'Yes' => array(
          'handlerMessage' => new TextMessage('Got a Yes'),
          'goto' => 'nextStep',
        ),
        'No' => array(
          'handlerMessage' => new TextMessage('Got a No'),
          'goto' => 'skipStep',
        ),
        '*' => array(
          'handlerMessage' => new TextMessage('Got something else'),
          'goto' => 'defaultStep',
        ),
      )
    );

    $validationCallback = function($input) {
      return $input['message_content'] !== "fail";
    };

    $this->questionMessageStep->setValidationCallback($validationCallback);

    $this->questionMessageStep->setInvalidResponseMessage(new TextMessage("Sorry. I don't understand."));
  }

  /**
   * Verify we can instantiate the object.
   */
  public function testBasicInstantiation() {
    $this->assertInstanceOf('Drupal\fb_messenger_bot\Step\BotWorkflowStepInterface', $this->questionMessageStep, 'BotWorkflowStep does not implement the BotWorkflowStepInterface.');
    $this->assertInstanceOf('Drupal\fb_messenger_bot\Step\BotWorkflowStepInterface', $this->questionCallbackStep, 'BotWorkflowStep does not implement the BotWorkflowStepInterface.');
  }

  /**
   * Verify we can set a validation callback and get back the Step.
   */
  public function testSetValidationCallback() {
    $callback = function() {
      echo "win";
    };
    $this->assertSame($this->questionMessageStep, $this->questionMessageStep->setValidationCallback($callback), 'Failed to set validationCallback to a closure function.');
  }

  /**
   * Verify setting the validation with non-callable fails.
   *
   * @expectedException PHPUnit_Framework_Error
   */
  public function testSetValidationCallbackWithNonCallable() {
    $callback = "string";
    $this->assertSame($this->questionMessageStep, $this->questionMessageStep->setValidationCallback($callback), 'Error: successfully set validationCallback to a non-callable.');
  }

  /**
   * Verify we can set the invalid message.
   */
  public function testSetInvalidMessage() {
    $mockMessage = $this->getMockBuilder('Drupal\fb_messenger_bot\Message\MessageInterface')
      ->getMock();
    $this->assertSame($this->questionMessageStep, $this->questionMessageStep->setInvalidResponseMessage($mockMessage), 'Failed to set invalidMessage to an instance of the MessageInterface.');
  }

  /**
   * Test the default question message callback.
   */
  public function testShouldReturnQuestionMessage() {
    $actual = $this->questionMessageStep->getQuestionMessage();
    $this->assertSame($this->mockMessage, $actual[0]);
  }

  /**
   * Test that the questionCallbackStep uses the custom callback.
   */
  public function testShouldUseMessageCallback() {
    $params = [
      'foo' => 'bar'
    ];
    $stepMessageOutput = $this->questionCallbackStep->getQuestionMessage($params);
    $callbackOutput = $this->mockMessageCallback->__invoke($params);

    // The dummy callback used to instantiate this step should return exactly
    // what it is given.
    $this->assertSame($params, $stepMessageOutput);
    $this->assertSame($callbackOutput, $stepMessageOutput);
  }

  /**
   * Test that required inputs setter and getter.
   */
  public function testShouldSetRequiredInputs() {
    $step = new BotWorkflowStep('test', 'test', $this->mockMessageCallback);

    // Make sure the object initializes with an empty array of required inputs.
    $this->assertEmpty($step->getRequiredProperties());

    $arguments = [
      '@string1' => 'property1',
      '@string2' => 'property2'
    ];
    foreach ($arguments as $key => $value) {
      $step->addRequiredProperty($key, $value);
    }
    $requiredInputs = $step->getRequiredProperties();
    $this->assertEquals($arguments, $requiredInputs);
  }

  /**
   * Ensure property is parsed from the replacement string when not provided.
   */
  public function testShouldParsePropertyNameFromReplacementString() {
    $testCases = [
      // No regex match.
      'string1' => 'string1',
      // Match leading '@'.
      '@string2' => 'string2',
      // Match leading '%'.
      '%string3' => 'string3',
      // Match leading ':'.
      ':string4' => 'string4',
      // Do not match special characters at positions other than 0.
      'string@!:5' => 'string@!:5'
    ];

    foreach ($testCases as $replacement_string => $expected_property_name) {
      $step = new BotWorkflowStep('test', 'test', $this->mockMessageCallback);
      $expected = [
        $replacement_string => $expected_property_name
      ];

      $step->addRequiredProperty($replacement_string);
      $actual = $step->getRequiredProperties();
      $this->assertSame($expected, $actual);
    }
  }

  /**
   * Test the return values of processMessage.
   *
   * @dataProvider incomingMessageProvider
   */
  public function testProcessMessageMethod($incomingMessage, $expectedValid, $expectedMessage, $expectedGoto) {

    $outcome = $this->questionMessageStep->processResponse($incomingMessage);

    $this->assertInstanceOf('\Drupal\fb_messenger_bot\Step\StepInvocationOutcome', $outcome, 'BotWorkflowStep::procesMessage() did not return a StepInvocationOutcomes.');

    $this->assertEquals($expectedValid, $outcome->incomingResponseIsValid());
    $this->assertEquals($expectedMessage, $outcome->getOutboundMessage()[0]->getFormattedMessage()['text']);
    $this->assertEquals($expectedGoto, $outcome->getGotoStep());
  }

  /**
   * Provide test data for the testProcessMessageMethod test.
   */
  public function incomingMessageProvider() {

    $incoming1 = array(
      'message_type' => 'text',
      'message_content' => 'Yes',
    );
    $incoming2 = array(
      'message_type' => 'text',
      'message_content' => 'No',
    );
    $incoming3 = array(
      'message_type' => 'text',
      'message_content' => 'Llama badger',
    );
    $incoming4 = array(
      'message_type' => 'text',
      'message_content' => 'fail',
    );

    return array(
      array($incoming1, TRUE, 'Got a Yes', 'nextStep'),
      array($incoming2, TRUE, 'Got a No', 'skipStep'),
      array($incoming3, TRUE, 'Got something else', 'defaultStep'),
      array($incoming4, FALSE, 'Sorry. I don\'t understand.', 'questionMessageStep'),
    );

  }

  /**
   * Test the getQuestionMessage and getInvalidMessage methods' return values.
   *
   * We expect it will return an array of MessageInterface objects representing
   * outgoing messages to send back to the user.
   */
  public function testGetMessageReturnValues() {

    $questionMessages = $this->questionMessageStep->getQuestionMessage();
    $this->assertTrue(is_array($questionMessages));

    // Make sure we have at least one message.
    $this->assertTrue(count($questionMessages) >= 1);

    // Make sure all contents of the array are actually MessageInterfaces.
    foreach ($questionMessages as $message) {
      $this->assertInstanceOf('\Drupal\fb_messenger_bot\Message\MessageInterface', $message);
    }

    // Make sure the invalid message is returned as an array.
    $invalidMessages = $this->questionMessageStep->getInvalidResponse();
    $this->assertTrue(is_array($invalidMessages));

    // Make sure we have at least one message.
    $this->assertTrue(count($invalidMessages) >= 1);

    // Make sure all contents of the array are actually MessageInterfaces.
    foreach ($invalidMessages as $message) {
      $this->assertInstanceOf('\Drupal\fb_messenger_bot\Message\MessageInterface', $message);
    }
  }

}
