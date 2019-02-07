<?php

namespace Drupal\Tests\block_style_plugins\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test a Yaml only configuration.
 *
 * Test a styles with a template set by yaml. Include only on
 * "Powerd By Drupal" block.
 *
 * @group block_style_plugins
 */
class TemplateSetWithYamlTest extends BrowserTestBase {

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
      'id' => 'templatetest',
      'region' => 'content',
    ]);
  }

  /**
   * Test template set by Yaml.
   *
   * Test that the "Powered by Drupal" block uses the template set via YAML.
   */
  public function testTemplateByYaml() {
    $assert = $this->assertSession();

    // Go to the block instance configuration page.
    $this->drupalGet('admin/structure/block/manage/templatetest');
    $assert->statusCodeEquals(200);

    // Check that the style options are available.
    $assert->responseContains('Template Title');

    $this->submitForm(
      [
        'third_party_settings[block_style_plugins][template_set_with_yaml][test_field]' => 'custom-class',
      ],
      'Save block'
    );

    // Go to the home page.
    $this->drupalGet('<front>');
    $assert->statusCodeEquals(200);

    // Assert that the block was placed and has the template applied.
    $assert->responseContains('This is a custom template');
  }

}
