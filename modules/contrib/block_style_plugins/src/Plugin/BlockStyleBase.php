<?php

namespace Drupal\block_style_plugins\Plugin;

use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Base class for Block style plugins.
 */
abstract class BlockStyleBase extends PluginBase implements BlockStyleInterface, ContainerFactoryPluginInterface {

  /**
   * Plugin ID for the Block being configured.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * Plugin instance for the Block being configured.
   *
   * @var object
   */
  protected $blockPlugin;

  /**
   * Bundle type for 'Block Content' blocks.
   *
   * @var string
   */
  protected $blockContentBundle;

  /**
   * Instance of the Entity Repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Instance of the Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Style settings for the block styles.
   *
   * @var array
   *
   * @deprecated in 8.x-1.3 and will be removed before 8.x-2.x.
   *   Instead, you should just use $configuration.
   */
  protected $styles;

  /**
   * Construct method for BlockStyleBase.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   An Entity Repository instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   An Entity Type Manager instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityRepositoryInterface $entityRepository, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // Store our dependencies.
    $this->entityRepository = $entityRepository;
    $this->entityTypeManager = $entityTypeManager;
    // Store the plugin ID.
    $this->pluginId = $plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.repository'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // TODO: replace deprecated formElements() with an empty array before 8.x-2.x.
    return $this->formElements($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function prepareForm(array $form, FormStateInterface $form_state) {
    // Get the current block config entity.
    /** @var \Drupal\block\Entity\Block $entity */
    $entity = $form_state->getFormObject()->getEntity();

    // Set properties and configuration.
    $this->blockPlugin = $entity->getPlugin();
    $this->setBlockContentBundle();

    // Check to see if this should only apply to includes or if it has been
    // excluded.
    if ($this->includeOnly() && !$this->exclude()) {

      // Create a fieldset to contain style fields.
      if (!isset($form['block_styles'])) {
        $form['block_styles'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Block Styles'),
          '#collapsible' => FALSE,
          '#collapsed' => FALSE,
          '#weight' => 0,
        ];
      }

      $styles = $entity->getThirdPartySetting('block_style_plugins', $this->pluginId);
      $styles = is_array($styles) ? $styles : [];
      $this->setConfiguration($styles);

      // Create containers to place each plugin style settings into the styles
      // fieldset.
      $form['third_party_settings']['block_style_plugins'][$this->pluginId] = [
        '#type' => 'container',
        '#group' => 'block_styles',
      ];

      // Allow plugins to add field elements to this form.
      $subform_state = SubformState::createForSubform($form['third_party_settings']['block_style_plugins'][$this->pluginId], $form, $form_state);
      $form['third_party_settings']['block_style_plugins'][$this->pluginId] += $this->buildConfigurationForm($form['third_party_settings']['block_style_plugins'][$this->pluginId], $subform_state);

      // Allow plugins to alter this form.
      $form = $this->formAlter($form, $form_state);

      // Add form Validation.
      $form['#validate'][] = [$this, 'validateForm'];

      // Add the submitForm method to the form.
      array_unshift($form['actions']['submit']['#submit'], [$this, 'submitForm']);
    }

