<?php

namespace Drupal\sharemessage\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'ShareMessage' block with the AddThis widgets.
 *
 * @Block(
 *   id = "sharemessage_block",
 *   admin_label = @Translation("Share message"),
 *   category = @Translation("Sharing"),
 * )
 */
class ShareMessageBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity storage controller for Share Messages.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storageController;

  /**
   * The entity view builder for Share Message.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * Constructs an ShareMessageBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->storageController = $entity_type_manager->getStorage('sharemessage');
    $this->viewBuilder = $entity_type_manager->getViewBuilder('sharemessage');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'sharemessage' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'view sharemessages');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $sharemessages = $this->storageController->loadMultiple();
    $options = [];
    foreach ($sharemessages as $sharemessage) {
      $options[$sharemessage->id()] = $sharemessage->label();
    }
    $form['sharemessage'] = [
      '#type' => 'select',
      '#title' => t('Select the sharemessage that should be displayed'),
      '#default_value' => $this->configuration['sharemessage'],
      '#options' => $options,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['sharemessage'] = $form_state->getValue('sharemessage');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Only display the block if there are items to show.
    if ($sharemessage = $this->storageController->load($this->configuration['sharemessage'])) {
      return $this->viewBuilder->view($sharemessage);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'url';
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [
      'config' => ['sharemessage.sharemessage.' . $this->configuration['sharemessage']],
    ];
  }

}
