<?php

namespace Drupal\fb_messenger_bot\Step;

/**
 * Class StepInvocationOutcome.
 *
 * @package Drupal\fb_messenger_bot\Step
 */
class StepInvocationOutcome implements StepInvocationOutcomeInterface {

  /**
   * Whether or not the response from the user was valid for the invoked step.
   *
   * @var boolean
   */
  protected $incomingResponseIsValid;

  /**
   * The machine name of the invoked step.
   *
   * @var string
   */
  protected $stepMachineName;

  /**
   * The content of the user's response.
   *
   * @var string
   */
  protected $incomingResponseContent;

  /**
   * The machine name of the next step to go to in the workflow.
   *
   * @var string
   */
  protected $gotoStep;

  /**
   * An array of MessageInterface objects to send the user.
   *
   * @var array
   */
  protected $outboundMessage;

  /**
   * StepInvocationOutcome constructor.
   *
   * @param string $stepMachineName
   *   The machine name of the invoked step.
   * @param string $incomingResponseContent
   *   The content of the user's response.
   */
  public function __construct($stepMachineName, $incomingResponseContent) {
    $this->stepMachineName = $stepMachineName;
    $this->incomingResponseContent = $incomingResponseContent;
  }

  /**
   * {@inheritdoc}
   */
  public function getIncomingResponseContent() {
    return $this->incomingResponseContent;
  }

  /**
   * {@inheritdoc}
   */
  public function setOutboundMessage(array $outboundMessage = NULL) {
    $this->outboundMessage = $outboundMessage;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutboundMessage() {
    return $this->outboundMessage;
  }

  /**
   * {@inheritdoc}
   */
  public function incomingResponseIsValid() {
    return $this->incomingResponseIsValid;
  }

  /**
   * {@inheritdoc}
   */
  public function getStepMachineName() {
    return $this->stepMachineName;
  }

  /**
   * {@inheritdoc}
   */
  public function setGotoStep($step) {
    $this->gotoStep = $step;
  }

  /**
   * {@inheritdoc}
   */
  public function getGotoStep() {
    return $this->gotoStep;
  }

  /**
   * {@inheritdoc}
   */
  public function setIncomingResponseIsValid($incomingResponseIsValid) {
    $this->incomingResponseIsValid = $incomingResponseIsValid;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return array(
      'machineName' => $this->getStepMachineName(),
      'response' => $this->getIncomingResponseContent(),
      'gotoStep' => $this->getGotoStep(),
      'responseIsValid' => $this->incomingResponseIsValid(),
    );
  }

}
