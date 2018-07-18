<?php

namespace Drupal\sharemessage\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures Share Message global settings.
 */
class ShareMessageSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sharemessage.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'sharemessage_settings_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {

    $config = $this->config('sharemessage.settings');

    // Global setting.
    $form['message_enforcement'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow to enforce Share Messages'),
      '#description' => t('This will enforce loading of a Share Message if the ?smid argument is present in an URL. If something else on your site is using this argument, disable this option.'),
      '#default_value' => $config->get('message_enforcement'),
    ];

    $form['add_twitter_card'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Add meta tags for twitter's summary card with large image"),
      '#description' => $this->t('Enables sharing of images on twitter, check <a href="@url">this</a> for more information.', ['@url' => 'https://dev.twitter.com/cards/types/summary-large-image']),
      '#default_value' => $config->get('add_twitter_card'),
    ];

    $form['twitter_user'] = [
      '#title' => t('Twitter account username'),
      '#description' => t('This is required when enabling the meta tags for twitter cards above.'),
      '#type' => 'textfield',
      '#default_value' => $config->get('twitter_user'),
      '#states' => [
        'visible' => [
          ':input[name="add_twitter_card"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if ($form_state->getValue('add_twitter_card') && empty($form_state->getValue('twitter_user'))) {
      $form_state->setErrorByName('twitter_user', t('Please enter your twitter account username in order to activate meta tags for twitter cards.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('sharemessage.settings')
      ->set('message_enforcement', $form_state->getValue('message_enforcement'))
      ->set('add_twitter_card', $form_state->getValue('add_twitter_card'))
      ->set('twitter_user', $form_state->getValue('twitter_user'))
      ->save();
  }

}
