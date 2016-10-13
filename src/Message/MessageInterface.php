<?php
/**
 * @file
 * Contains Drupal\fb_messenger_bot\Message\MessageInterface.
 */

namespace Drupal\fb_messenger_bot\Message;

/**
 * Base interface for message classes.
 */
interface MessageInterface {

  /**
   * Retrieve formatted message contents.
   *
   * @return array
   *   A structured message for the Facebook Messenger Platform Send API.
   */
  public function getFormattedMessage();

}
