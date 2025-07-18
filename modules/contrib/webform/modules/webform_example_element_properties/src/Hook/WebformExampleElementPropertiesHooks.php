<?php

namespace Drupal\webform_example_element_properties\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for webform_example_element_properties.
 */
class WebformExampleElementPropertiesHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_webform_element_default_properties_alter().
   */
  #[Hook('webform_element_default_properties_alter')]
  public function webformElementDefaultPropertiesAlter(array &$properties, array &$definition) {
    // Add custom data property to all webform elements.
    // Setting the custom property to an empty string makes the corresponding
    // element defined via hook_webform_element_configuration_form_alter()
    // automatically visible.
    $properties['custom_data'] = '';
  }

  /**
   * Implements hook_webform_element_translatable_properties_alter().
   */
  #[Hook('webform_element_translatable_properties_alter')]
  public function webformElementTranslatablePropertiesAlter(array &$properties, array &$definition) {
    // Make the custom data property translatable.
    $properties[] = 'custom_data';
  }

  /**
   * Implements hook_webform_element_configuration_form_alter().
   */
  #[Hook('webform_element_configuration_form_alter')]
  public function webformElementConfigurationFormAlter(&$form, FormStateInterface $form_state) {
    // If you want add element properties to specific element type, you can use
    // the below code to the current element's type and more.
    /** @var \Drupal\webform_ui\Form\WebformUiElementEditForm $form_object */
    $form_object = $form_state->getFormObject();
    $element_plugin = $form_object->getWebformElementPlugin();
    $element_label = $element_plugin->getPluginLabel();
    $element_type = $element_plugin->getTypeName();
    // Append custom properties details container and textfield element.
    $t_args = ['@label' => $element_label, '@type' => $element_type];
    $form['custom_properties'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom properties'),
      '#description' => $this->t('The below custom properties are provided and managed by the webform_example_element_properties.module.'),
      '#open' => TRUE,
          // Add custom properties after all fieldset elements, which have a
          // weight of -20.
          // @see \Drupal\webform\Plugin\WebformElementBase::buildConfigurationForm
      '#weight' => -10,
    ];
    $form['custom_properties']['custom_data'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom data'),
      '#description' => $this->t("The custom data value will be added to @label (@type) data-* attributes.", $t_args),
    ];
  }

  /**
   * Implements hook_webform_element_alter().
   */
  #[Hook('webform_element_alter')]
  public function webformElementAlter(array &$element, FormStateInterface $form_state, array $context) {
    // Add data-custom to the element's attributes.
    if (!empty($element['#custom_data'])) {
      $element['#attributes']['data-custom'] = $element['#custom_data'];
    }
  }

}
