<?php

namespace Drupal\url_embed;

use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Wrapper methods for URL embedding.
 *
 * This utility trait should only be used in application-level code, such as
 * classes that would implement ContainerInjectionInterface. Services registered
 * in the Container should not use this trait but inject the appropriate service
 * directly for easier testing.
 */
trait UrlEmbedHelperTrait {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The URL embed service.
   *
   * @var \Drupal\url_embed\UrlEmbedService
   */
  protected $urlEmbed;

  /**
   * Returns the module handler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  protected function moduleHandler() {
    if (!isset($this->moduleHandler)) {
      $this->moduleHandler = \Drupal::moduleHandler();
    }
    return $this->moduleHandler;
  }

  /**
   * Sets the module handler service.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   *
   * @return self
   *   The current object.
   */
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    return $this;
  }

  /**
   * Returns the URL embed service.
   *
   * @return \Drupal\url_embed\UrlEmbedInterface
   *   The URL embed service.
   */
  protected function urlEmbed() {
    if (!isset($this->urlEmbed)) {
      $this->urlEmbed = \Drupal::service('url_embed');
    }
    return $this->urlEmbed;
  }

  /**
   * Sets the URL embed service.
   *
   * @param \Drupal\url_embed\UrlEmbedInterface $url_embed
   *   The URL embed service.
   *
   * @return self
   *   The current object.
   */
  public function setUrlEmbed(UrlEmbedInterface $url_embed) {
    $this->urlEmbed = $url_embed;
    return $this;
  }

}
