<?php
/**
 * @file
 * Contains Drupal\fb_messenger_bot\Message\ListMessage.
 */

namespace Drupal\fb_messenger_bot\Message;

/**
 * Class ListMessage.
 *
 * @package Drupal\fb_messenger_bot
 */
class ListMessage implements MessageInterface {

  /**
   * A nested array of list elements.
   */
  protected $listElements;

  /**
   * ListMessage constructor.
   *
   * @param string $listElements
   *   The elements array.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the $buttons argument contains invalid objects.
   *
   * @todo: Add verification that the URL is actually an image.
   */
  public function __construct($listElements) {
    if (is_array($listElements)) {
      $this->listElements = $listElements;
    }
    else {
      throw new \InvalidArgumentException("Invalid elements array.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedMessage() {
    return [
      'attachment' => [
        'type' => 'template',
        'payload' => [
          'template_type' => 'list',
          'elements' => $this->listElements,
        ],
      ],
    ];
  }

}
