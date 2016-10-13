<?php

namespace Drupal\fb_messenger_bot\Step;

/**
 * Interface StepInvocationOutcomeInterface.
 *
 * @package Drupal\fb_messenger_bot\Step
 */
interface StepInvocationOutcomeInterface {

  /**
   * Returns the content of the user's response to the step question.
   *
   * @return string
   *    Content of the user's response to the step question.
   */
  public function getIncomingResponseContent();

  /**
   * Set the message(s) to send to the user.
   *
   * @param array $outboundMessage
   *   An array of MessageInterface objects.
   */
  public function setOutboundMessage(array $outboundMessage);

  /**
   * Returns message(s) to send the user.
   *
   * @return array
   *   An array of MessageInterface objects.
   */
  public function getOutboundMessage();

  /**
   * Returns whether or not the user's response to the step question was valid.
   *
   * @return bool
   *   TRUE if the user's response to the step question was valid,
   *   otherwise, FALSE.
   */
  public function incomingResponseIsValid();

  /**
   * Returns the machine name of the invoked step.
   *
   * @return string
   *    The machine name of the invoked step.
   */
  public function getStepMachineName();

  /**
   * Set the machine name of the next step to go to in the workflow.
   *
   * @param string $step
   *   The machine name of the next step to go to in the workflow.
   */
  public function setGotoStep($step);

  /**
   * Returns the machine name of the next step to go to in the workflow.
   *
   * @return string
   *   The machine name of the next step to go to in the workflow.
   */
  public function getGotoStep();

  /**
   * Set whether or not the incoming response was valid for the step.
   *
   * @param bool $incomingResponseIsValid
   *   TRUE if the user's response to the step question was valid,
   *   otherwise, FALSE.
   */
  public function setIncomingResponseIsValid($incomingResponseIsValid);

  /**
   * Returns a keyed array of data resulting from invoking the step.
   *
   * @return array
   *   'machineName' => String, the step's machine name
   *   'response' => String, the content of the user's response to the step
   *                 question
   *   'gotoStep' => String, the machine name of the next step to go to in the
   *                 workflow
   *   'responseIsValid' => Boolean, TRUE if user's response was valid,
   *                        otherwise, FALSE
   */
  public function getData();

}
