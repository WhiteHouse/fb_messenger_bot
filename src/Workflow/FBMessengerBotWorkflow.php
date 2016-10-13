<?php

namespace Drupal\fb_messenger_bot\Workflow;

use Drupal\fb_messenger_bot\Conversation\BotConversationInterface;
use Drupal\fb_messenger_bot\Conversation\ConversationFactoryInterface;
use Drupal\fb_messenger_bot\FacebookService;
use Drupal\fb_messenger_bot\Message\ButtonMessage;
use Drupal\fb_messenger_bot\Message\PostbackButton;
use Drupal\fb_messenger_bot\Message\TextMessage;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\fb_messenger_bot\Step\BotWorkflowStep;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Psr\Log\LoggerInterface;

/**
 * Class FBMessengerBotWorkflow.
 *
 * @package Drupal\fb_messenger_bot\Workflow
 */
class FBMessengerBotWorkflow implements BotWorkflowInterface {
  use BotWorkflowTrait;
  use StringTranslationTrait;

  /**
   * @var \Drupal\fb_messenger_bot\Conversation\ConversationFactoryInterface;
   */
  protected $conversationFactory;

  /**
   * @var \Drupal\fb_messenger_bot\FacebookService
   */
  protected $fbService;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Allowed message types.
   */
  protected $allowedMessageTypes = array(
    FacebookService::MESSAGE_TYPE_TEXT,
    FacebookService::MESSAGE_TYPE_POSTBACK,
  );

  /**
   * FBMessengerBotWorkflow constructor.
   *
   * Build our step list and call trait's setSteps method.
   *
   * @param ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\fb_messenger_bot\Conversation\ConversationFactoryInterface $conversationFactory
   *   The conversation factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The string translation service.
   * @param FacebookService $fbService
   *   The facebook service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ConversationFactoryInterface $conversationFactory, TranslationInterface $stringTranslation, FacebookService $fbService, LoggerInterface $logger) {
    $this->config = $configFactory->get('fb_messenger_bot.settings');
    $this->conversationFactory = $conversationFactory;
    $this->setSteps($this->buildSteps());
    $this->stringTranslation = $stringTranslation;
    $this->fbService = $fbService;
    $this->logger = $logger;
  }

  /**
   * Helper function to build out steps.
   *
   * @return array (BotWorkflowStepInterface)
   *   An array of BotWorkflowStepInterfaces.
   *
   */
  protected function buildSteps() {
    $stepList = array();

    // Set step welcoming user to conversation.
    $welcomeStep = new BotWorkflowStep('Welcome', 'welcome',
      array(
        new TextMessage('Hi there!'),
      )
    );

    $welcomeStep->setResponseHandlers(
      array(
        '*' => array(
          'handlerMessage' => NULL,
          'goto' => 'builtABot',
        ),
      )
    );

    $stepList['welcome'] = $welcomeStep;

    $builtStep = new BotWorkflowStep('Built A Bot', 'builtABot',
      new ButtonMessage('Glad you stopped by for a chat. Have you ever built a chat bot?',
        array(
          new PostbackButton('Yep!', 'builtABot_Yes'),
          new PostbackButton("Nope!", 'builtABot_No'),
        )
      )
    );

    $builtStep->setResponseHandlers(
      array(
        'builtABot_Yes' => array(
          'handlerMessage' => NULL,
          'goto' => 'veteranBuilder',
        ),
        'builtABot_No' => array(
          'handlerMessage' => NULL,
          'goto' => 'neverBuilt',
        ),
      )
    );

    $stepList['builtABot'] = $builtStep;

    $veteranStep = new BotWorkflowStep('Veteran Builder', 'veteranBuilder',
      array(
        new TextMessage("Awesome. We'd love to get your constructive feedback on this module we've put together."),
        new TextMessage("Maybe even some contributions to our repo if you've got ideas!"),
        new ButtonMessage("Click the button below to go to the next step!",
          array(
            new PostbackButton('Final step', 'veteranBuilder_final'),
          )
        ),
      )
    );

    $veteranStep->setResponseHandlers(
      array(
        'veteranBuilder_final' => array(
          'handlerMessage' => NULL,
          'goto' => 'closing',
        ),
      )
    );

    $stepList['veteranBuilder'] = $veteranStep;

    $neverBuiltStep = new BotWorkflowStep('Never Built', 'neverBuilt',
      array(
        new TextMessage("No problem! We hope this module we put together helps you out in launching your own Facebook bot!"),
        new ButtonMessage("Click the button below to go to the next step!",
          array(
            new PostbackButton('Final step', 'neverBuilt_final'),
          )
        ),
      )
    );

    $neverBuiltStep->setResponseHandlers(
      array(
        'neverBuilt_final' => array(
          'handlerMessage' => NULL,
          'goto' => 'closing',
        ),
      )
    );

    $stepList['neverBuilt'] = $neverBuiltStep;

    $closingStep = new BotWorkflowStep('Closing', 'closing',
      array(
        new TextMessage("Whether or not you've built a bot in the past,"),
        new TextMessage('drop us a line in Github with comments, thoughts, ideas, and/or feedback.'),
        new TextMessage("Anyone is open to contribute to this project! :)"),
      )
    );

    $stepList['closing'] = $closingStep;

    // Set validation callbacks.
    foreach ($stepList as $step) {
      $step_name = $step->getMachineName();
      switch ($step_name) {
        case 'welcome':
          $validationFunction = $this->getTextMessageValidatorFunction();
          $invalidResponse = $this->getGenericValidationFailMessage();
          break;

        case 'builtABot':
          $allowedPayloads = ['builtABot_Yes', 'builtABot_No'];
          $validationFunction = $this->getPostbackValidatorFunction($allowedPayloads);
          $invalidResponse = $this->getPostbackValidationFailMessage();
          break;

        case 'veteranBuilder':
          $allowedPayloads = ['veteranBuilder_final'];
          $validationFunction = $this->getPostbackValidatorFunction($allowedPayloads);
          $invalidResponse = $this->getPostbackValidationFailMessage();
          break;

        case 'neverBuilt':
          $allowedPayloads = ['neverBuilt_final'];
          $validationFunction = $this->getPostbackValidatorFunction($allowedPayloads);
          $invalidResponse = $this->getPostbackValidationFailMessage();
          break;

        default:
          $validationFunction = $this->getGenericValidatorFunction();
          $invalidResponse = $this->getGenericValidationFailMessage();
          break;
      }

      $step->setValidationCallback($validationFunction);
      $step->setInvalidResponseMessage($invalidResponse);
    }

    return $stepList;
  }

