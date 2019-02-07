<?php

namespace Drupal\block_style_plugins\Plugin;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a default class for block styles declared by yaml.
 */
class BlockStyle extends BlockStyleBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = [];
    if (isset($this->pluginDefinition['form'])) {
      foreach ($this->pluginDefinition['form'] as $field => $setting) {
        if (isset($setting['#default_value'])) {
          $defaults[$field] = $setting['#default_value'];
        }
      }
    }
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    // Get form fields from Yaml.
    foreach ($this->pluginDefinition['form'] as $field => $setting) {
      $element = [];
      foreach ($setting as $property_key => $property) {
        $element[$property_key] = $property;
      }
      if (isset($this->configuration[$field])) {
        $element['#default_value'] = $this->configuration[$field];
      }
      $elements[$field] = $element;
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function themeSuggestion(array $suggestions, array $variables) {
    // Ensure that a template is set in the info file.
    if (isset($this->pluginDefinition['template'])) {
      $template = $this->pluginDefinition['template'];

      $styles = $this->getStylesFromVariables($variables);

      // Only set suggestions if styles have been set for the block.
      if ($styles) {
        foreach ($styles as $style) {
          if (!empty($style)) {
            $suggestions[] = $template;
            break;
          }
        }
      }
    }

    return $suggestions;
  }

}
