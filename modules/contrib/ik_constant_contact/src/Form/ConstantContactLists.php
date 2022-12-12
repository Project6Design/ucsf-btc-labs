<?php

namespace Drupal\ik_constant_contact\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\ik_constant_contact\Service\ConstantContact;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConstantContactLists.
 *
 * Configuration form for enabling lists for use.
 * (ex: in either blocks or REST endpoints.)
 */
class ConstantContactLists extends ConfigFormBase {

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
   * ConstantContactLists constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Drupal\Core\Config\ConfigFactoryInterface.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Drupal\Core\Messenger\MessengerInterface.
   * @param \Drupal\ik_constant_contact\Service\ConstantContact $constantContact
   *   Constant contact service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MessengerInterface $messenger, ConstantContact $constantContact) {
    parent::__construct($config_factory);
    $this->messenger = $messenger;
    $this->constantContact = $constantContact;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('messenger'),
      $container->get('ik_constant_contact')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ik_constant_contact_lists';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ik_constant_contact.enabled_lists',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->constantContact->getConfig();
    $enabled = $this->config('ik_constant_contact.enabled_lists')->getRawData();

    $form['lists'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Your Constant Contact Lists'),
      '#description' => $this->t('Check the lists you would like to enable as a block or as a REST endpoint.'),
    ];

    if (isset($config['access_token']) && isset($config['refresh_token'])) {
      $lists = $this->constantContact->getContactLists();

      if ($lists && count($lists) > 0) {
        foreach ($lists as $list) {
          $form['lists'][$list->list_id] = [
            '#type' => 'checkbox',
            '#title' => $list->name,
            '#default_value' => isset($enabled[$list->list_id]) ? $enabled[$list->list_id] : NULL,
            '#description' => $this->t('List ID: @listID', ['@listID' => $list->list_id]),
          ];
        }
      }
    }
    else {
      $form['lists']['#descriptions'] = $this->t('You must authorize Constant Contact before enabling a list.');
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ik_constant_contact.enabled_lists');
    $config->clear('ik_constant_contact.enabled_lists');

    foreach ($form_state->getValues() as $key => $value) {
      if (is_int($value)) {
        $config->set($key, $value);
      }
    }

    $config->save();

    $this->messenger->addMessage($this->t('Your configuration has been saved'));
  }

}
