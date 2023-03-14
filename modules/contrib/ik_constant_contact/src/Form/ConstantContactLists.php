<?php

namespace Drupal\ik_constant_contact\Form;

use Drupal\ik_constant_contact\Service\ConstantContact;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
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

    $header = [
      'name' => $this->t('List Name'),
      'list_id' => $this->t('List UUID'),
    ];

    $output = $defaultValue = [];

    if (isset($config['access_token']) && isset($config['refresh_token'])) {
      $lists = $this->constantContact->getContactLists();

      if ($lists && count($lists) > 0) {
        foreach ($lists as $list) {
          $output[$list->list_id] = [
            'name' => $list->name,
            'list_id' => $list->list_id,
          ];

          $defaultValue[$list->list_id] = isset($enabled[$list->list_id]) && $enabled[$list->list_id] === 1 ? $list->list_id : NULL;
        }
      }
    }

    $form['lists'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $output,
      '#default_value' => $defaultValue,
      '#empty' => $this->t('You must authorize Constant Contact before enabling a list or there are no lists available on your account.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ik_constant_contact.enabled_lists');
    $config->clear('ik_constant_contact.enabled_lists');
    $enabled = $form_state->getValues()['lists'];
    $lists = $this->constantContact->getContactLists();

    foreach ($enabled as $key => $value) {
      $config->set($key, ($value === 0 ? 0 : 1));
    }

    $config->save();

    $this->constantContact->saveContactLists($lists);

    $this->messenger->addMessage($this->t('Your configuration has been saved'));
  }

}
