<?php

namespace Drupal\features\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for features.
 */
class FeaturesHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_file_download().
   */
  #[Hook('file_download')]
  public static function fileDownload($uri) {
    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
    $scheme = $stream_wrapper_manager->getScheme($uri);
    $target = $stream_wrapper_manager->getTarget($uri);
    if ($scheme == 'temporary' && $target) {
      $request = \Drupal::request();
      $route = $request->attributes->get('_route');
      // Check if we were called by Features download route.
      // No additional access checking needed here: route requires
      // "export configuration" permission, token validated by the controller.
      // @see \Drupal\features\Controller\FeaturesController::downloadExport()
      if ($route == 'features.export_download') {
        return [
          'Content-disposition' => 'attachment; filename="' . $target . '"',
        ];
      }
    }
  }

  /**
   * Implements hook_modules_installed().
   */
  #[Hook('modules_installed')]
  public static function modulesInstalled($modules) {
    if (!in_array('features', $modules)) {
      /** @var \Drupal\features\FeaturesAssignerInterface $assigner */
      $assigner = \Drupal::service('features_assigner');
      $assigner->purgeConfiguration();
    }
  }

}
