<?php

namespace Drupal\sharemessage;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Default controller class for source plugins.
 *
 * @ingroup sharemessage
 */
abstract class SharePluginBase extends PluginBase implements SharePluginInterface {

  use StringTranslationTrait;

  /**
   * Share Message.
   *
   * @var \Drupal\sharemessage\ShareMessageInterface $shareMessage
   */
  protected $shareMessage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->shareMessage = $configuration['sharemessage'];
  }

  /**
   * Provides the action link plugin's default configuration.
   *
   * Derived classes will want to override this method.
   *
   * @return array
   *   The plugin configuration array.
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * Provides the action link plugin's current configuration array.
   *
   * @return array
   *   An array containing the plugin's current configuration.
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * Updates the plugin's current configuration.
   *
   * @param array $configuration
   *   An array containing the plugin's configuration.
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Nothing to do here by default.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Nothing to do here by default.
  }

}
