<?php

namespace Drupal\Tests\anchor_link\Unit;

use Drupal\Tests\UnitTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests the shape of the CKEditor 5 plugin definition.
 *
 * @group anchor_link
 */
class CKEditor5PluginDefinitionTest extends UnitTestCase {

  /**
   * The parsed plugin definition.
   *
   * @var array
   */
  protected array $definition;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $file = dirname(__DIR__, 3) . '/anchor_link.ckeditor5.yml';
    $this->definition = Yaml::parseFile($file)['anchor_link_ckeditor5_anchor'];
  }

  /**
   * The link editing plugins load with the anchor plugin.
   *
   * The anchor UI requires them, so the editor attaches even when the Link
   * button is not in the toolbar.
   */
  public function testLinkPluginsLoadWithAnchor(): void {
    $plugins = $this->definition['ckeditor5']['plugins'];
    $this->assertContains('anchorDrupal.Anchor', $plugins);
    $this->assertContains('link.LinkEditing', $plugins);
    $this->assertContains('link.LinkUI', $plugins);
  }

  /**
   * The anchor attributes are kept away from General HTML Support.
   *
   * The disallow lives in this plugin's own configuration, so it only applies
   * where the Anchor button is enabled and other editors are left alone.
   */
  public function testAnchorAttributesAreDisallowedForGhs(): void {
    $disallow = $this->definition['ckeditor5']['config']['htmlSupport']['disallow'];
    $this->assertContains([
      'name' => 'a',
      'attributes' => ['id', 'name'],
    ], $disallow);
  }

  /**
   * The elements list claims only what the plugin creates.
   */
  public function testElementsClaimOnlyAnchorAttributes(): void {
    $this->assertSame(['<a>', '<a id="">', '<a name="">'], $this->definition['drupal']['elements']);
  }

}
