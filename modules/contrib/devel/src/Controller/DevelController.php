<?php

namespace Drupal\devel\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Field\WidgetPluginManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Theme\Registry;
use Drupal\Core\Url;
use Drupal\devel\DevelDumperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for devel module routes.
 */
class DevelController extends ControllerBase {

  /**
   * The dumper service.
   */
  protected DevelDumperManagerInterface $dumper;

  /**
   * The entity type bundle info service.
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * The field type plugin manager service.
   */
  protected FieldTypePluginManagerInterface $fieldTypeManager;

  /**
   * The field formatter plugin manager.
   */
  protected FormatterPluginManager $formatterPluginManager;

  /**
   * The field widget plugin manager.
   */
  protected WidgetPluginManager $widgetPluginManager;


  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The theme registry.
   */
  protected Registry $themeRegistry;

  /**
   * DevelController constructor.
   *
   * @param \Drupal\devel\DevelDumperManagerInterface $dumper
   *   The dumper service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type manager service.
   * @param \Drupal\Core\Field\FormatterPluginManager $formatter_plugin_manager
   *   The field formatter plugin manager.
   * @param \Drupal\Core\Field\WidgetPluginManager $widget_plugin_manager
   *   The field widget plugin manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   * @param \Drupal\Core\Theme\Registry $theme_registry
   *   The theme registry.
   */
  public function __construct(
    DevelDumperManagerInterface $dumper,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    FieldTypePluginManagerInterface $field_type_manager,
    FormatterPluginManager $formatter_plugin_manager,
    WidgetPluginManager $widget_plugin_manager,
    AccountInterface $current_user,
    TranslationInterface $string_translation,
    Registry $theme_registry
  ) {
    $this->dumper = $dumper;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->fieldTypeManager = $field_type_manager;
    $this->formatterPluginManager = $formatter_plugin_manager;
    $this->widgetPluginManager = $widget_plugin_manager;
    $this->currentUser = $current_user;
    $this->stringTranslation = $string_translation;
    $this->themeRegistry = $theme_registry;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('devel.dumper'),
      $container->get('entity_type.bundle.info'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('plugin.manager.field.formatter'),
      $container->get('plugin.manager.field.widget'),
      $container->get('current_user'),
      $container->get('string_translation'),
      $container->get('theme.registry'),
    );
  }

  /**
   * Clears all caches, then redirects to the previous page.
   */
  public function cacheClear() {
    drupal_flush_all_caches();

    // @todo Use DI for messenger once https://www.drupal.org/project/drupal/issues/2940148 is resolved.
    $this->messenger()->addMessage($this->t('Cache cleared.'));

    return $this->redirect('<front>');
  }

  /**
   * Theme registry.
   *
   * @return array
   *   The complete theme registry as renderable.
   */
  public function themeRegistry(): array {
    $hooks = $this->themeRegistry->get();
    ksort($hooks);
    return $this->dumper->exportAsRenderable($hooks);
  }

  /**
   * Builds the fields info overview page.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function fieldInfoPage() {
    $fields = $this->entityTypeManager->getStorage('field_storage_config')->loadMultiple();
    ksort($fields);
    $output['fields'] = $this->dumper->exportAsRenderable($fields, $this->t('Fields'));

    $field_instances = $this->entityTypeManager->getStorage('field_config')->loadMultiple();
    ksort($field_instances);
    $output['instances'] = $this->dumper->exportAsRenderable($field_instances, $this->t('Instances'));

    $bundles = $this->entityTypeBundleInfo->getAllBundleInfo();
    ksort($bundles);
    $output['bundles'] = $this->dumper->exportAsRenderable($bundles, $this->t('Bundles'));

    $field_types = $this->fieldTypeManager->getUiDefinitions();
    ksort($field_types);
    $output['field_types'] = $this->dumper->exportAsRenderable($field_types, $this->t('Field types'));

    $formatter_types = $this->formatterPluginManager->getDefinitions();
    ksort($formatter_types);
    $output['formatter_types'] = $this->dumper->exportAsRenderable($formatter_types, $this->t('Formatter types'));

    $widget_types = $this->widgetPluginManager->getDefinitions();
    ksort($widget_types);
    $output['widget_types'] = $this->dumper->exportAsRenderable($widget_types, $this->t('Widget types'));

    return $output;
  }

  /**
   * Builds the state variable overview page.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function stateSystemPage(): array {
    $can_edit = $this->currentUser->hasPermission('administer site configuration');

    $header = [
      'name' => $this->t('Name'),
      'value' => $this->t('Value'),
    ];

    if ($can_edit) {
      $header['edit'] = $this->t('Operations');
    }

    $rows = [];
    // State class doesn't have getAll method so we get all states from the
    // KeyValueStorage.
    foreach ($this->keyValue('state')->getAll() as $state_name => $state) {
      $rows[$state_name] = [
        'name' => [
          'data' => $state_name,
          'class' => 'table-filter-text-source',
        ],
        'value' => [
          'data' => $this->dumper->export($state),
        ],
      ];

      if ($can_edit) {
        $operations['edit'] = [
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('devel.system_state_edit', ['state_name' => $state_name]),
        ];
        $rows[$state_name]['edit'] = [
          'data' => ['#type' => 'operations', '#links' => $operations],
        ];
      }
    }

    $output['states'] = [
      '#type' => 'devel_table_filter',
      '#filter_label' => $this->t('Search'),
      '#filter_placeholder' => $this->t('Enter state name'),
      '#filter_title' => $this->t('Enter a part of the state name to filter by.'),
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No state variables found.'),
      '#attributes' => [
        'class' => ['devel-state-list'],
      ],
    ];

    return $output;
  }

  /**
   * Builds the session overview page.
   *
   * @return array
   *   Array of page elements to render.
   */
  public function session() {
    $output['description'] = [
      '#markup' => '<p>' . $this->t('Here are the contents of your $_SESSION variable.') . '</p>',
    ];
    $output['session'] = [
      '#type' => 'table',
      '#header' => [$this->t('Session name'), $this->t('Session ID')],
      '#rows' => [[session_name(), session_id()]],
      '#empty' => $this->t('No session available.'),
    ];
    $output['data'] = $this->dumper->exportAsRenderable($_SESSION);

    return $output;
  }

}
