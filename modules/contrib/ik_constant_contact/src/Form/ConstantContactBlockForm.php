<?php

namespace Drupal\ik_constant_contact\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\ik_constant_contact\Service\ConstantContact;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class ConstantContactBlockForm.
 *
 * Creates a form for block on frontend to post
 * contact info and send to Constant Contact.
 */
class ConstantContactBlockForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  protected $formIdentifier;

  /**
   * Drupal\Core\Messenger\MessengerInterface.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   *   Messenger Interface.
   */
  protected $messenger;

  /**
   * Drupal\ik_constant_contact\Service\ConstantContact.
   *
   * @var \Drupal\ik_constant_contact\Service\ConstantContact
   *   Constant contact service.
   */
  protected $constantContact;

  /**
   * \Drupal\Core\Extension\ModuleHandlerInterface
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   *   Module handler interface
   */
  protected $moduleHandler;

  /**
   * ConstantContactBlockForm constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   MessengerInterface.
   * @param \Drupal\ik_constant_contact\Service\ConstantContact $constantContact
   *   Constant contact service.
   */
  public function __construct(MessengerInterface $messenger, ConstantContact $constantContact, ModuleHandlerInterface $moduleHandler) {
    $this->messenger = $messenger;
    $this->constantContact = $constantContact;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('ik_constant_contact'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setFormIdentifier($formIdentifier) {
    $this->formIdentifier = $formIdentifier;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $form_id = 'ik_constant_contact_sigup_form';
    if ($this->formIdentifier) {
      $form_id .= '-' . $this->formIdentifier;
    }

    return $form_id;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $listConfig = []) {
    // Don't show anything if we don't have a list_id set.
    if (!isset($listConfig['list_id'])) {
      return NULL;
    }

    if (isset($listConfig['success_message']) && $listConfig['success_message']) {
      $form_state->set('success_message', $listConfig['success_message']);
    }

    if (isset($listConfig['body']) && isset($listConfig['body']['value'])) {
      $form['body'] = [
        '#markup' => $listConfig['body']['value'],
      ];
    }

    if (isset($listConfig['first_name']) && $listConfig['first_name'] === 1) {
      $form['first_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('First Name'),
        '#required' => isset($listConfig['first_name_required']) && $listConfig['first_name_required'] === 1,
      ];
    }

    if (isset($listConfig['last_name']) && $listConfig['last_name'] === 1) {
      $form['last_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Last Name'),
        '#required' => isset($listConfig['last_name_required']) && $listConfig['last_name_required'] === 1,
      ];
    }

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#required' => TRUE,
    ];

    $otherFields = $this->constantContact->getConfig()['fields'];

    foreach ($otherFields as $fieldName => $fieldLabel) {
      if (!in_array($fieldName, ['first_name', 'last_name', 'street_address', 'birthday', 'anniversary'])) {
        if (isset($listConfig[$fieldName]) && $listConfig[$fieldName] === 1) {
          $form[$fieldName] = [
            '#type' => 'textfield',
            '#title' => $this->t($fieldLabel),
            '#required' => isset($listConfig[$fieldName . '_required']) && $listConfig[$fieldName . '_required'] === 1,
          ];
        }
      } else if ($fieldName === 'street_address') {
        $addressFields = $this->constantContact->getConfig()['address_subfields'];
        $addressElements = [];

        foreach ($addressFields as $subfield => $subLabel) {
          if (isset($listConfig[$fieldName . '_' . $subfield]) && $listConfig[$fieldName . '_' . $subfield] === 1) {
            $addressElements[$subfield] = [
              '#type' => 'textfield',
              '#title' => $this->t($subLabel),
              '#required' => isset($listConfig[$fieldName . '_' . $subfield . '_required']) && $listConfig[$fieldName . '_' . $subfield . '_required'] === 1
            ];
          }
        }

        if (count($addressElements) > 0) {
          $form['street_address'] = $addressElements;
          $form['street_address']['#type'] = 'fieldset';
          $form['street_address']['#title'] = $this->t($fieldLabel);
          $form['#tree'] = true;
        }
      
      } else if ($fieldName === 'birthday') {
        if (isset($listConfig[$fieldName]) && $listConfig[$fieldName] === 1) {
          $form[$fieldName] = [
            '#type' => 'fieldset',
            '#title' => $this->t($fieldLabel),
            '#tree' => true,
            'month' => [
              '#type' => 'select',
              '#title' => $this->t('Month'),
              '#options' => ['', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'],
              '#required' => isset($listConfig[$fieldName . '_required']) && $listConfig[$fieldName . '_required'] === 1,
            ],
            'day' => [
              '#type' => 'select',
              '#title' => $this->t('Day'),
              '#options' => ['', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31'],
              '#required' => isset($listConfig[$fieldName . '_required']) && $listConfig[$fieldName . '_required'] === 1,
            ],
          ];
        }
      } else if ($fieldName === 'anniversary' && $this->moduleHandler->moduleExists('datetime')) {
        if (isset($listConfig[$fieldName]) && $listConfig[$fieldName] === 1) {
          $form[$fieldName] = [
            '#type' => 'date',
            '#title' => $this->t($fieldLabel),
            '#required' => isset($listConfig[$fieldName . '_required']) && $listConfig[$fieldName . '_required'] === 1,
          ];
        }
      }
    }

    // Custom Fields
    if (isset($listConfig['custom_fields']) && count($listConfig['custom_fields']) > 0) {
      foreach ($listConfig['custom_fields'] as $id => $values) {
        if ($values['display'] === 1) {
          if ($values['type'] === 'date' && $this->moduleHandler->moduleExists('datetime')) {
            $form['custom_field__' . $id] = [
              '#type' => 'date',
              '#title' => $this->t($values['label']),
              '#required' => $values['required'] === 1,
            ];
          } else if ($values['type'] === 'string') {
            $form['custom_field__' . $id] = [
              '#type' => 'textfield',
              '#title' => $this->t($values['label']),
              '#required' => $values['required'] === 1,
            ];
          }
        }
      }
    }

    // Add our list_id into the form.
    if ($listConfig['list_id'] === 'ik_constant_contact_multi') {
      $list_ids = array_filter(array_values($listConfig['lists']));
      $options = [];

      // Need at least one list_id
      if (count($list_ids) === 0) {
        return null;
      }

      foreach ($list_ids as $id) {
        if (isset($listConfig['lists_all'][$id])) {
          $options[$id] = $listConfig['lists_all'][$id]->name;
        }
      }

      if ($listConfig['lists_user_select'] === 1) {
        $form['list_id'] = [
          '#type' =>  'checkboxes',
          '#title' => isset($listConfig['lists_select_label']) ? $listConfig['lists_select_label'] : $this->t('Sign me up for:'),
          '#required' => true,
          '#options' => $options
        ];
      } else {
        $form['list_id'] = [
          '#type' => 'value',
          '#value' => $list_ids,
        ];
      }

      
    } else {
      $form['list_id'] = [
        '#type' => 'value',
        '#value' => $listConfig['list_id'],
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sign Up'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $message_type = 'status';

    $otherFields = $this->constantContact->getConfig()['fields'];

    $data = [
      'email_address' => $values['email'],
    ];

    foreach ($otherFields as $field => $label) {
      if (isset($values[$field]) && $values[$field]) {
        $data[$field] = $values[$field];
      }
    }


    // Add custom field values.
    // Skip adding it if there's no value.
    $fieldKeys = array_keys($values);
    foreach ($fieldKeys as $field) {
      if (strpos($field, 'custom_field__') !== false && isset($values[$field]) && $values[$field]) {
        $data['custom_fields'][str_replace('custom_field__', '', $field)] = $values[$field];
      }
    }

    $lists = [];

    if (is_string($values['list_id'])) {
      $lists = [$values['list_id']];
    } else {
      $lists = array_filter(array_values($values['list_id']));
    }

    $response = $this->constantContact->submitContactForm($data, $lists);

    if (isset($response['error'])) {
      $message = 'There was a problem signing you up. Please try again later.';
      $message_type = 'error';
    }
    else {
      if ($form_state->get('success_message')) {
        $message = $form_state->get('success_message');
      }
      else {
        $message = $this->t('You have been signed up. Thank you.');
      }
    }

    $this->messenger->addMessage(strip_tags($message), $message_type);
  }

}
