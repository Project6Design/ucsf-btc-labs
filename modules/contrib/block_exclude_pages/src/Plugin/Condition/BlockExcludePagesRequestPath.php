<?php

namespace Drupal\block_exclude_pages\Plugin\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Plugin\Condition\RequestPath;

/**
 * {@inheritdoc}
 */
class BlockExcludePagesRequestPath extends RequestPath {
  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $paths = array_map('trim', explode("\n", $form_state->getValue('pages')));
    foreach ($paths as $path) {
      // Added extra condition to if statement to allow for '!/' to be used.
      if (empty($path) || $path === '<front>' || $path === '!<front>' || str_starts_with($path, '/') || str_starts_with($path, '!/') ) {
        continue;
      }
      $form_state->setErrorByName('pages', $this->t("The path %path requires a leading forward slash or exclamation point when used with the Pages setting.", ['%path' => $path]));
    }
  }
}
