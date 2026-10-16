<?php

declare(strict_types=1);

namespace Drupal\features\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a "Generation" attribute for plugin discovery.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Generation extends Plugin {

  /**
   * Constructs the "Generation" attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param int $weight
   *   The weight relative to others.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $name
   *   The human-readable name of the Generation.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   The human-readable description of the Generation.
   */
  public function __construct(
    string $id,
    public readonly int $weight = 0,
    public readonly ?TranslatableMarkup $name = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
  ) {
    parent::__construct($id);
  }

}
