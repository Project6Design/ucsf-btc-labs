<?php

/**
 * @file
 * Photoswipe theme preprocess functions.
 */

use Drupal\Core\Language\Language;
use Drupal\image\Entity\ImageStyle;

/**
 * Prepares variables for a Photoswipe image field formatter.
 *
 * @param $variables
 *   An associative array containing:
 *   - item: An ImageItem object.
 *   - display_settings: optional image styles.
 *
 * @ingroup themeable
 */
function template_preprocess_photoswipe_image_formatter(&$variables) {
  $item = $variables['item'];
  $settings = $variables['display_settings'];
  $node = $item->getParent()->getParent()->getValue();
  $uri = $item->entity->getFileUri();
  $alt = !empty($item->alt) ? $item->alt : '';
  $title = !empty($item->title) ? $item->title : '';

  if (empty($alt) && !empty($item->field_file_image_alt_text[Language::LANGCODE_NOT_SPECIFIED])) {
    $alt = $item->field_file_image_alt_text[Language::LANGCODE_NOT_SPECIFIED][0]['value'];
  }
  if (empty($title) && !empty($item->field_file_image_title_text[Language::LANGCODE_NOT_SPECIFIED])) {
    $title = $item->field_file_image_title_text[Language::LANGCODE_NOT_SPECIFIED][0]['value'];
  }
  $image = array(
    '#theme' => 'image_style',
    '#uri' => $uri,
    '#alt' => $alt,
    '#title' => $title,
    '#attributes' => $item->_attributes,
    '#style_name' => $settings['photoswipe_node_style'],
  );

  $dimensions = array();
  if (isset($item->width) && isset($item->height)) {
    $image['#width'] = $dimensions['width'] = $item->width;
    $image['#height'] = $dimensions['height'] = $item->height;
  }

  // Create the path to the image that will show in Photoswipe
  if ($style_name = $settings['photoswipe_image_style']) {
    // Load the image style
    $style = ImageStyle::load($style_name);

    // Fetch the Image style path from the Image URI
    $path = $style->buildUrl($uri);

    // Set the dimensions
    $style->transformDimensions($dimensions, $uri);
  }
  else {
    $path = file_create_url($uri);
  }

  // Render as a standard image if an image style is not given
  if (empty($image['#style_name'])) {
    $image['#theme'] = 'image';
  }

  // Set Caption for this image
  if (isset($settings['photoswipe_caption'])) {
    $caption_setting = $settings['photoswipe_caption'];
    switch ($caption_setting) {
      case 'alt':
        $caption = $alt;
        break;
      case 'title':
        $caption = $title;
        break;
      case 'node_title':
        $caption = $node->title->value;
        break;
      default:
        $field_view = $node->{$caption_setting}->view();
        $field_view['#view_mode'] = ($settings['photoswipe_view_mode']) ? $settings['photoswipe_view_mode'] : 'default';
        $caption = render($field_view);
        break;
    }
  }
  else {
    $caption = $alt;
  }

  $variables['image'] = $image;
  $variables['path'] = $path;
  $variables['attributes'] = array(
    'class' => 'photoswipe',
    'data-size' => $dimensions['width'] . 'x' . $dimensions['height'],
    'data-overlay-title' => $caption,
  );
}
