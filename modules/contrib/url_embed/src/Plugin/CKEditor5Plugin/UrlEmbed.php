<?php

declare(strict_types=1);

namespace Drupal\url_embed\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Component\Utility\Html;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\editor\EditorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin class to add dialog url for embedded content.
 */
class UrlEmbed extends CKEditor5PluginDefault implements ContainerFactoryPluginInterface {

  /**
   * The CSRF Token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfTokenGenerator;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * DrupalEntity constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token_generator
   *   The CSRF Token generator service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    CKEditor5PluginDefinition $plugin_definition,
    CsrfTokenGenerator $csrf_token_generator,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->csrfTokenGenerator = $csrf_token_generator;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('csrf_token'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    // First load configuration from the url_embed.ckeditor5.yml file.
    $plugin_config = $static_plugin_config;

    $plugin_config['urlEmbed']['format'] = $editor->id();
    $embedded_content_preview_url = Url::fromRoute('embed.preview', [
      'filter_format' => $editor->id(),
    ])
      ->toString(TRUE)
      ->getGeneratedUrl();
    $plugin_config['urlEmbed']['previewURL'] = $embedded_content_preview_url;

    // See #3285139.
    $plugin_config['urlEmbed']['previewCsrfToken'] = $this->csrfTokenGenerator->get('X-Drupal-EmbedPreview-CSRF-Token');

    $embedded_content_dialog_url = Url::fromRoute('url_embed.cke5dialog', [
      'editor' => $editor->id(),
    ])
    ->toString(TRUE)
    ->getGeneratedUrl();
    $plugin_config['urlEmbed']['dialogURL'] = $embedded_content_dialog_url;
    return $plugin_config;
  }
}
