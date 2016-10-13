<?php
/**
 * @file
 * Contains \Drupal\fb_messenger_bot\Form\FacebookServiceSettingsForm.
 */
namespace Drupal\fb_messenger_bot\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class FacebookServiceSettingsForm extends ConfigFormBase {

  /**
   * @inheritdoc
   */
  public function getFormId() {
    return 'fb_messenger_bot_facebook_service_admin_settings';
  }

  /**
   * @inheritdoc
   */
  protected function getEditableConfigNames() {
    return [
      'fb_messenger_bot.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fb_messenger_bot.settings');

    $form['fb_messenger_bot_fb_api_url'] = array(
      '#type' => 'url',
      '#title' => $this->t('Facebook API URL'),
      '#default_value' => $config->get('fb_api_url'),
      '#required' => TRUE,
    );

    $form['fb_messenger_bot_fb_verify_token'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Facebook Verify Token'),
      '#default_value' => $config->get('fb_verify_token'),
      '#required' => TRUE,
    );

    $form['fb_messenger_bot_fb_page_access_token'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Facebook Page Access Token'),
      '#default_value' => $config->get('fb_page_access_token'),
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('fb_messenger_bot.settings')
      ->set('fb_api_url', $form_state->getValue('fb_messenger_bot_fb_api_url'))
      ->set('fb_verify_token', $form_state->getValue('fb_messenger_bot_fb_verify_token'))
      ->set('fb_page_access_token', $form_state->getValue('fb_messenger_bot_fb_page_access_token'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
