<?php

namespace Drupal\webform_views\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Webform submission composite field.
 *
 * @ViewsField("webform_submission_composite_field")
 */
class WebformSubmissionCompositeField extends WebformSubmissionField {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $this->getEntity($values);

    if ($webform_submission->access('view')) {
      $webform = $webform_submission->getWebform();

      // Get format and element key.
      $format = $this->options['webform_element_format'];
      $element_key = $this->definition['webform_submission_field'];
      $composite_key = $this->definition['webform_submission_property'];

      // Get element and element handler plugin.
      $element = $webform->getElement($element_key, TRUE);
      if (!$element) {
        return [];
      }

      // Set the format.
      $element['#format'] = $format;

      $composite_element = $element['#webform_composite_elements'][$composite_key];
      $composite_element['#webform_key'] = $element['#webform_key'];
      $options = [
        'composite_key' => $composite_key,
      ];

      return $this->webformElementManager->invokeMethod('formatHtml', $composite_element, $webform_submission, $options);
    }

    return [];
  }

}
