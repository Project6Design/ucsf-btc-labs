<?php

namespace Drupal\Tests\block_style_plugins\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the DropdownWithInclude plugin.
 *
 * Test a dropdown style selector for only the Powered by Drupal block any
 * "basic" custom block types.
 *
 * @group block_style_plugins
 */
class DropdownWithIncludeTest extends BrowserTestBase {

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
   * Test that the "Breadcrumb" block does not have style options.
   */
  public function testBreadcrumbBlockUnstyled() {
    $assert = $this->assertSession();

    // Go to the block instance configuration page.
    $this->drupalGet('admin/structure/block/manage/breadcrumbtest');
    $assert->statusCodeEquals(200);

    // Check that the style options are NOT available.
    $assert->pageTextNotContains('Choose a style from the dropdown');
    $assert->fieldNotExists('third_party_settings[block_style_plugins][dropdown_with_include][dropdown_class]');
  }

  /**
   * Test the the "Powered by Drupal" block does include style options.
   */
  public function testPoweredByBlockIncluded() {
    $assert = $this->assertSession();

    // Go to the block instance configuration page.
    $this->drupalGet('admin/structure/block/manage/poweredbytest');
    $assert->statusCodeEquals(200);

    // Check that the style options are available.
    $assert->responseContains('Choose a style from the dropdown');
    $assert->fieldValueEquals('third_party_settings[block_style_plugins][dropdown_with_include][dropdown_class]', 'style-3');

    // Submit the form.
    $this->submitForm(
      ['third_party_settings[block_style_plugins][dropdown_with_include][dropdown_class]' => 'style-1'],
      'Save block'
    );

    // Go to the home page.
    $this->drupalGet('<front>');
    $assert->statusCodeEquals(200);

    // Assert that the block was placed and has the custom class.
    $assert->responseContains('style-1');

    // Go back to the block instance configuration page.
    $this->drupalGet('admin/structure/block/manage/poweredbytest');

    // Check that the class is set in the style field.
    $assert->fieldValueEquals('third_party_settings[block_style_plugins][dropdown_with_include][dropdown_class]', 'style-1');
  }

}
