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
  public function defaultStyles() {
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
  public function formElements($form, FormStateInterface $form_state) {
    $elements = [];

    // Fields that will need translations
    $translation_fields = [
      '#title',
      '#description',
    ];

    // Get form fields from yaml.
    foreach ($this->pluginDefinition['form'] as $field => $setting) {
      $element = [];
      foreach ($setting as $property_key => $property) {
        if (in_array($property_key, $translation_fields)) {
          $element[$property_key] = $this->t($property);
        }
        else {
          $element[$property_key] = $property;
        }
      }
      if (isset($this->styles[$field])) {
        $element['#default_value'] = $this->styles[$field];
      }
      $elements[$field] = $element;
    }
    return $elements;
  }

}