    return $form;
  }

  /**
   * Returns an array of field elements.
   *
   * @deprecated in 8.x-1.3 and will be removed before 8.x-2.x.
   *   Instead, you should just use buildConfigurationForm().
   */
  public function formElements($form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function formAlter(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Adds block style specific validation handling for the block form.
   *
   * TODO: Add this to the BlockStyleInterface before 8.x-2.x.
   *
   * @param array $form
   *   The form definition array for the full block configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    // Allow plugins to manipulate the validateForm.
    $subform_state = SubformState::createForSubform($form['third_party_settings']['block_style_plugins'][$this->pluginId], $form, $form_state);
    $this->validateConfigurationForm($form['third_party_settings']['block_style_plugins'][$this->pluginId], $subform_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm($form, FormStateInterface $form_state) {
    // Allow plugins to manipulate the submitForm.
    $subform_state = SubformState::createForSubform($form['third_party_settings']['block_style_plugins'][$this->pluginId], $form, $form_state);
    $this->submitConfigurationForm($form['third_party_settings']['block_style_plugins'][$this->pluginId], $subform_state);
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $variables) {
    $styles = $this->getStylesFromVariables($variables);

    if ($styles) {
      // Add all styles config to the $variables array.
      $variables['block_styles'][$this->pluginId] = $styles;

      // Add each style value as a class.
      foreach ($styles as $class) {
        // Don't put a boolean from a checkbox as a class.
        if (is_int($class)) {
          continue;
        }

        $variables['attributes']['class'][] = $class;
      }
    }

    return $variables;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    // TODO: Replace the deprecated defaultStyles() with defaultConfiguration() before 8.x-2.x.
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultStyles(),
      $configuration
    );
    // Set the deprecated $styles property.
    // TODO: Remove the deprecated $styles setting before 8.x-2.x.
    $this->styles = $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * Gets default style configuration for this plugin.
   *
   * @deprecated in 8.x-1.3 and will be removed before 8.x-2.x.
   *   Instead, you should just use defaultConfiguration().
   */
  public function defaultStyles() {
    return $this->defaultConfiguration();
  }

  /**
   * Gets this plugin's style configuration.
   *
   * @deprecated in 8.x-1.3 and will be removed before 8.x-2.x.
   *   Instead, you should just use getConfiguration().
   */
  public function getStyles() {
    @trigger_error('::getStyles() is deprecated in 8.x-1.3 and will be removed before 8.x-2.x. Instead, you should just use getConfiguration(). See https://www.drupal.org/project/block_style_plugins/issues/3016288.', E_USER_DEPRECATED);
    return $this->getConfiguration();
  }

  /**
   * Sets the style configuration for this plugin instance.
   *
   * @deprecated in 8.x-1.3 and will be removed before 8.x-2.x.
   *   Instead, you should just use setConfiguration().
   */
  public function setStyles(array $styles) {
    @trigger_error('::setStyles() is deprecated in 8.x-1.3 and will be removed before 8.x-2.x. Instead, you should just use setConfiguration(). See https://www.drupal.org/project/block_style_plugins/issues/3016288.', E_USER_DEPRECATED);
    $this->setConfiguration($styles);
  }

  /**
   * {@inheritdoc}
   */
  public function exclude() {
    $list = [];

    if (isset($this->pluginDefinition['exclude'])) {
      $list = $this->pluginDefinition['exclude'];
    }

    $block_plugin_id = $this->blockPlugin->getPluginId();

    if (!empty($list) && (in_array($block_plugin_id, $list) || in_array($this->blockContentBundle, $list))) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function includeOnly() {
    $list = [];

    if (isset($this->pluginDefinition['include'])) {
      $list = $this->pluginDefinition['include'];
    }

    $block_plugin_id = $this->blockPlugin->getPluginId();

    if (empty($list) || (in_array($block_plugin_id, $list) || in_array($this->blockContentBundle, $list))) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function themeSuggestion(array $suggestions, array $variables) {
    return $suggestions;
  }

  /**
   * Set the block content bundle type.
   */
  public function setBlockContentBundle() {
    $base_id = $this->blockPlugin->getBaseId();
    $uuid = $this->blockPlugin->getDerivativeId();

    if ($base_id == 'block_content') {
      $plugin = $this->entityRepository->loadEntityByUuid('block_content', $uuid);

      if ($plugin) {
        $this->blockContentBundle = $plugin->bundle();
      }
    }
  }

  /**
   * Get styles for a block set in a preprocess $variables array.
   *
   * @param array $variables
   *   Block variables coming from a preprocess hook.
   *
   * @return array|false
   *   Return the styles array or FALSE
   */
  protected function getStylesFromVariables(array $variables) {
    // Ensure that we have a block id.
    if (empty($variables['elements']['#id'])) {
      return FALSE;
    }

    // Load the block config entity.
    /** @var \Drupal\block\Entity\Block $block */
    $block = $this->entityTypeManager->getStorage('block')->load($variables['elements']['#id']);
    $styles = $block->getThirdPartySetting('block_style_plugins', $this->pluginId);

    if ($styles) {
      $this->setConfiguration($styles);
      return $styles;
    }
    else {
      return FALSE;
    }
  }

}
