<?php

namespace Drupal\photoswipe_svg_image_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Template\Attribute;
use Drupal\photoswipe\PhotoswipeAssetsManagerInterface;
use Drupal\svg_image_field\Plugin\Field\FieldFormatter\SvgImageFieldFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'photoswipe_svg_image_field_formatter'.
 *
 * Renders the SVG exactly like the "SVG Image Field formatter" and opens the
 * sanitized SVG in the Photoswipe layer. An image opens the layer when it is
 * clicked. An inline SVG gets a small "enlarge" button in its corner instead,
 * as it can contain links itself.
 *
 * @FieldFormatter(
 *   id = "photoswipe_svg_image_field_formatter",
 *   label = @Translation("Photoswipe SVG Image Field formatter"),
 *   field_types = {
 *     "svg_image_field"
 *   }
 * )
 */
class PhotoswipeSvgImageFieldFormatter extends SvgImageFieldFormatter {

  /**
   * The assets manager.
   *
   * @var \Drupal\photoswipe\PhotoswipeAssetsManagerInterface
   */
  protected $photoswipeAssetManager;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'photoswipe_remove_gallery_wrapper_class' => FALSE,
      'photoswipe_show_download_button' => FALSE,
      'image_loading' => [
        'attribute' => 'lazy',
      ],
    ] + parent::defaultSettings();
  }

  /**
   * Constructs a PhotoswipeSvgImageFieldFormatter object.
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
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\photoswipe\PhotoswipeAssetsManagerInterface $assets_manager
   *   The assets manager.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    LoggerChannelFactoryInterface $logger,
    ModuleHandlerInterface $module_handler,
    FileUrlGeneratorInterface $file_url_generator,
    ConfigFactoryInterface $config_factory,
    EntityRepositoryInterface $entity_repository,
    PhotoswipeAssetsManagerInterface $assets_manager,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $logger, $module_handler, $file_url_generator, $config_factory, $entity_repository);
    $this->photoswipeAssetManager = $assets_manager;
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
      $container->get('logger.factory'),
      $container->get('module_handler'),
      $container->get('file_url_generator'),
      $container->get('config.factory'),
      $container->get('entity.repository'),
      $container->get('photoswipe.assets_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    // The SVG can not be wrapped in another link, as this would nest the
    // links with the Photoswipe trigger and with the links inside of inline
    // SVGs.
    unset($element['link']);

    $imageLoading = $this->getSetting('image_loading');
    $element['image_loading'] = [
      '#type' => 'details',
      '#title' => $this->t('Image loading'),
      '#weight' => 10,
      '#description' => $this->t('Lazy render images with native image loading attribute (<em>loading="lazy"</em>). This improves performance by allowing browsers to lazily load images. Not applicable to SVGs which are output inline.'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings_edit_form][settings][inline]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $element['image_loading']['attribute'] = [
      '#title' => $this->t('Image loading attribute'),
      '#type' => 'radios',
      '#default_value' => $imageLoading['attribute'],
      '#options' => [
        'lazy' => $this->t('Lazy (<em>loading="lazy"</em>)'),
        'eager' => $this->t('Eager (<em>loading="eager"</em>)'),
      ],
      '#description' => $this->t('Select the loading attribute for images. <a href=":link">Learn more about the loading attribute for images.</a>', [
        ':link' => 'https://html.spec.whatwg.org/multipage/urls-and-fetching.html#lazy-loading-attributes',
      ]),
    ];

    $element['photoswipe_remove_gallery_wrapper_class'] = [
      '#title' => $this->t('Remove photoswipe-gallery wrapper class'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('photoswipe_remove_gallery_wrapper_class'),
      '#description' => $this->t('Advanced: Removes the default "photoswipe-gallery" wrapper class. This is useful to combine multiple fields into a single gallery by wrapping them in a custom parent container with the "photoswipe-gallery" class, e.g. through Views or Fences.'),
    ];
    $element['photoswipe_show_download_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show download button'),
      '#default_value' => $this->getSetting('photoswipe_show_download_button'),
      // @todo This might change in https://www.drupal.org/project/photoswipe/issues/3635489:
      '#description' => $this->t('Display a download button in the PhotoSwipe toolbar. Note, that the downloaded file is always sanitized regardless of the "Sanitize SVG code" setting.'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings][photoswipe_remove_gallery_wrapper_class]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if (empty($this->getSetting('inline'))) {
      $imageLoading = $this->getSetting('image_loading');
      $summary[] = $this->t('Image loading: @attribute', [
        '@attribute' => $imageLoading['attribute'],
      ]);
    }

    $removeWrapperClassStatus = $this->getSetting('photoswipe_remove_gallery_wrapper_class') ? $this->t('Yes') : $this->t('No');
    $summary[] = $this->t('Remove photoswipe-gallery wrapper class: %status', ['%status' => $removeWrapperClassStatus]);

    $showDownloadButton = $this->getSetting('photoswipe_show_download_button') ? $this->t('Yes') : $this->t('No');
    $summary[] = $this->t('Show download button: %status', ['%status' => $showDownloadButton]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::view($items, $langcode);
    if (empty($elements['#attributes'])) {
      $elements['#attributes'] = new Attribute();
    }
    if (!$this->getSetting('photoswipe_remove_gallery_wrapper_class')) {
      $elements['#attributes']->addClass('photoswipe-gallery');
      if ($this->getSetting('photoswipe_show_download_button')) {
        $elements['#attributes']->setAttribute('data-show-download-button', 'true');
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $imageLoading = $this->getSetting('image_loading');
    $elements = [];

    // The parent renders the SVG (this also includes sanitization when
    // checked):
    foreach (parent::viewElements($items, $langcode) as $delta => $svgElement) {
      $item = $items->get($delta);
      if (empty($item->entity) || !$item->entity->access('view')) {
        continue;
      }

      if (empty($svgElement['#inline'])) {
        $svgElement['#attributes'] += $item->_attributes ?: [];
        $svgElement['#attributes']['loading'] = $imageLoading['attribute'];
      }

      $elements[$delta] = [
        '#theme' => 'photoswipe_svg_image_field_formatter',
        '#svg' => $svgElement,
        '#inline' => !empty($svgElement['#inline']),
        '#item' => $item,
        '#entity' => $items->getEntity(),
        '#display_settings' => $this->getSettings(),
        '#third_party_settings' => $this->getThirdPartySettings(),
        '#delta' => $delta,
      ];
      $this->photoswipeAssetManager->attach($elements[$delta]);

      // Add the corner icon only when in inline mode:
      if (!empty($svgElement['#inline'])) {
        $elements[$delta]['#attached']['library'][] = 'photoswipe_svg_image_field/formatter';
      }
    }
    return $elements;
  }

}
