<?php

namespace Drupal\roleassign\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure RoleAssign settings.
 */
class RoleAssignAdminForm extends ConfigFormBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'role_assign_admin';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['roleassign.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /******************************
     * Get all available roles except for:
     * - 'anonymous user'
     * - 'authenticated user'
     ******************************/
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    unset($roles[RoleInterface::ANONYMOUS_ID]);
    unset($roles[RoleInterface::AUTHENTICATED_ID]);
    $roles = array_map(fn(RoleInterface $role) => $role->label(), $roles);

    /******************************
     * Show checkboxes with roles
     * that can be delegated, if any
     ******************************/
    if ($roles) {
      $config = $this->config('roleassign.settings');

      /******************************
       * Roles
       ******************************/
      $form['roleassign_roles'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Roles'),
        '#default_value' => $config->get('roleassign_roles'),
        '#options' => $roles,
        '#description' => $this->t('Select roles that should be available for assignment.'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /******************************
     * Clean up roleassign_roles values
     * before saving to config
     ******************************/
    $roleassign_roles = $form_state->getValue('roleassign_roles');
    $roleassign_roles = array_keys(array_filter($roleassign_roles));
    sort($roleassign_roles);

    $this->config('roleassign.settings')
      ->set('roleassign_roles', $roleassign_roles)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
