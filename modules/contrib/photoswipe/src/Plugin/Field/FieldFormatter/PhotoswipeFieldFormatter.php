<?php

namespace Drupal\photoswipe\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\photoswipe\PhotoswipeAssetsManagerInterface;

/**
 * Plugin implementation of the 'photoswipe_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "photoswipe_field_formatter",
 *   label = @Translation("Photoswipe"),
 *   field_types = {
 *     "entity_reference",
 *     "image"
 *   }
 * )
 */
class PhotoswipeFieldFormatter extends FormatterBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The assets manager.
   *
   * @var \Drupal\photoswipe\PhotoswipeAssetsManagerInterface
   */
  protected $photoswipeAssetManager;

  /**
   * True if include 'hidden' style for images.
   *
   * @var bool
   */
  protected $includeHidden = TRUE;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\image\ImageStyleStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'photoswipe_node_style_first' => '',
      'photoswipe_node_style' => '',
      'photoswipe_image_style' => '',
      'photoswipe_reference_image_field' => '',
      'photoswipe_caption' => 'alt',
      'photoswipe_caption_custom' => '',
      'photoswipe_view_mode' => '',
    ] + parent::defaultSettings();
  }

  /**
   * Constructs a FormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\photoswipe\PhotoswipeAssetsManagerInterface $assets_manager
   *   The assets manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    ModuleHandlerInterface $module_handler,
    EntityFieldManagerInterface $entity_field_manager,
    EntityRepositoryInterface $entity_repository,
    PhotoswipeAssetsManagerInterface $assets_manager,
    EntityStorageInterface $image_style_storage
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->fieldDefinition = $field_definition;
    $this->settings = $settings;
    $this->label = $label;
    $this->viewMode = $view_mode;
    $this->thirdPartySettings = $third_party_settings;
    $this->moduleHandler = $module_handler;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityRepository = $entity_repository;
    $this->photoswipeAssetManager = $assets_manager;
    $this->imageStyleStorage = $image_style_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('module_handler'),
      $container->get('entity_field.manager'),
      $container->get('entity.repository'),
      $container->get('photoswipe.assets_manager'),
      $container->get('entity_type.manager')->getStorage('image_style'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles_hide = $this->includeHidden
      ? $this->getImageStyles() + ['hide' => $this->t('Hide (do not display image)')]
      : $this->getImageStyles();
    $element['photoswipe_node_style'] = [
      '#title' => $this->t('Thumbnail image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('photoswipe_node_style'),
      '#empty_option' => $this->t('None (Original image)'),
      '#options' => $image_styles_hide,
      '#description' => $this->t('Select the image style for the thumbnail display. Clicking a thumbnail will open the Photoswipe layer.'),
    ];
    $element['photoswipe_node_style_first'] = [
      '#title' => $this->t('Override first image thumbnail style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('photoswipe_node_style_first'),
      '#empty_option' => $this->t('No override (use default thumbnail image style)'),
      '#options' => $image_styles_hide,
      '#description' => $this->t('Sometimes the first image should be displayed differently, e.g. larger than other images. This option overrides the first image style.'),
    ];
    $element['photoswipe_image_style'] = [
      '#title' => $this->t('Photoswipe modal image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('photoswipe_image_style'),
      '#empty_option' => $this->t('None (Original image)'),
      '#options' => $this->getPhotoSwipeStyles(),
      '#description' => $this->t('Select the image style to display the image in the Photoswipe modal.'),
    ];

    // Set our caption options.
    $caption_options = [
      'title' => $this->t('Image title tag'),
      'alt' => $this->t('Image alt tag'),
      'entity_label' => $this->t('Entity label'),
      'custom' => $this->t('Custom (with tokens)'),
    ];
    // Add media entity name if the target is a media entity:
    $field_settings = $this->getFieldSettings();
    if (isset($field_settings['target_type']) && $field_settings['target_type'] === 'media') {
      $caption_options['media_name'] = $this->t('Media entity name');
    }

    $element = $this->addEntityReferenceSettings($element);

    // Add the other parent entity fields as options.
    if (isset($form['#fields'])) {
      foreach ($form['#fields'] as $parent_field) {
        if ($parent_field != $this->fieldDefinition->getName()) {
          $caption_options[$parent_field] = $parent_field;
        }
      }
    }

    $element['photoswipe_caption'] = [
      '#title' => $this->t('Photoswipe image caption'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('photoswipe_caption'),
      '#options' => $caption_options,
      '#description' => $this->t('Field that should be used for the caption displayed in the Photoswipe modal.'),
    ];

    $element['photoswipe_caption_custom'] = [
      '#title' => $this->t('Custom caption'),
      '#type' => 'textarea',
      '#default_value' => $this->getSetting('photoswipe_caption_custom'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings][photoswipe_caption]"]' => ['value' => 'custom'],
        ],
      ],
    ];
    if ($this->moduleHandler->moduleExists('token')) {
      // Get the field target type, e.g. 'file':
      $target_type = $this->fieldDefinition->getSetting('target_type');
      // Get the field parent entity type, e.g. 'node':
      $entity_type = $this->fieldDefinition->getTargetEntityTypeId();
      $element['photoswipe_token_caption'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Replacement patterns'),
        '#theme' => 'token_tree_link',
        '#token_types' => [
          // Default it will work for file type.
          'file',
          // The "entity_type" key is set in \Drupal\Core\Field\FieldConfigBase.
          $entity_type ?: 'node',
          // For prevent duplicate file type.
          ($target_type !== 'file' ? $target_type : 'media'),
        ],
        '#states' => [
          'visible' => [
            ':input[name$="[settings][photoswipe_caption]"]' => ['value' => 'custom'],
          ],
        ],
      ];
    }
    else {
      $element['photoswipe_token_caption'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Replacement patterns'),
        '#description' => '<strong class="error">' . $this->t('For token support the <a href="@token_url">token module</a> must be installed.', ['@token_url' => 'http://drupal.org/project/token']) . '</strong>',
        '#states' => [
          'visible' => [
            ':input[name$="[settings][photoswipe_caption]"]' => ['value' => 'custom'],
          ],
        ],
      ];
    }

    // Add the current view mode so we can control view mode for node fields.
    $element['photoswipe_view_mode'] = [
      '#type' => 'hidden',
      '#value' => $this->viewMode,
    ];

    return $element + parent::settingsForm($form, $form_state);
  }

  /**
   * Get default image styles.
   *
   * @return array
   *   Image styles.
   */
  protected function getImageStyles() {
    return image_style_options(FALSE);
  }

  /**
   * Get image styles for the photoswipe.
   *
   * @return array
   *   Image styles.
   */
  protected function getPhotoSwipeStyles() {
    return image_style_options(FALSE);
  }

  /**
   * Adds extra settings related when dealing with an entity reference.
   *
   * @param array $element
   *   The settings form structure of this formatter.
   *
   * @return array
   *   The modified settings form structure of this formatter.
   */
  public function addEntityReferenceSettings(array $element) {
    if ($this->fieldDefinition->getType() !== 'entity_reference') {
      return $element;
    }

    $target_type = $this->fieldDefinition->getSetting('target_type');
    $target_bundles = $this->fieldDefinition->getSetting('handler_settings')['target_bundles'];

    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $fields */
    $fields = [];
    foreach ($target_bundles as $bundle) {
      $fields += $this->entityFieldManager->getFieldDefinitions($target_type, $bundle);
    }
    $fields = array_filter($fields, function (FieldDefinitionInterface $field) {
      return $field->getType() === 'image' && $field->getName() !== 'thumbnail';
    });

    $field_options = [];
    foreach ($fields as $name => $field) {
      $field_options[$name] = $field->getName();
    }

    $element['photoswipe_reference_image_field'] = [
      '#title' => $this->t('Image field of the referenced entity'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('photoswipe_reference_image_field'),
      '#options' => $field_options,
      '#description' => $this->t('Field that contains the image to be used.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $image_styles = $this->getImageStyles();
    $photoswipe_styles = $this->getPhotoSwipeStyles();
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    if (isset($image_styles[$this->getSetting('photoswipe_node_style')])) {
      $summary[] = $this->t('Thumbnail image style: @style', ['@style' => $image_styles[$this->getSetting('photoswipe_node_style')]]);
    }
    elseif ($this->getSetting('photoswipe_node_style') == 'hide') {
      $summary[] = $this->t('Thumbnail image style: Hidden');
    }
    else {
      $summary[] = $this->t('Thumbnail image style: Original image');
    }

    if (isset($image_styles[$this->getSetting('photoswipe_node_style_first')])) {
      $summary[] = $this->t('First image thumbnail style override: @style', ['@style' => $image_styles[$this->getSetting('photoswipe_node_style_first')]]);
    }
    elseif ($this->getSetting('photoswipe_node_style_first') == 'hide') {
      $summary[] = $this->t('First image thumbnail style override: Hidden');
    }
    else {
      $summary[] = $this->t('No special first image thumbnail style.');
    }

    if (isset($photoswipe_styles[$this->getSetting('photoswipe_image_style')])) {
      $summary[] = $this->t('Photoswipe modal image style: @style', ['@style' => $photoswipe_styles[$this->getSetting('photoswipe_image_style')]]);
    }
    else {
      $summary[] = $this->t('Photoswipe modal image style: Original image');
    }

    if ($this->getSetting('photoswipe_reference_image_field')) {
      $summary[] = $this->t('Referenced entity image field: @field', ['@field' => $this->getSetting('photoswipe_reference_image_field')]);
    }

    if ($this->getSetting('photoswipe_caption')) {
      $caption_options = [
        'alt' => $this->t('Image alt tag'),
        'title' => $this->t('Image title tag'),
        'entity_label' => $this->t('Entity label'),
        'media_name' => $this->t('Media entity name'),
        'custom' => $this->t('Custom (with tokens)'),
      ];
      if (array_key_exists($this->getSetting('photoswipe_caption'), $caption_options)) {
        $caption_setting = $caption_options[$this->getSetting('photoswipe_caption')];
      }
      else {
        $caption_setting = $this->getSetting('photoswipe_caption');
      }
      $summary[] = $this->t('Photoswipe Caption: @field', ['@field' => $caption_setting]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $settings = $this->getSettings();

    if ($items->isEmpty()) {
      $default_image = $this->getFieldSetting('default_image');
      // If we are dealing with a configurable field, look in both
      // instance-level and field-level settings.
      if (empty($default_image['uuid']) && $this->fieldDefinition instanceof FieldConfigInterface) {
        $default_image = $this->fieldDefinition->getFieldStorageDefinition()
          ->getSetting('default_image');
      }
      if (!empty($default_image['uuid']) && $file = $this->entityRepository->loadEntityByUuid('file', $default_image['uuid'])) {
        // Clone the FieldItemList into a runtime-only object for the formatter,
        // so that the fallback image can be rendered without affecting the
        // field values in the entity being rendered.
        $items = clone $items;
        $items->setValue([
          'target_id' => $file->id(),
          'alt' => $default_image['alt'],
          'title' => $default_image['title'],
          'width' => $default_image['width'],
          'height' => $default_image['height'],
          'entity' => $file,
          '_loaded' => TRUE,
          '_is_default' => TRUE,
        ]);
      }
    }

    $this->photoswipeAssetManager->attach($elements);
    if (!empty($items) && count($items) > 1) {
      // If there are more than 1 elements, add the gallery wrapper.
      // Otherwise this is done in javascript for more flexibility.
      $elements['#prefix'] = '<div class="photoswipe-gallery">';
      $elements['#suffix'] = '</div>';
    }

    foreach ($items as $delta => $item) {
      // Check if the entity is an Entity (e.g. wasn't deleted) and the entity
      // is accessible:
      if ($item->entity instanceof EntityInterface && $item->entity->access('view')) {
        $elements[$delta] = [
          '#theme' => 'photoswipe_image_formatter',
          '#item' => $item,
          '#entity' => $items->getEntity(),
          '#display_settings' => $settings,
          '#delta' => $delta,
        ];
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $style_ids = [];
    $style_ids[] = $this->getSetting('photoswipe_node_style');
    if (!empty($this->getSetting('photoswipe_node_style_first'))) {
      $style_ids[] = $this->getSetting('photoswipe_node_style_first');
    }
    $style_ids[] = $this->getSetting('photoswipe_image_style');
    foreach ($style_ids as $style_id) {
      /** @var \Drupal\image\ImageStyleInterface $style */
      if ($style_id && $style = $this->imageStyleStorage->load($style_id)) {
        // If this formatter uses a valid image style to display the image, add
        // the image style configuration entity as dependency of this formatter.
        $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
      }
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);
    $style_ids = [];
    $style_ids['photoswipe_node_style'] = $this->getSetting('photoswipe_node_style');
    if (!empty($this->getSetting('photoswipe_node_style_first'))) {
      $style_ids['photoswipe_node_style_first'] = $this->getSetting('photoswipe_node_style_first');
    }
    $style_ids['photoswipe_image_style'] = $this->getSetting('photoswipe_image_style');
    foreach ($style_ids as $name => $style_id) {
      /** @var \Drupal\image\ImageStyleInterface $style */
      if (!empty($style_id) && $style = $this->imageStyleStorage->load($style_id)) {
        if (!empty($dependencies[$style->getConfigDependencyKey()][$style->getConfigDependencyName()])) {
          $replacement_id = $this->imageStyleStorage->getReplacementId($style_id);
          // If a valid replacement has been provided in the storage, replace
          // the image style with the replacement and signal that the formatter
          // plugin settings were updated.
          if ($replacement_id && $this->imageStyleStorage->load($replacement_id)) {
            $this->setSetting($name, $replacement_id);
            $changed = TRUE;
          }
        }
      }
    }
    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $field_type = $field_definition->getType();
    if ($field_type === 'image' ||
       ($field_type === 'entity_reference' && $field_definition->getSetting('target_type') === 'media')) {
      return TRUE;
    }
    return FALSE;
  }

}
