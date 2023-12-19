<?php

namespace Drupal\sharemessage\Plugin\sharemessage;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\sharemessage\SharePluginBase;
use Drupal\sharemessage\SharePluginInterface;
use Drupal\Core\Url;

/**
 * Sharrre plugin.
 *
 * @SharePlugin(
 *   id = "sharrre",
 *   label = @Translation("Sharrre"),
 *   description = @Translation("Sharrre is a jQuery plugin that allows you to create nice widgets sharing for Facebook, Twitter, Google Plus (with PHP script) and more. (http://sharrre.com)"),
 * )
 */
class Sharrre extends SharePluginBase implements  SharePluginInterface {

  /**
   * Check if the plugin can work.
   *
   * The library needs to be either external or we need the local library module with the library present.
   *
   * @param $show_message
   *   A flag that determines if the warning should be shown or not.
   *
   * @return mixed
   */
  public static function checkConfiguration($show_message = FALSE) {
    if (\Drupal::config('sharemessage.sharrre')->get('library_url') ) {
      // We have an external library URL configured. Fine.
      return NULL;
    }

    // Check for both, local library and remote URL.
    if (!\Drupal::moduleHandler()->moduleExists('libraries') && !\Drupal::config('sharemessage.sharrre')->get('library_url') && $show_message) {
      $form['message'] = [
        '#type' => 'container',
        '#markup' => t('Either set the library locally (in /libraries/sharrre) and enable the libraries module or enter the remote URL on <a href=":sharrre_settings">Sharrre settings page</a>.', [':sharrre_settings' => Url::fromRoute('sharemessage.sharrre.settings')->toString()]),
        '#attributes' => [
          'class' => ['messages messages--error'],
        ],
      ];
      return $form;
    }

    if (\Drupal::moduleHandler()->moduleExists('libraries')) {
      // Check if local library is set correctly.
      $directory = \Drupal::service('libraries.manager')->getLibrary('sharrre');
      $file = 'jquery.sharrre.min.js';
      if (!file_exists($directory . '/' . $file) && $show_message) {
        $form['message'] = [
          '#type' => 'container',
          '#markup' => t('The library file is not present in the expected directory (/libraries/sharrre).'),
          '#attributes' => [
            'class' => ['messages messages--error'],
          ],
        ];
        return $form;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build($context, $plugin_attributes) {

    $attributes = new Attribute(['id' => [
      'sharemessage',
    ]]);

    if ($plugin_attributes) {
      $attributes['sharrre:url'] = $this->shareMessage->getUrl($context);
      $attributes['sharrre:title'] = $this->shareMessage->getTokenizedField($this->shareMessage->title, $context);
      $attributes['sharrre:description'] = $this->shareMessage->getTokenizedField($this->shareMessage->message_long, $context);
    }

    // Add Sharrre buttons.
    $build = [
      '#theme' => 'sharemessage_sharrre',
      '#attributes' => $attributes,
      '#attached' => [
        'library' => ['sharemessage/sharrre'],
        'drupalSettings' => [
          'sharrre_config' => [
            'services' => $this->shareMessage->getSetting('services') ?: \Drupal::config('sharemessage.sharrre')->get('services'),
            'library_url' => $this->shareMessage->getSetting('library_url') ?: \Drupal::config('sharemessage.sharrre')->get('library_url'),
            'shorter_total' => $this->shareMessage->getSetting('shorter_total') ?: \Drupal::config('sharemessage.sharrre')->get('shorter_total'),
            'enable_hover' => $this->shareMessage->getSetting('enable_hover') ?: \Drupal::config('sharemessage.sharrre')->get('enable_hover'),
            'enable_counter' => $this->shareMessage->getSetting('enable_counter') ?: \Drupal::config('sharemessage.sharrre')->get('enable_counter'),
            'enable_tracking' => $this->shareMessage->getSetting('enable_tracking') ?: \Drupal::config('sharemessage.sharrre')->get('enable_tracking'),
            'url_curl' => Url::fromRoute('sharemessage.sharrre.counter')->toString(),
            'url' => $this->shareMessage->share_url,
          ],
        ],
      ],
    ];
    $cacheability_metadata = CacheableMetadata::createFromObject(\Drupal::config('sharemessage.sharrre'));
    $cacheability_metadata->applyTo($build);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  function getSetting($key) {
    $override = $this->shareMessage->getSetting('override_default_settings');
    if (isset($override)) {
      return $this->shareMessage->getSetting($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    // Check if the library configuration is valid.
    if ($check_form = static::checkConfiguration(TRUE)) {
      return $check_form;
    }

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
      '#options' => [
        'googlePlus' => $this->t('Google+'),
        'facebook' => $this->t('Facebook'),
        'twitter' => $this->t('Twitter'),
        'digg' => $this->t('Digg'),
        'delicious' => $this->t('Delicious'),
        'stumbleupon' => $this->t('StumpleUpon'),
        'linkedin' => $this->t('Linkedin'),
        'pinterest' => $this->t('Pinterest'),
      ],
      '#default_value' => $this->getSetting('services'),
      '#size' => 10,
      '#states' => [
        'invisible' => [
          ':input[name="settings[override_default_settings]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['sharrre_website_documentation'] = [
      '#type' => 'item',
      '#title' => t('See the <a href=":url">Sharrre documentation</a> page for more information.',[':url' => 'http://sharrre.com']),
      '#states' => [
        'invisible' => [
          ':input[name="settings[override_default_settings]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['shorter_total'] = [
      '#type' => 'checkbox',
      '#title' => t('Shorter total'),
      '#description' => t('Format number like 1.2k or 5M.'),
      '#default_value' => $this->getSetting('shorter_total'),
      '#states' => [
        'invisible' => [
          ':input[name="settings[override_default_settings]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['enable_counter'] = [
      '#type' => 'checkbox',
      '#title' => t('Counter'),
      '#description' => t('Enable the total counter.'),
      '#default_value' => $this->getSetting('enable_counter'),
      '#states' => [
        'invisible' => [
          ':input[name="settings[override_default_settings]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['enable_hover'] = [
      '#type' => 'checkbox',
      '#title' => t('Hover'),
      '#description' => t('Allow displaying the sharing buttons when hovering over the counter.'),
      '#default_value' => $this->getSetting('enable_hover'),
      '#states' => [
        'invisible' => [
          ':input[name="settings[override_default_settings]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['enable_tracking'] = [
      '#type' => 'checkbox',
      '#title' => t('Tracking'),
      '#description' => t('Allow tracking social interaction with Google Analytics.'),
      '#default_value' => $this->getSetting('enable_tracking'),
      '#states' => [
        'invisible' => [
          ':input[name="settings[override_default_settings]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $sharrre_config = \Drupal::config('sharemessage.sharrre');

    $library_exists = FALSE;
    // Check if the library module is enabled and if the library is present.
    if (\Drupal::moduleHandler()->moduleExists('libraries')) {
      $directory = \Drupal::service('libraries.manager')->getLibrary('sharrre');
      $file = 'jquery.sharrre.min.js';
      if (file_exists($directory . '/' . $file)) {
        $library_exists = TRUE;
      }
    }

    // General error if neither the library nor the URL from configuration exist.
    if (!$library_exists && !$sharrre_config->get('library_url')) {
      $form_state->setErrorByName('plugin', t('Either set the library locally (in /libraries/sharrre) and enable the libraries module or enter the remote URL on <a href=":sharrre_settings">Sharrre settings page</a>.', [':sharrre_settings' => Url::fromRoute('sharemessage.sharrre.settings')->toString()]));
    }

  }

}
