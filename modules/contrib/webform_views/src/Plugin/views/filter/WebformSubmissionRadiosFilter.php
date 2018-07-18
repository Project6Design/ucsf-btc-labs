<?php

namespace Drupal\webform_views\Plugin\views\filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\views\Plugin\views\filter\StringFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter for 'radios' webform elements.
 *
 * @ViewsFilter("webform_submission_radios_filter")
 */
class WebformSubmissionRadiosFilter extends WebformSubmissionFieldFilter {

  /**
   * {@inheritdoc}
   */
  protected function exposedTranslate(&$form, $type) {
    $type = $form['#type'];
    parent::exposedTranslate($form, $type);
    if ($form['#type'] == 'select' && $form['#type'] != $type) {
      $form['#theme_wrappers'][] = 'form_element';
    }
  }

}
