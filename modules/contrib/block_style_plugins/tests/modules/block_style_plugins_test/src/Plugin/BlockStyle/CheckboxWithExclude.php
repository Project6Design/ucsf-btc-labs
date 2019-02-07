<?php

namespace Drupal\block_style_plugins_test\Plugin\BlockStyle;

use Drupal\block_style_plugins\Plugin\BlockStyleBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class to demonstrate excluding a block.
 *
 * Provides a 'CheckboxWithExclude' block style for adding a checkbox to all
 * blocks except for the "Powered by Drupal" block.
 *
 * The plugin definition for this class is defined in the
 * block_style_plugins_test.blockstyle.yml file.
 */
class CheckboxWithExclude extends BlockStyleBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['checkbox_class' => ''];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Checkboxes are do not apply a class automatically like other form
    // elements. Instead, they simply pass a boolean value that can be accessed
    // inside a Twig template like:
    // {% set checkbox_class = (block_styles.checkbox_with_exclude.checkbox_class == '1') ? TRUE : FALSE %}
    $elements['checkbox_class'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check this box to pass a boolean to the theme'),
      '#default_value' => $this->configuration['checkbox_class'],
    ];

    return $elements;
  }

}
