<?php

namespace Drupal\photoswipe\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'photoswipe_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "photoswipe_field_formatter",
 *   label = @Translation("Photoswipe"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class PhotoswipeFieldFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'photoswipe_node_style' => '',
      'photoswipe_image_style' => '',
      'photoswipe_caption' => '',
      'photoswipe_view_mode' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    $image_styles_hide = $image_styles;
    $image_styles_hide['hide'] = $this->t('Hide (do not display image)');
    $element['photoswipe_node_style'] = array(
      '#title' => $this->t('Node image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('photoswipe_node_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles_hide,
      '#description' => $this->t('Image style to use in the node.'),
    );
    $element['photoswipe_image_style'] = array(
      '#title' => $this->t('Photoswipe image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('photoswipe_image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $this->t('Image style to use in the Photoswipe.'),
    );

    // Set our caption options
    $caption_options = array(
      'title' => t('Image Title Tag'),
      'alt' => t('Image Alt Tag'),
      'node_title' => t('Node Title'),
    );
    // Add the other node fields as options
    foreach ($form['#fields'] as $node_field) {
      if ($node_field != $this->fieldDefinition->getName()) {
        $caption_options[$node_field] = $node_field;
      }
    }

    $element['photoswipe_caption'] = array(
      '#title' => $this->t('Photoswipe image caption'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('photoswipe_caption'),
      '#options' => $caption_options,
      '#description' => $this->t('Field that should be used for the caption.'),
    );

    // Add the current view mode so we can control the view mode for node fields.
    $element['photoswipe_view_mode'] = array(
      '#type' => 'hidden',
      '#value' => $this->viewMode,
    );

    return $element + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    if (isset($image_styles[$this->getSetting('photoswipe_node_style')])) {
      $summary[] = $this->t('Node image style: @style', array('@style' => $image_styles[$this->getSetting('photoswipe_node_style')]));
    }
    else if ($this->getSetting('photoswipe_node_style') == 'hide') {
      $summary[] = $this->t('Node image style: Hide');
    }
    else {
      $summary[] = $this->t('Node image style: Original image');
    }

    if (isset($image_styles[$this->getSetting('photoswipe_image_style')])) {
      $summary[] = $this->t('Photoswipe image style: @style', array('@style' => $image_styles[$this->getSetting('photoswipe_image_style')]));
    }
    else {
      $summary[] = $this->t('photoswipe image style: Original image');
    }

    if ($this->getSetting('photoswipe_caption')) {
      $caption_options = array(
        'alt' => t('Image Alt Tag'),
        'title' => t('Image Title Tag'),
        'node_title' => t('Node Title'),
      );
      if (array_key_exists($this->getSetting('photoswipe_caption'), $caption_options)) {
        $caption_setting = $caption_options[$this->getSetting('photoswipe_caption')];
      }
      else {
        $caption_setting = $this->getSetting('photoswipe_caption');
      }
      $summary[] = $this->t('Photoswipe Caption: @field', array('@field' => $caption_setting));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $settings = $this->getSettings();

    if (!empty($items)) {
      $elements = array(
        '#attributes' => array('class' => array('photoswipe-gallery')),
      );
      \Drupal::service('photoswipe.assets_manager')->attach($elements);
    }

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#theme' => 'photoswipe_image_formatter',
        '#item' => $item,
        '#display_settings' => $settings,
      );
    }

    return $elements;
  }

}
