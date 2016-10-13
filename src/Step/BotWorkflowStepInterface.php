<?php

namespace Drupal\fb_messenger_bot\Step;

use Drupal\fb_messenger_bot\Message\MessageInterface;

/**
 * Interface BotWorkflowStepInterface.
 *
 * @package Drupal\fb_messenger_bot\Step
 */
interface BotWorkflowStepInterface {

  /**
   * Set a function to use for validating responses.
   *
   * @param callable $callback
   *   The validation function for a response.
   *
   * @return BotWorkflowStepInterface
   *   Return $this for chainability.
   */
  public function setValidationCallback(callable $callback);

  /**
   * Set the Message to be sent when a response fails validation.
   *
   * @param mixed $invalidMessage
   *   A MessageInterface object, or an array of MessageInterface objects, to
   *   send when input is invalid.
   *
   * @return BotWorkflowStepInterface
   *   Return $this for chainability.
   */
  public function setInvalidResponseMessage($invalidMessage);

  /**
   * Invoke the validation callback function.
   *
   * @param array $response
   *    An array with keys of message_type and message_content.
   *
   * @return BotWorkflowStepInterface
   *   Return self.
   */
  public function validateResponse(array $response);

  /**
   * Returns the Message which will be sent when a response fails validation.
   *
   * @return MessageInterface
   *   A message object to send the user if their answer is invalid.
   */
  public function getInvalidResponse();

  /**
   * Returns the steps Message which is asked to elicit a response.
   *
   * @param array|Null $params
   *   (Optional) an array of parameters passed to a questionMessageCallback.
   *
   * @return array
   *   An array of MessageInterface objects representing the Step's question.
   */
  public function getQuestionMessage(array $params);

  /**
   * Add a required property for message string interpolation.
   *
   * @param string $replacement
   *   The string to search for and replace.
   * @param string|null $propertyName
   *   (Optional) If different from the $replacement, the name of the property
   *   to use from the Conversation's validAnswers array.
   */
  public function addRequiredProperty($replacement, $propertyName = NULL);

  /**
   * Get a list of required properties for message string interpolation.
   *
   * @return array
   *   An array of property names required to complete the question message, or
   *   an empty array if no properties are required.
   */
  public function getRequiredProperties();

  /**
   * Returns the Step's machine name.
   *
   * @return string
   *   The machine name of the step.
   */
  public function getMachineName();

  /**
   * Returns the Step's human readable name.
   *
   * @return string
   *   The human readable name of the step.
   */
  public function getStepName();

  /**
   * Process an incoming user response.
   *
   * @param array $receivedMessage
   *   The incoming message from the user.
   *
   * @return StepInvocationOutcomeInterface
   *   Object encapsulating the outcome of validation of the user message.
   */
  public function processResponse(array $receivedMessage);

  /**
   * Set the handlers for expected user responses.
   *
   * Handlers include the user input that will trigger the response handler,
   * a response Message, and goto step machine name.
   *
   * @param array $responseHandlers
   *   Array of handlers for expected user responses.
   *
   * @return BotWorkflowStepInterface
   *   Return self for chainability.
   */
  public function setResponseHandlers(array $responseHandlers);

}
