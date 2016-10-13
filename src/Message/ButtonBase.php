<?php
/**
 * @file
 * Contains Drupal\fb_messenger_bot\Message\ButtonBase.
 */

namespace Drupal\fb_messenger_bot\Message;

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;

/**
 * Base class for a button object.
 */
abstract class ButtonBase {
  const VALID_TYPES = [
    'web_url',
    'postback',
  ];
  protected $type;
  protected $title;

  /**
   * ButtonBase constructor.
   *
   * @param string $type
   *   The button type.
   * @param string $title
   *   The button's title text.
   */
  public function __construct($type, $title) {
    self::assertValidType($type);
    $this->type = $type;
    $this->title = $title;
  }

  /**
   * Assert a valid button type.
   *
   * @param string $type
   *   The type to be examined.
   *
   * @throws InvalidArgumentException
   *   Thrown if the type supplied is not one of the allowed types.
   */
  public static function assertValidType($type) {
    if (!in_array($type, self::VALID_TYPES)) {
      throw new InvalidArgumentException("Type {$type} is not a valid button type.");
    }
  }

  /**
   * Returns an array of the button's properties and values.
   *
   * @return array
   *   An associative array of properties and values.
   */
  public function toArray() {
    $properties = [];
    foreach ($this as $var => $value) {
      $properties[$var] = $value;
    }
    return $properties;
  }

}
