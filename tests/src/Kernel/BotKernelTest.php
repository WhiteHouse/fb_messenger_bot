<?php

namespace Drupal\Tests\fb_messenger_bot\Kernel;

use Drupal\fb_messenger_bot\Bot\FBBot;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for the FB bot class.
 *
 * @group fb_messenger_bot
 */
class BotKernelTest extends KernelTestBase {

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
  protected $bot;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('fb_messenger_bot_conversation');
    $this->installConfig(static::$modules);
    $this->bot = $this->container->get('fb_messenger_bot.bot');
  }

  /**
   * Test bot instantiation.
   */
  public function testBotShouldInstantiate() {
    $this->assertInstanceOf('\Drupal\fb_messenger_bot\Bot\FBBot', $this->bot, 'Bot instantiated is not an instance of \Drupal\fb_messenger_bot\Bot\FBBot');
  }

}
