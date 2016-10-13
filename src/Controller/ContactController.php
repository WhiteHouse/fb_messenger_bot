<?php

namespace Drupal\fb_messenger_bot\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\fb_messenger_bot\Bot\FBBot;
use Drupal\fb_messenger_bot\FacebookService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handle incoming requests from Facebook.
 *
 * @package Drupal\fb_messenger_bot\Controller
 */
class ContactController extends ControllerBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request $request
   */
  public $request;

  /**
   * Logging settings.
   *
   * @var array A keyed array of option => boolean
   */
  public $loggingSettings;

  /**
   * Logging channel to use for writing log events.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Facebook Service.
   *
   * @var \Drupal\fb_messenger_bot\FacebookService
   */
  protected $fbService;

  /**
   * Facebook Bot.
   *
   * @var \Drupal\fb_messenger_bot\Bot\FBBot
   */
  protected $fbBot;

  /**
   * The incoming messages queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $incomingMessageQueue;

  /**
   * Queueing settings.
   *
   * @var array A keyed array of option => value
   */
  protected $queueingSettings;

  /**
   * {@inheritdoc}
   */
  public function __construct(RequestStack $request, LoggerChannelInterface $logger, FacebookService $fbService, QueueFactory $queueFactory, FBBot $fbBot) {
    $this->request = $request->getCurrentRequest();
    $this->logger = $logger;

    // Get logging and queueing settings from config.
    $config = $this->config('fb_messenger_bot.settings');
    $this->loggingSettings = $config->get('logging');
    $this->queueingSettings = $config->get('queueing');
    $this->fbService = $fbService;
    $this->fbBot = $fbBot;

    // Instantiate reliable queue.
    $this->incomingMessageQueue = $queueFactory->get('incoming_messages', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('logger.factory')->get('fb_messenger_bot'),
      $container->get('fb_messenger_bot.fb_service'),
      $container->get('queue'),
      $container->get('fb_messenger_bot.bot')
    );
  }

  /**
   * Route responder for the webhook/contact path.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An HTTP response object.
   */
  public function contact() {

    $requestMethod = $this->request->server->get('REQUEST_METHOD');

    try {
      switch ($requestMethod) {
        case 'POST':
          $response = $this->parsePost();
          break;

        case 'GET':
          $response = $this->parseGet();
          $response->headers->set('Content-Type', 'text/plain');
          break;

        default:
          $response = new Response('Method not allowed.');
          $response->setStatusCode(405);
      }
    }
    catch (\Exception $e) {
      $this->logger->error("Failed to process in-coming data from %method: @exception",
        array(
          '%method' => $requestMethod,
          '@exception' => $e->getMessage(),
        ));
      $this->logger->emergency("Failed to process in coming data: %method", array('%method' => $requestMethod));

      // Based on testing, it looks like FB auto retries if we send them a 400.
      // I wonder if they auto retry based on other responses.
      $response = new Response('Invalid data received');

      $this->logHttpResponse($response);
    }

    $this->logHttpResponse($response);
    return $response;
  }

  /**
   * Process an incoming GET method request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An HTTP response object.
   */
  public function parseGet() {
    $response = $this->fbService->challenge();
    return $response;
  }

  /**
   * Process an incoming POST method request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   An HTTP response object.
   */
  public function parsePost() {
    $rawDataReceived = $this->request->getContent();
    $this->logIncomingPost($rawDataReceived);
    $this->queueIncoming($rawDataReceived);
    $this->processQueueItems();

    return new Response('Message(s) received.');
  }

  /**
   * When incoming post logging is enabled, log the decoded post body.
   *
   * @var $dataReceived string
   *
   * @return bool
   *   Returns TRUE if incoming post was logged, otherwise, returns FALSE.
   *
   * @todo: sanitize dataReceived before logging
   */
  public function logIncomingPost($dataReceived) {
    if ($this->loggingSettings['log_incoming_post']) {
      $this->logger->debug("Incoming POST received: @dataReceived",
        array(
          '@dataReceived' => print_r($dataReceived, TRUE),
        )
      );
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * When http response logging is enabled, log response controller will return.
   *
   * @var $response Response
   *
   * @return bool
   *   Returns TRUE if response was logged, otherwise, returns FALSE.
   *
   * @todo: sanitize dataReceived before logging
   */
  public function logHttpResponse(Response $response) {
    if ($this->loggingSettings['log_http_response']) {
      $this->logger->debug("Returning HTTP Response @code: @message",
          array(
            '@code' => $response->getStatusCode(),
            '@message' => $response->getContent(),
          )
        );
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Queue the incoming posts from Facebook.
   *
   * @param string $rawDataReceived
   *   Raw post data received.
   */
  protected function queueIncoming($rawDataReceived) {
    $this->incomingMessageQueue->createItem($rawDataReceived);
  }

  /**
   * Claim and process items in the incoming_messages queue.
   */
  protected function processQueueItems() {
    // Continue to claim and process items as long as we don't exceed the
    // timeout setting specified in configuration, defaults to 30 seconds.
    $end = time() + $this->queueingSettings['incoming']['timeout'];
    while ((time() < $end) && ($item = $this->incomingMessageQueue->claimItem())) {
      $this->fbBot->process($item->data);
      $this->incomingMessageQueue->deleteItem($item);
    }
  }

}
