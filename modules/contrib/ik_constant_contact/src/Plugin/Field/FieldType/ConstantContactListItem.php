<?php

namespace Drupal\ik_constant_contact\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\options\Plugin\Field\FieldType\ListStringItem;

/**
 * Provides a field type for Constant Contact Lists.
 * 
 * @FieldType(
 *   id = "constant_contact_lists",
 *   label = @Translation("Constant Contact Lists"),
 *   category = "constant_contact",
 *   default_widget = "constant_contact_lists_default",
 *   default_formatter = "constant_contact_lists_formatter",
 *   description = @Translation("All entity to select a Constant Contact list(s) to subscribe to."),
 * )
 */

 class ConstantContactListItem extends FieldItemBase implements OptionsProviderInterface {

  /**
   * Instantiate our service.
   * Doesn'ts eem to be able to inject in FieldType
   * @see https://drupal.stackexchange.com/questions/224247/how-do-i-inject-a-dependency-into-a-fieldtype-plugin
   *
   * @return Drupal\ik_constant_contact\Service\ConstantContact;
   */
  private function constantContact() {
    return \Drupal::service('ik_constant_contact');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    $cc = \Drupal::service('ik_constant_contact');
    $ccConfig = $cc->getConfig();
    $ccFields = $ccConfig['fields'];

    $settings = [
      'enabled_lists_only' => TRUE,
      'subscribe_on_save' => FALSE,
      'unsubscribe_on_delete' => FALSE,
      'field_mapping' => [
        'email_address' => NULL
      ]
    ] + parent::defaultStorageSettings();


    foreach ($ccFields as $ccKey => $ccField) {
      $settings['field_mapping'][$ccField] = NULL;
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
      'indexes' => [
        'value' => ['value'],
      ],
    ];
  }

   /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('List UUID'))
      ->addConstraint('Length', ['max' => 255]);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = parent::storageSettingsForm($form, $form_state, false);
    $entityType = $this->getEntity();
    $entityFields = $entityType->getFields();
    $cc = $this->constantContact();
    $lists = $cc->getEnabledContactLists(false);


    $element['subscribe_on_save'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Subscribe to the selected lists on entity save/create. Will require an email field to map the contact to.'),
      '#default_value' => $this->getSetting('subscribe_on_save')
    ];

    $element['unsubscribe_on_delete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unsubscribe to the selected lists on entity delete.'),
      '#description' => $this->t('Checking this will unsubscribe the contact from all lists upon entity delete. Will require an email field to map the contact to.'),
      '#default_value' => $this->getSetting('unsubscribe_on_delete'),
      '#states' => [
        'visible' => [':input[name="settings[subscribe_on_save]"]' => ['checked' => TRUE]]
      ]
    ];

    $element['field_mapping'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Entity Field Mapping to Constant Contact Fields'),
      '#tree' => TRUE,
      '#states' => [
        'visible' => [':input[name="settings[subscribe_on_save]"]' => ['checked' => TRUE]]
      ]
    ];

    $element['field_mapping']['email_address'] = [
      '#type' => 'select',
      '#title' => $this->t('Email'),
      '#default_value' => isset($this->getSetting('field_mapping')['email_address']) ? $this->getSetting('field_mapping')['email_address'] : NULL,
      '#description' => $this->t('Requires a field of type <strong>email</strong>'),
      '#states' => [
        'required' => [':input[name="settings[subscribe_on_save]"]' => ['checked' => TRUE]]
      ]
    ];

    $ccConfig = $cc->getConfig();
    $ccFields = $ccConfig['fields'];

    foreach ($ccFields as $ccKey => $ccField) {
      $element['field_mapping'][$ccKey] = [
        '#type' => 'select',
        '#title' => $ccField,
        '#default_value' => isset($this->getSetting('field_mapping')[$ccKey]) ? $this->getSetting('field_mapping')[$ccKey] : NULL,
        '#options' => ['' => 'Do not map this field']
      ];
    }

    $element['field_mapping']['street_address']['#description'] = $this->t('Requires a field of type <strong>address</strong>');


    // Add field mapping options
    // @TODO - what to do if field is deleted.
    foreach ($entityFields as $fieldName => $fieldItemList) {
      $fieldDefinition = $fieldItemList->getFieldDefinition();
      $fieldLabel = $fieldDefinition->getLabel();
      $fieldType = $fieldDefinition->getType();

      if ($fieldType === 'email') {
        $element['field_mapping']['email_address']['#options'][$fieldName] = $fieldLabel;
      } else if ($fieldType === 'address') {
        $element['field_mapping']['street_address']['#options'][$fieldName] = $fieldLabel;
      } else if ($fieldType === 'datetime') {
        $element['field_mapping']['birthday']['#options'][$fieldName] = $fieldLabel;
        $element['field_mapping']['anniversary']['#options'][$fieldName] = $fieldLabel;
      } else if (in_array($fieldType, ['string'])) {
        foreach ($ccFields as $ccKey => $ccField) {
          if ($ccKey !== 'street_address') {
            $element['field_mapping'][$ccKey]['#options'][$fieldName] = $fieldLabel;
          }
        }
      }
    }

    if (count($lists) === 0) {
      \Drupal::service('messenger')->addError(t('You must enable at least one mailing list for this field to work. <a href="/admin/config/services/ik-constant-contact/lists">View available lists.</a>') );
    }

    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    return $this->getSettableValues($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    return $this->getSettableOptions($account);
  }


  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    // Flatten options first, because "settable options" may contain group
    // arrays.
    $flatten_options = OptGroup::flattenOptions($this->getSettableOptions($account));
    return array_keys($flatten_options);
  }

  public function getSettableOptions(AccountInterface $account = NULL) {
    $cc = $this->constantContact();
    $options = [];

    $lists = $cc->getEnabledContactLists(false);

    foreach ($lists as $id => $list) {
      $options[$id] = $list->name;
    }
    
    return $options;
  }
}