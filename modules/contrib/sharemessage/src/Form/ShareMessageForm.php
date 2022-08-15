<?php

namespace Drupal\sharemessage\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\sharemessage\SharePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form controller for Share Message edit forms.
 */
class ShareMessageForm extends EntityForm {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Share plugin manager.
   *
   * @var \Drupal\sharemessage\SharePluginManager
   */
  protected $sharePluginManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new ShareMessageForm object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * The module handler.
   * @param \Drupal\sharemessage\SharePluginManager $share_manager
   *   The share manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   */
  public function __construct(ModuleHandlerInterface $module_handler, SharePluginManager $share_manager, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->moduleHandler = $module_handler;
    $this->sharePluginManager = $share_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('plugin.manager.sharemessage.share'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\sharemessage\ShareMessageInterface $sharemessage */
    $sharemessage = $this->entity;
    $defaults = \Drupal::config('sharemessage.settings');
    $available = $this->sharePluginManager->getLabels();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#required' => TRUE,
      '#default_value' => $sharemessage->label(),
      '#weight' => -3,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => t('Machine Name'),
      '#machine_name' => [
        'exists' => 'sharemessage_check_machine_name_if_exist',
        'source' => ['label'],
      ],
      '#required' => TRUE,
      '#weight' => -2,
      '#disabled' => !$sharemessage->isNew(),
      '#default_value' => $sharemessage->id(),
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#default_value' => $sharemessage->title,
      '#description' => t('Used as title in the Share Message, where applicable: Facebook, E-Mail subject, ...'),
      '#weight' => 5,
    ];

    $form['message_long'] = [
      '#type' => 'textarea',
      '#title' => t('Long Description'),
      '#default_value' => $sharemessage->message_long,
      '#description' => t('Used as long description for the Share Message, where applicable: Facebook, Email body, ...'),
      '#weight' => 10,
    ];

    $form['message_short'] = [
      '#type' => 'textfield',
      '#title' => t('Short Description'),
      '#default_value' => $sharemessage->message_short,
      '#description' => t('Used as short description for twitter messages.'),
      '#weight' => 15,
    ];

    $form['video_url'] = [
      '#type' => 'textfield',
      '#title' => t('Video URL'),
      '#default_value' => $sharemessage->video_url,
      '#description' => t('The video URL that will be used for sharing.'),
      '#weight' => 18,
    ];

    $form['image_url'] = [
      '#type' => 'textfield',
      '#title' => t('Image URL'),
      '#default_value' => $sharemessage->image_url,
      '#description' => t('The image URL that will be used for sharing. If a video URL is set, the image is used as a thumbnail for the video.'),
      '#weight' => 20,
    ];

    $form['image_width'] = [
      '#type' => 'textfield',
      '#title' => t('Image Width'),
      '#default_value' => $sharemessage->image_width,
      '#description' => t('The width of the image that will be used for sharing.'),
      '#weight' => 21,
    ];

    $form['image_height'] = [
      '#type' => 'textfield',
      '#title' => t('Image Height'),
      '#default_value' => $sharemessage->image_height,
      '#description' => t('The height of the image that will be used for sharing.'),
      '#weight' => 22,
    ];

    // @todo: Convert this to a file upload/selection widget.
    $form['fallback_image'] = [
      '#type' => 'textfield',
      '#title' => t('Fallback image (File UUID)'),
      '#default_value' => $sharemessage->fallback_image,
      '#description' => t('Specify a static fallback image that is used if the Image URL is empty (For example, when tokens are used and the specified image field is empty).'),
      '#weight' => 23,
    ];

    $form['share_url'] = [
      '#type' => 'textfield',
      '#title' => t('Shared URL'),
      '#default_value' => $sharemessage->share_url,
      '#description' => t('Specific URL that will be shared, defaults to the current page.'),
      '#weight' => 25,
    ];

    // If the Share Message plugin is not set, pick AddThis plugin as the
    // default.
    if (!($sharemessage->hasPlugin())) {
      $sharemessage->setPluginID('addthis');
    }

    $form['plugin_wrapper'] = [
      '#type' => 'container',
      '#prefix' => '<div id="sharemessage-plugin-wrapper">',
      '#suffix' => '</div>',
    ];

    $definition = $this->sharePluginManager->getDefinition($sharemessage->getPluginID());
    if ($sharemessage->hasPlugin()) {
      $form['plugin_wrapper']['plugin'] = [
        '#type' => 'select',
        '#title' => t('Share Message plugin'),
        '#description' => isset($definition['description']) ? Xss::filter($definition['description']) : '',
        '#options' => $available,
        '#default_value' => $sharemessage->getPluginID(),
        '#required' => TRUE,
        '#ajax' => [
          'callback' => [$this, 'ajaxShareMessagePluginSelect'],
          'wrapper' => 'sharemessage-plugin-wrapper',
        ],
      ];
      $form['plugin_wrapper']['plugin_select'] = [
        '#type' => 'submit',
        '#value' => $this->t('Select plugin'),
        '#submit' => ['::ajaxShareMessagePluginSelect'],
        '#attributes' => ['class' => ['js-hide']],
      ];

      $form['plugin_wrapper']['settings'] = [
        '#type' => 'details',
        '#title' => t('@plugin plugin settings', ['@plugin' => $definition['label']]),
        '#tree' => TRUE,
        '#open' => TRUE,
      ];

      // Add the Share Message plugin settings form.
      $form['plugin_wrapper']['settings'] += $sharemessage->getPlugin()
        ->buildConfigurationForm($form['plugin_wrapper']['settings'], $form_state);
      if (!Element::children($form['plugin_wrapper']['settings'])) {
        // Settings fieldset.
        $form['plugin_wrapper']['settings']['override_default_settings'] = [
          '#type' => 'item',
          '#description' => t("The @plugin plugin doesn't provide any settings.", [
            '@plugin' => $sharemessage->getPluginDefinition()['label'],
          ]),
        ];
      }
    }

    if ($defaults->get('message_enforcement')) {
      $form['enforce_usage'] = [
        '#type' => 'checkbox',
        '#title' => t('Enforce the usage of this Share Message on the page it points to'),
        '#description' => t('If checked, this Share Message will be used on the page that it is referring to and override the Share Message there.'),
        '#default_value' => $sharemessage->enforce_usage ?: 0,
        '#weight' => 40,
      ];
    }

    // Define a form to expose a Share Message as an extra field for a given
    // entity type and its bundle(s).
    $form['sharemessage_extra_field'] = [
      '#type' => 'container',
      '#prefix' => '<div id="sharemessage-extra-field">',
      '#suffix' => '</div>',
      '#weight' => 50,
    ];

    // If the entity type ajax has been triggered, store the new entity type
    // used to display its bundles on the UI.
    $current_type = $sharemessage->getExtraFieldEntityType();
    if ($form_state->hasValue('entity_type') && ($form_state->getValue('entity_type') !== $current_type)) {
      $sharemessage->setExtraFieldEntityType($form_state->getValue('entity_type'));
      $current_type = $sharemessage->getExtraFieldEntityType();
    }

    // Get the entity types that have view builder.
    $extra_field_entity_type_options = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $definition) {
      if (($definition->getGroup() == 'content') && ($definition->hasHandlerClass('view_builder'))) {
        // Check whether entity type has any view displays.
        $view_display_ids = \Drupal::entityQuery('entity_view_display')
          ->condition('targetEntityType', $entity_type_id)
          ->count()
          ->execute();
        if ($view_display_ids) {
          $extra_field_entity_type_options[$entity_type_id] = $definition->getLabel();
        }
      }
    }

