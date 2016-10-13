<?php

namespace Drupal\fb_messenger_bot\Conversation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a BotConversation entity.
 *
 * @ingroup fb_messenger_bot
 */
interface BotConversationInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Conversation does not have all answers necessary to be considered complete.
   */
  const INCOMPLETE = 0;

  /**
   * Conversation has all answers necessary to be considered complete.
   */
  const COMPLETE = 1;

  /**
   * Gets the ID of this conversation entity.
   *
   * @return int
   *   Conversation ID.
   */
  public function getConversationId();

  /**
   * Gets the userID of the user we are having a conversation with.
   *
   * @return int
   *   Page-scoped Facebook user id.
   */
  public function getUserId();

  /**
   * Gets the complete status of the conversation entity.
   *
   * @return int
   *   One of BotConversationInterface::COMPLETE or
   *   BotConversationInterface::INCOMPLETE.
   */
  public function getComplete();

  /**
   * Sets the complete status of the conversation entity.
   *
   * @param bool $complete
   *   Set to TRUE to mark conversation complete, FALSE to mark incomplete.
   *
   * @return \Drupal\fb_messenger_bot\Conversation\BotConversationInterface
   *   The called conversation entity.
   */
  public function setComplete($complete);

  /**
   * Sets the lastStep that we sent out to the user.
   *
   * @param int $lastStep
   *   Index of last step we sent to the user in the workflow object steps
   *   property.
   *
   * @return \Drupal\fb_messenger_bot\Conversation\BotConversationInterface
   *   The called conversation entity.
   */
  public function setLastStep($lastStep);

  /**
   * Gets the lastStep that we sent out to the user.
   *
   * @return int
   *   Index of last step we sent to the user in the workflow object steps
   *   property.
   */
  public function getLastStep();

  /**
   * Sets a validAnswer given the stepMachineName and the answer.
   *
   * @param string $stepMachineName
   *   Machine name of the step we received a valid answer for.
   * @param string $answer
   *   The valid answer to set for the corresponding step.
   * @param bool $replace
   *   Whether to replace or append $answer to the current value for the step.
   *
   * @return \Drupal\fb_messenger_bot\Conversation\BotConversationInterface
   *   The called conversation entity.
   */
  public function setValidAnswer($stepMachineName, $answer, $replace = FALSE);

  /**
   * Gets the validAnswers as key-value pairs the user has provided thus far.
   *
   * @return array
   *   An array of key value pairs where the keys are step machine names
   *   and the values are the answers to those steps.
   */
  public function getValidAnswers();

  /**
   * Gets the conversation's current error count.
   *
   * @return int
   *   The current error count.
   */
  public function getErrorCount();

  /**
   * Increments the conversation's error counter.
   *
   * @return \Drupal\fb_messenger_bot\Conversation\BotConversationInterface
   *   The called conversation entity.
   */
  public function incrementErrorCount();

  /**
   * Set the conversation's error counter to 0.
   *
   * @return \Drupal\fb_messenger_bot\Conversation\BotConversationInterface
   *   The called conversation entity.
   */
  public function resetErrorCount();

}
