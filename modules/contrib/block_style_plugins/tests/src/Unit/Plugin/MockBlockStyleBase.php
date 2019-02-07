<?php

namespace Drupal\Tests\block_style_plugins\Unit\Plugin;

use Drupal\block_style_plugins\Plugin\BlockStyleBase;

/**
 * Class MockBlockStyleBase.
 *
 * This class is mostly empty because we need it to unit test base methods in
 * the Abstract BlockStyleBase class.
 *
 * @package Drupal\Tests\block_style_plugins\Unit\Plugin
 */
class MockBlockStyleBase extends BlockStyleBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'sample_class' => '',
      'sample_checkbox' => FALSE,
    ];
  }

}
