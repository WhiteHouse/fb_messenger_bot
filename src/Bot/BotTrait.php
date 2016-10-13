<?php

namespace Drupal\fb_messenger_bot\Bot;

use Drupal\fb_messenger_bot\FacebookService;
use Drupal\fb_messenger_bot\Workflow\BotWorkflowInterface;
use Drupal\fb_messenger_bot\Conversation\ConversationFactory;

/**
 * Trait BotTrait.
 *
 * @package Drupal\fb_messenger_bot\Bot
 */
trait BotTrait {

  /**
   * The conversation factory.
   *
   * @var \Drupal\fb_messenger_bot\Conversation\ConversationFactoryInterface
   */
  protected $conversationFactory;

  /**
   * The Workflow the bot will use.
   *
   * @var \Drupal\fb_messenger_bot\Workflow\BotWorkflowInterface
   */
  protected $workflow;

  /**
   * The Facebook Service.
   *
   * @var \Drupal\fb_messenger_bot\FacebookService
   */
  protected $fbService;

  /**
   * {@inheritdoc}
   */
  public function process($data) {
    $incomingData = $this->fbService->translateRequest($data);

    // Iterate through received messages.
    foreach ($incomingData as $uid => $incomingMessages) {
      foreach ($incomingMessages as $incomingMessage) {
        $conversation = $this->conversationFactory->getConversation($uid);
        $response = $this->workflow->processConversation($conversation, $incomingMessage);
        $this->fbService->sendMessages($response, $uid);
      }
    }

  }

  /**
   * Sets the bot's $workflow property.
   *
   * @param BotWorkflowInterface $workflow
   *   The Workflow to set.
   *
   * @todo: Set workflow in the conversation iterator of the process() method.
   */
  public function setWorkflow(BotWorkflowInterface $workflow) {
    $this->workflow = $workflow;
  }

}
