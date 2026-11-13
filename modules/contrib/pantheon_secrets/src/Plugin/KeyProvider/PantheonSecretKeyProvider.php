<?php

namespace Drupal\pantheon_secrets\Plugin\KeyProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\KeyInterface;
use Drupal\key\Plugin\KeyPluginDeleteFormInterface;
use Drupal\key\Plugin\KeyPluginFormInterface;
use Drupal\key\Plugin\KeyProviderBase;
use PantheonSystems\CustomerSecrets\CustomerSecrets;
use PantheonSystems\CustomerSecrets\CustomerSecretsClientInterface;

/**
 * A key provider that allows a key to be retrieved from Pantheon secrets.
 *
 * @KeyProvider(
 *   id = "pantheon",
 *   label = @Translation("Pantheon"),
 *   description = @Translation("The Pantheon key provider allows a key to be retrieved from a Pantheon secret."),
 *   storage_method = "pantheon",
 *   key_value = {
 *     "accepted" = FALSE,
 *     "required" = FALSE
 *   }
 * )
 */
class PantheonSecretKeyProvider extends KeyProviderBase implements KeyPluginFormInterface, KeyPluginDeleteFormInterface {

  /**
   * The customer secrets client.
   *
   * @var \PantheonSystems\CustomerSecrets\CustomerSecretsClientInterface
   */
  protected CustomerSecretsClientInterface $secretsClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->secretsClient = CustomerSecrets::create()->getClient();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'secret_name' => '',
      'base64_encoded' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['secret_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Secret name'),
      '#options' => $this->getSecretNames(),
      '#description' => $this->t('Name of the secret set in Pantheon.'),
      '#required' => TRUE,
      '#default_value' => $this->getConfiguration()['secret_name'],
    ];

    if ($secret_name = $this->getConfiguration()['secret_name']) {
      $form['secret_value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Secret value'),
        '#description' => $this->t('The secret value is hidden for security reasons.'),
        '#disabled' => TRUE,
        '#default_value' => $this->maskSecretValue($this->secretsClient->getSecret($secret_name)->getValue()),
      ];
    }

    $form['base64_encoded'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Base64 encoded'),
      '#description' => $this->t('Check this box if the secret is base64 encoded.'),
      '#default_value' => $this->getConfiguration()['base64_encoded'] ?? $this->defaultConfiguration()['base64_encoded'],
    ];

    return $form;
  }

  /**
   * Mask secret value.
   */
  private function maskSecretValue($secret_value) {
    $kept_chars_options = [4, 2, 1];
    $value_length = strlen($secret_value);
    foreach ($kept_chars_options as $kept_chars) {
      if (($kept_chars + 2) > $value_length) {
        continue;
      }
      return str_repeat('*', $value_length - $kept_chars) . substr($secret_value, -$kept_chars);
    }
    // If it still hasn't returned, then keep only 1 char.
    return str_repeat('*', $value_length - 1) . substr($secret_value, -1);
  }

  /**
   * Get the secret names.
   *
   * @return array
   *   An array of secret names.
   */
  protected function getSecretNames() {
    $secrets = $this->secretsClient->getSecrets();
    $secret_names = [];

    foreach ($secrets as $secret) {
      $secret_names[$secret->getName()] = $secret->getName();
    }

    return $secret_names;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $key_provider_settings = $form_state->getValues();
    $secret_name = $key_provider_settings['secret_name'];
    $secret_value = $this->secretsClient->getSecret($secret_name);

    // Does the secret exist.
    if (!$secret_value) {
      $form_state->setErrorByName('secret_name', $this->t('The secret does not exist or it is empty.'));
      return;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyValue(KeyInterface $key) {
    $secret_name = $this->configuration['secret_name'];
    $secret = $this->secretsClient->getSecret($secret_name);

    if (!$secret) {
      return NULL;
    }

    $value = $secret->getValue();
    // Check if 'base64_encoded' key exists in configuration.
    if (isset($this->configuration['base64_encoded']) && $this->configuration['base64_encoded']) {
      $value = base64_decode($value);
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function buildDeleteForm(array &$form, FormStateInterface $form_state) {
    $form['warning'] = [
      '#type' => 'item',
      '#markup' => $this->t('Remember: deleting this key will NOT delete the secret from Pantheon.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateDeleteForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitDeleteForm(array &$form, FormStateInterface $form_state) {}

}
