<?php

namespace Drupal\Tests\url_embed\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\link\LinkItemInterface;

/**
 * Tests url_embed link field formatter.
 *
 * @group url_embed
 */
class LinkEmbedFormatterTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['url_embed', 'entity_test', 'link'];

  /**
   * {@inheritdoc}
   */
  public $defaultTheme = 'stark';

  /**
   * A field to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * Tests the default url_embed link field formatter.
   */
  public function testDefaultEmbedFormatter() {
    $field_name = mb_strtolower($this->randomMachineName());
    $display_options = [
      'type' => 'url_embed',
      'label' => 'hidden',
      'settings' => [
        'enable_responsive' => FALSE,
        'default_ratio' => '',
      ],
    ];
    $this->createLinkEmbedFormatter($field_name, $display_options);
    // Create an entity to test the embed formatter.
    $url = UrlEmbedTestBase::FLICKR_URL;
    $entity = EntityTest::create();
    $entity->set($field_name, $url);
    $entity->save();

    // Render the entity and verify that the link is output as an embed.
    $output = $this->renderTestEntity($entity->id());
    $this->assertStringContainsString(UrlEmbedTestBase::FLICKR_OUTPUT_FIELD, $output);
  }

  /**
   * Tests the responsive url_embed link field formatter.
   */
  public function testResponsiveEmbedFormatter() {
    $field_name = mb_strtolower($this->randomMachineName());
    $display_options = [
      'type' => 'url_embed',
      'label' => 'hidden',
      'settings' => [
        'enable_responsive' => TRUE,
        'default_ratio' => '66.669',
      ],
    ];
    $this->createLinkEmbedFormatter($field_name, $display_options);
    // Create an entity to test the embed formatter.
    $url = UrlEmbedTestBase::FLICKR_URL;
    $entity = EntityTest::create();
    $entity->set($field_name, $url);
    $entity->save();

    // Render the entity and verify that the link is output as an embed.
    $output = $this->renderTestEntity($entity->id());
    $this->assertStringContainsString(UrlEmbedTestBase::FLICKR_OUTPUT_FIELD_RESPONSIVE, $output);
  }

  /**
   * Provides a 'url_embed' formatter.
   *
   * @param string $field_name
   *   A machine name for a field.
   * @param array $display_options
   *   Field formatter options.
   */
  public function createLinkEmbedFormatter($field_name, $display_options) {
    // Create a field with settings to validate.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'link',
      'cardinality' => 2,
    ]);
    $this->fieldStorage->save();
    \Drupal::service('entity_type.manager')->getStorage('field_config')->create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'settings' => [
        'title' => DRUPAL_OPTIONAL,
        'link_type' => LinkItemInterface::LINK_GENERIC,
      ],
    ])->save();
    \Drupal::service('entity_display.repository')->getFormDisplay('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, [
        'type' => 'link_default',
      ])
      ->save();
    \Drupal::service('entity_display.repository')->getViewDisplay('entity_test', 'entity_test', 'full')
      ->setComponent($field_name, $display_options)
      ->save();
  }

  /**
   * Renders a test_entity and returns the output.
   *
   * @param int $id
   *   The test_entity ID to render.
   * @param string $view_mode
   *   (optional) The view mode to use for rendering.
   * @param bool $reset
   *   (optional) Whether to reset the entity_test storage cache. Defaults to
   *   TRUE to simplify testing.
   *
   * @return string
   *   The rendered HTML output.
   */
  protected function renderTestEntity($id, $view_mode = 'full', $reset = TRUE) {
    if ($reset) {
      $this->container->get('entity_type.manager')->getStorage('entity_test')->resetCache([$id]);
    }
    $entity = EntityTest::load($id);
    $display = \Drupal::service('entity_display.repository')->getViewDisplay($entity->getEntityTypeId(), $entity->bundle(), $view_mode);
    $content = $display->build($entity);
    $output = \Drupal::service('renderer')->renderRoot($content);
    $output = (string) $output;
    return $output;
  }

}
