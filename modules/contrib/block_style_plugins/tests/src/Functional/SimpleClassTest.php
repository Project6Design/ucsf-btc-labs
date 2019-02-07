<?php

namespace Drupal\Tests\block_style_plugins\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test a simple class being added in a text box.
 *
 * @group block_style_plugins
 */
class SimpleClassTest extends BrowserTestBase {

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

    $this->adminUser = $this->drupalCreateUser(['administer blocks', 'access administration pages']);
    $this->drupalLogin($this->adminUser);

    // Place the "Powered By Drupal" block.
    $this->drupalPlaceBlock('system_powered_by_block', [
      'id' => 'poweredbytest',
      'region' => 'content',
    ]);
  }

  /**
   * Test that a class name can be added to a block instance in a text field.
   */
  public function testAddClassToBlock() {
    $assert = $this->assertSession();

    // Go to the block instance configuration page.
    $this->drupalGet('admin/structure/block/manage/poweredbytest');

    // Check that the style options are available.
    $assert->responseContains('Block Styles');
    $assert->responseContains('Add a custom class to this block');
    $assert->fieldExists('third_party_settings[block_style_plugins][simple_class][simple_class]');

    $this->submitForm(
      ['third_party_settings[block_style_plugins][simple_class][simple_class]' => 'sample-class'],
      'Save block'
    );

    // Go to the home page.
    $this->drupalGet('<front>');
    $assert->statusCodeEquals(200);

    // Assert that the block was placed and has the custom class.
    $assert->linkExists('Drupal');
    $assert->responseContains('sample-class');

    // Go back to the block instance configuration page.
    $this->drupalGet('admin/structure/block/manage/poweredbytest');

    // Check that the class is set in the style field.
    $assert->fieldValueEquals('third_party_settings[block_style_plugins][simple_class][simple_class]', 'sample-class');
  }

}
