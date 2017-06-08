<?php
/**
 * @file
 * Contains \Drupal\fb_messenger_bot\FacebookService.
 *
 * @todo: refactor to conform to an interface standard
 */
namespace Drupal\fb_messenger_bot;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use Drupal\fb_messenger_bot\Message\MessageInterface;
use GuzzleHttp\Exception;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Utility\Html;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class FacebookService {

  const MESSAGE_TYPE_TEXT = 'text';
  const MESSAGE_TYPE_POSTBACK = 'postback';
  const MESSAGE_TYPE_ATTACHMENT = 'attachment';
  const MESSAGE_TYPE_TEXT_OUT_LIMIT = 320;


  private $apiURL;
  private $verifyToken;
  private $pageAccessToken;

  /**
   * The HTTP client to make calls to Facebook with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var array Keyed array of logging options and boolean value.
   */
  protected $loggingSettings;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request $request
   */
  public $request;

  /**
   * Constructs a FacebookService.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   A Guzzle client object.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  function __construct(ConfigFactoryInterface $configFactory, ClientInterface $httpClient, LoggerInterface $logger, RequestStack $request) {
    $config = $configFactory->get('fb_messenger_bot.settings');
    $this->apiURL = $config->get('fb_api_url');
    $this->verifyToken = $config->get('fb_verify_token');
    $this->pageAccessToken = $config->get('fb_page_access_token');
    $this->loggingSettings = $config->get('logging');
    $this->httpClient = $httpClient;
    $this->logger = $logger;
    $this->request = $request->getCurrentRequest();
  }

  /**
   * Respond to Facebook's challenge method.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function challenge() {
    $request_query = $this->request->query;

    // Get verify token and challenge we expect Facebook to send in the request.
    $verify_token = $request_query->get('hub_verify_token');
    $challenge = $request_query->get('hub_challenge');

    // If the tokens match, respond to Facebook with the challenge they sent.
    if ($verify_token === $this->verifyToken) {
      $response = new Response($challenge);
    }
    else {
      $response = new Response('Error, wrong verification token');
      $this->logger->notice("The verification token received (" . $verify_token . ") does not match the one stored in settings (" . $this->verifyToken . ")");
    }
    return $response;
  }

  /**
   * Helper function to unpack an array of Messages into independant items.
   *
   * @param array $messages
   *   An array of 1+ MessageInterface objects to send to the user.
   * @param int $userID
   *   The numeric user id.
   */
  public function sendMessages(array $messages, $userID) {
    foreach ($messages as $message) {
      try {
        $this->sendMessage($message, $userID);
      }
      catch (\Exception $e) {
        $loggerVariables = array(
          '@exception_message' => $e->getMessage(),
        );
        $this->logger->error('sendMessage returned and error. Exception: @exception_message', $loggerVariables);
      }
    }

  }

  /**
   * Send a Message to a Facebook Messenger user.
   *
   * @param MessageInterface $message
   *   The formatted message body.
   * @param int $user_id
   *   The numeric user id.
   *
   * @return bool
   *   The request status.
   */
  public function sendMessage(MessageInterface $message, $user_id) {
    $formatted_message = [
      'recipient' => [
        'id' => $user_id,
      ],
      'message' => $message->getFormattedMessage(),
    ];

    $messageSendingURL = $this->apiURL . 'me/messages?access_token=' . $this->pageAccessToken;
    $client = $this->httpClient;
    try {
      $request = $client->post($messageSendingURL, [
        'json' => $formatted_message,
      ]);
      return TRUE;
    }
    catch (Exception\RequestException $e) {
      $rawResponse = $e->getResponse()->getBody();
      $response = Json::decode($rawResponse);
      if (empty($response)) {
        $loggerVariables = array(
          '@exception_message' => $e->getMessage(),
        );
        $this->logger->error('Send API error: Exception: @exception_message', $loggerVariables);
      }
      // Facebook sent back an error.
      elseif (array_key_exists('error', $response)) {
        $this->logFacebookErrorResponse($response);
      }
      return FALSE;
    }
    catch (\Exception $e) {
      $loggerVariables = array(
        '@exception_message' => $e->getMessage(),
      );
      $this->logger->error('Send API error: Exception: @exception_message', $loggerVariables);
      return FALSE;
    }
    finally {
      $this->logOutgoingPost($formatted_message);
    }
  }

  /**
   * Translate json from the Facebook API and group by user ID.
   *
   * @param string $rawData
   *   Json encoded data from the Facebook API.
   *
   * @return array
   *   A multidimensional array of user messages, keyed by user id.
   *
   * @throws \Exception
   *   Thrown if the array key 'entry' is not present.
   */
  public function translateRequest($rawData) {
    $messages = [];
    $data = Json::decode($rawData);

    // Ensure the expected 'entry' key is in the array.
    if (!is_array($data) || !array_key_exists('entry', $data)) {
      throw new \Exception('Unable to parse data due to unexpected structure');
    }

    foreach ($data['entry'] as $entry) {
      foreach ($entry['messaging'] as $message) {
        $uid = $message['sender']['id'];
        $messageType = self::typeFromMessage($message);
        $messageContent = self::contentFromMessage($message);

        // Do not continue if uid, type or content could not be determined.
        if (!$messageType || !$messageContent || !$uid) {
          $this->logger->error('Omitting message due to unexpected structure.');
          continue;
        }

        $messages[$uid] = isset($messages[$uid]) ? $messages[$uid] : [];
        $messages[$uid][] = [
          'message_sender'    => $message['sender'],
          'message_recipient' => $message['recipient'],
          'message_timestamp' => $message['timestamp'],
          'message_type'      => $messageType,
          'message_content'   => $messageContent,
        ];
      }
    }

    return $messages;
  }

  /**
   * Determine message type from array structure.
   *
   * @param array $message
   *   The value of the 'messaging' key from a facebook API event.
   *
   * @return bool|string
   *   The message type, or FALSE if none of the valid array keys was found.
   */
  public static function typeFromMessage($message) {
    $messageType = FALSE;
    if (isset($message['message']['text'])) {
      $messageType = self::MESSAGE_TYPE_TEXT;
    }
    elseif (isset($message['postback'])) {
      $messageType = self::MESSAGE_TYPE_POSTBACK;
    }
    elseif (isset($message['message']['attachments'])) {
      $messageType = self::MESSAGE_TYPE_ATTACHMENT;
    }

    return $messageType;
  }

  /**
   * Return the message content, based on the message type.
   *
   * @param array $message
   *   The value of the 'messaging' key from a facebook API event.
   *
   * @return mixed
   *   The message content, or FALSE if no valid array key was found.
   */
  public static function contentFromMessage(array $message) {
    switch (self::typeFromMessage($message)) {
      case self::MESSAGE_TYPE_TEXT:
        $content = $message['message']['text'];
        break;

      case self::MESSAGE_TYPE_ATTACHMENT:
        $content = $message['message']['attachments'];
        break;

      case self::MESSAGE_TYPE_POSTBACK:
        $content = $message['postback']['payload'];
        break;

      default:
        $content = FALSE;
    }

    return $content;
  }

  /**
   * Return an array of the passed string split into sizes within FB's outgoing limit.
   *
   * @param string $message
   *   A string which may be longer than FB's outgoing message limit.
   *
   * @return mixed
   *   An array of decoded strings which are within FB's outgoing limit message size.
   */
  public static function splitTextMessage($message, $startPosition = 0) {
    $maxLength = self::MESSAGE_TYPE_TEXT_OUT_LIMIT;
    $messageParts = array();
    $message = Html::decodeEntities(trim($message), ENT_QUOTES);
    $messagePart = substr($message, $startPosition, $maxLength);

    if (strlen($message) > ($startPosition + $maxLength)) {
      $whiteSpaceMatches = preg_match('/.*\s([^\s]+)$/', $messagePart, $matches);
      $trimLength = 0;
      if (!empty($matches[1])) {
        if (strlen($matches[1]) < strlen($messagePart)) {
          $trimLength = strlen($matches[1]);
          $maxLength = $maxLength - $trimLength;
          $messagePart = substr($message, $startPosition, $maxLength);
        }
      }
    }
    $messageParts[] = trim($messagePart);
    if (strlen($message) > ($startPosition + $maxLength)) {
      $messageParts = array_merge($messageParts, self::splitTextMessage($message, $startPosition + $maxLength));
    }
    return $messageParts;
  }

  /**
   * Get a user's FB info given a user ID and fields to retrieve from FB.
   *
   * @param $userID
   *  The Facebook User ID.
   * @param array $fieldsToRetrieve
   *  The fields to retrieve from Facebook pertaining to the passed userID.
   * @return array|void
   *  The requested fields from Facebook or null in the case of a request error.
   */
  public function getUserInfo($userID, array $fieldsToRetrieve = array('first_name','last_name')) {
    $userProfileApi = $this->apiURL . $userID;
    $fieldsAsQueryString = implode(",", $fieldsToRetrieve);
    $query_string = array(
      'fields' => $fieldsAsQueryString,
      'access_token' => $this->pageAccessToken,
    );

    // Request to User Profile API.
    $client = $this->httpClient;
    try {
      $request = $client->get($userProfileApi, [
        'query' => $query_string,
      ]);
      $rawResponse = $request->getBody();
    }
    catch (Exception\RequestException $e) {
      $rawResponse = $e->getResponse()->getBody();
      $response = Json::decode($rawResponse);
      // Not a json-formatted response like we expected.
      if (empty($response)) {
        $loggerVariables = array(
          '@exception_message' => $e->getMessage(),
        );
        $this->logger->error('User Profile API error: Exception: @exception_message.', $loggerVariables);
      }
      // Facebook sent back an error.
      elseif (array_key_exists('error', $response)) {
        $this->logFacebookErrorResponse($response, 'User Profile API');
      }
      return;
    }
    catch (\Exception $e) {
      $loggerVariables = array(
        '@exception_message' => $e->getMessage(),
      );
      $this->logger->error('User Profile API error: Exception: @exception_message.', $loggerVariables);
      return;
    }

    // Haven't decoded the $raw_response yet, so decode now.
    if (empty($response)) {
      $response = Json::decode($rawResponse);
    }

    // Build user info array to return to user.
    $userInfo = array();
    foreach ($response as $field => $fieldValue) {
      $userInfo[$field] = $fieldValue;
    }
    return $userInfo;
  }

  /**
   * Helper function to Log JSON error object received from Facebook.
   *
   * @param $response
   *  Error object received from Facebook.
   * @param string $api
   *  API we were using when we received the error.
   */
  public function logFacebookErrorResponse($response, $api = 'Send API') {
    $message = isset($response['error']['message']) ? $response['error']['message'] : '';
    $type = isset($response['error']['type']) ? $response['error']['type'] : '';
    $code = isset($response['error']['code']) ? $response['error']['code'] : '';
    $loggerVariables = array(
      '@api' => $api,
      '@message' => $message,
      '@type' => $type,
      '@code' => $code,
    );
    $this->logger->error('@api error: @message. Type: @type. Code: @code.',
      $loggerVariables
    );
  }

  /**
   * Helper function to log outgoing POST messages being sent.
   *
   * @param string $message
   *
   * @return bool
   */
  public function logOutgoingPost($message) {
    if (array_key_exists('log_outgoing_post', (array) $this->loggingSettings) && $this->loggingSettings['log_outgoing_post']) {
      $this->logger->debug("Sending outgoing POST (in JSON): @message", array('@message' => Json::encode($message)));
      return TRUE;
    }
    else {
      return FALSE;
    }
  }
}