    // Get the bundles of the selected content entity type.
    $extra_field_bundle_options = [];
    if ($current_type) {
      $extra_field_bundle_options = $this->getBundles($current_type);
    }

    // Define the entity type select form.
    $form['sharemessage_extra_field']['entity_type'] = [
      '#type' => 'select',
      '#title' => t('Share Message extra field'),
      '#description' => t('Select an entity type, then check the content entities where you want to show this Share Message. This only displays entity types which have at least one view display configured. In case an entity type does not show up, go to "Manage display" and press "Save".'),
      '#options' => $extra_field_entity_type_options,
      '#empty_option' => t('- None -'),
      '#empty_value' => '',
      '#default_value' => $current_type,
      '#required' => FALSE,
      '#ajax' => [
        'callback' => [$this, 'ajaxShareMessageContentTypeSelect'],
        'wrapper' => 'sharemessage-extra-field',
      ],
    ];
    $form['sharemessage_extra_field']['entity_type_select'] = [
      '#type' => 'submit',
      '#value' => $this->t('Select entity type'),
      '#submit' => ['::ajaxShareMessageContentTypeSelect'],
      '#attributes' => ['class' => ['js-hide']],
    ];

    // Show the selected entity type's bundles, if there are any. Check the
    // ones that have been selected. Otherwise don't check any to allow all.
    if ($current_type) {
      $entity_type = $this->entityTypeManager->getDefinition($current_type);
      if ($entity_type->hasKey('bundle')) {
        // If all bundles have been allowed, don't select any.
        $enabled_all = array_diff(array_keys($extra_field_bundle_options), $sharemessage->getExtraFieldBundles());
        // Get the selected bundles.
        $extra_field_bundles = array_intersect($sharemessage->getExtraFieldBundles(), array_keys($extra_field_bundle_options));
        $form['sharemessage_extra_field']['bundles'] = [
          '#type' => 'checkboxes',
          '#title' => $this->getEntityBundleLabel($entity_type),
          '#description' => t("Per default this extra field is not visible. Go to the bundle's Manage display page to enable it. Select none to allow all @content_entity.", [
            '@content_entity' => strtolower($this->getEntityBundleLabel($entity_type))
          ]),
          '#options' => $extra_field_bundle_options,
          '#default_value' => empty($enabled_all) ? [] : $extra_field_bundles,
          '#tree' => TRUE,
          '#open' => TRUE,
        ];
      }
    }

