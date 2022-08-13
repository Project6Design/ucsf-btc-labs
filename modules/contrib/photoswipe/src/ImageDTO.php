<?php

namespace Drupal\photoswipe;

use Drupal\media\MediaInterface;

/**
 * Contains image item.
 */
class ImageDTO {

  /**
   * Image dimensions.
   */
  const HEIGHT = 'height';
  const WIDTH = 'width';

  /**
   * Preprocesed image settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * Item.
   *
   * @var mixed
   */
  protected $item;

  /**
   * Variables.
   *
   * @var array
   */
  protected $variables;

  /**
   * Image url.
   *
   * @var string|null
   */
  protected $uri;

  /**
   * Image title.
   *
   * @var null|string
   */
  protected $title;

  /**
   * Image alt text.
   *
   * @var null|string
   */
  protected $alt;

  /**
   * Entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Caption.
   *
   * @var string|null
   */
  protected $caption;

  /**
   * Image dimensions.
   *
   * @var array
   */
  protected $dimensions = [
    self::HEIGHT => 150,
    self::WIDTH => 150,
  ];

  /**
   * The path to the image that will show in Photoswipe.
   *
   * @var string
   */
  protected $path;

  /**
   * Construct new ImageDTO object.
   *
   * @param array $variables
   *   Variables from which fetch the image information.
   */
  public function __construct(array $variables) {
    $this->settings = $variables['display_settings'];

    $this->entity = $variables['entity'];
    $this->item = $variables['item'];

    // If item is instance of Media.
    if ($this->item->entity instanceof MediaInterface && $media = $this->item->entity) {
      // If the photoswipe_reference_image_field setting is not configured,
      // we get the source field.
      $photoswipe_reference_image_field = $this->settings['photoswipe_reference_image_field'] ?: $this->getMediaSourceField($media);

      $this->item = $media->get($photoswipe_reference_image_field);
    }

    $this->uri = $this->item->entity->getFileUri();
    $this->alt = $this->item->alt ?: NULL;
    $this->title = $this->item->title ?: NULL;
    $this->setDimensions([
      ImageDTO::HEIGHT => $this->item->height,
      ImageDTO::WIDTH => $this->item->width,
    ]);
  }

  /**
   * Create from variables.
   *
   * @param array $variables
   *   Variables.
   *
   * @return self
   *   New self.
   */
  public static function createFromVariables(array $variables) {
    return new static($variables);
  }

  /**
   * Get item settings.
   *
   * @return array
   *   Settings.
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * Get item.
   *
   * @return mixed
   *   Item.
   */
  public function getItem() {
    return $this->item;
  }

  /**
   * Get entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Get image alt.
   *
   * @return string|null
   *   Image alternative text.
   */
  public function getAlt() {
    return $this->alt;
  }

  /**
   * Get image uri.
   *
   * @return string|null
   *   Image uri.
   */
  public function getUri() {
    return $this->uri;
  }

  /**
   * Get Caption.
   *
   * @return string|null
   *   Image caption.
   */
  public function getCaption() {
    return $this->caption;
  }

  /**
   * Get image title.
   *
   * @return string|null
   *   Image title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Get image dimensions.
   *
   * @return array
   *   Set image dimensions.
   */
  public function getDimensions() {
    return $this->dimensions;
  }

  /**
   * Image dimensions.
   *
   * @param array $dimensions
   *   Dimentions.
   */
  public function setDimensions(array $dimensions) {
    $this->dimensions = $dimensions;
  }

  /**
   * Image width.
   *
   * @return int
   *   Image width.
   */
  public function getWidth() {
    return $this->dimensions[self::WIDTH];
  }

  /**
   * Image height.
   *
   * @return int
   *   Image height.
   */
  public function getHeight() {
    return $this->dimensions[self::HEIGHT];
  }

  /**
   * Get the path.
   *
   * @return string
   *   Path.
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * Returns the source field name of the given media entity.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity object.
   *
   * @return string
   *   The media source field name.
   */
  protected function getMediaSourceField(MediaInterface $media) {
    $media_source_configuration = $media->getSource()->getConfiguration();

    return $media_source_configuration['source_field'];
  }

}
