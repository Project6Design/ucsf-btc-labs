<?php

namespace Drupal\Tests\block_style_plugins\Unit\Plugin;

use Drupal\block_style_plugins\Plugin\BlockStyleBase;

class MockBlockStyleBase extends BlockStyleBase {

  // This class is mostly empty because we need it to unit test base methods in
  // the Abstract BlockStyleBase class

  /**
   * {@inheritdoc}
   */
  public function defaultStyles() {
    return [
      'sample_class' => '',
      'sample_checkbox' => FALSE,
    ];
  }

}
