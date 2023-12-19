<?php

namespace Drupal\ik_constant_contact\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Drupal\ik_constant_contact\Service\ConstantContact;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Form submission to Constant Contact handler.
 *
 * @WebformHandler(
 *   id = "constant_contact",
 *   label = @Translation("Constant Contact"),
 *   category = @Translation("Constant Contact"),
 *   description = @Translation("Sends a form submission to a Constant Contact list."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class WebformConstantContactHandler extends WebformHandlerBase {

    /**
   * Drupal\Core\Cache\CacheBackendInterface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   *   Drupal cache.
   */
  protected $cache;

    /**
   * Drupal\ik_constant_contact\Service\ConstantContact.
   *
   * @var \Drupal\ik_constant_contact\Service\ConstantContact
   *   Constant contact service.
   */
  protected $constantContact;

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $token_manager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->setCacheManager($container->get('cache.default'));
    $instance->setTokenManager($container->get('webform.token_manager'));
    $instance->setConstantContact($container->get('ik_constant_contact'));
    return $instance;
  }

  /**
   * Set Cache dependency
   */
  protected function setCacheManager(CacheBackendInterface $cache) {
    $this->cache = $cache;
  }
  /**
   * Set Token Manager dependency
   */
  protected function setTokenManager(WebformTokenManagerInterface $token_manager) {
    $this->tokenManager = $token_manager;
  }

  /**
   * Set Constant Contact dependency
   */
  protected function setConstantContact(ConstantContact $constantContact) {
    $this->constantContact = $constantContact;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $fields = $this->getWebform()->getElementsInitializedAndFlattened();
    $lists = $this->constantContact->getContactLists();

    $email_summary = $this->configuration['email'];
    if (!empty($fields[$this->configuration['email']])) {
      $email_summary = $fields[$this->configuration['email']]['#title'];
    }
    $email_summary = '<strong>' . $this->t('Email') . ': </strong>' . $email_summary;


    $list_summary = $this->configuration['list'];
    if (!empty($lists[$this->configuration['list']])) {
      $list_summary = $lists[$this->configuration['list']]->name;
    }
    $list_summary = '<strong>' . $this->t('List') . ': </strong>' . $list_summary;

    $markup = "$email_summary<br/>$list_summary";
    return [
      '#markup' => $markup,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'list' => '',
      'email' => '',
      'mergevars' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $enabled = $this->configFactory->get('ik_constant_contact.enabled_lists')->getRawData();
    $lists = $this->constantContact->getContactLists();

    $options = [];
    foreach ($lists as $list) {
      if ($enabled[$list->list_id] == 1) {
        $options[$list->list_id] = $list->name;
      }
    }

    $form['constant_contact'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Constant Contact settings'),
      '#attributes' => ['id' => 'webform-constant-contact-handler-settings'],
    ];

    $form['constant_contact']['update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Refresh lists & groups'),
      '#ajax' => [
        'callback' => [$this, 'ajaxConstantContactListHandler'],
        'wrapper' => 'webform-constant-contact-handler-settings',
      ],
      '#submit' => [[get_class($this), 'constantcontactUpdateConfigSubmit']],
    ];

    $form['constant_contact']['list'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('List'),
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select an option -'),
      '#default_value' => $this->configuration['list'],
      '#options' => $options,
      '#ajax' => [
        'callback' => [$this, 'ajaxConstantContactListHandler'],
        'wrapper' => 'webform-constant-contact-handler-settings',
      ],
      '#description' => $this->t('Select the list you want to send this submission to. Alternatively, you can also use the Other field for token replacement.'),
    ];

    $fields = $this->getWebform()->getElementsInitializedAndFlattened();
    $options = [];
    foreach ($fields as $field_name => $field) {
      if (in_array($field['#type'], ['email', 'webform_email_confirm'])) {
        $options[$field_name] = $field['#title'];
      }
    }

    $default_value = $this->configuration['email'];
    if (empty($this->configuration['email']) && count($options) == 1) {
      $default_value = key($options);
    }
    $form['constant_contact']['email'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Email field'),
      '#required' => TRUE,
      '#default_value' => $default_value,
      '#options' => $options,
      '#empty_option'=> $this->t('- Select an option -'),
      '#description' => $this->t('Select the email element you want to use for subscribing to the Constant Contact list specified above. Alternatively, you can also use the Other field for token replacement.'),
    ];

    $form['constant_contact']['mergevars'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Merge vars'),
      '#default_value' => $this->configuration['mergevars'],
      '#description' => $this->t('You can map additional fields from your webform to fields in your Constant Contact list, one per line. An example might be first_name: [webform_submission:values:first_name]. The result is sent as an array. You may use tokens.'),
    ];

    $form['constant_contact']['token_tree_link'] = $this->tokenManager->buildTreeLink();

    return $form;
  }

  /**
   * Ajax callback to update Webform Constant Contact settings.
   */
  public static function ajaxConstantContactListHandler(array $form, FormStateInterface $form_state) {
    return $form['settings']['constant_contact'];
  }


  /**
   * Submit callback for the refresh button.
   */
  public static function constantcontactUpdateConfigSubmit(array $form, FormStateInterface $form_state) {
    // Trigger list and group category refetch by deleting lists cache.
    $cache = \Drupal::cache();
    $cache->delete('ik_constant_contact.lists');
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    foreach ($this->configuration as $name => $value) {
      if (isset($values['constant_contact'][$name])) {
        $this->configuration[$name] = $values['constant_contact'][$name];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    // If update, do nothing
    if ($update) {
      return;
    }

    $fields = $webform_submission->toArray(TRUE);

    $configuration = $this->tokenManager->replace($this->configuration, $webform_submission);

    // Check if we are using a token for the list field & replace value.
    if (isset($this->configuration['list']) && strpos($this->configuration['list'], '[webform_submission:values:') !== false) {
      $configuration['list'] = null;
      $fieldToken = str_replace(['[webform_submission:values:', ']'], '', $this->configuration['list']);

      if (isset($fields['data'][$fieldToken])) {
        if (is_string($fields['data'][$fieldToken])) {
          $configuration['list'] = [$fields['data'][$fieldToken]];
        }
        else if (is_array($fields['data'][$fieldToken])) {
          $configuration['list'] = $fields['data'][$fieldToken];
        }
      }
    }

    // Email could be a webform element or a string/token.
    if (!empty($fields['data'][$configuration['email']])) {
      $email = $fields['data'][$configuration['email']];
    }
    else {
      $email = $configuration['email'];
    }

    $mergevars = Yaml::decode($configuration['mergevars']) ?? [];

    // Allow other modules to alter the merge vars.
    // @see hook_constant_contact_lists_mergevars_alter().
    $entity_type = 'webform_submission';
    \Drupal::moduleHandler()->alter('constant_contact_lists_mergevars', $mergevars, $webform_submission, $entity_type);
    \Drupal::moduleHandler()->alter('webform_constant_contact_lists_mergevars', $mergevars, $webform_submission, $this);

    $handler_link = Link::createFromRoute(
      t('Edit handler'),
      'entity.webform.handler.edit_form',
      [
        'webform' => $this->getWebform()->id(),
        'webform_handler' => $this->getHandlerId(),
      ]
    )->toString();

    $submission_link = ($webform_submission->id()) ? $webform_submission->toLink($this->t('View'))->toString() : NULL;

    $context = [
      'link' => $submission_link . ' / ' . $handler_link,
      'webform_submission' => $webform_submission,
      'handler_id' => $this->getHandlerId(),
    ];

    if (!empty($configuration['list']) && !empty($email)) {
      $data = array_merge(['email_address' => $email], $mergevars);

      if (is_string($configuration['list'])) {
        $lists = [$configuration['list']];
      } else {
        $lists = $configuration['list'];
      }

      $this->constantContact->submitContactForm($data , $lists);
    }
    else {
      if (empty($configuration['list'])) {
        \Drupal::logger('webform_submission')->warning(
          'No Constant Contact list was provided to the handler.',
          $context
        );
      }
      if (empty($email)) {
        \Drupal::logger('webform_submission')->warning(
          'No email address was provided to the handler.',
          $context
        );
      }
    }
  }

}
