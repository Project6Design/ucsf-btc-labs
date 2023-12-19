<?php

namespace Drupal\roleassign;

use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Prevents uninstallation of roleassign module by restricted users.
 */
class RoleAssignUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * Gets the current active user.
   *
   * @var Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountProxy $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];
    if ($this->isCli()) {
      return $reasons;
    }

    switch ($module) {
      case 'roleassign':
        if (!$this->currentUser->hasPermission('administer roles')) {
          $reasons[] = $this->t('You are not allowed to disable this module.');
        }
        break;
    }

    return $reasons;
  }

  /**
   * Indicates whether this is a CLI request.
   *
   * @return bool
   *   TRUE for a cli request, or FALSE.
   */
  public function isCli() {
    return PHP_SAPI === 'cli';
  }

}
