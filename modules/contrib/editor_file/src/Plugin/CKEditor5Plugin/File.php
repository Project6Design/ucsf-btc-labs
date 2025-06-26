<?php

declare(strict_types=1);

namespace Drupal\editor_file\Plugin\CKEditor5Plugin;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Component\Utility\Environment;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\ckeditor5\Plugin\CKEditor5Plugin\DynamicPluginConfigWithCsrfTokenUrlTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\editor\EditorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CKEditor 5 File plugin.
 *
 * @internal
 *   Plugin classes are internal.
 */
class File extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface, ContainerFactoryPluginInterface {

  use CKEditor5PluginConfigurableTrait;
  use DynamicPluginConfigWithCsrfTokenUrlTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Constructs a File object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   *   The stream wrapper manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $configFactory,
    StreamWrapperManagerInterface $streamWrapperManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $configFactory;
    $this->streamWrapperManager = $streamWrapperManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('stream_wrapper_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $config = $static_plugin_config;

    $config += [
      'drupalFileUpload' => [
        'format' => $editor->id(),
        'dialogTitleAdd' => $this->t('Add File'),
        'dialogTitleEdit' => $this->t('Edit File'),
      ],
    ];

    return $config;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\editor\Form\EditorImageDialog
   * @see editor_image_upload_settings_form()
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    // Migrate from CKEditor4.
    $this->migratePluginThirdPartyConfiguration($form_state->get('editor'));

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable file uploads'),
      '#default_value' => $this->configuration['status'],
      '#attributes' => [
        'data-editor-file-upload' => 'status',
      ],
    ];
    $show_if_file_uploads_enabled = [
      'visible' => [
        ':input[data-editor-file-upload="status"]' => ['checked' => TRUE],
      ],
    ];

    // Any visible, writable wrapper can potentially be used for uploads,
    // including a remote file system that integrates with a CDN.
    $options = $this->streamWrapperManager->getDescriptions(StreamWrapperInterface::WRITE_VISIBLE);
    if (!empty($options)) {
      $default_value = !empty($this->configuration['scheme']) ? $this->configuration['scheme'] : array_key_first($options);
      $form['scheme'] = [
        '#type' => 'radios',
        '#title' => $this->t('File storage'),
        '#default_value' => $default_value,
        '#options' => $options,
        '#states' => $show_if_file_uploads_enabled,
        '#access' => count($options) > 1,
      ];
    }
    // Set data-attributes with human-readable names for all possible stream
    // wrappers, so that drupal.ckeditor.drupalfile.admin's summary rendering
    // can use that.
    foreach ($this->streamWrapperManager->getNames(StreamWrapperInterface::WRITE_VISIBLE) as $scheme => $name) {
      $form['scheme'][$scheme]['#attributes']['data-label'] = $this->t('Storage: @name', ['@name' => $name]);
    }

    $form['directory'] = [
      '#type' => 'textfield',
      '#default_value' => $this->configuration['directory'],
      '#title' => $this->t('Upload directory'),
      '#description' => $this->t("A directory relative to Drupal's files directory where uploaded files will be stored."),
      '#states' => $show_if_file_uploads_enabled,
    ];

    $extensions = str_replace(' ', ', ', $this->configuration['extensions']);
    $form['extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed file extensions'),
      '#default_value' => $extensions,
      '#description' => $this->t('Separate extensions with a space or comma and do not include the leading dot.'),
      '#element_validate' => [
        [
          '\Drupal\file\Plugin\Field\FieldType\FileItem', 'validateExtensions',
        ],
      ],
      '#maxlength' => 256,
      // By making this field required, we prevent a potential security issue
      // that would allow files of any type to be uploaded.
      '#required' => TRUE,
      '#states' => $show_if_file_uploads_enabled,
    ];

    $default_max_size = DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '10.2.0', fn() => ByteSizeMarkup::create(Environment::getUploadMaxSize()), fn() => format_size(Environment::getUploadMaxSize()));
    $form['max_size'] = [
      '#type' => 'textfield',
      '#default_value' => $this->configuration['max_size'],
      '#title' => $this->t('Maximum file size'),
      '#description' => $this->t('If this is left empty, then the file size will be limited by the PHP maximum upload size of @size.', ['@size' => $default_max_size]),
      '#maxlength' => 20,
      '#size' => 10,
      '#placeholder' => $default_max_size,
      '#states' => $show_if_file_uploads_enabled,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $form_value = $form_state->getValue('status');
    $form_state->setValue('status', (bool) $form_value);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $keys = [
      'status',
      'scheme',
      'directory',
      'extensions',
      'max_size',
    ];
    foreach ($keys as $key) {
      $this->configuration[$key] = $form_state->getValue($key);
    }
  }

  /**
   * {@inheritdoc}
   *
   * This returns an empty array as file upload config is stored out of band.
   */
  public function defaultConfiguration(): array {
    return [
      'status' => FALSE,
      'scheme' => '',
      'directory' => '',
      'extensions' => '',
      'max_size' => '',
    ];
  }

  /**
   * Migrate plugin module third party configuration.
   *
   * Currently module configuration is saved in editor's
   * third party settings. The setting will migrate from there.
   *
   * @param \Drupal\editor\EditorInterface $editor
   *   The ckeditor5 editor.
   *
   * @return \Drupal\editor\EditorInterface
   *   The updated ckeditor5 editor.
   *
   * @see \Drupal\Core\Config\Entity\ThirdPartySettingsInterface::getThirdPartySetting()
   * @see \Drupal\Core\Config\Entity\ThirdPartySettingsInterface::setThirdPartySetting()
   */
  private function migratePluginThirdPartyConfiguration(EditorInterface $editor): EditorInterface {
    // If the ckeditor5 already has the third party settings
    // then we do nothing.
    if (!empty($this->configuration) && !empty($this->configuration['scheme'])) {
      return $editor;
    }
    // Get the ckeditor4 editor to get the third party settings.
    $ck4_editor = $this->entityTypeManager->getStorage('editor')->loadByProperties([
      'format' => $editor->get('format'),
      'editor' => 'ckeditor',
    ]);

    if (!empty($ck4_editor)) {
      $ck4_settings = reset($ck4_editor)->getThirdPartySettings('editor_file');
      if (!empty($ck4_settings)) {
        foreach ($ck4_settings as $key => $value) {
          $this->configuration[$key] = $value;
        }
      }
    }
    return $editor;
  }

}
