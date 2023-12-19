<?php

namespace Drupal\sharemessage\Plugin\sharemessage;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\Attribute;
use Drupal\sharemessage\SharePluginBase;
use Drupal\sharemessage\SharePluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * SocialSharePrivacy plugin.
 *
 * @SharePlugin(
 *   id = "socialshareprivacy",
 *   label = @Translation("Social Share Privacy"),
 *   description = @Translation("Social Share Privacy is a jQuery plugin that lets you add social share buttons to your website that don't allow the social sites to track your users."),
 * )
 */
class SocialSharePrivacy extends SharePluginBase implements SharePluginInterface, ContainerFactoryPluginInterface {

  /**
   * Social Share Privacy config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $socialSharePrivacyConfig;

  /**
   * Constructs Social Share Privacy plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   An immutable configuration object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ImmutableConfig $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->socialSharePrivacyConfig = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')->get('sharemessage.socialshareprivacy')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build($context, $plugin_attributes) {
    $attributes = new Attribute(['class' => ['socialshareprivacy']]);

    if ($plugin_attributes) {
      $attributes['socialshareprivacy:url'] = $this->shareMessage->getUrl($context);
      $attributes['socialshareprivacy:title'] = $this->shareMessage->getTokenizedField($this->shareMessage->title, $context);
      $attributes['socialshareprivacy:description'] = $this->shareMessage->getTokenizedField($this->shareMessage->message_long, $context);
    }

    // Add Social Share Privacy buttons.
    $build = [
      '#theme' => 'sharemessage_socialshareprivacy',
      '#attributes' => $attributes,
      '#attached' => [
        'library' => ['sharemessage/socialshareprivacy'],
        'drupalSettings' => [
          'socialshareprivacy_config' => [
            'services' => $this->services(),
            'url' => $this->shareMessage->share_url,
          ],
        ],
      ],
    ];
    $cacheability_metadata = CacheableMetadata::createFromObject($this->socialSharePrivacyConfig);
    $cacheability_metadata->applyTo($build);
    return $build;
  }

  /**
   * {@inheritdoc}
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
      '#title' => t('Default visible services'),
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => static::allServices(),
      '#default_value' => $this->getSetting('services'),
      '#size' => 11,
      '#states' => [
        'visible' => [
          ':input[name="settings[override_default_settings]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['facebook_action'] = [
      '#title' => $this->t('Choose facebook action'),
      '#type' => 'radios',
      '#default_value' => $this->getSetting('facebook_action') ?: 'like',
      '#options' => ['like' => $this->t('Like'), 'recommend' => $this->t('Recommend')],
      '#states' => [
        'visible' => [
          ':input[name="settings[override_default_settings]"]' => ['checked' => TRUE],
          // @todo Uncomment after https://www.drupal.org/node/1149078 lands.
          // 'select[name="settings[services][]"]' => ['value' => ['facebook']],
        ],
      ],
    ];
    $form['disqus_shortname'] = [
      '#title' => $this->t('Disqus shortname'),
      '#type' => 'textfield',
      '#description' => $this->t('You can get shortname from <a href=":url">Disqus</a>.', [
        ':url' => 'https://disqus.com/',
      ]),
      '#default_value' => $this->getSetting('disqus_shortname'),
      '#states' => [
        'visible' => [
          ':input[name="settings[override_default_settings]"]' => ['checked' => TRUE],
          // @todo Uncomment after https://www.drupal.org/node/1149078 lands.
          // 'select[name="settings[services][]"]' => ['value' => ['disqus']],
        ],
      ],
    ];
    $form['flattr_uid'] = [
      '#title' => $this->t('Flattr user id'),
      '#type' => 'textfield',
      '#description' => $this->t('You can get user id from <a href=":url">Flattr</a>.', [
        ':url' => 'https://flattr.com/',
      ]),
      '#default_value' => $this->getSetting('flattr_uid'),
      '#states' => [
        'visible' => [
          ':input[name="settings[override_default_settings]"]' => ['checked' => TRUE],
          // @todo Uncomment after https://www.drupal.org/node/1149078 lands.
          // 'select[name="settings[services][]"]' => ['value' => ['flattr']],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Gets all Social Share Privacy services.
   *
   * @return array
   *   All supported Social Share Privacy services.
   */
  public static function allServices() {
    return [
      'buffer' => t('Buffer'),
      'delicious' => t('Delicious'),
      'disqus' => t('Disqus'),
      'facebook' => t('Facebook Like/Recommend'),
      'fbshare' => t('Facebook Share'),
      'flattr' => t('Flattr'),
      'gplus' => t('Google+'),
      'hackernews' => t('Hacker News'),
      'linkedin' => t('LinkedIn'),
      'mail' => t('Mail'),
      'pinterest' => t('Pinterest'),
      'reddit' => t('Reddit'),
      'stumbleupon' => t('Stumble Upon'),
      'tumblr' => t('Tumblr'),
      'twitter' => t('Twitter'),
      'xing' => t('XING'),
    ];
  }

  /**
   * Social Share Privacy services with settings.
   *
   * @return array
   *   Social Share Privacy services with settings.
   */
  public function servicesWithSettings() {
    return [
      'facebook' => [
        'action' => $this->shareMessage->getSetting('facebook_action') ?: $this->socialSharePrivacyConfig->get('facebook_action'),
      ],
      'flattr' => [
        'uid' => $this->shareMessage->getSetting('flattr_uid') ?: $this->socialSharePrivacyConfig->get('flattr_uid'),
      ],
      'disqus' => [
        'shortname' => $this->shareMessage->getSetting('disqus_shortname') ?: $this->socialSharePrivacyConfig->get('disqus_shortname'),
      ],
    ];
  }

  /**
   * Prepare services for drupalSettings.
   *
   * @return array
   *   Prepared services array.
   */
  public function services() {
    $all_services = [];
    foreach (array_keys(static::allServices()) as $service) {
      $all_services[$service]['status'] = FALSE;
    };
    $enabled_services = [];

    $library_discovery = \Drupal::service('library.discovery');
    $library = $library_discovery->getLibraryByName('sharemessage', 'socialshareprivacy');
    if (!empty($library['library path'])) {
      $images_folder = $library['library path'] . '/images/';
    }
    else {
      $images_folder = 'libraries/socialshareprivacy/images/';
    }
    $services = $this->shareMessage->getSetting('services') ?: $this->socialSharePrivacyConfig->get('services');
    foreach ($services as $service) {
      $enabled_services[$service]['status'] = TRUE;
      if (in_array($service, array_keys($this->servicesWithSettings()))) {
        $enabled_services[$service] = array_merge($enabled_services[$service], $this->servicesWithSettings()[$service]);
      }
      if (in_array($service, ['mail', 'tumblr', 'fbshare'])) {
        $enabled_services[$service]['line_img'] = \Drupal::service('file_url_generator')->generateAbsoluteString($images_folder . $service . '.png');
      }
      else {
        $enabled_services[$service]['dummy_line_img'] = \Drupal::service('file_url_generator')->generateAbsoluteString($images_folder . 'dummy_' . $service . '.png');
      }
    }

    return array_replace($all_services, $enabled_services);
  }

}
