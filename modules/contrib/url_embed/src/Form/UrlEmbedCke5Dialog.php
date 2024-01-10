<?php

/**
 * @file
 * Contains \Drupal\url_embed\Form\UrlEmbedCke5Dialog.
 */

namespace Drupal\url_embed\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\editor\EditorInterface;
use Drupal\url_embed\UrlEmbedHelperTrait;
use Drupal\url_embed\UrlEmbedInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to embed URLs.
 */
class UrlEmbedCke5Dialog extends FormBase {
  use UrlEmbedHelperTrait;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a UrlEmbedDialog object.
   *
   * @param \Drupal\url_embed\UrlEmbedInterface $url_embed
   *   The URL embed service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The Form Builder.
   */
  public function __construct(UrlEmbedInterface $url_embed, FormBuilderInterface $form_builder) {
    $this->setUrlEmbed($url_embed);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('url_embed'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'url_embed_dialog_cke5';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\editor\EditorInterface $editor
   *   The editor to which this dialog corresponds.
   */
  public function buildForm(array $form, FormStateInterface $form_state, EditorInterface $editor = NULL) {
    $values = $form_state->getValues();
    $input = $form_state->getUserInput();
    // Populate value from existing CKEditor attribute.
    $url = $this->getRequest()->get('url_element');
    // Set URL button element in form state, so that it can be used later in
    // validateForm() function.
    $form_state->set('editor', $editor);
    // Initialize URL element with form attributes, if present.
    $url_element = empty($values['attributes']) ? array() : $values['attributes'];
    $url_element += empty($input['attributes']) ? array() : $input['attributes'];
    // The default values are set directly from \Drupal::request()->request,
    // provided by the editor plugin opening the dialog.
    if (!$form_state->get('url_element')) {
      $form_state->set('url_element', isset($input['editor_object']) ? $input['editor_object'] : array());
    }
    $url_element += $form_state->get('url_element');
    $url_element += array(
      'data-embed-url' => $url,
      'data-url-provider' => '',
    );
    $form_state->set('url_element', $url_element);
    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="url-embed-dialog-form">';
    $form['#suffix'] = '</div>';
    $form['attributes']['data-embed-url'] = array(
      '#type' => 'textfield',
      '#title' => 'URL',
      '#default_value' => $url_element['data-embed-url'],
      '#required' => TRUE,
    );
    try {
      if (!empty($url_element['data-embed-url']) && $info = $this->urlEmbed()->getEmbed($url_element['data-embed-url'])) {
        $url_element['data-url-provider'] = $info->providerName;
      }
    }
    catch (\Exception $e) {
      watchdog_exception('url_embed', $e);
    }
    $form['attributes']['data-url-provider'] = array(
      '#type' => 'value',
      '#value' => $url_element['data-url-provider'],
    );
    $form['actions'] = array(
      '#type' => 'actions',
    );
    $form['actions']['save_modal'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Embed'),
      '#button_type' => 'primary',
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => array(),
      '#ajax' => array(
        'callback' => '::submitForm',
        'event' => 'click',
      ),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    try {
      $this->urlEmbed()->getEmbed($values['attributes']['data-embed-url']);
    }
    catch (\Exception $e) {
      $form_state->setErrorByName('attributes[data-embed-url]', $this->t('This is not a supported URL to embed.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $values = $form_state->getValues();
    // Display errors in form, if any.
    if ($form_state->hasAnyErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = array(
        '#type' => 'status_messages',
        '#weight' => -10,
      );
      $response->addCommand(new HtmlCommand('#url-embed-dialog-form', $form));
    }
    else {
      $response->addCommand(new EditorDialogSave($values));
      $response->addCommand(new CloseModalDialogCommand());
    }
    return $response;
  }
}
