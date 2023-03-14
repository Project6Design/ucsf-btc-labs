<?php

namespace Drupal\ik_constant_contact\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ik_constant_contact\Service\ConstantContact;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines CustomFieldsController class.
 */
class CustomFieldsController extends ControllerBase {

  /**
   * Constructor function.
   *
   * @param \Drupal\ik_constant_contact\Service\ConstantContact $constantContact
   *   Constant contact service.
   */
  public function __construct(ConstantContact $constantContact) {
    $this->constantContact = $constantContact;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ik_constant_contact')
    );
  }

  /**
   * Display the markup.
   *
   * @return array
   *   Return markup array.
   */
  public function content() {
    $fields = $this->constantContact->getCustomFields(false);
    $header = ['Custom Field Name', 'Field Type', 'Custom Field ID'];
    $rows = [];

    if ($fields && count($fields->custom_fields) > 0)  {
      $markup = $this->t('Custom fields available for your account:') . '<ul>';
      foreach ($fields->custom_fields as $field) {
        $rows[] = [
          $this->t($field->label),
          $this->t($field->type),
          [
            'data' => [
              '#markup' => '<code>' . $field->custom_field_id . '</code>'
            ]
          ]
        ];
      }

      
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('There are no custom fields found.')
    ];
  }

}