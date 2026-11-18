<?php

declare(strict_types=1);

namespace Drupal\Tests\photoswipe_svg_image_field\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the 'photoswipe_svg_image_field_formatter' field formatter.
 */
#[Group('photoswipe')]
#[RunTestsInSeparateProcesses]
class PhotoswipeSvgImageFieldFormatterTest extends FieldKernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'image',
    'svg_image_field',
    'photoswipe',
    'photoswipe_svg_image_field',
  ];

  /**
   * {@inheritdoc}
   *
   * The update hook test stores settings which are not part of the schema
   * anymore.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * The svg_image_field field name used in the test.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The entity view display used in the test.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $display;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    // File access checks 'access content' for public-scheme files.
    $this->setUpCurrentUser([], ['access content']);

    $this->fieldName = $this->randomMachineName();

    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => $this->fieldName,
      'type' => 'svg_image_field',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => $this->fieldName,
      'bundle' => 'entity_test',
    ])->save();

    $this->display = \Drupal::service('entity_display.repository')
      ->getViewDisplay('entity_test', 'entity_test')
      ->setComponent($this->fieldName, [
        'type' => 'photoswipe_svg_image_field_formatter',
        'label' => 'hidden',
        'settings' => [
          'width' => 50,
          'height' => 60,
        ],
      ]);
    $this->display->save();
  }

  /**
   * Creates an SVG file entity and writes the given contents to its URI.
   */
  protected function createSvgFile(string $filename, string $contents): File {
    $file = File::create(['uri' => 'public://' . $filename]);
    $file->save();
    file_put_contents($file->getFileUri(), $contents);
    return $file;
  }

  /**
   * Tests the rendered markup for a single SVG Image Field item.
   */
  public function testFormatterRendersSvgWithPhotoswipeMarkup(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    $svg = $this->createSvgFile('field-test.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="150"></svg>');

    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
      $this->fieldName => [
        ['target_id' => $svg->id(), 'alt' => 'An SVG image', 'title' => 'SVG title'],
      ],
    ]);
    $entity->save();

    $build = $this->display->build($entity);
    $output = (string) $renderer->renderRoot($build[$this->fieldName][0]);

    // This field type has no image style support to build a derivative
    // from, but the lightbox link still must not point directly at the
    // stored file, which may contain untrusted markup from the upload; it
    // points at the sanitizing route instead.
    $this->assertMatchesRegularExpression('#<a href="[^"]*/photoswipe/svg-image-field/' . $svg->id() . '/sanitized"[^>]*class="[^"]*photoswipe[^"]*"#', $output);
    // The intrinsic SVG dimensions (not the thumbnail size) drive the
    // Photoswipe zoom target.
    $this->assertStringContainsString('data-pswp-width="200"', $output);
    $this->assertStringContainsString('data-pswp-height="150"', $output);
    // The <img> uses the configured dimensions instead.
    $this->assertMatchesRegularExpression('#<img[^>]*width="50"[^>]*height="60"#', $output);
    $this->assertStringContainsString('loading="lazy"', $output);
    // The image itself opens the layer, there is no button in the corner.
    $this->assertMatchesRegularExpression('#<a href="[^"]*/sanitized"[^>]*>\s*<img[^>]*>\s*</a>#', $output);
    $this->assertStringNotContainsString('Enlarge image', $output);
    $this->assertStringNotContainsString('photoswipe-svg-inline', $output);
    $this->assertStringContainsString('alt="An SVG image"', $output);
    $this->assertStringContainsString('title="SVG title"', $output);
  }

  /**
   * Tests the inline mode keeps SVG links usable and adds a separate trigger.
   */
  public function testInlineSvgRendersSanitizedMarkupWithSiblingTrigger(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    $svg = $this->createSvgFile('inline-test.svg', '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" width="200" height="150" onload="alert(1)"><script>alert(2)</script><a href="https://example.com/target"><rect width="10" height="10"/></a></svg>');

    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
      $this->fieldName => [['target_id' => $svg->id(), 'alt' => 'An SVG image']],
    ]);
    $entity->save();

    $this->display->setComponent($this->fieldName, [
      'type' => 'photoswipe_svg_image_field_formatter',
      'label' => 'hidden',
      'settings' => [
        'width' => 50,
        'height' => 60,
        'inline' => TRUE,
      ],
    ])->save();

    $build = $this->display->build($entity);
    $output = (string) $renderer->renderRoot($build[$this->fieldName][0]);

    // The SVG is output inline, including its link, but sanitized.
    $this->assertStringContainsString('<svg', $output);
    $this->assertStringContainsString('href="https://example.com/target"', $output);
    $this->assertStringNotContainsString('<script', $output);
    $this->assertStringNotContainsString('onload', $output);
    $this->assertStringNotContainsString('<?xml', $output);
    $this->assertStringNotContainsString('<img', $output);
    // The configured dimensions are applied to the SVG.
    $this->assertMatchesRegularExpression('#<svg[^>]*width="50"[^>]*height="60"#', $output);
    // The lightbox trigger is a sibling of the SVG, not a wrapper around it,
    // and still points at the sanitizing route with the intrinsic dimensions.
    $this->assertMatchesRegularExpression('#</svg>\s*<a href="[^"]*/photoswipe/svg-image-field/' . $svg->id() . '/sanitized"[^>]*class="[^"]*photoswipe[^"]*"#', $output);
    $this->assertStringContainsString('data-pswp-width="200"', $output);
    $this->assertStringContainsString('data-pswp-height="150"', $output);
    $this->assertStringContainsString('photoswipe-svg-inline', $output);
    $this->assertStringContainsString('Enlarge image', $output);
  }

  /**
   * Tests that the update hook converts the settings of the 5.x releases.
   */
  public function testLegacySettingsUpdate(): void {
    $this->display->setComponent($this->fieldName, [
      'type' => 'photoswipe_svg_image_field_formatter',
      'label' => 'hidden',
      'settings' => [],
    ]);
    // The plugin manager drops unknown settings, so store them directly.
    $config = $this->config('core.entity_view_display.' . $this->display->id());
    $config->set('content.' . $this->fieldName . '.settings', [
      'photoswipe_thumbnail_width' => 50,
      'photoswipe_thumbnail_height' => 60,
      'photoswipe_remove_gallery_wrapper_class' => TRUE,
    ])->save();

    $this->container->get('module_handler')->loadInclude('photoswipe_svg_image_field', 'install');
    $sandbox = [];
    photoswipe_svg_image_field_update_9001($sandbox);

    $settings = EntityViewDisplay::load($this->display->id())->getComponent($this->fieldName)['settings'];
    $this->assertSame(50, $settings['width']);
    $this->assertSame(60, $settings['height']);
    $this->assertTrue($settings['apply_dimensions']);
    $this->assertTrue($settings['photoswipe_remove_gallery_wrapper_class']);
    $this->assertArrayNotHasKey('photoswipe_thumbnail_width', $settings);
    $this->assertArrayNotHasKey('photoswipe_thumbnail_height', $settings);

    // The converted display renders the image like the 5.x release did.
    $svg = $this->createSvgFile('upgrade-test.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="150"></svg>');
    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
      $this->fieldName => [['target_id' => $svg->id()]],
    ]);
    $entity->save();
    $build = EntityViewDisplay::load($this->display->id())->build($entity);
    $output = (string) $this->container->get('renderer')->renderRoot($build[$this->fieldName][0]);
    $this->assertMatchesRegularExpression('#<img[^>]*width="50"[^>]*height="60"#', $output);

    // An empty thumbnail size meant that no dimensions were applied.
    $config->set('content.' . $this->fieldName . '.settings', [
      'photoswipe_thumbnail_width' => NULL,
      'photoswipe_thumbnail_height' => NULL,
    ])->save();
    $sandbox = [];
    photoswipe_svg_image_field_update_9001($sandbox);
    $settings = EntityViewDisplay::load($this->display->id())->getComponent($this->fieldName)['settings'];
    $this->assertFalse($settings['apply_dimensions']);
  }

  /**
   * Tests that dimensions fall back to the viewBox when width/height absent.
   */
  public function testDimensionsFallBackToViewBox(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    $svg = $this->createSvgFile('viewbox-test.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 32"></svg>');

    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
      $this->fieldName => [['target_id' => $svg->id()]],
    ]);
    $entity->save();

    $build = $this->display->build($entity);
    $output = (string) $renderer->renderRoot($build[$this->fieldName][0]);

    $this->assertStringContainsString('data-pswp-width="64"', $output);
    $this->assertStringContainsString('data-pswp-height="32"', $output);
  }

  /**
   * Tests that the field wrapper gets the photoswipe-gallery class.
   */
  public function testGalleryWrapperClass(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    $svg = $this->createSvgFile('wrapper-test.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>');

    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
      $this->fieldName => [['target_id' => $svg->id()]],
    ]);
    $entity->save();

    $build = $this->display->build($entity);
    $field_output = (string) $renderer->renderRoot($build[$this->fieldName]);
    $this->assertStringContainsString('photoswipe-gallery', $field_output);

    // Disabling the setting removes the wrapper class again.
    $this->display->setComponent($this->fieldName, [
      'type' => 'photoswipe_svg_image_field_formatter',
      'label' => 'hidden',
      'settings' => [
        'photoswipe_remove_gallery_wrapper_class' => TRUE,
      ],
    ])->save();

    $build = $this->display->build($entity);
    $field_output = (string) $renderer->renderRoot($build[$this->fieldName]);
    $this->assertStringNotContainsString('photoswipe-gallery', $field_output);
  }

  /**
   * Tests that the route Photoswipe links to strips untrusted SVG markup.
   *
   * The sanitizer's own behavior is covered by
   * \Drupal\Tests\photoswipe\Kernel\Controller\SanitizedSvgControllerTest;
   * this only needs to prove the route this module registers actually
   * reaches that controller.
   */
  public function testSanitizedSvgRouteStripsScripts(): void {
    $svg = $this->createSvgFile('malicious.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(2)</script></svg>');

    $path = Url::fromRoute('photoswipe_svg_image_field.sanitized_svg', ['file' => $svg->id()])->toString();
    $response = \Drupal::service('http_kernel')->handle(Request::create($path), HttpKernelInterface::SUB_REQUEST);

    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('image/svg+xml', $response->headers->get('Content-Type'));
    $content = $response->getContent();
    $this->assertStringNotContainsString('<script', $content);
    $this->assertStringNotContainsString('onload', $content);
  }

  /**
   * Tests that the sanitizing route enforces file view access.
   */
  public function testSanitizedSvgRouteDeniesAccessWithoutPermission(): void {
    $svg = $this->createSvgFile('protected.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
    // Replace the 'access content' user set up in setUp() with one that
    // lacks it, so the file access handler denies the request.
    $this->setUpCurrentUser([], []);

    $path = Url::fromRoute('photoswipe_svg_image_field.sanitized_svg', ['file' => $svg->id()])->toString();
    $response = \Drupal::service('http_kernel')->handle(Request::create($path), HttpKernelInterface::SUB_REQUEST);

    $this->assertSame(403, $response->getStatusCode());
  }

}
