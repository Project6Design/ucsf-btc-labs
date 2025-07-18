<?php

declare(strict_types=1);

namespace Drupal\editor_file\Plugin\CKEditorPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "drupalfile" plugin.
 *
 * @CKEditorPlugin(
 *   id = "drupalfile",
 *   label = @Translation("File upload"),
 *   module = "ckeditor"
 * )
 *
 * @deprecated in editor_file:2.0.0 and is removed from editor_file:2.1.0. This
 *  code is only used for the now unsupported CKEditor4 plugin.
 * @see https://www.drupal.org/project/editor_file/issues/3415204
 */
class DrupalFile extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return \Drupal::service('extension.list.module')
      ->getPath('editor_file') . '/js/plugins/drupalfile/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'core/drupal.ajax',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [
      'drupalFile_dialogTitleAdd' => $this->t('Add File'),
      'drupalFile_dialogTitleEdit' => $this->t('Edit File'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    $path = \Drupal::service('extension.list.module')
      ->getPath('editor_file') . '/js/plugins/drupalfile';
    return [
      'DrupalFile' => [
        'label' => $this->t('File'),
        'image' => $path . '/file.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\editor\Form\EditorFileDialog
   * @see editor_file_upload_settings_form()
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $form_state->loadInclude('editor_file', 'admin.inc');
    $form['file_upload'] = editor_file_upload_settings_form($editor);
    $form['file_upload']['#attached']['library'][] = 'editor_file/drupal.ckeditor.drupalfile.admin';
    $form['file_upload']['#element_validate'][] = [
      $this,
      'validateFileUploadSettings',
    ];
    return $form;
  }

  /**
   * Validates the "file_upload" form element in settingsForm().
   *
   * Moves the text editor's file upload settings from the DrupalFile plugin's
   * own settings into $editor->file_upload.
   *
   * @see \Drupal\editor\Form\EditorFileDialog
   * @see editor_file_upload_settings_form()
   */
  public function validateFileUploadSettings(array $element, FormStateInterface $form_state) {
    $settings = &$form_state->getValue($element['#parents']);
    $editor = $form_state->get('editor');

    $keys = [
      'status',
      'scheme',
      'directory',
      'extensions',
      'max_size',
    ];
    foreach ($keys as $key) {
      if (array_key_exists($key, $settings)) {
        $editor->setThirdPartySetting('editor_file', $key, $settings[$key]);
      }
      else {
        $editor->unsetThirdPartySetting('editor_file', $key);
      }
    }

    $form_state->unsetValue(array_slice($element['#parents'], 0, -1));
  }

}
