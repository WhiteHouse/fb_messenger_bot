<?php

namespace Drupal\fb_messenger_bot\Step;

use Drupal\contact\Entity\Message;
use Drupal\fb_messenger_bot\Message\MessageInterface;

/**
 * Class BotWorkflowStep.
 *
 * @package Drupal\fb_messenger_bot\Step
 */
class BotWorkflowStep implements BotWorkflowStepInterface {

  /**
   * A machine name for the step, by which it can be identified to code.
   *
   * @var string $machineName
   */
  protected $machineName;

  /**
   * A human readable name for the step, for display to administrators.
   *
   * @var string $stepName
   */
  protected $stepName;

  /**
   * An array of Message object(s) which the step sends to the user.
   *
   * @var array $questionMessage
   */
  protected $questionMessage;

  /**
   * Callback returning an instance of MessageInterface sent to the user.
   *
   * @var callable
   */
  protected $questionMessageCallback;

  /**
   * An associative array of string replacement keys and replacement properties.
   *
   * The properties in this array should correspond to properties available in
   * the conversation's validAnswers array.
   *
   * @var array
   */
  protected $requiredProperties;

  /**
   * A callable (i.e., closure) used to validate the user's response.
   *
   * @var callable $validationCallback
   */
  protected $validationCallback;

  /**
   * The Message object to send if a response is invalid.
   *
   * @var MessageInterface $invalidResponseMessage
   */
  protected $invalidResponseMessage;

  /**
   * Array of keyed handlers for expected user responses.
   *
   * @var array $responseHandlers
   */
  protected $responseHandlers;

  /**
   * Constructor.
   *
   * @param string $stepName
   *   A human-readable name for the step.
   * @param string $machineName
   *   A machine friendly name for the step, unique within a given Workflow.
   * @param mixed $questionMessage
   *   The Message which will be sent to the user to elicit a response, an array
   *   of Messages, or a callable callback returning MessageInterface.
   *
   * @throws \Exception
   *   Thrown if the $questionMessage argument is empty or does not implement
   *   one of the allowable types.
   *
   * @todo: reconsider how to get the machine name. Relying on it as is does not ensure uniqueness.
   */
  public function __construct($stepName, $machineName, $questionMessage) {
    $this->stepName = $stepName;
    $this->machineName = $machineName;
    $this->requiredProperties = array();

    if ($questionMessage instanceof MessageInterface || is_array($questionMessage)) {
      $this->setQuestionMessage($questionMessage);
      $this->questionMessageCallback = function() {
        return $this->questionMessage;
      };
    }
    elseif (is_callable($questionMessage)) {
      $this->questionMessageCallback = $questionMessage;
    }
    else {
      throw new \Exception('Argument 3 for \Drupal\fb_messenger_bot\Step\BotWorkflowStep cannot be empty and must either be callable, and array, or implement \Drupal\fb_messenger_bot\Message\MessageInterface.');
    }

  }

  /**
   * Helper function to figure out what the questionMessage should be set to.
   *
   * @param mixed $questionMessage
   *   A Message which will be sent to the user to elicit a response or array of
   *   Messages.
   */
  protected function setQuestionMessage($questionMessage) {
    if ($questionMessage instanceof MessageInterface) {
      $this->questionMessage = array($questionMessage);
    }
    elseif (is_array($questionMessage)) {
      // Clear out the questionMessage attribute so we don't accrete arrays.
      $this->questionMessage = array();

      foreach ($questionMessage as $message) {
        if (!($message instanceof MessageInterface)) {
          throw new \InvalidArgumentException("All items in parameter 3 must implement MessageInterface.");
        }
        else {
          $this->questionMessage[] = $message;
        }
      }
    }
    else {
      throw new \InvalidArgumentException("Parameter 3 must be either an instance of MessageInterface or an array of MessageInterfaces");
    }
  }

  /**
   * For casting to string, return the name of the step.
   *
   * @return string
   *   The step name.
   */
  public function __toString() {
    return $this->stepName;
  }

  /**
   * Setter method for the validation callback.
   *
   * @inheritdoc
   */
  public function setValidationCallback(callable $callback) {
    $this->validationCallback = $callback;
    return $this;
  }

