<?php

namespace Drupal\ik_constant_contact\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ik_constant_contact\Service\ConstantContact;

/**
 * Provides a constant contact signup form per list that is enabled.
 *
 * @Block(
 *   id = "ik_constant_contact",
 *   admin_label = @Translation("Constant Contact Signup Form"),
 *   deriver = "Drupal\ik_constant_contact\Plugin\Derivative\ConstantContactBlockDerivative"
 * )
 */
class ConstantContactBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder, ConstantContact $constantContact) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
    $this->constantContact = $constantContact;
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
      $container->get('ik_constant_contact')
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $values = $form_state->getValues();
    $ccConfig = $this->constantContact->getConfig();

    foreach ($ccConfig['fields'] as $fieldName => $fieldLabel) {

      if ($fieldName === 'street_address') {
        foreach ($ccConfig['address_subfields'] as $subname => $sublabel) {
          $this->configuration[$fieldName . '_' . $subname] = $values['cc_fields']['field_' . $fieldName][$fieldName . '_' . $subname];
          $this->configuration[$fieldName . '_' . $subname . '_required'] = $values['cc_fields']['field_' . $fieldName][$fieldName . '_' . $subname . '_required'];
        }
      } else {
        if (isset($values['cc_fields']['field_' . $fieldName][$fieldName])) {
          $this->configuration[$fieldName] = $values['cc_fields']['field_' . $fieldName][$fieldName];
        }
  
        if (isset($values['cc_fields']['field_' . $fieldName][$fieldName . '_required'])) {
          $this->configuration[$fieldName . '_required'] = $values['cc_fields']['field_' . $fieldName][$fieldName . '_required'];
        }
      }
    }

    $this->configuration['custom_fields'] = $values['custom_fields'];
    $this->configuration['body'] = $values['body'];
    $this->configuration['success_message'] = $values['success_message'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $listConfig = $this->getConfiguration();
    $listConfig['list_id'] = str_replace('ik_constant_contact:', '', $this->getPluginId());

    return $this->formBuilder->getForm('Drupal\ik_constant_contact\Form\ConstantContactBlockForm', $listConfig);
  }

}
