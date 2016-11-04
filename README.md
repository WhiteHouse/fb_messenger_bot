# Features

This Drupal module provides a tool to build a chat bot to work on Facebook's Messenger
Platform.

## Requirements

- Drupal 8
- PHP 5.6 or greater
- Facebook App and Page

## Installation

 1. Enable the fb_messenger_bot module.
 2. Visit /admin/config/fb-messenger and enter a custom value for the Facebook
    Verify Token (save the configuration).
 3. Complete steps 1-4 outlined in [Facebook's quickstart documentation](https://developers.facebook.com/docs/messenger-platform/quickstart)
    including the copying of the page access token in step 3 (note: this module sets up the webhook at /webhook/contact).
 4. Return to /admin/config/fb-messenger and paste the page access token into
    the Facebook Page Access Token field (save the configuration).
 5. Clear caches.
 6. Visit the Facebook page your app is subscribed to and send it a message.
    You should receive a response from the bot!

## Customizing your bot

 *Note:* You can use the `demo_fb_messenger_bot` folder as a starting point or
 reference.
 1. In a custom module, create a custom workflow by creating a class that
    extends the FBMessengerBotWorkflow class.
 2. Modify the `fb_messenger_bot.workflow` service to use your custom workflow by
    creating a class that extends ServiceProviderBase and implements the `alter()`
    method ([documentation from d.o.](https://www.drupal.org/node/2026959)).

### Supported message types

As of right now, this module can handle sending text, button, generic, and video
messages from [Facebook's Send API](https://developers.facebook.com/docs/messenger-platform/product-overview/conversation#send_messages).

#### Text message

```php
$textMessage =  new TextMessage('Text Message!');
```

#### Button message

```php
$buttonMessage = new ButtonMessage(
  array(
    new PostbackButton('Next Step', 'buttonMessage_Next'),
    new UrlButton('URL Button', 'http://www.example.com');
  )
);
```

#### Video message
```php
$videoMessage = new VideoMessage('http://techslides.com/demos/sample-videos/small.mp4');
```

### Steps

The meat of a workflow resides in its steps. Each step is an object responsible
for providing the workflow with a human readable name (see Roadmap), machine
name, and message(s) to send the user. In addition to those basics, steps need
to indicate what the next step to go to in the workflow based on a user's
response. Lastly, steps should provide a validation callback which will be
invoked to validate the user's response to the step, as well as an invalid
response message, which will be sent to the user in the event that the callback
returns false.

#### Instantiating a step in your workflow

```php
$stepHumanReadableName
$stepMachineName
$messageToSend = array(new TextMessage('Hi there!'));

// Set step welcoming user to conversation.
$welcomeStep = new BotWorkflowStep($humanReadableName, $stepMachineName, $messageToSend);
```
#### Adding response handlers

As mentioned above, every step needs to indicate the next step to go to based on
a user's response to that step. This is achieved with response handlers.

```php
$welcomeStep->setResponseHandlers(
  array(
    '*' => array(
      'handlerMessage' => NULL,
      'goto' => 'builtABot',
    ),
  )
);
```

In the example above, we indicate that regardless of what the user sends us (the
asterisk), we want to proceed to the step in the workflow with machine name
`builtABot`. By indicating `NULL` for the `handlerMessage`, we are saying we want
to send whatever message text is configured in the `builtABot` step. If we want to
override that behavior, we can simply set the handlerMessage value to be an
array of messages(s) of type MessageInterface.

If you sent out a message with buttons, and want to route a user to different
steps depending on which button they clicked, you would achieve that using
response handlers:

```php
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
```

#### Step validation

Often times, you'll want to collect some sort of information from the user that
requires validation prior to saving it. The module ships with a few validation
callbacks you can use, but you're free to override them, and/or
implement your own. These include:
- text message (ensure the user sent text)
- postback (ensure the user clicked one of the buttons you sent them)
- zip code
- e-mail
- phone number (U.S.)

```php
$validationFunction = $this->getTextMessageValidatorFunction();
$invalidResponse = $this->getGenericValidationFailMessage();
$welcomeStep->setValidationCallback($validationFunction);
$welcomeStep->setInvalidResponseMessage($invalidResponse);
```

## Roadmap

### Documentation Improvements

Add documentation on:
- starting over a conversation
- fetching and storing a user's info from Facebook
- pre and post conversation process methods
- handling trolling users

### Enhancements and hopes

We'd love to:
- Refactor as much of the code as possible to be usable in a general
PHP context, outside of Drupal.
- Convert objects to be Drupal entities where appropriate. Refactoring steps
to being config entities, for example, would allow for configuration of steps by
an admin.
- Support multiple workflows (e.g. you could have a intro workflow with
certain steps and then transition a user to a gather contact info workflow with
a different set of steps etc.)
- See you all fork and contribute to the module! (It would be great to see
someone from the community polish the code and contribute it to [drupal.org](drupal.org)).