  /**
   * Setter method for the invalid response message.
   *
   * @inheritdoc
   */
  public function setInvalidResponseMessage($invalidMessage) {
    if ($invalidMessage instanceof MessageInterface) {
      $this->invalidResponseMessage = array($invalidMessage);
    }
    elseif (is_array($invalidMessage)) {
      // Clear out the invalidMessage attribute so we don't accrete arrays.
      $this->invalidMessage = array();

      foreach ($invalidMessage as $message) {
        if (!($message instanceof MessageInterface)) {
          throw new \InvalidArgumentException("All items in array passed to setInvalidResponseMessage must implement MessageInterface.");
        }
        else {
          $this->invalidResponseMessage[] = $message;
        }
      }
    }
    else {
      throw new \InvalidArgumentException("The invalidMessage parameter must be either an instance of MessageInterface or an array of MessageInterfaces");
    }
    return $this;
  }

  /**
   * Invoke the validation callback function.
   *
   * @inheritdoc
   */
  public function validateResponse(array $response) {
    if ($this->validationCallback->__invoke($response)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Get the InvalidMessage.
   *
   * @inheritdoc
   */
  public function getInvalidResponse() {
    return $this->invalidResponseMessage;
  }

  /**
   * Get the Message for this Step.
   *
   * @inheritdoc
   */
  public function getQuestionMessage(array $params = array()) {
    return $this->questionMessageCallback->__invoke($params);
  }

  /**
   * {@inheritdoc}
   */
  public function addRequiredProperty($replacement, $propertyName = NULL) {
    $this->requiredProperties[$replacement] = $propertyName ?: preg_replace('/^[\%\@\:]/', '', $replacement);
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredProperties() {
    return $this->requiredProperties;
  }

  /**
   * Get the machine name for this Step.
   *
   * @inheritdoc
   */
  public function getMachineName() {
    return $this->machineName;
  }

  /**
   * Get the human readable name for this Step.
   *
   * @inheritdoc
   */
  public function getStepName() {
    return $this->stepName;
  }

  /**
   * Process an incoming response from the user.
   *
   * @inheritdoc
   */
  public function processResponse(array $receivedMessage) {

    // Validate input.
    if (!$this->validateResponse($receivedMessage)) {

      // Received message is invalid.
      $valid = FALSE;
      $responseMessage = $this->getInvalidResponse();
      $goto = $this->getMachineName();
    }
    else {
      // Initialize the $responseMessage to an empty array.
      $responseMessage = array();

      // Received message is valid.
      $valid = TRUE;

      // Look for matching response handler to get goto and message.
      if (in_array($receivedMessage['message_content'], array_keys($this->responseHandlers))) {
        $handler = $this->responseHandlers[$receivedMessage['message_content']];
      }
      else {
        $handler = $this->responseHandlers['*'];
      }

      // Initialize the variable in case it isn't set by the time we hit line
      // 260, which shouldn't happen in most cases but isn't invalid either.
      // It does raise a Warning if the variable isn't set though.
      $responseMessage = array();

      $handlerMessage = $handler['handlerMessage'];
      if (!is_null($handlerMessage)) {
        if ($handlerMessage instanceof MessageInterface) {
          $responseMessage = array($handlerMessage);
        }
        elseif (is_array($handlerMessage)) {
          foreach ($handlerMessage as $message) {
            if (!($message instanceof MessageInterface)) {
              throw new \InvalidArgumentException("All handler messages must implement MessageInterface.");
            }
            else {
              $responseMessage[] = $message;
            }
          }
        }
        else {
          throw new \InvalidArgumentException("Handler messages must be an instance of MessageInterface or an array of MessageInterfaces");
        }
      }
      $goto = $handler['goto'];
    }

    // Populate StepInvocationOutcome with fitting values.
    $outcome = new StepInvocationOutcome($this->getMachineName(), $receivedMessage['message_content']);
    $outcome->setIncomingResponseIsValid($valid);
    $outcome->setOutboundMessage($responseMessage);
    $outcome->setGotoStep($goto);
    return $outcome;
  }

  /**
   * Set the response handlers for this step.
   *
   * @inheritdoc
   */
  public function setResponseHandlers(array $responseHandlers) {
    $this->responseHandlers = $responseHandlers;
    return $this;
  }

}
