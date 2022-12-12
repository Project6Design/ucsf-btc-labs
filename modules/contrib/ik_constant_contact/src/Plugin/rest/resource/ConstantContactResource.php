<?php

namespace Drupal\ik_constant_contact\Plugin\rest\resource;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\ik_constant_contact\Service\ConstantContact;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a Constant Contact API Resource.
 *
 * @RestResource(
 *   id = "ik_constant_contact_resource",
 *   label = @Translation("IK Constant Contact Resource"),
 *   uri_paths = {
 *     "create" = "/constant_contact/{list_id}"
 *   }
 * )
 */
class ConstantContactResource extends ResourceBase {

  /**
   * Drupal\Core\Path\CurrentPathStack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\ik_constant_contact\Service\ConstantContact.
   *
   * @var \Drupal\ik_constant_contact\Service\ConstantContact
   *   Constant contact service.
   */
  protected $constantContact;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, CurrentPathStack $current_path, ConfigFactoryInterface $config_factory, ConstantContact $constantContact) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentPath = $current_path;
    $this->configFactory = $config_factory;
    $this->constantContact = $constantContact;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('ik_constant_contact'),
      $container->get('path.current'),
      $container->get('config.factory'),
      $container->get('ik_constant_contact')
    );
  }

  /**
   * Responds to entity POST requests.
   *
   * Takes the post request and sends it
   * to Constant Contact API endpoints.
   * @param string $list_id
   *   CC list (list_id). Can be an array of list uuids.
   *
   * @param array $data
   *   Form data.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws HttpException in case of error.
   */
  public function post($list_id, array $data) {
    $enabledLists = $this->configFactory->get('ik_constant_contact.enabled_lists')->getRawData();
    $lists = [];

    if (is_array($list_id)) {
      foreach ($list_id as $lid) {
        if (!isset($enabledLists[$lid]) || $enabledLists[$lid] !== 1) {
          throw new AccessDeniedHttpException('This list is not enabled or does not exist.');
        } else {
          $lists[] = $lid;
        }
      }
    } else if (is_string($list_id)) {
      if (!isset($enabledLists[$list_id]) || $enabledLists[$list_id] !== 1) {
        throw new AccessDeniedHttpException('This endpoint is not enabled or does not exist.');
      } else {
        $lists[] = $list_id;
      }
    }

    $response = $this->constantContact->submitContactForm($data, [$list_id]);
    return new ModifiedResourceResponse($response);
  }
}
