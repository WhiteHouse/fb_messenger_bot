<?php

namespace Drupal\fb_messenger_bot\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\fb_messenger_bot\Conversation\BotConversationInterface;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the BotConversation entity class.
 *
 * @ingroup fb_messenger_bot
 *
 * @ContentEntityType(
 *   id = "fb_messenger_bot_conversation",
 *   label = @Translation("Bot Conversation entity"),
 *   base_table = "bot_conversation",
 *   handlers = {
 *     "storage_schema" = "Drupal\fb_messenger_bot\Entity\BotConversationStorageSchema",
 *   },
 *   entity_keys = {
 *     "id" = "cid",
 *     "uid" = "userId",
 *     "complete" = "complete",
 *     "last_step" = "lastStep",
 *     "valid_answers" = "validAnswers",
 *     "error_count" = "errorCount"
 *   },
 * )
 */
class BotConversation extends ContentEntityBase implements BotConversationInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getConversationId() {
    return $this->get('cid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserId() {
    return $this->get('uid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getComplete() {
    return $this->get('complete')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setComplete($complete) {
    $this->set('complete', $complete ? BotConversationInterface::COMPLETE : BotConversationInterface::INCOMPLETE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastStep($lastStep) {
    $this->set('last_step', $lastStep);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastStep() {
    return $this->get('last_step')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setValidAnswer($stepMachineName, $answer, $replace = FALSE) {
    $validAnswers = $this->getValidAnswers();
    if (array_key_exists($stepMachineName, $validAnswers) && !$replace) {
      $validAnswers[$stepMachineName] .= PHP_EOL . PHP_EOL . $answer;
    }
    else {
      $validAnswers[$stepMachineName] = $answer;
    }

    $this->set('valid_answers', $validAnswers);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getValidAnswers() {
    if ($fieldValue = $this->get('valid_answers')->getValue()) {
      return $fieldValue[0];
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorCount() {
    return $this->get('error_count')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function incrementErrorCount() {
    $currentCount = $this->getErrorCount();
    $this->set('error_count', ++$currentCount);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function resetErrorCount() {
    $this->set('error_count', 0);
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * Field property definitions.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // ID field for the Conversation.
    $fields['cid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Conversation ID'))
      ->setDescription(t('A unique ID to identify the conversation.'))
      ->setReadOnly(TRUE);

    // UserID field for the Conversation.
    $fields['uid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('User ID'))
      ->setDescription(t('The Facebook User ID.'))
      ->setReadOnly(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 100,
      ));

    // Complete field for the Conversation.
    $fields['complete'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Complete'))
      ->setDescription(t('Indicates if a conversation with a user is complete.'))
      ->setDefaultValue(FALSE);

    // lastStep field for the Conversation.
    $fields['last_step'] = BaseFieldDefinition::create('string')
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setLabel(t('Last Step'))
      ->setDescription(t('The position of the last step sent to the user in the
        BotWorkflow.'));

    // validAnswers field for the Conversation.
    $fields['valid_answers'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Valid Answers'))
      ->setDescription(t('Serialized array containing valid answers collected
        from a Facebook User.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['error_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Error Count'))
      ->setDescription('The number of errors encountered during this Conversation.')
      ->setDefaultValue(0);

    return $fields;
  }

}
