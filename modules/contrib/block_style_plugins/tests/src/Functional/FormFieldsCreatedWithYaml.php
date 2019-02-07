<?php

namespace Drupal\Tests\block_style_plugins\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test a Yaml only configuration.
 *
 * Test a styles with form fields created by yaml. Include only on
 * "Powerd By Drupal" block.
 *
 * @group block_style_plugins
 */
class FormFieldsCreatedWithYamlTest extends BrowserTestBase {

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
   * Test that the styles do not appear on the "Breadcrumb" block instance.
   */
  public function testBreadcrumbBlockExcluded() {
    $assert = $this->assertSession();

    // Go to the block instance configuration page.
    $this->drupalGet('admin/structure/block/manage/breadcrumbtest');
    $assert->statusCodeEquals(200);

    // Check that the style options are NOT available.
    $assert->pageTextNotContains('Styles Created by Yaml');
    $assert->fieldNotExists('third_party_settings[block_style_plugins][form_fields_created_with_yaml][test_field]');
  }

  /**
   * Test styles created by Yaml.
   *
   * Test that the "Powered by Drupal" block does include style options created
   * by the yaml file.
   */
  public function testPoweredByBlockCreatedByYaml() {
    $assert = $this->assertSession();

    // Go to the block instance configuration page.
    $this->drupalGet('admin/structure/block/manage/poweredbytest');
    $assert->statusCodeEquals(200);

    // Check that the style options are available.
    $assert->responseContains('Title Created by Yaml');
    $assert->fieldExists('third_party_settings[block_style_plugins][form_fields_created_with_yaml][test_field]');
    $assert->fieldValueEquals('third_party_settings[block_style_plugins][form_fields_created_with_yaml][test_field]', 'text goes here');
    $assert->responseContains('Choose a style');
    $assert->fieldExists('third_party_settings[block_style_plugins][form_fields_created_with_yaml][second_field]');

    $this->submitForm(
      [
        'third_party_settings[block_style_plugins][form_fields_created_with_yaml][test_field]' => 'custom-class',
        'third_party_settings[block_style_plugins][form_fields_created_with_yaml][second_field]' => 'style-2',
      ],
      'Save block'
    );

    // Go to the home page.
    $this->drupalGet('<front>');
    $assert->statusCodeEquals(200);

    // Assert that the block was placed and has the custom class.
    $assert->responseContains('id="block-poweredbytest"');
    $assert->responseContains('custom-class');
    $assert->responseContains('style-2');

    // Go back to the block instance configuration page.
    $this->drupalGet('admin/structure/block/manage/poweredbytest');

    // Check that the class is set in the style field.
    $assert->fieldValueEquals('third_party_settings[block_style_plugins][form_fields_created_with_yaml][test_field]', 'custom-class');
    $assert->fieldValueEquals('third_party_settings[block_style_plugins][form_fields_created_with_yaml][second_field]', 'style-2');
  }

}
