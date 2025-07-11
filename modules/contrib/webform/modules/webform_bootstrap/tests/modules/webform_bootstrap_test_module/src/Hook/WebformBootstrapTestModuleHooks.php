<?php

namespace Drupal\webform_bootstrap_test_module\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for webform_bootstrap_test_module.
 */
class WebformBootstrapTestModuleHooks {

  /**
   * Implements hook_webform_submission_form_alter().
   */
  #[Hook('webform_submission_form_alter')]
  public function webformSubmissionFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
    // Wrap any form that does not have a fieldset or details widget in a .well.
    $has_container = FALSE;
    foreach ($form['elements'] as $element) {
      if (isset($element['#type']) && in_array($element['#type'], ['fieldset', 'details'])) {
        $has_container = TRUE;
        break;
      }
    }
    if (!$has_container) {
      $form['#attributes']['class'][] = 'well';
      $form['#attributes']['class'][] = 'well-lg';
    }
  }

  /**
   * Implements hook_webform_element_alter().
   */
  #[Hook('webform_element_alter')]
  public function webformElementAlter(array &$element, FormStateInterface $form_state, array $context) {
    if (!isset($element['#type'])) {
      return;
    }
    // Add 'input-lg' to elements generate input's that support #attribute.
    switch ($element['#type']) {
      case 'webform_checkboxes_other':
      case 'webform_radios_other':
      case 'webform_buttons_other':
        $element['#other__attributes']['class'][] = 'input-lg';
        break;

      case 'webform_select_other':
        $element['#attributes']['class'][] = 'input-lg';
        $element['#other__attributes']['class'][] = 'input-lg';
        break;

      case 'textfield':
      case 'textarea':
      case 'email':
      case 'entity_autocomplete':
      case 'password':
      case 'select':
      case 'date':
      case 'datelist':
      case 'tel':
      case 'url':
      case 'webform_autocomplete':
      case 'webform_email_multiple':
      case 'webform_time':
      case 'webform_term_select':
      case 'webform_entity_select':
        $element['#attributes']['class'][] = 'input-lg';
        break;
    }
  }

}
