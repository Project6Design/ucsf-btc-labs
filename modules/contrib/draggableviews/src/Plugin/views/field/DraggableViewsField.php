<?php

namespace Drupal\draggableviews\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\draggableviews\DraggableViews;
use Drupal\views\Plugin\views\field\BulkForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Defines a draggableviews form element.
 *
 * @ViewsField("draggable_views_field")
 */
class DraggableViewsField extends BulkForm {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The action storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $actionStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a DraggableViewsField object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $entity_type_manager, $language_manager, $messenger, $entity_repository, $route_match, AccountProxyInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $language_manager, $messenger, $entity_repository, $route_match);
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $current_user = $container->get('current_user');
    if (!$current_user instanceof AccountProxyInterface) {
      $current_user = \Drupal::currentUser();
    }
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('messenger'),
      $container->get('entity.repository'),
      $container->get('current_route_match'),
      $current_user
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['draggable_views_hierarchy'] = ['default' => 0];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['draggable_views_help'] = [
      '#markup' => $this->t("A draggable element will be added to the first table column. You do not have to set this field as the first column in your View."),
    ];

    $form['draggable_views_hierarchy'] = [
      '#title' => $this->t('Enable hierarchy'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['draggable_views_hierarchy'],
      '#weight' => -1,
    ];

    parent::buildOptionsForm($form, $form_state);
    // Remove all the fields that would break this or are completely ignored
    // when rendering the drag interface.
    $form['custom_label']['#access'] = FALSE;
    $form['label']['#access'] = FALSE;
    $form['element_label_colon']['#access'] = FALSE;
    $form['action_title']['#access'] = FALSE;
    $form['include_exclude']['#access'] = FALSE;
    $form['selected_actions']['#access'] = FALSE;
    $form['exclude']['#access'] = FALSE;
    $form['alter']['#access'] = FALSE;
    $form['empty_field_behavior']['#access'] = FALSE;
    $form['empty']['#access'] = FALSE;
    $form['empty_zero']['#access'] = FALSE;
    $form['hide_empty']['#access'] = FALSE;
    $form['hide_alter_empty']['#access'] = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  // @codingStandardsIgnoreStart
  public function render_item($count, $item) {
    // @codingStandardsIgnoreEnd
    // Using internal method. @todo Recheck after drupal stable release.
    return Markup::create('<!--form-item-' . $this->options['id'] . '--' . $this->view->row_index . '-->');
  }

  /**
   * {@inheritdoc}
   */
  public function viewsForm(&$form, FormStateInterface $form_state) {
    $form[$this->options['id']] = [
      '#tree' => TRUE,
    ];

    $draggableviews = new DraggableViews($this->view);
    foreach ($this->view->result as $row_index => $row) {
      $entity = $this->getEntity($row);
      if (empty($entity) || !is_object($entity)) {
        continue;
      }
      $form[$this->options['id']][$row_index] = [
        '#tree' => TRUE,
      ];
      // Native handler: get weight from draggableviews structure table.
      $weight = property_exists($row, 'draggableviews_structure_weight') ? $row->draggableviews_structure_weight : '';
      $form[$this->options['id']][$row_index]['weight'] = [
        '#type' => 'textfield',
        '#size' => '5',
        '#maxlength' => '5',
        '#value' => $weight,
        '#attributes' => ['class' => ['draggableviews-weight']],
      ];
      // Item to keep id of the entity.
      $form[$this->options['id']][$row_index]['id'] = [
        '#type' => 'hidden',
        '#value' => $entity->id(),
        '#attributes' => ['class' => ['draggableviews-id']],
      ];
      // Add parent.
      $form[$this->options['id']][$row_index]['parent'] = [
        '#type' => 'hidden',
        '#default_value' => $draggableviews->getParent($row_index),
        '#attributes' => ['class' => ['draggableviews-parent']],
      ];
    }

    if ($this->currentUser->hasPermission('access draggableviews')) {
      // Get an array of field group titles.
      $fieldGrouping = $draggableviews->fieldGrouping();
      foreach ($fieldGrouping as $key => $row) {
        $options = [
          'table_id' => $draggableviews->getHtmlId($key),
          'action' => 'match',
          'relationship' => $this->options['draggable_views_hierarchy'] === 1 ? 'parent' : 'sibling',
          'group' => 'draggableviews-parent',
          'subgroup' => 'draggableviews-parent',
          'source' => 'draggableviews-id',
        ];
        drupal_attach_tabledrag($form, $options);
      }
    }
  }

  /**
   * Sets the current_user service.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current user.
   *
   * @return $this
   */
  public function setCurrentUser(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
    return $this;
  }

}
