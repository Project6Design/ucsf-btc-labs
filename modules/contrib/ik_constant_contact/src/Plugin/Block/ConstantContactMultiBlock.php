<?php

namespace Drupal\ik_constant_contact\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ik_constant_contact\Service\ConstantContact;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a constant contact signup form per list that is enabled.
 *
 * @Block(
 *   id = "ik_constant_contact_multi",
 *   admin_label = @Translation("Constant Contact Multiple Lists Signup Form"),
 * )
 */
class ConstantContactMultiBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Form\FormBuilderInterface.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Drupal\ik_constant_contact\Service\ConstantContact.
   *
   * @var \Drupal\ik_constant_contact\Service\ConstantContact
   *   Constant contact service.
   */
  protected $constantContact;


  /**
   * Drupal\Core\Config\ConfigFactoryInterface
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder, ConstantContact $constant_contact, ConfigFactoryInterface $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
    $this->constantContact = $constant_contact;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
      $container->get('ik_constant_contact'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $ccConfig = $this->constantContact->getConfig();
    $customFields = $this->constantContact->getCustomFields();
    $enabled = $this->config->get('ik_constant_contact.enabled_lists')->getRawData();

    $form['cc_fields'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add Form Fields from Constant Contact'),
      '#description' => $this->t('Select any <a href="https://v3.developer.constantcontact.com/api_guide/contacts_create_or_update.html#method-request-body" target="_blank" rel="nofollow noreferrer">available fields from Constant Contact</a> to add to the signup form.'),
    ];

    foreach ($ccConfig['fields'] as $fieldName => $fieldLabel) {
      $form['cc_fields']['field_' . $fieldName] = [
        '#type' => 'fieldset',
        '#title' => $this->t($fieldLabel),
      ];

      if ($fieldName === 'anniversary') {
        $form['cc_fields']['field_' . $fieldName]['#description'] = $this->t('Requires the <a href="https://www.drupal.org/docs/8/core/modules/datetime" target="_blank" rel="nofollow noreferrer">datetime</a> module to be installed.');
      }

      if ($fieldName === 'street_address') {
        foreach ($ccConfig['address_subfields'] as $subname => $sublabel) {
          $form['cc_fields']['field_' . $fieldName][$fieldName . '_' . $subname] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Display ' . $fieldLabel . ' ' . $sublabel . '?'),
            '#default_value' => isset($config[$fieldName . '_' . $subname]) ? $config[$fieldName . '_' . $subname] : 0,
          ];
          $form['cc_fields']['field_' . $fieldName][$fieldName . '_' . $subname . '_required'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Require ' . $fieldLabel . ' ' . $sublabel . ' ?'),
            '#default_value' => isset($config[$fieldName . '_' . $subname . '_required']) ? $config[$fieldName . '_' . $subname . '_required'] : 0,
          ];
        }
      } else {
        $form['cc_fields']['field_' . $fieldName][$fieldName] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Display ' . $fieldLabel . ' field?'),
          '#default_value' => isset($config[$fieldName]) ? $config[$fieldName] : 0,
        ];
        $form['cc_fields']['field_' . $fieldName][$fieldName . '_required'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Require ' . $fieldLabel . ' field?'),
          '#default_value' => isset($config[$fieldName . '_required']) ? $config[$fieldName . '_required'] : 0,
        ];
      }
    }

    // Custom Fields
    if ($customFields && $customFields->custom_fields && count($customFields->custom_fields) > 0) {
      $form['custom_fields'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Custom Fields'),
        '#description' => $this->t('Select any <a href="https://knowledgebase.constantcontact.com/articles/KnowledgeBase/33120-Create-and-Manage-Custom-Contact-Fields?lang=en_US" target="_blank" rel="nofollow noreferrer">custom fields from your Constant Contact account</a> to add to the signup form.'),
        '#tree' => true
      ];

      foreach ($customFields->custom_fields as $field) {
        $form['custom_fields'][$field->custom_field_id] = [
          '#type' => 'fieldset',
          '#title' => $this->t($field->label),
        ];
        $form['custom_fields'][$field->custom_field_id]['display'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Display ' . $field->label . ' field'),
          '#default_value' => isset($config['custom_fields'][$field->custom_field_id]['display']) ? $config['custom_fields'][$field->custom_field_id]['display'] : NULL,
        ];
        $form['custom_fields'][$field->custom_field_id]['required'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Require ' . $field->label . ' field'),
          '#default_value' => isset($config['custom_fields'][$field->custom_field_id]['required']) ? $config['custom_fields'][$field->custom_field_id]['required'] : NULL,
        ];
        $form['custom_fields'][$field->custom_field_id]['name'] = [
          '#type' => 'hidden',
          '#value' => $field->name,
        ];
        $form['custom_fields'][$field->custom_field_id]['type'] = [
          '#type' => 'hidden',
          '#value' => $field->type,
        ];
        $form['custom_fields'][$field->custom_field_id]['label'] = [
          '#type' => 'hidden',
          '#value' => $field->label,
        ];

        if ($field->type === 'date') {
          $form['custom_fields'][$field->custom_field_id]['#description'] = $this->t('Requires the <a href="https://www.drupal.org/docs/8/core/modules/datetime" target="_blank" rel="nofollow noreferrer">datetime</a> module to be installed.');
        }
      }
    }
    
    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#default_value' => isset($config['body']) ? $config['body']['value'] : NULL,
      '#format' => isset($config['format']) ? $config['body']['format'] : NULL,
    ];
    $form['success_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom Success Message'),
      '#default_value' => isset($config['success_message']) ? $config['success_message'] : NULL,
    ];

    $form['lists'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Your Constant Contact Lists'),
      '#description' => $this->t('Check the lists you would like to enable in this block. Note that if a list is missing, make sure it is enabled <a href="/admin/config/services/ik-constant-contact/lists" target="_blank">here</a>.'),
      '#default_value' => isset($config['lists']) ? $config['lists'] : NULL,
    ];

    if (isset($ccConfig['access_token']) && isset($ccConfig['refresh_token'])) {
      $lists = $this->constantContact->getContactLists();

      if ($lists && count($lists) > 0) {
        foreach ($lists as $list) {
          if (isset($enabled[$list->list_id]) && $enabled[$list->list_id] === 1) {
            $form['lists']['#options'][$list->list_id] = $list->name;
          }
        }
      } 
    } else {
      $form['lists']['#description'] = $this->t('You must authorize Constant Contact before enabling a list.');
    }

    $form['lists_select_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label for list checkboxes'),
      '#default_value' => isset($config['lists_select_label']) ? $config['lists_select_label'] : 'Sign me up for:',
    ];

    $form['lists_user_select'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow user to choose which lists from above?'),
      '#default_value' => isset($config['lists_user_select']) ? $config['lists_user_select'] : 0,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $ccFields = $values['cc_fields'];
    $customFields = $values['custom_fields'];

    foreach ($ccFields as $field => $vals) {
      foreach ($vals as $k => $v) {
        $this->configuration[$k] = $v;
      }
    }

    $this->configuration['custom_fields'] = $values['custom_fields'];
    $this->configuration['body'] = $values['body'];
    $this->configuration['success_message'] = $values['success_message'];
    $this->configuration['lists'] = $values['lists'];
    $this->configuration['lists_user_select'] = $values['lists_user_select'];
    $this->configuration['lists_select_label'] = $values['lists_select_label'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $listConfig = $this->getConfiguration();
    $listConfig['list_id'] = str_replace('ik_constant_contact:', '', $this->getPluginId());
    $listConfig['lists_all'] = $this->constantContact->getContactLists();

    return $this->formBuilder->getForm('Drupal\ik_constant_contact\Form\ConstantContactBlockForm', $listConfig);
  }

}
