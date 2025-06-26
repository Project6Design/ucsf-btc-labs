<?php

declare(strict_types=1);

namespace Drupal\editor_file\Form;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Component\Utility\Environment;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\file\IconMimeTypes;
use Drupal\file\Validation\FileValidatorInterface;
use Drupal\filter\Entity\FilterFormat;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a link dialog for text editors.
 */
class EditorFileDialog extends FormBase implements BaseFormIdInterface {

  /**
   * The file storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The file URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The file validator service.
   *
   * @var \Drupal\file\Validation\FileValidatorInterface
   */
  protected $fileValidator;

  /**
   * Constructs a form object for image dialog.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $file_storage
   *   The file storage service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator service.
   * @param \Drupal\file\Validation\FileValidatorInterface $file_validator
   *   The file validator service.
   */
  public function __construct(EntityStorageInterface $file_storage, EntityRepositoryInterface $entity_repository, RendererInterface $renderer, FileUrlGeneratorInterface $file_url_generator, FileValidatorInterface $file_validator) {
    $this->fileStorage = $file_storage;
    $this->entityRepository = $entity_repository;
    $this->renderer = $renderer;
    $this->fileUrlGenerator = $file_url_generator;
    $this->fileValidator = $file_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('file'),
      $container->get('entity.repository'),
      $container->get('renderer'),
      $container->get('file_url_generator'),
      $container->get('file.validator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'editor_file_dialog';
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    // Use the EditorLinkDialog form id to ease alteration.
    return 'editor_link_dialog';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\filter\Entity\FilterFormat|null $filter_format
   *   The filter format for which this dialog corresponds.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?FilterFormat $filter_format = NULL) {
    // This form is special, in that the default values do not come from the
    // server side, but from the client side, from a text editor. We must cache
    // this data in form state, because when the form is rebuilt, we will be
    // receiving values from the form, instead of the values from the text
    // editor. If we don't cache it, this data will be lost.
    if (isset($form_state->getUserInput()['editor_object'])) {
      // By convention, the data that the text editor sends to any dialog is in
      // the 'editor_object' key. And the image dialog for text editors expects
      // that data to be the attributes for an <img> element.
      $file_element = $form_state->getUserInput()['editor_object'];
      $form_state->set('file_element', $file_element);
      $form_state->setCached(TRUE);
    }
    else {
      // Retrieve the image element's attributes from form state.
      $file_element = $form_state->get('file_element') ?: [];
    }

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="editor-file-dialog-form">';
    $form['#suffix'] = '</div>';

    // Load dialog settings.
    $editor = editor_load($filter_format->id());
    $settings = $editor->getSettings();
    $file_upload = $settings['plugins']['editor_file_file'];
    $form_state->set('file_upload_settings', $file_upload);
    $max_filesize = !empty($file_upload['max_size']) ? min(Bytes::toNumber($file_upload['max_size']), Environment::getUploadMaxSize()) : Environment::getUploadMaxSize();

    $existing_file = isset($file_element['data-entity-uuid']) ? $this->entityRepository->loadEntityByUuid('file', $file_element['data-entity-uuid']) : NULL;
    $fid = $existing_file ? $existing_file->id() : NULL;

    $form['fid'] = [
      '#title' => $this->t('File'),
      '#type' => 'managed_file',
      '#upload_location' => $file_upload['scheme'] . '://' . $file_upload['directory'],
      '#default_value' => $fid ? [$fid] : NULL,
      '#upload_validators' => [
        'FileExtension' => ['extensions' => !empty($file_upload['extensions']) ? $file_upload['extensions'] : ['txt']],
        'FileSizeLimit' => ['fileLimit' => $max_filesize],
      ],
      '#required' => TRUE,
    ];

    $file_upload_help = [
      '#theme' => 'file_upload_help',
      '#description' => '',
      '#upload_validators' => $form['fid']['#upload_validators'],
      '#cardinality' => 1,
    ];
    $form['fid']['#description'] = $this->renderer->renderPlain($file_upload_help);

    $form['attributes']['href'] = [
      '#title' => $this->t('URL'),
      '#type' => 'textfield',
      '#default_value' => $file_element['href'] ?? '',
      '#maxlength' => 2048,
      '#required' => TRUE,
    ];

    if ($file_upload['status']) {
      $form['attributes']['href']['#access'] = FALSE;
      $form['attributes']['href']['#required'] = FALSE;
    }
    else {
      $form['fid']['#access'] = FALSE;
      $form['fid']['#required'] = FALSE;
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      // No regular submit-handler. This form only works via JavaScript.
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitForm',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $fid = $form_state->getValue(['fid', 0]);

    if (empty($fid)) {
      return;
    }

    $file = $this->fileStorage->load($fid);

    if (empty($file)) {
      return;
    }

    $settings = $form_state->get('file_upload_settings');

    // Validate the uploaded file.
    $max_filesize = !empty($settings['max_size']) ? min(Bytes::toNumber($settings['max_size']), Environment::getUploadMaxSize()) : Environment::getUploadMaxSize();
    $violations = $this->fileValidator->validate($file, [
      'FileExtension' => ['extensions' => $settings['extensions'] ?? ['txt']],
      'FileSizeLimit' => ['fileLimit' => $max_filesize],
    ]);
    if ($violations->count()) {
      foreach ($violations as $violation) {
        $form_state->setErrorByName('fid', $violation->getMessage());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Convert any uploaded files from the FID values to data-entity-uuid
    // attributes and set data-entity-type to 'file'.
    $fid = $form_state->getValue(['fid', 0]);
    if (!empty($fid)) {
      $file = $this->fileStorage->load($fid);

      // Make sure the file is permanent.
      $file->setPermanent();
      $file->save();

      $file_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      // Transform absolute file URLs to relative file URLs: prevent problems
      // on multisite set-ups and prevent mixed content errors.
      $file_url = $this->fileUrlGenerator->transformRelative($file_url);
      $form_state->setValue(['attributes', 'href'], $file_url);
      $form_state->setValue([
        'attributes',
        'filename',
      ], urldecode(basename($file_url)));
      $form_state->setValue(['attributes', 'data-entity-uuid'], $file->uuid());
      $form_state->setValue(['attributes', 'data-entity-type'], 'file');

      $mime_type = $file->getMimeType();
      // Classes to add to the file field for icons.
      $classes = [
        'file',
        // Add a specific class for each and every mime type.
        'file--mime-' . strtr($mime_type, ['/' => '-', '.' => '-']),
        // Add a more general class for groups of well known MIME types.
        'file--' . DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '10.3.0', fn() => IconMimeTypes::getIconClass($mime_type), fn() => file_icon_class($mime_type)),
      ];
      // Merge with existing classes (eg: those added w/ Editor Advanced Link).
      if (!empty($form_state->getValue('attributes')['class'])) {
        $existing_classes = preg_split('/\s+/', $form_state->getValue('attributes')['class']);
        $classes = array_unique(array_merge($existing_classes, $classes));
      }
      $form_state->setValue(['attributes', 'class'], implode(' ', $classes));
    }

    if ($form_state->getErrors()) {
      $messages = [];
      foreach ($form_state->getErrors() as $error) {
        $messages['error'][] = $error->__toString();
      }
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#theme' => 'status_messages',
        '#weight' => -10,
        '#message_list' => $messages,
      ];
      $response->addCommand(new HtmlCommand('#editor-file-dialog-form', $form));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state->getValues()));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

}
