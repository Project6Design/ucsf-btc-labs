<?php

namespace Drupal\features_ui\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for features_ui.
 */
class FeaturesUiHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public static function theme() {
    return [
      'features_items' => [
        'variables' => [
          'items' => [],
        ],
      ],
      'features_assignment_configure_form' => [
        'render element' => 'form',
      ],
    ];
  }

  /**
   * Prepares variables for package assignment configuration form.
   *
   * @param array $variables
   *   An associative array containing:
   *   - form: A render element representing the form.
   */
  #[Hook('preprocess_features_assignment_configure_form')]
  public function preprocessFeaturesAssignmentConfigureForm(array &$variables) {
    $form = &$variables['form'];

    $header = [
      $this->t('Assignment method'),
      $this->t('Description'),
      $this->t('Enabled'),
      $this->t('Weight'),
    ];

    // If there is at least one operation enabled, show the operation column.
    if ($form['#show_operations']) {
      $header[] = $this->t('Operations');
    }

    $table = [
      '#type' => 'table',
      '#weight' => 5,
      '#header' => $header,
      '#attributes' => ['id' => 'features-assignment-methods'],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'assignment-method-weight',
        ],
      ],
    ];

    foreach ($form['title'] as $id => $element) {
      // Do not take form control structures.
      if (is_array($element) && Element::child($id)) {
        $table[$id]['#attributes']['class'][] = 'draggable';
        $table[$id]['#weight'] = $element['#weight'];

        $table[$id]['title'] = $form['title'][$id];
        $table[$id]['title']['#prefix'] = '<strong>';
        $table[$id]['title']['#suffix'] = '</strong>';
        $table[$id]['description'] = $form['description'][$id];
        $table[$id]['enabled'] = $form['enabled'][$id];
        $table[$id]['weight'] = $form['weight'][$id];
        if ($form['#show_operations']) {
          $table[$id]['operation'] = $form['operation'][$id];
        }
        // Unset to prevent rendering along with children.
        unset($form['title'][$id]);
        unset($form['description'][$id]);
        unset($form['enabled'][$id]);
        unset($form['weight'][$id]);
        unset($form['operation'][$id]);
      }
    }

    // For some reason, the #weight is not being handled by drupal_render.
    // So we remove the actions and then put them back into the form after the
    // table.
    $actions = $form['actions'];
    unset($form['actions']);
    $form['table'] = $table;
    $form['actions'] = $actions;
  }

}
