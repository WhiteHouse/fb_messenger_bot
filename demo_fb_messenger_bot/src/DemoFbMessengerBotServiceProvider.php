<?php
/**
 * @file
 * Contains Drupal\demo_fb_messenger_bot\DemoFbMessengerBotServiceProvider.
 */

namespace Drupal\demo_fb_messenger_bot;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the fb_messenger_bot workflow service.
 */
class DemoFbMessengerBotServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides fb_messenger_bot.workflow to insert custom implementation.
    $definition = $container->getDefinition('fb_messenger_bot.workflow');
    $definition->setClass('Drupal\demo_fb_messenger_bot\Workflow\DemoFBMessengerBotWorkflow');
  }

}
