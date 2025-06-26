<?php

namespace Drupal\anchor_link\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;

/**
 * Defines the "link" plugin.
 *
 * @CKEditorPlugin(
 *   id = "link",
 *   label = @Translation("CKEditor Web link"),
 *   module = "anchor_link"
 * )
 */
class Link extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->getLibraryPath() . '/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    $libraryUrl = $this->getLibraryUrl();

    return [
      'Link' => [
        'label' => $this->t('Link'),
        'image' => $libraryUrl . '/icons/link.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

    /**
   * Get the CKEditor Link library path.
   */
  protected function getLibraryPath() {
    $module_path = \Drupal::service('module_handler')->getModule('anchor_link')->getPath();
    return $module_path . '/js/link';
  }

  /**
   * Get the CKEditor Link library URL.
   */
  protected function getLibraryUrl() {

    $originUrl = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();
    $module_path = \Drupal::service('module_handler')->getModule('anchor_link')->getPath();

    return $originUrl . $module_path . '/js/link';
  }

}
