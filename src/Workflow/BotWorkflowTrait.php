<?php

namespace Drupal\fb_messenger_bot\Workflow;

use Drupal\fb_messenger_bot\Message\TextMessage;
use Drupal\fb_messenger_bot\Step\BotWorkflowStepInterface;
use Drupal\fb_messenger_bot\Conversation\BotConversationInterface;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Class BotWorkflowTrait.
 *
 * @package Drupal\fb_messenger_bot\Workflow
 */
trait BotWorkflowTrait {

  /**
   * Configuration for fb_messenger_bot.
   *
   * @var ImmutableConfig
   */
  public $config;

  public $steps;

  /**
   * Set the steps for the workflow.
   *
   * @inheritdoc
   */
  public function setSteps($steps) {
    if (!is_array($steps)  && !$steps instanceof \Traversable) {
      throw new \InvalidArgumentException("Input to setSteps must be traversable with foreach.");
    }

    foreach ($steps as $i => $step) {
      if (!$step instanceof BotWorkflowStepInterface) {
        throw new \InvalidArgumentException(
          sprintf(
            "All steps must be instance of BotWorkflowStepInterface, got %s for step %d.",
            gettype($step),
            $i
          )
        );
      }
    }

    $this->steps = $steps;

    return TRUE;
  }

  /**
   * Check the incoming message and get a response message to send back.
   *
   * @inheritdoc
   */
  public function processConversation(BotConversationInterface $conversation, array $receivedMessage) {

    // Return an early response if the type is disallowed.
    if ($response = $this->checkDisallowedMessageType($receivedMessage, $conversation)) {
      return $response;
    }
    // Return an early response if the content is somehow special.
    if ($response = $this->preprocessSpecialMessages($receivedMessage, $conversation)) {
      return $response;
    }

    // Set the default step to go to.
    $gotoStepIndex = $this->getDefaultStep();
    $outboundMessage = NULL;
    $responseIsValid = TRUE;

    // Get the last step if this is an existing conversation.
    if ($lastStepIndex = $conversation->getLastStep()) {

      // Get Step object.
      $thisStep = $this->getStep($lastStepIndex);

      // Invoke step.
      $invocationOutcome = $thisStep->processResponse($receivedMessage);

      if ($invocationOutcome->incomingResponseIsValid()) {
        $answerData = $invocationOutcome->getData();
        $replace = in_array($lastStepIndex, array('firstName', 'lastName'));

        $conversation->setValidAnswer($answerData['machineName'], $answerData['response'], $replace)
          ->resetErrorCount();
      }
      else {
        $responseIsValid = FALSE;
        $conversation->incrementErrorCount();
      }

      $gotoStepIndex = $invocationOutcome->getGotoStep();
      $outboundMessage = $invocationOutcome->getOutboundMessage();
    }

    $conversation->setLastStep($gotoStepIndex);

    // If we're on the last step, set the conversation to complete.
    if ($conversation->getLastStep() == $this->getFinalStepKey()) {
      $conversation->setComplete(TRUE);
    }

    // Check first to see if we've reached the trolling threshold.
    if ($conversation->getErrorCount() >= $this->config->get('trolling_threshold')) {
      $response = $this->getTrollingMessage();
    }
    // Invoke the processSpecialMessage handler only if the response was valid.
    elseif ($responseIsValid && $specialMessage = $this->processSpecialMessages($receivedMessage, $conversation)) {
      $response = is_array($specialMessage) ? $specialMessage : array($specialMessage);
    }
    // Then check to see if the outbound message was already set by the
    // Invocation Outcome.
    elseif ($outboundMessage) {
      $response = is_array($outboundMessage) ? $outboundMessage : array($outboundMessage);
    }
    // Finally, use the step's question message as the response if none of the
    // above produced an outbound message.
    else {
      $step = $this->getStep($conversation->getLastStep());
      $validAnswers = $conversation->getValidAnswers();
      $args = [];
      foreach ($step->getRequiredProperties() as $replacement => $lookup) {
        $args[$replacement] = isset($validAnswers[$lookup]) ? $validAnswers[$lookup] : NULL;
      };
      $response = $step->getQuestionMessage($args);
    }

    // Ensure any changes made to the conversation are saved.
    $conversation->save();

    return $response;
  }

  /**
   * Gets the Step corresponding to the given key.
   *
   * @param mixed $key
   *   The key for the Step in the Workflow's step array.
   *
   * @return mixed
   *   The Step corresponding to the given key.
   */
  protected function getStep($key) {
    if (array_key_exists($key, $this->steps)) {
      $step = $this->steps[$key];
    }
    else {
      throw new \InvalidArgumentException(
        sprintf(
          'Unable to load step with given key; %s',
          $key
        )
      );
    }

    return $step;
  }

  /**
   * Gets the machine name of the default step in the workflow.
   *
   * @return string
   *   The machine name of the default step in the workflow, in this case,
   *   the first step.
   */
  protected function getDefaultStep() {
    if (!is_array($this->steps)  && !$this->steps instanceof \Traversable) {
      throw new \InvalidArgumentException("Steps must be set using setSteps.");
    }

    $steps = $this->steps;
    reset($steps);
    return key($steps);
  }

  /**
   * Special message handler invoked early in the processConversation Method.
   *
   * @param array $receivedMessage
   *   Array of the incoming message from the user.
   * @param \Drupal\fb_messenger_bot\Conversation\BotConversationInterface $conversation
   *   The conversation being processed.
   *
   * @return array
   *   Array of 0 or more MessageInterface objects to send to the user.
   */
  protected function preprocessSpecialMessages(array $receivedMessage, BotConversationInterface &$conversation) {
    return array();
  }

  /**
   * Special message handler invoked late in the processConversation Method.
   *
   * @param array $receivedMessage
   *   Array of the incoming message from the user.
   * @param \Drupal\fb_messenger_bot\Conversation\BotConversationInterface $conversation
   *   The conversation being processed.
   *
   * @return array
   *   Array of 0 or more MessageInterface objects to send to the user.
   */
  protected function processSpecialMessages(array $receivedMessage, BotConversationInterface &$conversation) {
    return array();
  }

  /**
   * Checks incoming messages to see if the type is allowed before processing.
   *
   * @param array $receivedMessage
   *   Array of the incoming message from the user.
   * @param \Drupal\fb_messenger_bot\Conversation\BotConversationInterface $conversation
   *   The conversation being processed.
   *
   * @return array
   *   Array of 0 or more MessageInterface objects to send to the user.
   *
   */
  protected function checkDisallowedMessageType(array $receivedMessage, BotConversationInterface &$conversation) {
    return array();
  }

  /**
   * Gets trolling response to give to user after exceeding error threshold.
   *
   * @return array MessageInterface
   *   An array of one or more Message objects that are the trolling response.
   */
  protected function getTrollingMessage() {
    $messages = array();
    $messages[] = new TextMessage("Read the last message we sent out carefully and try again.");
    return $messages;
  }

  /**
   * Gets the machine name of the last step in the workflow.
   *
   * @return string
   *   The machine name of the last step in the workflow.
   */
  protected function getFinalStepKey() {
    if (!is_array($this->steps)  && !$this->steps instanceof \Traversable) {
      throw new \InvalidArgumentException("Steps must be set using setSteps.");
    }

    $keys = array_keys($this->steps);
    $lastStep = end($keys);

    return $lastStep;
  }

}
