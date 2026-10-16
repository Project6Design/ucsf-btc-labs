<?php

namespace Drupal\url_embed\Hook;

use Drupal\Component\Utility\UrlHelper;
use Drupal\editor\Entity\Editor;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for url_embed.
 */
class UrlEmbedHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path) {
    return [
      'responsive_embed' => [
        'variables' => [
          'url_output' => NULL,
          'ratio' => NULL,
        ],
      ],
    ];
  }

  /**
   * Implements hook_ckeditor_css_alter().
   */
  #[Hook('ckeditor_css_alter')]
  public function ckeditorCssAlter(array &$css, Editor $editor) {
    $filter_format = $editor->getFilterFormat();
    if ($filter_format) {
      $filter = $filter_format->filters('url_embed');
      $config = $filter->getConfiguration();
      // Add the responsive embed css if the url_embed module has it enabled.
      if (!empty($config['settings']['enable_responsive'])) {
        $css[] = \Drupal::service('extension.list.module')->getPath('url_embed') . '/css/url_embed.responsive.css';
      }
    }
  }

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(&$libraries, $extension) {
    if ($extension === 'ckeditor5') {
      // Add paths to stylesheets specified by a modules's ckeditor5-stylesheets
      // config property.
      $module = 'url_embed';
      $module_path = \Drupal::service('extension.list.module')->getPath($module);
      $info = \Drupal::service('extension.list.module')->getExtensionInfo($module);
      if (isset($info['ckeditor5-stylesheets']) && $info['ckeditor5-stylesheets'] !== FALSE) {
        $css = $info['ckeditor5-stylesheets'];
        foreach ($css as $key => $url) {
          // CSS URL is external or relative to Drupal root.
          if (UrlHelper::isExternal($url) || $url[0] === '/') {
            $css[$key] = $url;
          }
          else {
            $css[$key] = '/' . $module_path . '/' . $url;
          }
        }
      }
      $existing = $libraries['internal.drupal.ckeditor5.stylesheets']['css']['theme'] ?? [];
      $url_embed = array_fill_keys(array_values($css), []);
      $merged = array_merge($existing, $url_embed);
      $libraries['internal.drupal.ckeditor5.stylesheets']['css']['theme'] = $merged;
    }
  }

}
