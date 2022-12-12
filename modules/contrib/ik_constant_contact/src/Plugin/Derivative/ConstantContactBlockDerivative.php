<?php

namespace Drupal\ik_constant_contact\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\ik_constant_contact\Service\ConstantContact;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for IK Constant Contact blocks.
 *
 * @see \Drupal\ik_constant_contact\Plugin\Block\ConstantContactBlock
 */
class ConstantContactBlockDerivative extends DeriverBase implements ContainerDeriverInterface {

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
   * ConstantContactBlockDerivative constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   ConfigFactoryInterface.
   * @param \Drupal\ik_constant_contact\Service\ConstantContact $constantContact
   *   Constant contact service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ConstantContact $constantContact) {
    $this->configFactory = $configFactory;
    $this->constantContact = $constantContact;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('config.factory'),
      $container->get('ik_constant_contact')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $lists = $this->constantContact->getContactLists();
    $enabledLists = $this->configFactory->get('ik_constant_contact.enabled_lists')
      ->getRawData();

    if ($lists && $enabledLists && count($lists) > 0 && count($enabledLists) > 0) {
      foreach ($enabledLists as $id => $value) {
        if ($value === 1) {
          $this->derivatives[$id] = $base_plugin_definition;
          $this->derivatives[$id]['admin_label'] = $lists[$id]->name . '  Signup Block';
        }
      }
    }

    return $this->derivatives;
  }

}
