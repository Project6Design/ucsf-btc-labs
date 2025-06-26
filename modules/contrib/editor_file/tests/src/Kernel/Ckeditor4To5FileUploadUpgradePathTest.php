<?php

declare(strict_types=1);

namespace Drupal\Tests\editor_file\Kernel;

use Drupal\Component\Assertion\Inspector;
use Drupal\KernelTests\KernelTestBase;
use Drupal\ckeditor5\HTMLRestrictions;

/**
 * Ckeditor 4 to 5 upgrade path test.
 *
 * @covers \Drupal\editor_file\Plugin\CKEditor4To5Upgrade\EditorFile
 * @group editor_file
 * @group ckeditor5
 * @internal
 */
class Ckeditor4To5FileUploadUpgradePathTest extends KernelTestBase {

  /**
   * The CKEditor 4 toolbar button.
   *
   * @var string
   *
   * @see \Drupal\editor_file\Plugin\CKEditor4To5Upgrade\EditorFile
   */
  const BUTTON_IN_CKEDITOR4 = 'DrupalFile';

  /**
   * The "CKEditor 5 plugin" plugin manager.
   *
   * @var \Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface
   */
  protected $cke5PluginManager;

  /**
   * The CKEditor 4 to 5 upgrade plugin manager.
   *
   * @var \Drupal\ckeditor5\Plugin\CKEditor4To5UpgradePluginManager
   */
  protected $upgradePluginManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    'editor_file',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // The tested service is private; expose it under a public test-only alias.
    $this->container->setAlias('sut', 'plugin.manager.ckeditor4to5upgrade.plugin');

    $this->cke5PluginManager = $this->container->get('plugin.manager.ckeditor5.plugin');
    $this->upgradePluginManager = $this->container->get('sut');

  }

  /**
   * Tests `drupalfile` plugin button that have an upgrade path.
   */
  public function testFileUploadButtonUpgradePath(): void {
    $equivalent = $this->upgradePluginManager->mapCKEditor4ToolbarButtonToCKEditor5ToolbarItem(self::BUTTON_IN_CKEDITOR4, HTMLRestrictions::emptySet());
    $this->assertTrue($equivalent === NULL || (is_array($equivalent) && Inspector::assertAllStrings($equivalent)));
    // The returned equivalent CKEditor 5 toolbar item(s) must exist.
    if (is_string($equivalent)) {
      foreach (explode(',', $equivalent) as $equivalent_cke5_toolbar_item) {
        $this->assertArrayHasKey($equivalent_cke5_toolbar_item, $this->cke5PluginManager->getToolbarItems());
      }
    }
  }

}
