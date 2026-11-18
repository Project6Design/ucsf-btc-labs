<?php

declare(strict_types=1);

namespace Drupal\embed\Hook;

use Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Hook implementations for embed.
 */
class EmbedHooks {

  public function __construct(
    #[Autowire(service: 'plugin.manager.ckeditor5.plugin')]
    protected readonly ?CKEditor5PluginManagerInterface $ckeditor5PluginManager = NULL,
  ) {}

  /**
   * Implements hook_form_FORM_ID_alter() for embed_button_add_form.
   *
   * This hook is implemented on behalf of ckeditor.module.
   *
   * @see https://www.drupal.org/node/3639267
   */
  #[Hook('form_embed_button_add_form_alter', module: 'ckeditor')]
  public function ckeditorFormEmbedButtonAddFormAlter(array &$form, FormStateInterface $form_state): void {
    $form['#validate'][] = [static::class, 'validateCkeditorButtonId'];
  }

  /**
   * CKEditor-validation callback for new embed buttons.
   *
   * Checks to make sure that when adding a new embed button, its ID will not
   * conflict with any existing CKEditor buttons.
   *
   * @see https://www.drupal.org/node/3639267
   */
  public static function validateCkeditorButtonId(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\ckeditor\CKEditorPluginManager $ckeditor_plugin_manager */
    $ckeditor_plugin_manager = \Drupal::service('plugin.manager.ckeditor.plugin');

    // Get a list of all buttons that are provided by all plugins.
    $button_ids = array_reduce($ckeditor_plugin_manager->getButtons(), function ($result, $item) {
      return array_merge($result, array_keys($item));
    }, []);
    $button_ids = array_map('mb_strtolower', $button_ids);

    // Ensure that button ID is unique.
    $button_id = $form_state->getValue('id');
    if (in_array($button_id, $button_ids, TRUE)) {
      $form_state->setErrorByName('id', new TranslatableMarkup('A CKEditor button with ID %id already exists.', ['%id' => $button_id]));
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter() for the filter format forms.
   */
  #[Hook('form_filter_format_edit_form_alter')]
  #[Hook('form_filter_format_add_form_alter')]
  public function formFilterFormatFormAlter(array &$form): void {
    $form['#validate'][] = [static::class, 'validateFilterFormat'];
  }

  /**
   * Validate callback for buttons that have a required_filter_plugin_id.
   */
  public static function validateFilterFormat(array $form, FormStateInterface $form_state): void {
    if ($form_state->getTriggeringElement()['#name'] !== 'op') {
      return;
    }

    // Right now we can only validate CKEditor configurations.
    if ($form_state->getValue(['editor', 'editor']) !== 'ckeditor') {
      return;
    }

    $button_group_path = [
      'editor',
      'settings',
      'toolbar',
      'button_groups',
    ];

    $buttons_to_validate = [];
    $buttons = \Drupal::service('plugin.manager.ckeditor.plugin')->getButtons();
    foreach ($buttons as $plugin_id => $plugin_buttons) {
      foreach ($plugin_buttons as $button_id => $button) {
        if (!empty($button['required_filter_plugin_id'])) {
          $buttons_to_validate[$plugin_id . ':' . $button_id] = $button['required_filter_plugin_id'];
        }
      }
    }

    if ($button_groups = $form_state->getValue($button_group_path)) {
      $selected_buttons = [];
      $button_groups = Json::decode($button_groups);

      foreach ($button_groups as $button_row) {
        foreach ($button_row as $button_group) {
          $selected_buttons = array_merge($selected_buttons, array_values($button_group['items']));
        }
      }

      $get_filter_label = function ($filter_plugin_id) use ($form) {
        return (string) $form['filters']['order'][$filter_plugin_id]['filter']['#markup'];
      };

      foreach ($buttons_to_validate as $button_id => $filter_plugin_id) {
        [$plugin_id, $button_id] = explode(':', $button_id, 2);
        if (in_array($button_id, $selected_buttons, TRUE)) {
          $filter_enabled = $form_state->getValue([
            'filters',
            $filter_plugin_id,
            'status',
          ]);

          if (!$filter_enabled) {
            $error_message = new TranslatableMarkup('The %filter-label filter must be enabled to use the %button button.', [
              '%filter-label' => $get_filter_label($filter_plugin_id),
              '%button' => $buttons[$plugin_id][$button_id]['label'],
            ]);
            $form_state->setErrorByName('filters][' . $filter_plugin_id . '][status', $error_message);
          }
        }
      }
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for embed_button.
   */
  #[Hook('embed_button_presave')]
  public function embedButtonPresave(): void {
    // Invalidate the CKEditor 5 plugin cache, so new toolbar items will appear
    // based on which Embed Buttons are configured.
    if ($this->ckeditor5PluginManager) {
      $this->ckeditor5PluginManager->clearCachedDefinitions();
      // @see entity_embed_library_info_alter()
      Cache::invalidateTags(['library_info']);
    }
  }

}
