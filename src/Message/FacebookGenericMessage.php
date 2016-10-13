<?php
/**
 * @file
 * Contains Drupal\fb_messenger_bot\FacebookGenericMessage.
 */

namespace Drupal\fb_messenger_bot\Message;

/**
 * Class FacebookGenericMessage.
 *
 * @package Drupal\fb_messenger_bot
 */
class FacebookGenericMessage implements MessageInterface {

  /**
   * The elements storage.
   */
  protected $elements;

  /**
   * Constructs a new FacebookGenericMessage.
   *
   * @param array $elements
   *   The message elements.
   */
  public function __construct(array $elements) {
    $this->elements = $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedMessage() {
    return [
      'attachment' => [
        'type' => 'template',
        'payload' => [
          'template_type' => 'generic',
          'elements' => $this->elements,
        ],
      ],
    ];
  }

}
