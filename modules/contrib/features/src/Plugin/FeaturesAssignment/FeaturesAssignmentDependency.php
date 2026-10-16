<?php

namespace Drupal\features\Plugin\FeaturesAssignment;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\features\Attribute\Assignment;
use Drupal\features\FeaturesAssignmentMethodBase;

/**
 * Class for assigning configuration to packages based on configuration
 * dependencies.
 *
 * @Plugin(
 *   id = "dependency",
 *   weight = 15,
 *   name = @Translation("Dependency"),
 *   description = @Translation("Add to packages configuration dependent on items already in that package."),
 * )
 */
#[Assignment(
  id: 'dependency',
  weight: 15,
  name: new TranslatableMarkup('Dependency'),
  description: new TranslatableMarkup('Add to packages configuration dependent on items already in that package.')
)]
class FeaturesAssignmentDependency extends FeaturesAssignmentMethodBase {

  /**
   * {@inheritdoc}
   */
  public function assignPackages($force = FALSE) {
    $this->featuresManager->assignConfigDependents();
  }

}
