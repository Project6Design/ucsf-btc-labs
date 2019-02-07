<?php

namespace Drupal\Tests\block_style_plugins\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the CheckboxWithExclude plugin.
 *
 * Test a checkbox style selector for all blocks except the "Powered by Drupal"
 * block and any "basic" custom block types.
 *
 * @group block_style_plugins
 */
class CheckboxWithExcludeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block_style_plugins', 'block_style_plugins_test'];

  /**
   * A user that can edit content types.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer blocks',
      'access administration pages',
    ]);
    $this->drupalLogin($this->adminUser);

    // Place the "Powered By Drupal" block.
    $this->drupalPlaceBlock('system_powered_by_block', [
      'id' => 'poweredbytest',
      'region' => 'content',
    ]);
    // Place the "Breadcrumb" block.
    $this->drupalPlaceBlock('system_breadcrumb_block', [
      'id' => 'breadcrumbtest',
      'region' => 'content',
    ]);
  }

  /**
   * Test a style is successfully excluded from a block.
   *
   * Test that the Checkbox style does not appear on the "Powered by Drupal"
   * block instance.
   */
  public function testPoweredByBlockExcluded() {
    $assert = $this->assertSession();

    // Go to the block instance configuration page.
    $this->drupalGet('admin/structure/block/manage/poweredbytest');
    $assert->statusCodeEquals(200);

    // Check that the style options are NOT available.
    $assert->pageTextNotContains('Check this box');
    $assert->fieldNotExists('third_party_settings[block_style_plugins][checkbox_with_exclude][checkbox_class]');
  }

  /**
   * Test a style is successfully shown for a block.
   *
   * Test that the "Breadcrumb" block does include style options for the
   * checkbox.
   */
  public function testBreadcrumbBlockCheckbox() {
    $assert = $this->assertSession();

    // Go to the block instance configuration page.
    $this->drupalGet('admin/structure/block/manage/breadcrumbtest');
    $assert->statusCodeEquals(200);

    // Check that the style options are available.
    $assert->responseContains('Check this box');
    $assert->fieldExists('third_party_settings[block_style_plugins][checkbox_with_exclude][checkbox_class]');

    $this->submitForm(
      ['third_party_settings[block_style_plugins][checkbox_with_exclude][checkbox_class]' => 1],
      'Save block'
    );

    // Go to the home page.
    $this->drupalGet('<front>');
    $assert->statusCodeEquals(200);

    // Assert that the block was placed and has the custom class.
    $assert->responseContains('id="block-breadcrumbtest"');
    $assert->responseNotContains('checkbox_class');

    // Go back to the block instance configuration page.
    $this->drupalGet('admin/structure/block/manage/breadcrumbtest');

    // Check that the class is set in the style field.
    $assert->checkboxChecked('third_party_settings[block_style_plugins][checkbox_with_exclude][checkbox_class]');
  }

}
