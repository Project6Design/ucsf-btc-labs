<?php

namespace Drupal\Tests\block_style_plugins\Functional;

use Drupal\Tests\block_content\Functional\BlockContentTestBase;

/**
 * Test styles showing on content block types.
 *
 * Test the visibility of styles included or excluded from custom content block
 * types.
 *
 * @group block_style_plugins
 */
class CustomBlockVisibilityTest extends BlockContentTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block_style_plugins', 'block_style_plugins_test'];

  /**
   * {@inheritdoc}
   */
  protected $permissions = [
    'administer blocks',
    'access administration pages',
  ];

  /**
   * Test visibility of styles on custom block types.
   *
   * Test that the correct styles have been included or excluded from custom
   * block content types.
   */
  public function testVisibilityOfStylesOnCustomBlockTypes() {
    $assert = $this->assertSession();

    $this->drupalLogin($this->adminUser);

    // Create the custom block.
    $block = $this->createBlockContent('Custom Block Test');

    // Place the custom block.
    $this->drupalPlaceBlock('block_content:' . $block->uuid(), [
      'id' => 'customblocktest',
      'region' => 'content',
    ]);

    $this->drupalGet('admin/structure/block/block-content/types');
    $this->drupalGet('admin/structure/block/block-content');
    $this->drupalGet('block/' . $block->id());
    $assert->statusCodeEquals(200);

    // Go to the block instance configuration page.
    $this->drupalGet('admin/structure/block/manage/customblocktest');

    // Check that the simple class option is visible.
    $assert->responseContains('Add a custom class to this block');
    // Check that the dropdown style options are available.
    $assert->responseContains('Choose a style from the dropdown');
    // Check that the checkbox style options are NOT available.
    $assert->pageTextNotContains('Check this box');
  }

}
