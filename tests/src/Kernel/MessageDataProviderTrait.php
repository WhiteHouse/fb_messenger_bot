<?php

namespace Drupal\Tests\fb_messenger_bot\Kernel;

use Drupal\fb_messenger_bot\FacebookService;
use Drupal\Component\Serialization\Json;

/**
 * Trait MessageDataProviderTrait.
 */
trait MessageDataProviderTrait {

  /**
   * Helper function to format a base message request.
   *
   * @return array
   *   The base message, missing keys for text, postback, or attachment.
   */
  private function getBaseMessage() {
    $message = [
      "object" => "page",
      "entry" => [
        0 => [
          "id" => 1,
          "time" => 1234567890,
          "messaging" => [],
        ]
      ]
    ];

    $message['entry'][0]['messaging'][] = [
      "sender" => [
        "id" => 12345,
      ],
      "recipient" => [
        "id" => 23456
      ],
      "message" => [
        "mid" => "mid.12345.678990",
        "seq" => 1,
      ]
    ];

    return $message;
  }

  /**
   * Returns a test request containing a text message.
   *
   * @return array
   *   The test text message.
   */
  public function getTestTextMessage() {
    $message = $this->getBaseMessage();
    $message['entry'][0]['messaging'][0]['message']['text'] = "Hello Facebook bot!";
    return $message;
  }

  /**
   * Returns a test request containing a postback message.
   *
   * @return array
   *   The test text message.
   */
  public function getTestPostbackMessage() {
    $message = $this->getBaseMessage();
    unset($message['entry'][0]['messaging'][0]["message"]);
    $message['entry'][0]['messaging'][0]['postback'] = [
      "payload" => "TEST_PAYLOAD"
    ];
    return $message;
  }

  /**
   * Returns a test request containing an attachment message.
   *
   * @return array
   *   The test text message.
   */
  public function getTestAttachmentMessage() {
    $message = $this->getBaseMessage();
    $message['entry'][0]['messaging'][0]['message']['attachments'] = [
      0 => [
        "type" => "image",
        "payload" => [
          "url" => ""
        ]
      ]
    ];
    return $message;
  }

  /**
   * Returns a test request containing multiple messages.
   *
   * @return array
   *   The test request, containing 4 messages in the following order:
   *   - A text message
   *   - An invalid message
   *   - A postback message
   *   - An attachment message
   */
  public function getMultipleTestMessages() {
    $message = $this->getBaseMessage();

    // Text message.
    $message['entry'][0]['messaging'][0]['message']['text'] = "Hello Facebook bot!";

    // Invalid message.
    $message['entry'][0]['messaging'][1] = [
      "sender" => [
        "id" => 10001,
      ],
      "recipient" => [
        "id" => 23456
      ],
    ];

    // Postback message.
    $message['entry'][0]['messaging'][2] = [
      "sender" => [
        "id" => 10003,
      ],
      "recipient" => [
        "id" => 23456
      ],
      "timestamp" => 1458692752478,
      "postback" => [
        "payload" => "USER_DEFINED_PAYLOAD",
      ]
    ];

    // Attachment message.
    $message['entry'][0]['messaging'][3] = [
      "sender" => [
        "id" => 10004,
      ],
      "recipient" => [
        "id" => 22223
      ],
      "message" => [
        "mid" => "mid.23456.78901",
        "seq" => 1,
        "attachments" => [
          0 => [
            "type" => "image",
            "payload" => [
              "url" => ""
            ]
          ]
        ]
      ]
    ];
    return $message;
  }

  /**
   * Returns a test message of type postback.
   *
   * @return array
   *   The translated message, with 'message_type' and 'message_content' keys.
   */
  public function getTestTranslatedTextMessage() {
    return [
      'message_type' => FacebookService::MESSAGE_TYPE_TEXT,
      'message_content' => 'Hello Facebook bot!',
    ];
  }

  /**
   * Returns a test message of type postback.
   *
   * @return array
   *   The translated message, with 'message_type' and 'message_content' keys.
   */
  public function getTestTranslatedPostbackMessage() {
    return [
      'message_type' => FacebookService::MESSAGE_TYPE_POSTBACK,
      'message_content' => 'TEST_POSTBACK_PAYLOAD',
    ];
  }

  /**
   * Test data provider for text messages.
   *
   * @return array
   *   Multidimensional array, containing json encoded request data.
   */
  public function textMessageDataProvider() {
    return array(array(Json::encode($this->getTestTextMessage())));
  }

  /**
   * Test data provider for pre-translated text messages.
   *
   * @return array
   *   Associative array with message_type and message_content keys.
   */
  public function translatedTextMessageDataProvider() {
    return array(array($this->getTestTranslatedTextMessage()));
  }

  /**
   * Test data provider for postback messages.
   *
   * @return array
   *   Multidimensional array, containing json encoded request data.
   */
  public function postbackMessageDataProvider() {
    return array(array(Json::encode($this->getTestPostbackMessage())));
  }

  /**
   * Test data provider for pre-translated postback messages.
   *
   * @return array
   *   Associative array with message_type and message_content keys.
   */
  public function translatedPostbackMessageDataProvider() {
    return array(array($this->getTestTranslatedPostbackMessage()));
  }

  /**
   * Test data provider for attachment messages.
   *
   * @return array
   *   Multidimensional array, containing json encoded request data.
   */
  public function attachmentMessageDataProvider() {
    return array(array(Json::encode($this->getTestAttachmentMessage())));
  }

  /**
   * Test data provider for invalid messages.
   *
   * @return array
   *   Multidimensional array, containing json encoded request data.
   */
  public function invalidMessageDataProvider() {
    return array(array(Json::encode($this->getBaseMessage())));
  }

  /**
   * Test data provider with valid and invalid messages in a single request.
   *
   * @return array
   *   Multidimensional array, containing json encoded request data.
   */
  public function mixedMessageDataProvider() {
    return array(array(Json::encode($this->getMultipleTestMessages())));
  }

  /**
   * Test data provider with multiple incoming requests and message types.
   *
   * @return array
   *   Multidimensional array, containing json encoded request data.
   */
  public function multipleRequestDataProvider() {
    return array(
      array(
        Json::encode($this->getTestTextMessage()),
        Json::encode($this->getTestPostbackMessage()),
        Json::encode($this->getTestAttachmentMessage()),
      )
    );
  }

}
