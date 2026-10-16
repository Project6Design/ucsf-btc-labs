<?php

/**
 * @file
 * Post update functions for CKEditor Anchor Link.
 */

use Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface;
use Drupal\editor\Entity\Editor;

/**
 * Allow the "name" attribute on anchors in formats using the plugin.
 */
function anchor_link_post_update_allow_anchor_name_attribute() {
  _anchor_link_append_to_filter_html_settings('anchor_link_ckeditor5_anchor', '<a name>');
}

/**
 * Expands filter_html allowed tags for a plugin that supports more HTML.
 *
 * Carries its own name, so that it never collides with the identically
 * shaped helper Drupal 10 core ships in ckeditor5.post_update.php.
 *
 * @param string $cke5_plugin_id
 *   The CKEditor 5 plugin ID which supports more HTML after an update.
 * @param string $allowed_html_to_append
 *   The string to append to `filter_html`'s `allowed_html` setting.
 */
function _anchor_link_append_to_filter_html_settings(string $cke5_plugin_id, string $allowed_html_to_append) {
  // A site updating from 8.x-2.x may run its updates before CKEditor 5 is
  // installed. With no CKEditor 5 there is no editor using the plugin, so
  // there is nothing to append and the update is a no-op.
  if (!\Drupal::hasService('plugin.manager.ckeditor5.plugin') || !\Drupal::moduleHandler()->moduleExists('editor')) {
    return;
  }

  $cke5_plugin_manager = \Drupal::service('plugin.manager.ckeditor5.plugin');
  assert($cke5_plugin_manager instanceof CKEditor5PluginManagerInterface);

  // 1. Determine which text editors use the updated CKEditor 5 plugin.
  $affected_editors = [];
  foreach (Editor::loadMultiple() as $editor) {
    // Text editors not using CKEditor 5 cannot be affected.
    if ($editor->getEditor() !== 'ckeditor5') {
      continue;
    }
    // Ask the plugin manager which CKEditor 5 plugins are enabled; this works
    // for every plugin, no matter if they have toolbar items or not,
    // conditions or not, et cetera.
    $enabled_cke5_plugin_ids = array_keys($cke5_plugin_manager->getEnabledDefinitions($editor));
    if (in_array($cke5_plugin_id, $enabled_cke5_plugin_ids, TRUE)) {
      $affected_editors[] = $editor;
    }
  }

  // 2. Update the corresponding text formats' `filter_html` configuration, if
  // they are using that filter plugin.
  foreach ($affected_editors as $editor) {
    $format = $editor->getFilterFormat();
    // Text formats not using `filter_html` filter do not need to be updated.
    if (!$format->filters('filter_html')->status) {
      continue;
    }
    // Append to "Allowed HTML tags" setting.
    $filter_html_config = $format->filters('filter_html')->getConfiguration();
    $filter_html_config['settings']['allowed_html'] .= ' ' . trim($allowed_html_to_append);
    $format->setFilterConfig('filter_html', $filter_html_config);
    // Save updated text format.
    $format->save();
  }
}
