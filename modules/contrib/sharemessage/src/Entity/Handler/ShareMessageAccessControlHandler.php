<?php

namespace Drupal\sharemessage\Entity\Handler;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the ShareMessage entity.
 *
 * @see \Drupal\contact\Entity\ShareMessage.
 */
class ShareMessageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'delete' || $operation == 'update') {
      return AccessResult::allowedIfHasPermission($account, 'administer sharemessages');
    }
    else {
      return AccessResult::allowed();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer sharemessages');
  }

}
