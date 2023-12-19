<?php

namespace Drupal\roleassign\Plugin\views\field;

use Drupal\user\Plugin\views\field\UserBulkForm;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Defines a user operations bulk form element with RoleAssign logic applied.
 *
 * @ViewsField("roleassign_user_bulk_form")
 */
class RoleAssignUserBulkForm extends UserBulkForm {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if (_roleassign_restrict_access()) {
      /******************************
       * Remove actions that are not allowed
       * based on the RoleAssign settings
       ******************************/
      $assignable_roles = _roleassign_get_assignable_roles();
      $denied_actions = [
        'user_add_role_action',
        'user_remove_role_action',
      ];

      foreach ($this->actions as $action_key => $action) {
        if (in_array($action->get('plugin'), $denied_actions)) {
          $config = $action->get('configuration');

          if (!in_array($config['rid'], $assignable_roles)) {
            unset($this->actions[$action_key]);
          }
        }
      }
    }
  }

}
