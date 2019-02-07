<?php

namespace Drupal\block_style_plugins_test\Plugin\BlockStyle;

use Drupal\block_style_plugins\Plugin\BlockStyleBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'SimpleClass' block style for adding a class in a text field.
 *
 * @BlockStyle(
 *  id = "simple_class",
 *  label = @Translation("Simple Class"),
 * )
 */
class SimpleClass extends BlockStyleBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['simple_class' => ''];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $elements['simple_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Add a custom class to this block'),
      '#description' => $this->t('Do not add the "period" to the start of the class'),
      '#default_value' => $this->configuration['simple_class'],
    ];

    return $elements;
  }

}
