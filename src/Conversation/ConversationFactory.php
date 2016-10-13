<?php

namespace Drupal\fb_messenger_bot\Conversation;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\fb_messenger_bot\Entity\BotConversation;

/**
 * Class ConversationFactory.
 *
 * @package Drupal\fb_messenger_bot\Conversation
 */
class ConversationFactory implements ConversationFactoryInterface {

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * ConversationFactory constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $queryFactory
   *   The entity query factory.
   */
  public function __construct(QueryFactory $queryFactory) {
    $this->queryFactory = $queryFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function getConversation($uid) {
    // Check for an active, incomplete conversation.
    $cid = $this->getActiveConversationId($uid);
    if ($cid && $conversation = BotConversation::load($cid)) {
      return $conversation;
    }

    // If there is no active conversation, create a new one.
    $conversation = BotConversation::create(['uid' => $uid]);
    $conversation->save();
    return $conversation;
  }

  /**
   * Check for an active conversation with a given uid.
   *
   * @param string $uid
   *   The conversation uid.
   *
   * @return array|int
   *   The entityQuery result.
   */
  protected function getActiveConversationId($uid) {
    $query = $this->queryFactory->get('fb_messenger_bot_conversation')
      ->condition('uid', $uid)
      ->condition('complete', BotConversationInterface::INCOMPLETE)
      ->sort('created')
      ->range(0, 1);
    $ids = $query->execute();
    foreach ($ids as $id) {
      return $id;
    }
  }

}
