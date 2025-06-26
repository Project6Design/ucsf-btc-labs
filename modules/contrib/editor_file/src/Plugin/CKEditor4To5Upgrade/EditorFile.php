<?php

declare(strict_types=1);

namespace Drupal\editor_file\Plugin\CKEditor4To5Upgrade;

use Drupal\Core\Plugin\PluginBase;
use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor4To5UpgradePluginInterface;
use Drupal\filter\FilterFormatInterface;

// phpcs:disable Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

/**
 * Provides the CKEditor 4 to 5 upgrade for drupalInsertFile CKEditor plugins.
 *
 * @CKEditor4To5Upgrade(
 *   id = "editor_file_upgrade",
 *   cke4_buttons = {
 *     "DrupalFile"
 *   },
 *   cke4_plugin_settings = {},
 *   cke5_plugin_elements_subset_configuration = {}
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class EditorFile extends PluginBase implements CKEditor4To5UpgradePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function mapCKEditor4ToolbarButtonToCKEditor5ToolbarItem(string $cke4_button, HTMLRestrictions $text_format_html_restrictions): ?array {
    if ($cke4_button === 'DrupalFile') {
      return ['drupalInsertFile'];
    }
    throw new \OutOfBoundsException();
  }

  /**
   * {@inheritdoc}
   */
  public function mapCKEditor4SettingsToCKEditor5Configuration(string $cke4_plugin_id, array $cke4_plugin_settings): ?array {
    throw new \OutOfBoundsException();
  }

  /**
   * {@inheritdoc}
   */
  public function computeCKEditor5PluginSubsetConfiguration(string $cke5_plugin_id, FilterFormatInterface $text_format): ?array {
    throw new \OutOfBoundsException();
  }

}
