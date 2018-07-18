<?php

namespace Drupal\ckwordcount\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "wordcount" plugin.
 *
 * @CKEditorPlugin(
 *   id = "wordcount",
 *   label = @Translation("Word Count & Character Count")
 * )
 */
class Wordcount extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface, CKEditorPluginContextualInterface {
  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return ['notification'];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return 'libraries/wordcount/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    $settings = $editor->getSettings();
    return isset($settings['plugins']['wordcount']) ? $settings['plugins']['wordcount']['enable'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $settings = $editor->getSettings();

    return [
      'wordcount' => [
        'showParagraphs' => !empty($settings['plugins']['wordcount']['show_paragraphs']) ? $settings['plugins']['wordcount']['show_paragraphs'] : false,
        'showWordCount' => !empty($settings['plugins']['wordcount']['show_word_count']) ? $settings['plugins']['wordcount']['show_word_count'] : false,
        'showCharCount' => !empty($settings['plugins']['wordcount']['show_char_count']) ? $settings['plugins']['wordcount']['show_char_count'] : false,
        'countSpacesAsChars' => !empty($settings['plugins']['wordcount']['count_spaces']) ? $settings['plugins']['wordcount']['count_spaces'] : false,
        'countHTML' => !empty($settings['plugins']['wordcount']['count_html']) ? $settings['plugins']['wordcount']['count_html'] : false,
        'maxWordCount' => !empty($settings['plugins']['wordcount']['max_words']) ? $settings['plugins']['wordcount']['max_words'] : -1,
        'maxCharCount' => !empty($settings['plugins']['wordcount']['max_chars']) ? $settings['plugins']['wordcount']['max_chars'] : -1
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();

    $form['enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the counter'),
      '#default_value' => !empty($settings['plugins']['wordcount']['enable']) ? $settings['plugins']['wordcount']['enable'] : false,
    );

    $form['show_paragraphs'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show the paragraphs count'),
      '#default_value' => !empty($settings['plugins']['wordcount']['show_paragraphs']) ? $settings['plugins']['wordcount']['show_paragraphs'] : false,
    );


    $form['show_word_count'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show the word count'),
      '#default_value' => !empty($settings['plugins']['wordcount']['show_word_count']) ? $settings['plugins']['wordcount']['show_word_count'] : false,
    );

    $form['show_char_count'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show the character count'),
      '#default_value' => !empty($settings['plugins']['wordcount']['show_char_count']) ? $settings['plugins']['wordcount']['show_char_count'] : false,
    );

    $form['count_spaces'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Count spaces as characters'),
      '#default_value' => !empty($settings['plugins']['wordcount']['count_spaces']) ? $settings['plugins']['wordcount']['count_spaces'] : false,
    );

    $form['count_html'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Count HTML as characters'),
      '#default_value' => !empty($settings['plugins']['wordcount']['count_html']) ? $settings['plugins']['wordcount']['count_html'] : false,
    );

    $form['max_words'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Maximum word limit'),
      '#description' => $this->t('Enter a maximum word limit. Leave this set to -1 for unlimited.'),
      '#default_value' => !empty($settings['plugins']['wordcount']['max_words']) ? $settings['plugins']['wordcount']['max_words'] : -1,
    );

    $form['max_chars'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Maximum character limit'),
      '#description' => $this->t('Enter a maximum character limit. Leave this set to -1 for unlimited.'),
      '#default_value' => !empty($settings['plugins']['wordcount']['max_chars']) ? $settings['plugins']['wordcount']['max_chars'] : -1,
    );

    $form['max_words']['#element_validate'][] = [$this, 'isNumeric'];
    $form['max_chars']['#element_validate'][] = [$this, 'isNumeric'];

    return $form;
  }

  /**
   * @param array $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function isNumeric(array $element, FormStateInterface $form_state) {
    if (!is_numeric($element['#value'])) {
      $form_state->setError($element, 'Value must be a number.');
    }
  }
}