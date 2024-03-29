<?php

namespace Drupal\devel_generate;

use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base DevelGenerate plugin implementation.
 */
abstract class DevelGenerateBase extends PluginBase implements DevelGenerateBaseInterface {

  /**
   * The plugin settings.
   */
  protected array $settings = [];

  /**
   * The random data generator.
   */
  protected ?Random $random = NULL;

  /**
   * The entity type manager service.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The language manager.
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The module handler.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Constructs a new DevelGenerateBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    MessengerInterface $messenger,
    LanguageManagerInterface $language_manager,
    ModuleHandlerInterface $module_handler,
    TranslationInterface $string_translation
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->languageManager = $language_manager;
    $this->moduleHandler = $module_handler;
    $this->stringTranslation = $string_translation;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('language_manager'),
      $container->get('module_handler'),
      $container->get('string_translation'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting(string $key) {
    // Merge defaults if we have no value for the key.
    if (!array_key_exists($key, $this->settings)) {
      $this->settings = $this->getDefaultSettings();
    }
    return $this->settings[$key] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings(): array {
    $definition = $this->getPluginDefinition();
    return $definition['settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(): array {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormValidate(array $form, FormStateInterface $form_state): void {
    // Validation is optional.
  }

  /**
   * {@inheritdoc}
   */
  public function generate(array $values): void {
    $this->generateElements($values);
    $this->setMessage('Generate process complete.');
  }

  /**
   * Business logic relating with each DevelGenerate plugin.
   *
   * @param array $values
   *   The input values from the settings form.
   */
  protected function generateElements(array $values): void {

  }

