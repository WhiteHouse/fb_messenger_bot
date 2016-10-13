<?php

namespace Drupal\Tests\fb_messenger_bot\Kernel;

use Drupal\fb_messenger_bot\Entity\BotConversation;
use Drupal\fb_messenger_bot\Conversation\ConversationFactory;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for the Conversation Factory class.
 *
 * @group fb_messenger_bot
 */
class ConversationFactoryTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('fb_messenger_bot');

  /**
   * The bot instance.
   *
   * @var \Drupal\fb_messenger_bot\Bot\FBBot
   */
  protected $conversationFactory;

  protected $existingConversationUid;

  protected $existingConversationLastStep;

  protected $nonExistingConversationUid;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('fb_messenger_bot_conversation');

    // Add a conversation to the db to test the ConversationFactory's ability
    // to load an existing conversation.
    $this->existingConversationUid = '12345';
    $this->nonExistingConversationUid = '23456';
    $this->existingConversationLastStep = 10;
    BotConversation::create([
      'uid' => $this->existingConversationUid,
      'last_step' => $this->existingConversationLastStep,
    ])->save();

    // Instantiate an instance of the ConversationFactory.
    $this->conversationFactory = $this->container->get('fb_messenger_bot.conversation_factory');
  }

  /**
   * Test ConversationFactory instantiation.
   */
  public function testConversationFactoryShouldInstantiate() {
    $this->assertInstanceOf('\Drupal\fb_messenger_bot\Conversation\ConversationFactory', $this->conversationFactory, 'ConversationFactory instantiated is not an instance of \Drupal\fb_messenger_bot\Conversation\ConversationFactory');
  }

  /**
   * Ensure ConversationFactory loads an existing conversation.
   */
  public function testConversationFactoryShouldLoadExistingConversation() {
    $loadedConversation = $this->conversationFactory->getConversation($this->existingConversationUid);
    $this->assertEquals($this->existingConversationUid, $loadedConversation->getUserId());
    $this->assertEquals($this->existingConversationLastStep, $loadedConversation->getLastStep());
  }

  /**
   * Ensure ConversationFactory creates a new conversation when uid does not exist.
   */
  public function testConversationFactoryShouldCreateNewConversation() {
    $newConversation = $this->conversationFactory->getConversation($this->nonExistingConversationUid);
    $this->assertEquals($this->nonExistingConversationUid, $newConversation->getUserId());
  }

}
