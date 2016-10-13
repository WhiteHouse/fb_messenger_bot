<?php
/**
 * @file
 * Contains Drupal\fb_messenger_bot\Message\ButtonInterface.
 */

namespace Drupal\fb_messenger_bot\Message;

/**
 * Base class for a button object.
 */
class PostbackButton extends ButtonBase {

  /**
   * The value returned in the postback response.
   */
  protected $payload;

  /**
   * Postback button constructor.
   *
   * @param string $type
   *   The button type.
   * @param string $title
   *   The button's title.
   * @param string $payload
   *   The button's postback payload.
   */
  public function __construct($title, $payload) {
    parent::__construct('postback', $title);
    $this->payload = $payload;
  }

}
