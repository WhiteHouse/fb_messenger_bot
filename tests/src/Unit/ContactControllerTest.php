<?php
/**
 * @file Defines tests for the ContactController class.
 *
 */

namespace Drupal\Tests\fb_messenger_bot\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\fb_messenger_bot\Controller\ContactController;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Component\Serialization\Json;

/**
 * Tests for the fb_messenger_bot ContactController class.
 *
 * @group fb_messenger_bot
 */
class ContactControllerTest extends UnitTestCase {

  protected $controller;

  /**
   * Set up a controller so we dont have to build it each time.
   */

  public function setUp() {
    parent::setUp();

    // stolen from Drupal\Tests\Core\Datetime\DateTest::setUp()
    $container = new ContainerBuilder();
    $configFactory = $this->getConfigFactoryStub(['fb_messenger_bot.settings' => [
      'logging' => [
        'log_incoming_post' => '1',
        'log_http_response' => '1'
      ]
    ]]);
    $container->set('config.factory', $configFactory);
    \Drupal::setContainer($container);

    $mockRequest = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
      ->getMock();
    $mockRequest->method('getContent')
      ->willReturn($this->getJunkJSON());

    $mockRequestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
      ->getMock();
    $mockRequestStack->method('getCurrentRequest')
      ->willReturn($mockRequest);

    $mockLogger = $this->getMockBuilder('Drupal\Core\Logger\LoggerChannelInterface')
      ->getMock();
    $mockLogger->method('debug')
      ->will($this->returnCallback(function($input) {
        print $input;
      }));

    $mockFbService = $this->getMockBuilder('Drupal\fb_messenger_bot\FacebookService')
      ->disableOriginalConstructor()
      ->getMock();
    $mockQueueFactory = $this->getMockBuilder('Drupal\Core\Queue\QueueFactory')
      ->disableOriginalConstructor()
      ->getMock();

    $mockFBBot = $this->getMockBuilder('Drupal\fb_messenger_bot\Bot\FBBot')
      ->disableOriginalConstructor()
      ->getMock();

    $this->controller = new ContactController($mockRequestStack, $mockLogger, $mockFbService, $mockQueueFactory, $mockFBBot);

  }

  /**
   * Test the ContactController's logIncomingPost method.
   */
  public function testLogIncomingPost() {
    $expectedString = $this->getJunkJSON();
    $this->expectOutputString(Json::decode($expectedString));
    $this->controller->logIncomingPost($expectedString);
    $this->assertTrue($this->controller->logIncomingPost($expectedString));
  }

  /**
   * Test the ContactController's logIncomingPost method when settings set to
   * not log.
   */
  public function testLogIncomingPostDisabled() {
    $this->controller->loggingSettings = array(
      'log_incoming_post' => 0,
      'log_outgoing_post' => 0,
      'log_http_response' => 0,
    );
    $expectedString = $this->getJunkJSON();
    $this->expectOutputString(Json::decode($expectedString));
    $this->controller->logIncomingPost($expectedString);
    $this->assertFalse($this->controller->logIncomingPost($expectedString));
  }


  /**
   * Helper function to get a valid-ish JSON string.
   *
   * @return string
   */
  public function getJunkJSON() {
    $junkJSON = <<<EOD
{
  "object":"page",
  "entry":[
    {
      "id":PAGE_ID,
      "time":1457764198246,
      "messaging":[
        {
          "sender":{
            "id":"USER_ID"
          },
          "recipient":{
            "id":"PAGE_ID"
          },
          "timestamp":1457764197627,
          "message":{
            "mid":"mid.1457764197618:41d102a3e1ae206a38",
            "seq":73,
            "text":"hello, world!"
          }
        }
      ]
    }
  ]
}
EOD;

    return $junkJSON;
  }
}
