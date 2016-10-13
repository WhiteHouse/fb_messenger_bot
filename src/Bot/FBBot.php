<?php

namespace Drupal\fb_messenger_bot\Bot;

use Drupal\fb_messenger_bot\FacebookService;
use Drupal\fb_messenger_bot\Conversation\ConversationFactoryInterface;
use Drupal\fb_messenger_bot\Workflow\BotWorkflowInterface;


/**
 * Facebook Bot Implementation.
 *
 * @package Drupal\fb_messenger_bot\Bot
 */
class FBBot implements BotInterface {

  use BotTrait;

  /**
   * FBBot constructor.
   *
   * @param FacebookService $fbService
   *   The facebook service.
   * @param ConversationFactoryInterface $conversationFactory
   *   The conversation factory.
   * @param BotWorkflowInterface $workflow
   *   The workflow associated with this bot.
   */
  public function __construct(FacebookService $fbService, ConversationFactoryInterface $conversationFactory, BotWorkflowInterface $workflow = NULL) {
    $this->fbService = $fbService;
    $this->conversationFactory = $conversationFactory;
    if ($workflow) {
      $this->setWorkflow($workflow);
    }
  }

}
