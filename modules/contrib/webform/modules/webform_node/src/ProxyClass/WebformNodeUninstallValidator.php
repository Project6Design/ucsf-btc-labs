<?php
// @codingStandardsIgnoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\webform_node\WebformNodeUninstallValidator' "modules/sandbox/webform/src".
 */

namespace Drupal\webform_node\ProxyClass {

  use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
  use Drupal\Core\DependencyInjection\DependencySerializationTrait;
  use Symfony\Component\DependencyInjection\ContainerInterface;
  use Drupal\Core\StringTranslation\TranslationInterface;

  /**
   * Provides a proxy class for \Drupal\webform_node\WebformNodeUninstallValidator.
   *
   * @see \Drupal\Component\ProxyBuilder
   */
  class WebformNodeUninstallValidator implements ModuleUninstallValidatorInterface
  {

    use DependencySerializationTrait;

    /**
     * The id of the original proxied service.
     *
     * @var string
     */
    protected $drupalProxyOriginalServiceId;

    /**
     * The real proxied service, after it was lazy loaded.
     *
     * @var \Drupal\webform_node\WebformNodeUninstallValidator
     */
    protected $service;

    /**
     * The service container.
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * Constructs a ProxyClass Drupal proxy object.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *   The container.
     * @param string $drupal_proxy_original_service_id
     *   The service ID of the original service.
     */
    public function __construct(ContainerInterface $container, $drupal_proxy_original_service_id)
    {
      $this->container = $container;
      $this->drupalProxyOriginalServiceId = $drupal_proxy_original_service_id;
    }

    /**
     * Lazy loads the real service from the container.
     *
     * @return object
     *   Returns the constructed real service.
     */
    protected function lazyLoadItself()
    {
      if (!isset($this->service)) {
        $this->service = $this->container->get($this->drupalProxyOriginalServiceId);
      }

      return $this->service;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($module)
    {
      return $this->lazyLoadItself()->validate($module);
    }

    /**
     * {@inheritdoc}
     */
    public function setStringTranslation(TranslationInterface $translation)
    {
      return $this->lazyLoadItself()->setStringTranslation($translation);
    }

  }

}
