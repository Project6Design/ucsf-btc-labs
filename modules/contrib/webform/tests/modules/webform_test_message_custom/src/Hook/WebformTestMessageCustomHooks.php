<?php

namespace Drupal\webform_test_message_custom\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for webform_test_message_custom.
 */
class WebformTestMessageCustomHooks {

  /**
   * Implements hook_webform_message_custom().
   */
  #[Hook('webform_message_custom')]
  public function webformMessageCustom($operation, $id) {
    // Handle 'webform_test_message_custom' defined in
    // webform.webform.test_element_message.yml.
    if ($id === 'webform_test_message_custom') {
      switch ($operation) {
        case 'closed':
          return \Drupal::state()->get($id, FALSE);

        case 'close':
          \Drupal::state()->set($id, TRUE);
          return NULL;

        case 'reset':
          \Drupal::state()->delete($id);
          return NULL;
      }
    }
  }

}
