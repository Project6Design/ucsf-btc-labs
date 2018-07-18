<?php

namespace Drupal\photoswipe;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Photoswipe asset manager.
 */
class PhotoswipeAssetsManager implements PhotoswipeAssetsManagerInterface {

  /**
   * Whether the assets were attached somewhere in this request or not.
   *
   * @var bool
   */
  protected $attached;

  /**
   * Photoswipe config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Creates a \Drupal\photoswipe\PhotoswipeAssetsManager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(ConfigFactoryInterface $config, RendererInterface $renderer) {
    $this->config = $config->get('photoswipe.settings');
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function attach(array &$element) {
    // We only need to load only once per pace.
    if (!$this->attached) {
      // Add the library of Photoswipe assets.
      $element['#attached']['library'][] = 'photoswipe/photoswipe';
      // Load initialization file.
      $element['#attached']['library'][] = 'photoswipe/photoswipe.init';

      // Add photoswipe js settings.
      $element['#attached']['drupalSettings']['photoswipe']['options'] = $this->config->get('options');

      // Add photoswipe container with class="pswp".
      $template = ["#theme" => 'photoswipe_container'];
      $element['#attached']['drupalSettings']['photoswipe']['container'] = $this->renderer->renderPlain($template);

      $this->attached = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isAttached() {
    return $this->attached;
  }

}
