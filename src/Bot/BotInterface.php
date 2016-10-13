<?php

namespace Drupal\fb_messenger_bot\Bot;

/**
 * Interface BotInterface.
 *
 * @package Drupal\fb_messenger_bot
 */
interface BotInterface {

  /**
   * Process incomming data.
   *
   * @param string $data
   *   Json encoded data delivered by the Facebook API.
   */
  public function process($data);

}
