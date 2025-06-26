<?php

namespace Drupal\url_embed;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Embed\Embed;
use Embed\Extractor;

/**
 * A service class for handling URL embeds.
 */
class UrlEmbed implements UrlEmbedInterface {

  /**
   * The object config.
   *
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
   * Drupal module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a UrlEmbed object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param array $config
   *   (optional) The ptions passed to the adapter.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   (optional) The config factory.
   */
  public function __construct(ModuleHandlerInterface $module_handler, array $config = [], ConfigFactoryInterface|null $config_factory = NULL) {
    $global_config = $config_factory ? $config_factory->get('url_embed.settings') : NULL;
    $defaults = [];

    if ($global_config && $global_config->get('facebook_app_id') && $global_config->get('facebook_app_secret')) {
      $defaults['facebook:token'] = $global_config->get('facebook_app_id') . '|' . $global_config->get('facebook_app_secret');
      $defaults['instagram:token'] = $global_config->get('facebook_app_id') . '|' . $global_config->get('facebook_app_secret');
    }
    $this->config = array_replace_recursive($defaults, $config);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfig(array $config) {
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmbed(string $url, array $config = []): Extractor {
    $this->moduleHandler->invokeAll('url_embed_options_alter', [&$url, &$config]);

    $embed = new Embed();
    $embed->setSettings(array_replace_recursive($this->config, $config));
    return $embed->get($url);
  }

}
