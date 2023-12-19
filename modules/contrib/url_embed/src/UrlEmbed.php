<?php

namespace Drupal\url_embed;

use Drupal\Core\Config\ConfigFactoryInterface;
use Embed\Embed;
use Embed\Extractor;

/**
 * A service class for handling URL embeds.
 */
class UrlEmbed implements UrlEmbedInterface {

  /**
   * @var array
   */
  public $config;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a UrlEmbed object.
   *
   * @param array $config
   *   (optional) The ptions passed to the adapter.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   (optional) The config factory.
   */
  public function __construct(array $config = [], ConfigFactoryInterface $config_factory = NULL) {
    $global_config = $config_factory ? $config_factory->get('url_embed.settings') : NULL;
    $defaults = [];

    if ($global_config && $global_config->get('facebook_app_id') && $global_config->get('facebook_app_secret')) {
      $defaults['facebook:token'] = $global_config->get('facebook_app_id') . '|' . $global_config->get('facebook_app_secret');
      $defaults['instagram:token'] = $global_config->get('facebook_app_id') . '|' . $global_config->get('facebook_app_secret');
    }
    $this->config = array_replace_recursive($defaults, $config);
  }

  /**
   * @{inheritdoc}
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * @{inheritdoc}
   */
  public function setConfig(array $config) {
    $this->config = $config;
  }

  /**
   * @{inheritdoc}
   */
  public function getEmbed(string $url, array $config = []): Extractor {
    $embed = new Embed();
    $embed->setSettings(array_replace_recursive($this->config, $config));
    return $embed->get($url);
  }

}
