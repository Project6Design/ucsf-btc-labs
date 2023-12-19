<?php

namespace Drupal\anchor_link\Plugin\CKEditor4To5Upgrade;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor4To5UpgradePluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\filter\FilterFormatInterface;

/**
 * Provides the CKEditor 4 to 5 upgrade for the placeholder CKEditor plugin.
 *
 * @CKEditor4To5Upgrade(
 *   id = "anchor_link",
 *   cke4_buttons = {
 *     "Anchor",
 *   },
 *   cke4_plugin_settings = {
 *   }
 * )
 */
class Anchor extends PluginBase implements CKEditor4To5UpgradePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function mapCKEditor4ToolbarButtonToCKEditor5ToolbarItem(
    string $cke4_button,
    HTMLRestrictions $text_format_html_restrictions
  ): ?array {
    if ($cke4_button === 'Anchor') {
      return ['anchor'];
    }
    else {
      throw new \OutOfBoundsException();
    }
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
