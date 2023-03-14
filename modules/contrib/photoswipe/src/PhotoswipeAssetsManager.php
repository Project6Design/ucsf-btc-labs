<?php

namespace Drupal\photoswipe;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Theme\ThemeManager;

/**
 * Photoswipe asset manager.
 */
class PhotoswipeAssetsManager implements PhotoswipeAssetsManagerInterface {

  /**
   * The minimum PhotoSwipe version we support.
   *
   * @var string
   */
  public $photoswipeMinPluginVersion = '4.0.0';

  /**
   * The maximum PhotoSwipe version we support.
   *
   * @var string
   */
  public $photoswipeMaxPluginVersion = '4.1.3';

  /**
   * Whether the assets were attached somewhere in this request or not.
   *
   * @var bool
   */
  protected $attached = FALSE;

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
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManager
   */
  protected $themeManager;

  /**
   * Creates a \Drupal\photoswipe\PhotoswipeAssetsManager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Theme\ThemeManager $themeManager
   *   The theme manager.
   */
  public function __construct(ConfigFactoryInterface $config, RendererInterface $renderer, ModuleHandlerInterface $module_handler, ThemeManager $themeManager) {
    $this->config = $config->get('photoswipe.settings');
    $this->renderer = $renderer;
    $this->moduleHandler = $module_handler;
    $this->themeManager = $themeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function attach(array &$element) {
    // @todo IMPORTANT: THIS DOES NOT WORK! DO NOT ENABLE!
    // @see https://www.drupal.org/project/photoswipe/issues/3272485
    // @see https://git.drupalcode.org/project/photoswipe/-/merge_requests/39/diffs
    // which is BROKEN.
    // Help to find out why this doesn't work and if we can / should
    // make this working or remove the singleton!
    // Ensure this is only attached once, even if called multiple times.
    // if ($this->isAttached()) {
    // return;
    // }.
    // Add the library of Photoswipe library and init:
    $element['#attached']['library'][] = 'photoswipe/photoswipe.init';

    // Add photoswipe js settings.
    $options = $this->config->get('options');
    // Allow other modules to alter / extend the options to pass to photoswipe
    // JavaScript.
    $this->moduleHandler->alter('photoswipe_js_options', $options);
    $this->themeManager->alter('photoswipe_js_options', $options);
    $element['#attached']['drupalSettings']['photoswipe']['options'] = $options;

    // Add photoswipe container with class="pswp".
    $template = ["#theme" => 'photoswipe_container'];
    $element['#attached']['drupalSettings']['photoswipe']['container'] = $this->renderer->renderPlain($template);
    $this->attached = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isAttached() {
    return !empty($this->attached);
  }

}