  /**
   * Set up the message structure for the generic validation failure message.
   *
   * @return \Drupal\fb_messenger_bot\Message\MessageInterface
   *   The message to send back to the user.
   *
   */
  public static function getGenericValidationFailMessage() {
    $outgoingMessage = new TextMessage("Sorry, I couldn't process that. Can you please try that step again?");
    return $outgoingMessage;
  }

  /**
   * Set up a generic validation function.
   *
   * @return callable
   *   A validation function.
   *
   */
  protected function getGenericValidatorFunction() {
    $temporaryValidator = function ($input) {
      return $input;
    };

    return $temporaryValidator;
  }

  /**
   * Set up a generic validation function for text messages.
   *
   * @return callable
   *   A generic validation function for text messages.
   */
  protected function getTextMessageValidatorFunction() {
    $validator = function ($input) {
      if ((empty($input['message_type'])) || $input['message_type'] != FacebookService::MESSAGE_TYPE_TEXT) {
        return FALSE;
      }
      else {
        return TRUE;
      }
    };

    return $validator;
  }

  /**
   * Set up the message structure for the zip code validation failure message.
   *
   * @return \Drupal\fb_messenger_bot\Message\MessageInterface
   *   The message to send back to the user.
   */
  public static function getZipCodeValidationFailMessage() {
    $outgoingMessage = new TextMessage("Sorry! That's not a zip code that we can accept. It should be in one of the following formats:\n12345\n12345-6789");
    return $outgoingMessage;
  }

  /**
   * Set up a zip code validation function.
   *
   * @return callable
   *   A zip code validation function.
   */
  protected function getZipCodeValidatorFunction() {
    $zipCodeValidator = function ($input) {
      if ((empty($input['message_type'])) || $input['message_type'] != FacebookService::MESSAGE_TYPE_TEXT) {
        return FALSE;
      }
      $zipCodeRegex = "/^[0-9]{5,5}(\-)?([0-9]{4,4})?$/";
      if (!empty(preg_match($zipCodeRegex, $input['message_content']))) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    };

    return $zipCodeValidator;
  }

  /**
   * Set up the message structure for postback message validation failures.
   *
   * @return \Drupal\fb_messenger_bot\Message\MessageInterface
   *   The message to send back to the user.
   */
  public static function getPostbackValidationFailMessage() {
    $outgoingMessage = new TextMessage("To continue, just tap a button from the previous question.");
    return $outgoingMessage;
  }

  /**
   * Get the postback validator closure.
   *
   * @param array $allowedPayloads
   *   An array of strings, representing allowed payload names.
   *
   * @return callable
   *   The callable validation function.
   */
  protected function getPostbackValidatorFunction(array $allowedPayloads) {
    $postbackValidator = function($input) use($allowedPayloads) {
      if (empty($input['message_type']) || $input['message_type'] != FacebookService::MESSAGE_TYPE_POSTBACK) {
        return FALSE;
      }
      if (empty($input['message_content']) || !in_array($input['message_content'], $allowedPayloads)) {
        return FALSE;
      }
      return TRUE;
    };

    return $postbackValidator;
  }

  /**
   * Set up the message structure for the phone validation failure message.
   *
   * @return \Drupal\fb_messenger_bot\Message\MessageInterface
   *   The message to send back to the user.
   */
  public static function getPhoneValidationFailMessage() {
    $outgoingMessage = new TextMessage("Sorry! That's not a phone number that we can accept. It should be in the following format: 123-456-7890");
    return $outgoingMessage;
  }

