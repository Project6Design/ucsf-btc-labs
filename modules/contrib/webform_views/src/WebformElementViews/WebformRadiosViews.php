<?php

namespace Drupal\webform_views\WebformElementViews;

use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\Plugin\WebformElementInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform views handler for radios webform elements.
 */
class WebformRadiosViews extends WebformDefaultViews {

  /**
   * {@inheritdoc}
   */
  public function getElementViewsData(WebformElementInterface $element_plugin, array $element) {
    $views_data = parent::getElementViewsData($element_plugin, $element);

    $views_data['filter'] = [
      'id' => 'webform_submission_radios_filter',
      'real field' => 'value',
    ];

    return $views_data;
  }

}
