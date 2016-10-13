<?php

namespace Drupal\fb_messenger_bot\Conversation;

/**
 * Interface ConversationFactoryInterface.
 *
 * @package Drupal\fb_messenger_bot\Conversation
 */
interface ConversationFactoryInterface {

  /**
   * Load or instantiate a conversation, based on the sender's uid.
   *
   * @param string $uid
   *   The sender's user id.
   *
   * @return BotConversationInterface
   *   A conversation object.
   */
  public function getConversation($uid);

}
