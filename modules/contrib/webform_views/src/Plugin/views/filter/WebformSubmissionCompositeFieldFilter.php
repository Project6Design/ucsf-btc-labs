<?php

namespace Drupal\webform_views\Plugin\views\filter;

/**
 * Filter based on value of a composite of a webform submission.
 *
 * @ViewsFilter("webform_submission_composite_field_filter")
 */
class WebformSubmissionCompositeFieldFilter extends WebformSubmissionFieldFilter {

  /**
   * {@inheritdoc}
   */
  function operators() {
    $operators = parent::operators();

    // Replace all occurrences of "use the element type itself" with
    // "textfield". At the moment we do not support more complex composites than
    // plain text field.
    foreach ($operators as $k => $v) {
      if ($operators[$k]['webform_views_element_type'] == WebformSubmissionFieldFilter::ELEMENT_TYPE) {
        $operators[$k]['webform_views_element_type'] = 'textfield';
      }
    }

    return $operators;
  }

}
