<?php

namespace Drupal\anchor_link\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;

/**
 * Defines the "anchor" plugin.
 *
 * @CKEditorPlugin(
 *   id = "anchor",
 *   label = @Translation("CKEditor Anchor link"),
 *   module = "anchor_link"
 * )
 */
class Anchor extends CKEditorPluginBase {

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
    return [
      'fakeobjects',
    ];
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
      'Anchor' => [
        'label' => $this->t('Anchor'),
        'image' => $libraryUrl . '/icons/anchor.png',
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
   * Get the CKEditor Anchor library path.
   */
  protected function getLibraryPath() {
    $module_path = \Drupal::service('module_handler')->getModule('anchor_link')->getPath();
    return $module_path . '/js/anchor';
  }

  /**
   * Get the CKEditor Anchor library URL.
   */
  protected function getLibraryUrl() {

    $originUrl = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getBaseUrl();
    $module_path = \Drupal::service('module_handler')->getModule('anchor_link')->getPath();

    return $originUrl . $module_path . '/js/anchor';
  }

}
