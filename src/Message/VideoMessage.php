<?php
/**
 * @file
 * Contains Drupal\fb_messenger_bot\Message\TextMessage.
 */

namespace Drupal\fb_messenger_bot\Message;

/**
 * Class ButtonMessage.
 *
 * @package Drupal\fb_messenger_bot
 */
class VideoMessage implements MessageInterface {

  /**
   * The message Url.
   */
  protected $messageUrl;

  /**
   * ButtonMessage constructor.
   *
   * @param string $url
   *   The uro to use for this message.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the $buttons argument contains invalid objects.
   *
   * @todo: Add verification that the URL is actually a video.
   */
  public function __construct($url) {
    if (filter_var($url, FILTER_VALIDATE_URL)) {
      $this->messageUrl = $url;
    }
    else {
      throw new \InvalidArgumentException("Invalid URL passed to VideoMessage constructor.");
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getFormattedMessage() {
    return [
      'attachment' => [
        'type' => 'video',
        'payload' => [
          'url' => $this->messageUrl,
        ],
      ],
    ];
  }

}