  /**
   * Populate the fields on a given entity with sample values.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be enriched with sample field values.
   * @param array $skip
   *   A list of field names to avoid when populating.
   * @param array $base
   *   A list of base field names to populate.
   */
  public static function populateFields(EntityInterface $entity, array $skip = [], array $base = []): void {
    if (!$entity->getEntityType()->entityClassImplements(FieldableEntityInterface::class)) {
      // Nothing to do.
      return;
    }

    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $instances */
    $instances = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
    $instances = array_diff_key($instances, array_flip($skip));

    foreach ($instances as $instance) {
      $field_storage = $instance->getFieldStorageDefinition();
      $field_name = $field_storage->getName();
      if ($field_storage->isBaseField() && !in_array($field_name, $base)) {
        // Skip base field unless specifically requested.
        continue;
      }
      $max = $cardinality = $field_storage->getCardinality();
      if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
        // Just an arbitrary number for 'unlimited'.
        $max = random_int(1, 3);
      }
      $entity->$field_name->generateSampleItems($max);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function handleDrushParams($args) {

  }

  /**
   * Set a message for either drush or the web interface.
   *
   * @param string|MarkupInterface $msg
   *   The message to display.
   * @param string $type
   *   (optional) The message type, as defined in MessengerInterface. Defaults
   *   to MessengerInterface::TYPE_STATUS.
   */
  protected function setMessage($msg, string $type = MessengerInterface::TYPE_STATUS): void {
    if (function_exists('drush_log')) {
      $msg = strip_tags($msg);
      drush_log($msg);
    }
    else {
      $this->messenger->addMessage($msg, $type);
    }
  }

  /**
   * Check if a given param is a number.
   *
   * @param mixed $number
   *   The parameter to check.
   *
   * @return bool
   *   TRUE if the parameter is a number, FALSE otherwise.
   */
  public static function isNumber(mixed $number): bool {
    if ($number == NULL) {
      return FALSE;
    }
    if (!is_numeric($number)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Returns the random data generator.
   *
   * @return \Drupal\Component\Utility\Random
   *   The random data generator.
   */
  protected function getRandom(): Random {
    if (!$this->random) {
      $this->random = new Random();
    }
    return $this->random;
  }

  /**
   * Generates a random sentence of specific length.
   *
   * Words are randomly selected with length from 2 up to the optional parameter
   * $max_word_length. The first word is capitalised. No ending period is added.
   *
   * @param int $sentence_length
   *   The total length of the sentence, including the word-separating spaces.
   * @param int $max_word_length
   *   (optional) Maximum length of each word. Defaults to 8.
   *
   * @return string
   *   A sentence of the required length.
   */
  protected function randomSentenceOfLength(int $sentence_length, int $max_word_length = 8): string {
    // Maximum word length cannot be longer than the sentence length.
    $max_word_length = min($sentence_length, $max_word_length);
    $words = [];
    $remainder = $sentence_length;
    do {
      if ($remainder <= $max_word_length) {
        // If near enough to the end then generate the exact length word to fit.
        $next_word = $remainder;
      }
      else {
        // Cannot fill the remaining space with one word, so choose a random
        // length, short enough for a following word of at least minimum length.
        $next_word = mt_rand(2, min($max_word_length, $remainder - 3));
      }
      $words[] = $this->getRandom()->word($next_word);
      $remainder = $remainder - $next_word - 1;
    } while ($remainder > 0);
    return ucfirst(implode(' ', $words));
  }

  /**
   * Creates the language and translation section of the form.
   *
   * This is used by both Content and Term generation.
   *
   * @param string $items
   *   The name of the things that are being generated - 'nodes' or 'terms'.
   *
   * @return array
   *   The language details section of the form.
   */
  protected function getLanguageForm(string $items): array {
    // We always need a language, even if the language module is not installed.
    $options = [];
    $languages = $this->languageManager->getLanguages(LanguageInterface::STATE_CONFIGURABLE);
    foreach ($languages as $langcode => $language) {
      $options[$langcode] = $language->getName();
    }

    $language_module_exists = $this->moduleHandler->moduleExists('language');
    $translation_module_exists = $this->moduleHandler->moduleExists('content_translation');

    $form['language'] = [
      '#type' => 'details',
      '#title' => $this->t('Language'),
      '#open' => $language_module_exists,
    ];
    $form['language']['add_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the primary language(s) for @items', ['@items' => $items]),
      '#multiple' => TRUE,
      '#description' => $language_module_exists ? '' : $this->t('Disabled - requires Language module'),
      '#options' => $options,
      '#default_value' => [
        $this->languageManager->getDefaultLanguage()->getId(),
      ],
      '#disabled' => !$language_module_exists,
    ];
    $form['language']['translate_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the language(s) for translated @items', ['@items' => $items]),
      '#multiple' => TRUE,
      '#description' => $translation_module_exists ? $this->t('Translated @items will be created for each language selected.', ['@items' => $items]) : $this->t('Disabled - requires Content Translation module.'),
      '#options' => $options,
      '#disabled' => !$translation_module_exists,
    ];
    return $form;
  }

  /**
   * Return a language code.
   *
   * @param array $add_language
   *   Optional array of language codes from which to select one at random.
   *   If empty then return the site's default language.
   *
   * @return string
   *   The language code to use.
   */
  protected function getLangcode(array $add_language): string {
    if (empty($add_language)) {
      return $this->languageManager->getDefaultLanguage()->getId();
    }
    return $add_language[array_rand($add_language)];
  }

  /**
   * Convert a csv string into an array of items.
   *
   * Borrowed from Drush.
   *
   * @param string|array|null $args
   *   A simple csv string; e.g. 'a,b,c'
   *   or a simple list of items; e.g. array('a','b','c')
   *   or some combination; e.g. array('a,b','c') or array('a,','b,','c,').
   */
  public static function csvToArray($args): array {
    if ($args === NULL) {
      return [];
    }

    // 1: implode(',',$args) converts from array('a,','b,','c,') to 'a,,b,,c,'
    // 2: explode(',', ...) converts to array('a','','b','','c','')
    // 3: array_filter(...) removes the empty items
    // 4: array_map(...) trims extra whitespace from each item
    // (handles csv strings with extra whitespace, e.g. 'a, b, c')
    //
    $args = is_array($args) ? implode(',', array_map('strval', $args)) : (string) $args;
    return array_map('trim', array_filter(explode(',', $args)));
  }

}
