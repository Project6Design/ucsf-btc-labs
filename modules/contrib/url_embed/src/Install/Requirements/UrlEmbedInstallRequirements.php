<?php

declare(strict_types=1);

namespace Drupal\url_embed\Install\Requirements;

use Drupal\Core\Extension\InstallRequirementsInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;

/**
 * Define module installation requirements.
 */
class UrlEmbedInstallRequirements implements InstallRequirementsInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRequirements(): array {
    $requirements = [];
    if (!class_exists('\Embed\Embed')) {
      $requirements['url_embed_library'] = [
        'description' => t("URL Embed requires the Embed/Embed library."),
        'severity' => RequirementSeverity::Error,
      ];
    }
    return $requirements;
  }
}
