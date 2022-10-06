<?php

namespace Drupal\anchor_link\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;

/**
 * Defines the "unlink" plugin.
 *
 * @CKEditorPlugin(
 *   id = "unlink",
 *   label = @Translation("CKEditor Unlink"),
 *   module = "anchor_link"
 * )
 */
class Unlink extends CKEditorPluginBase {

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
      'Unlink' => [
        'label' => $this->t('Unlink'),
        'image' => $libraryUrl . '/icons/unlink.png',
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
   * Get the CKEditor Unlink library path.
   */
  protected function getLibraryPath() {
    $module_path = \Drupal::service('module_handler')->getModule('anchor_link')->getPath();
    return $module_path . '/js/unlink';
  }

  /**
   * Get the CKEditor Unlink library URL.
   */
  protected function getLibraryUrl() {

    $originUrl = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();
    $module_path = \Drupal::service('module_handler')->getModule('anchor_link')->getPath();

    return $originUrl . $module_path . '/js/unlink';
  }

}
