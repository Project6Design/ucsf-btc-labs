<?php

namespace Drupal\url_embed\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\url_embed\UrlEmbedHelperTrait;

/**
 * Plugin implementation of the 'url_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "url_embed",
 *   label = @Translation("Embedded URL"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkEmbedFormatter extends FormatterBase {
  use UrlEmbedHelperTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'enable_responsive' => FALSE,
      'default_ratio' => '66.669',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['enable_responsive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable responsive wrappers'),
      '#description' => $this->t('Automatically wrap embedded iframes with a container which will allow the embedded media to scale appropriately to the size of the page.'),
      '#default_value' => $this->getSetting('enable_responsive'),
    ];
    $element['default_ratio'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default ratio (as a percent)'),
      '#description' => $this->t('Embeds normally derive their aspect ratio from information from the provider. In cases where this information is not present, provide a fallback. Example: 56.25'),
      '#default_value' => $this->getSetting('default_ratio'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [$this->t('Responsive: @responsive', ['@responsive' => $this->getSetting('enable_responsive') ? $this->t('enabled') : $this->t('disabled')])];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $default_settings = self::defaultSettings();
    $default_ratio = $this->getSetting('default_ratio');
    $element = [];
    foreach ($items as $delta => $item) {
      $ratio = !empty($default_ratio) ? $default_ratio : $default_settings['default_ratio'];
      if ($url = $item->getUrl()->toString()) {
        try {
          if (($info = $this->urlEmbed()->getEmbed($url)) && !empty($info->code->html)) {
            $url_output = $info->code->html;
            if (!empty($info->code->ratio)) {
              $ratio = $info->code->ratio;
            }
          }
          if (empty($url_output)) {
            throw new \Exception('Could not retrieve HTML content for ' . $url);
          }
          // Wrap the embed code in a container to make it responsive.
          if ($this->getSetting('enable_responsive') === TRUE) {
            $element[$delta] = [
              '#theme' => 'responsive_embed',
              '#ratio' => $ratio,
              '#url_output' => $url_output,
              '#attached' => [
                'library' => [
                  'url_embed/responsive_styles',
                ],
              ],
            ];
          }
          else {
            $element[$delta] = [
              '#type' => 'inline_template',
              '#template' => $url_output,
            ];
          }
        }
        catch (\Exception $e) {
          \Drupal::logger('url_embed')->error($e->getMessage());
        }
      }
    }

    return $element;
  }

}