  /**
   * Set up a phone number validation function.
   *
   * @return callable
   *   A phone number validation function.
   */
  protected function getPhoneValidatorFunction() {
    $phoneNumberValidator = function ($input) {
      if ((empty($input['message_type'])) || $input['message_type'] != FacebookService::MESSAGE_TYPE_TEXT) {
        return FALSE;
      }
      $phoneNumberRegex = "/^([0-9]{3}|(\([0-9]{3}\)))[\-. ]?[0-9]{3}[\-. ]?[0-9]{4}$/";
      if (!empty(preg_match($phoneNumberRegex, $input['message_content']))) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    };

    return $phoneNumberValidator;
  }

  /**
   * Set up the message structure for the email validation failure message.
   *
   * @return \Drupal\fb_messenger_bot\Message\MessageInterface
   *   The message to send back to the user.
   */
  public static function getEmailValidationFailMessage() {
    $outgoingMessage = new TextMessage("Sorry! That's not an email address that we can accept. It should be in the following format: yourname@example.com");
    return $outgoingMessage;
  }

  /**
   * Set up an email validation function.
   *
   * @return callable
   *   An email validation function.
   */
  protected function getEmailValidatorFunction() {
    $emailValidator = function ($input) {
      if ((empty($input['message_type'])) || $input['message_type'] != FacebookService::MESSAGE_TYPE_TEXT) {
        return FALSE;
      }
      if (preg_match('/@.*?(\..*)+/', $input['message_content']) === 0) {
        return FALSE;
      }
      // Ensure no 4-byte characters are part of the e-mail, because those are stripped from messages.
      if (preg_match('/[\x{10000}-\x{10FFFF}]/u', $input['message_content']) !== 0) {
        return FALSE;
      }
      if ((bool)filter_var($input['message_content'], FILTER_VALIDATE_EMAIL) == FALSE) {
        return FALSE;
      }

      return \Drupal::service('email.validator')->isValid($input['message_content'], FALSE, TRUE);
    };

    return $emailValidator;
  }

  /**
   * Overrides default implementation provided in BotWorkflowTrait.
   *
   * {@inheritdoc}
   */
  protected function preprocessSpecialMessages(array $receivedMessage, BotConversationInterface &$conversation) {
    $specialMessages = array();

    // Start Over functionality.
    if (preg_match('/^start( )*over$/i', trim($receivedMessage['message_content']))) {
      $specialMessages = $this->startOver($conversation);
    }

    return $specialMessages;
  }

  /**
   *
   * Overrides default implementation provided in BotWorkflowTrait.
   *
   * {@inheritdoc}
   */
  protected function checkDisallowedMessageType(array $receivedMessage, BotConversationInterface &$conversation) {
    $allowedTypes = $this->allowedMessageTypes;
    if (in_array($receivedMessage['message_type'], $allowedTypes, TRUE)) {
      return array();
    }
    return array(
      new TextMessage("Whatever it is that you sent..we can't process it! Try again!"),
    );
  }

  /**
   * Overrides default implementation provided in BotWorkflowTrait.
   *
   * {@inheritdoc}
   */
  protected function getTrollingMessage() {
    $messages = array();
    $messages[] = new TextMessage("Hey there! I'm not following what you're trying to say.");
    $messages[] = new TextMessage("Read the last message we sent out to get an idea of what kind of response we're expecting.");
    $messages[] = new TextMessage("You can also start over by sending us the text 'Start Over'.");
    return $messages;
  }

  /**
   * Starts the Conversation over.
   *
   * @param BotConversationInterface $conversation
   *   The Conversation to start over. Will be destroyed and rebuilt.
   *
   * @return \Drupal\fb_messenger_bot\Message\MessageInterface
   *   Returns the start over message.
   */
  protected function startOver(BotConversationInterface &$conversation) {
    $stepName = $this->getDefaultStep();
    // Remove the existing conversation from the database and start new one.
    $uid = $conversation->getUserId();
    $conversation->delete();

    // Assign the newly loaded conversation to the original $conversation
    // variable passed by reference.
    $conversation = $this->conversationFactory->getConversation($uid)->setLastStep($stepName);
    $conversation->save();

    // Send the welcome message.
    $response = $this->getStep($stepName)->getQuestionMessage();
    return $response;
  }

  /**
   * Stores the user's first and last name from FB.
   *
   * @param BotConversationInterface $conversation
   *   The Conversation to retrieve and set the name for.
   *
   * @return bool
   *   TRUE if names set, FALSE if not.
   */
  protected function setName(BotConversationInterface &$conversation) {
    $uid = $conversation->getUserId();
    $nameFromFB = $this->fbService->getUserInfo($uid);
    if ((!empty($nameFromFB['first_name'])) && !empty($nameFromFB['first_name'])) {
      $conversation->setValidAnswer('firstName', $nameFromFB['first_name'], TRUE);
      $conversation->setValidAnswer('lastName', $nameFromFB['last_name'], TRUE);
      return TRUE;
    }
    else {
      $conversation->setValidAnswer('firstName', '', TRUE);
      $conversation->setValidAnswer('lastName', '', TRUE);
      $this->logger->error('Failed to retrieve first or last name for conversation for userID @uid.',
        array('@uid' => $uid));
      return FALSE;
    }
  }

}
