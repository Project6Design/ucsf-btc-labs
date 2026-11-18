<?php

namespace Drupal\embed\Entity;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\embed\EmbedButtonInterface;
use Drupal\embed\EmbedButtonListBuilder;
use Drupal\embed\Form\EmbedButtonForm;

/**
 * Defines the EmbedButton entity.
 */
#[ConfigEntityType(
  id: 'embed_button',
  label: new TranslatableMarkup('Embed button'),
  config_prefix: 'button',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
  ],
  handlers: [
    'form' => [
      'add' => EmbedButtonForm::class,
      'edit' => EmbedButtonForm::class,
      'delete' => EntityDeleteForm::class,
    ],
    'list_builder' => EmbedButtonListBuilder::class,
  ],
  links: [
    'edit-form' => '/admin/config/content/embed/button/manage/{embed_button}',
    'delete-form' => '/admin/config/content/embed/button/manage/{embed_button}/delete',
    'collection' => '/admin/config/content/embed',
  ],
  admin_permission: 'administer embed buttons',
  config_export: [
    'label',
    'id',
    'type_id',
    'type_settings',
    'icon',
    'icon_uuid',
  ],
)]
class EmbedButton extends ConfigEntityBase implements EmbedButtonInterface {

  use StringTranslationTrait;

  /**
   * The EmbedButton ID.
   *
   * @var string
   */
  public $id;

  /**
   * Label of EmbedButton.
   *
   * @var string
   */
  public $label;

  /**
   * The embed type plugin ID.
   *
   * @var string
   */
  public $type_id;

  /**
   * Embed type settings.
   *
   * An array of key/value pairs.
   *
   * @var array
   */
  public $type_settings = [];

  /**
   * An array of data about the encoded button image.
   *
   * @var array
   */
  public $icon = [];

  /**
   * {@inheritdoc}
   */
  public function getTypeId() {
    return $this->type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeLabel() {
    if ($definition = $this->embedTypeManager()->getDefinition($this->getTypeId(), FALSE)) {
      return $definition['label'];
    }
    return $this->t('Unknown');
  }

  /**
   * {@inheritdoc}
   */
  public function getTypePlugin() {
    if ($plugin_id = $this->getTypeId()) {
      return $this->embedTypeManager()->createInstance($plugin_id, $this->getTypeSettings());
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getIconFile() {
    @trigger_error(__METHOD__ . ' is deprecated in embed:8.x-1.2 and will be removed in embed:2.0.0. Use \Drupal\embed\Entity\EmbedButton::getIconUrl instead. See https://www.drupal.org/node/3139211', E_USER_DEPRECATED);
    if (!empty($this->icon_uuid)) {
      $files = $this->entityTypeManager()->getStorage('file')->loadByProperties(['uuid' => $this->icon_uuid]);
      return reset($files);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getIconUrl() {
    if (!empty($this->icon)) {
      $uri = $this->icon['uri'];
      if (!is_file($uri) && !UrlHelper::isExternal($uri)) {
        static::convertEncodedDataToImage($this->icon);
      }
    }
    else {
      $uri = $this->getTypePlugin()->getDefaultIconUrl();
    }

    $file_generator = $this->fileUrlGenerator();
    $uri = $file_generator->generateAbsoluteString($uri);
    return $file_generator->transformRelative($uri);
  }

  /**
   * {@inheritdoc}
   *
   * @return $this
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Gather the dependencies of the embed type plugin.
    if ($plugin = $this->getTypePlugin()) {
      $this->calculatePluginDependencies($plugin);
    }
    return $this;
  }

  /**
   * Gets the embed type plugin manager.
   *
   * @return \Drupal\embed\EmbedType\EmbedTypeManager
   *   The embed type plugin manager.
   */
  protected function embedTypeManager() {
    return \Drupal::service('plugin.manager.embed.type');
  }

  /**
   * Gets the file URL generator service.
   *
   * @return \Drupal\Core\File\FileUrlGeneratorInterface
   *   The file URL generator.
   */
  protected function fileUrlGenerator() {
    return \Drupal::service('file_url_generator');
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeSetting($key, $default = NULL) {
    if (isset($this->type_settings[$key])) {
      return $this->type_settings[$key];
    }
    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeSettings() {
    return $this->type_settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function convertImageToEncodedData($uri) {
    return [
      'data' => base64_encode(file_get_contents($uri)),
      'uri' => $uri,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function convertEncodedDataToImage(array $data) {
    if (!is_file($data['uri'])) {
      $directory = dirname($data['uri']);
      /** @var \Drupal\Core\File\FileSystemInterface $filesystem */
      $fileSystem = \Drupal::service('file_system');
      $fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
      $fileSystem->saveData(base64_decode($data['data']), $data['uri'], FileExists::Replace);
    }
    return $data['uri'];
  }

}
