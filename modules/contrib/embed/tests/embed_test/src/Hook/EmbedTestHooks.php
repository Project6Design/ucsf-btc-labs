<?php

declare(strict_types=1);

namespace Drupal\embed_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for embed_test.
 */
class EmbedTestHooks {

  /**
   * Implements hook_ENTITY_TYPE_view_alter() for node.
   */
  #[Hook('node_view_alter')]
  public function nodeViewAlter(array &$build): void {
    // @see \Drupal\Tests\embed\Functional\EmbedPreviewTest::testPreview
    $build['#attached']['library'][] = 'core/jquery';
  }

}
