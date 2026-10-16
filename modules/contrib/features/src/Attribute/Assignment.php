<?php

declare(strict_types=1);

namespace Drupal\features\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an "Assignment" attribute for plugin discovery.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Assignment extends Plugin {

  /**
   * Constructs the "Assignment" attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param int $weight
   *   The weight relative to others.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $name
   *   The human-readable name of the Generation.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   The human-readable description of the Generation.
   * @param string $config_route_name
   *   The config route name.
   * @param array $default_settings
   *   Default settings for the plugin.
   */
  public function __construct(
    string $id,
    public readonly int $weight = 0,
    public readonly ?TranslatableMarkup $name = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?string $config_route_name = NULL,
    public readonly ?array $default_settings = [],
  ) {
    parent::__construct($id);
  }

}
