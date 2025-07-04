<?php

namespace Drupal\webform_examples_accessibility\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for webform_examples_accessibility.
 */
class WebformExamplesAccessibilityHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments) {
    // Attach accessibility library which shows all fieldsets and labels.
    if (\Drupal::request()->query->get('accessibility') === '1') {
      $attachments['#attached']['library'][] = 'webform_examples_accessibility/webform_examples_accessibility';
    }
  }

  /**
   * Implements hook_webform_submission_form_alter().
   *
   * Adds button to disable/enable HTML client-side validation without have
   * to change any webform settings.
   *
   * The link is only applicable to webform ids that are prefix with examples_accessibility_*.
   */
  #[Hook('webform_submission_form_alter')]
  public function webformSubmissionFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
    if (strpos($form['#webform_id'], 'example_accessibility_') !== 0 && !preg_match('/^issue_\d+$/', $form['#webform_id']) && strpos($form['#webform_id'], 'test_') !== 0) {
      return;
    }
    $form['accessibility'] = ['#suffix' => '<hr/>', '#weight' => -1000];
    /* ************************************************************************ */
    // Accessibility.
    /* ************************************************************************ */
    // Get query without ajax parameters.
    $query = \Drupal::request()->query->all();
    unset($query['ajax_form'], $query['_wrapper_format']);
    $accessibility = \Drupal::request()->query->get('accessibility') === '1' ? TRUE : FALSE;
    $form['accessibility']['accessibility'] = [
      '#type' => 'link',
      '#title' => $accessibility ? $this->t('Hide accessibility') : $this->t('Show accessibility'),
      '#url' => Url::fromRoute('<current>', [], [
        'query' => [
          'accessibility' => $accessibility ? 0 : 1,
        ] + $query,
      ]),
    ];
    /* ************************************************************************ */
    // Required.
    /* ************************************************************************ */
    $form['accessibility'][] = ['#markup' => ' | '];
    if (\Drupal::request()->query->get('required') === '1') {
      $required = TRUE;
    }
    elseif (\Drupal::request()->query->get('required') === '0') {
      $required = FALSE;
    }
    else {
      $required = NULL;
    }
    $form['accessibility']['required'] = [
      '#type' => 'link',
      '#title' => $required ? $this->t('Disable required') : $this->t('Enable required'),
      '#url' => Url::fromRoute('<current>', [], [
        'query' => [
          'required' => $required ? 0 : 1,
        ] + $query,
      ]),
    ];
    if ($required !== NULL) {
      $elements =& WebformArrayHelper::flattenAssoc($form);
      foreach ($elements as &$element) {
        if (is_array($element) && isset($element['#type'])) {
          $element['#required'] = $required;
        }
      }
    }
    /* ************************************************************************ */
    // No validate.
    /* ************************************************************************ */
    $form['accessibility'][] = ['#markup' => ' | '];
    if (\Drupal::request()->query->get('novalidate') === '1') {
      $form['#attributes']['novalidate'] = TRUE;
      $novalidate = TRUE;
    }
    else {
      unset($form['#attributes']['novalidate']);
      $novalidate = FALSE;
    }
    $form['accessibility']['novalidate'] = [
      '#type' => 'link',
      '#title' => $novalidate ? $this->t('Enable client-side validation') : $this->t('Disable client-side validation'),
      '#url' => Url::fromRoute('<current>', [], [
        'query' => [
          'novalidate' => $novalidate ? 0 : 1,
        ] + $query,
      ]),
    ];
    /* ************************************************************************ */
    // Inline form error.
    /* ************************************************************************ */
    if (\Drupal::moduleHandler()->moduleExists('inline_form_errors')) {
      $form['accessibility'][] = ['#markup' => ' | '];
      if (\Drupal::request()->query->get('disable_inline_form_errors') === '1') {
        $form['#disable_inline_form_errors'] = TRUE;
        $disable_inline_form_errors = TRUE;
      }
      else {
        unset($form['#disable_inline_form_errors']);
        $disable_inline_form_errors = FALSE;
      }
      $form['accessibility']['disable_inline_form_errors'] = [
        '#type' => 'link',
        '#title' => $disable_inline_form_errors ? $this->t('Enable inline form errors') : $this->t('Disable inline form errors'),
        '#url' => Url::fromRoute('<current>', [], [
          'query' => [
            'disable_inline_form_errors' => $disable_inline_form_errors ? 0 : 1,
          ] + $query,
        ]),
      ];
    }
  }

}
