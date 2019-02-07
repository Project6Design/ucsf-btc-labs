<?php

namespace Drupal\block_style_plugins_test\Plugin\BlockStyle;

use Drupal\block_style_plugins\Plugin\BlockStyleBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Demonstrate using the 'include' parameter.
 *
 * Provides a 'DropdownWithInclude' block style for only adding styles to the
 * "Powered by Drupal" block.
 *
 * @BlockStyle(
 *  id = "dropdown_with_include",
 *  label = @Translation("Dropdown with Include"),
 *  include = {
 *    "system_powered_by_block",
 *    "basic",
 *  }
 * )
 */
class DropdownWithInclude extends BlockStyleBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // Default this to the third option.
    return ['dropdown_class' => 'style-3'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // The value of the options should be the class name which will be applied.
    $elements['dropdown_class'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose a style from the dropdown'),
      '#options' => [
        'style-1' => $this->t('Style 1'),
        'style-2' => $this->t('Style 2'),
        'style-3' => $this->t('Style 3'),
      ],
      '#default_value' => $this->configuration['dropdown_class'],
    ];

    return $elements;
  }

}