    // Update sharemessage_token_help according to the selected entity type.
    if ($this->moduleHandler->moduleExists('token')) {
      // If '- None -' option is selected, show 'node' as default.
      $token_type = $current_type ? \Drupal::service('token.entity_mapper')->getTokenTypeForEntityType($current_type) : 'node';
      $form['sharemessage_extra_field']['sharemessage_token_help'] = [
        '#title' => t('Replacement patterns'),
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#description' => t('These tokens can be used in all text fields.'),
        '#weight' => 2,
      ];
      $form['sharemessage_extra_field']['sharemessage_token_help']['browser'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => [$token_type, 'sharemessage'],
      ];
    }

    return $form;
  }

  /**
   * Ajax callback to fetch the selected Share Message settings.
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function ajaxShareMessagePluginSelect(array $form, FormStateInterface $form_state) {
    return $form['plugin_wrapper'];
  }

  /**
   * Ajax callback to fetch the selected content type with view builder.
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function ajaxShareMessageContentTypeSelect(array $form, FormStateInterface $form_state) {
    return $form['sharemessage_extra_field'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\sharemessage\ShareMessageInterface $sharemessage */
    $sharemessage = parent::buildEntity($form, $form_state);
    if (!$sharemessage->getSetting('override_default_settings')) {
      $sharemessage->settings = [];
    }

    // Store the selected content entities where the Share Message will be
    // displayed as an extra field into the config entity. Otherwise unset
    // extra_field_bundles if no specific entity type has been selected.
    $sharemessage->setExtraFieldEntityType($form_state->getValue('entity_type'));
    $sharemessage->setExtraFieldBundles($form_state->hasValue('bundles') ? array_keys(array_filter($form_state->getValue('bundles'))) : []);

    // Move the override field into the settings array.
//    if (\Drupal::config('sharemessage.settings')->get('message_enforcement')) {
//      $sharemessage->settings['enforce_usage'] = $sharemessage->enforce_usage;
//      unset($sharemessage->enforce_usage);
//    }
    return $sharemessage;
  }

  /**
   * Provides the bundle label with a fallback when not defined.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type we are looking the bundle label for.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The entity bundle label or a fallback label.
   */
  protected function getEntityBundleLabel($entity_type) {
    if ($label = $entity_type->getBundleLabel()) {
      return $this->t('@label', ['@label' => $label]);
    }

    $fallback = $entity_type->getLabel();
    if ($bundle_entity_type = $entity_type->getBundleEntityType()) {
      // This is a better fallback.
      $fallback =  $this->entityTypeManager->getDefinition($bundle_entity_type)->getLabel();
    }
    return $this->t('@label bundle', ['@label' => $fallback]);
  }

  /**
   * Gets the bundles of the selected content entity type.
   *
   * @param string $entity_type
   *   The entity type where to retrieve the bundles.
   *
   * @return array
   *   An associative array of bundle's labels keyed by the bundle's ID.
   */
  protected function getBundles($entity_type) {
    $extra_field_bundle_options = [];
    foreach ($this->entityTypeBundleInfo->getBundleInfo($entity_type) as $bundle => $info) {
      $extra_field_bundle_options[$bundle] = $info['label'];
    }
    return $extra_field_bundle_options;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\sharemessage\ShareMessageInterface $sharemessage */
    $sharemessage = $this->entity;
    $sharemessage->getPlugin()->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\sharemessage\ShareMessageInterface $sharemessage */
    $sharemessage = $this->entity;
    $status = $sharemessage->save();

    if ($status == SAVED_UPDATED) {
      $this->messenger()->addMessage(t('Share Message %label has been updated.', ['%label' => $sharemessage->label()]));
    }
    else {
      $this->messenger()->addMessage(t('Share Message %label has been added.', ['%label' => $sharemessage->label()]));
    }
    // Share Message settings might have changed, but it is not immediately
    // updated for the view display. Thus clear the entity extra field caches.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    $form_state->setRedirect('entity.sharemessage.collection');
  }

}
