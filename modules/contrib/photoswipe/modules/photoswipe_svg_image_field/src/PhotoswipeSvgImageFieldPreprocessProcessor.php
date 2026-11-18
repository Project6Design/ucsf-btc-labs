<?php

namespace Drupal\photoswipe_svg_image_field;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Preprocesses the Photoswipe link for the SVG Image Field module.
 *
 * The 'svg_image_field' field item has no width/height columns and is not
 * compatible with image styles, so this processor reads the source SVG
 * directly instead of relying on the DTO/image style pipeline the core
 * Photoswipe formatter uses for the 'image' field type.
 */
class PhotoswipeSvgImageFieldPreprocessProcessor implements ContainerInjectionInterface {

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new PhotoswipeSvgImageFieldPreprocessProcessor object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(LoggerChannelFactoryInterface $loggerFactory) {
    $this->logger = $loggerFactory->get('photoswipe');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory'),
    );
  }

  /**
   * Preprocess an SVG Image Field item into Photoswipe template variables.
   *
   * @param array $variables
   *   Variables.
   */
  public function preprocess(array &$variables) {
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    $item = $variables['item'];
    $settings = $variables['display_settings'];

    $file = $item->entity;
    if (!$file instanceof FileInterface) {
      $this->logger->warning('Can not apply photoswipe on image. The referenced file does not exist.');
      return;
    }

    $dimensions = $this->getSvgDimensions($file);
    // Without intrinsic dimensions, fall back to the configured size.
    $appliedWidth = !empty($settings['apply_dimensions']) ? (int) $settings['width'] : 0;
    $appliedHeight = !empty($settings['apply_dimensions']) ? (int) $settings['height'] : 0;
    $width = $dimensions['width'] ?: $appliedWidth;
    $height = $dimensions['height'] ?: $appliedHeight;

    // This processor only ever handles SVG files, and this field type has
    // no image style support, so link to a sanitized copy of the original
    // rather than the stored file itself, which may contain untrusted
    // markup from the upload.
    // The inline svg (when enabled) is sanitized through the parent
    // SvgImageFieldFormatter::loadInlineSvgData(), which respects the
    // "sanitize" and "sanitize_remote" display settings.
    // Because the inline sanitization doesn't modify the actual file, we still
    // need this linked file sanitization for the lightbox:
    // @todo This might change in https://www.drupal.org/project/photoswipe/issues/3635489:
    $variables['path'] = Url::fromRoute('photoswipe_svg_image_field.sanitized_svg', ['file' => $file->id()])
      ->setAbsolute()
      ->toString();
    $variables['attributes']['class'][] = 'photoswipe';
    if (!empty($width) && !empty($height)) {
      $variables['attributes']['data-pswp-width'] = $width;
      $variables['attributes']['data-pswp-height'] = $height;
    }

    if (!empty($variables['inline'])) {
      $variables['wrapper_attributes'] = new Attribute(['class' => ['photoswipe-svg-inline']]);
    }
  }

  /**
   * Reads the intrinsic width/height declared in an SVG file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The SVG file.
   *
   * @return array
   *   An array with 'width' and 'height' keys, either integers or NULL when
   *   they could not be determined.
   */
  protected function getSvgDimensions(FileInterface $file): array {
    $dimensions = ['width' => NULL, 'height' => NULL];

    $contents = @file_get_contents($file->getFileUri());
    if (!$contents) {
      return $dimensions;
    }

    // Explicitly keep entity substitution and DTD loading disabled, and
    // block network access, so a crafted SVG cannot trigger XXE/SSRF while
    // its dimensions are read:
    $previous = libxml_use_internal_errors(TRUE);
    $svg = simplexml_load_string($contents, \SimpleXMLElement::class, LIBXML_NONET);
    libxml_use_internal_errors($previous);
    if (!$svg) {
      return $dimensions;
    }

    $attributes = $svg->attributes();
    if (isset($attributes['width'], $attributes['height']) && is_numeric((string) $attributes['width']) && is_numeric((string) $attributes['height'])) {
      $dimensions['width'] = (int) $attributes['width'];
      $dimensions['height'] = (int) $attributes['height'];
      return $dimensions;
    }

    if (isset($attributes['viewBox'])) {
      $viewBox = preg_split('/[\s,]+/', trim((string) $attributes['viewBox']));
      if (count($viewBox) === 4) {
        $dimensions['width'] = (int) round((float) $viewBox[2]);
        $dimensions['height'] = (int) round((float) $viewBox[3]);
      }
    }

    return $dimensions;
  }

}
