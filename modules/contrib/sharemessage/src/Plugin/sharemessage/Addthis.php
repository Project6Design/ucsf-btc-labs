<?php

namespace Drupal\sharemessage\Plugin\sharemessage;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\sharemessage\SharePluginBase;
use Drupal\sharemessage\SharePluginInterface;

/**
 * AddThis plugin.
 *
 * @SharePlugin(
 *   id = "addthis",
 *   label = @Translation("AddThis"),
 *   description = @Translation("AddThis plugin for Share Message module."),
 * )
 */
class Addthis extends SharePluginBase implements  SharePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build($context, $plugin_attributes) {

    $attributes = new Attribute(['class' => [
      'addthis_toolbox',
      'addthis_default_style',
      $this->shareMessage->getSetting('icon_style') ?: \Drupal::config('sharemessage.addthis')->get('icon_style'),
    ]]);

    if ($plugin_attributes) {
      $attributes['addthis:url'] = $this->shareMessage->getUrl($context);
      $attributes['addthis:title'] = $this->shareMessage->getTokenizedField($this->shareMessage->title, $context);
      $attributes['addthis:description'] = $this->shareMessage->getTokenizedField($this->shareMessage->message_long, $context);
    }

    // Add AddThis buttons.
    $build = [
      '#theme' => 'sharemessage_addthis',
      '#attributes' => $attributes,
      '#services' => $this->shareMessage->getSetting('services') ?: \Drupal::config('sharemessage.addthis')->get('services'),
      '#additional_services' => $this->getSetting('additional_services') ?: \Drupal::config('sharemessage.addthis')->get('additional_services'),
      '#counter' => $this->getSetting('counter') ?: \Drupal::config('sharemessage.addthis')->get('counter'),
      '#twitter_template' => $this->shareMessage->getTokenizedField($this->shareMessage->message_short, $context),
      '#attached' => [
        'library' => ['sharemessage/addthis'],
        'drupalSettings' => [
          'addthis_config' => [
            'data_track_addressbar' => TRUE,
          ],
        ],
      ],
    ];
    $cacheability_metadata = CacheableMetadata::createFromObject(\Drupal::config('sharemessage.addthis'));
    $cacheability_metadata->applyTo($build);
    return $build;
  }

  /**
   * Gets the default AddThis settings.
   *
   * @param string $key
   *   The settings key.
   *
   * @return array
   */
  public function getSetting($key) {
    $override = $this->shareMessage->getSetting('override_default_settings');
    if (isset($override)) {
      return $this->shareMessage->getSetting($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    // Settings fieldset.
    $form['override_default_settings'] = [
      '#type' => 'checkbox',
      '#title' => t('Override default settings'),
      '#default_value' => $this->getSetting('override_default_settings'),
    ];

    $form['services'] = [
      '#title' => t('Visible services'),
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => sharemessage_get_addthis_services(),
      '#default_value' => $this->getSetting('services'),
      '#size' => 10,
      '#states' => [
        'invisible' => [
          ':input[name="settings[override_default_settings]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['additional_services'] = [
      '#type' => 'checkbox',
      '#title' => t('Show additional services button'),
      '#default_value' => $this->getSetting('additional_services'),
      '#states' => [
        'invisible' => [
          ':input[name="settings[override_default_settings]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['counter'] = [
      '#type' => 'select',
      '#title' => t('Show AddThis counter'),
      '#empty_option' => t('No'),
      '#options' => [
        'addthis_pill_style' => t('Pill style'),
        'addthis_bubble_style' => t('Bubble style'),
      ],
      '#default_value' => $this->getSetting('counter'),
      '#states' => [
        'invisible' => [
          ':input[name="settings[override_default_settings]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['icon_style'] = [
      '#title' => t('Default icon style'),
      '#type' => 'radios',
      '#options' => [
        'addthis_16x16_style' => '16x16 pix',
        'addthis_32x32_style' => '32x32 pix',
      ],
      '#default_value' => $this->getSetting('icon_style'),
      '#states' => [
        'invisible' => [
          ':input[name="settings[override_default_settings]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $form;
  }

}
