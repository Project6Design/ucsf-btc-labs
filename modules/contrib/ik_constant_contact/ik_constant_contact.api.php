<?php

/**
 * @file
 * Hooks provided by the Constant Contact module.
 */

use Drupal\webform\Plugin\WebformHandlerInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * For example if you would like to send custom fields.
 *
 * @see https://v3.developer.constantcontact.com/api_guide/custom_fields.html
 */

/**
 * Hook_ik_constant_contact_contact_data_alter.
 *
 * Alters the contact data that is sent to Constant Contact.
 * This hook is triggered on both create and update of contacts.
 *
 * @param array $data
 *   - The data received from a constant contact form.
 * @param object $body
 *   - The content of the request that is sent to the Constant Contact API.
 *
 * @return void
 */
function hook_ik_constant_contact_contact_data_alter(array $data, object &$body) {
  // Add custom field values
  // Set a value directly.
  $body->custom_fields[] = (object) [
  // The UUID of the custom field.
    'custom_field_id' => '00000000-0000-0000-0000-0000000000',
  // A value.
    'value' => 'Some Value',
  ];

  // Add custom field values
  // Set based on $data value.
  $body->custom_fields[] = (object) [
  // The UUID of the custom field.
    'custom_field_id' => '00000000-0000-0000-0000-0000000000',
  // A value.
    'value' => $data['company'],
  ];

  // Add another field value conditionally.
  $body->company_name = $data['company'] ?? '';
}

/**
 * Ik_constant_contact_contact_create_data_alter.
 *
 * Alters the contact data that is sent to Constant Contact.
 * This hook is triggered only on creation of a contact.
 *
 * @param array $data
 *   - The data received from a constant contact form.
 * @param object $body
 *   - The content of the request that is sent to the Constant Contact API.
 *
 * @return void
 */
function hook_ik_constant_contact_contact_create_data_alter(array $data, object &$body) {
  $body->company_name = $data['company'] ?? '';
}

/**
 * Ik_constant_contact_contact_update_data_alter.
 *
 * Alters the contact data that is sent to Constant Contact.
 * This hook is triggered only on update of a contact.
 *
 * @param array $data
 *   - The data received from a constant contact form.
 * @param object $body
 *   - The content of the request that is sent to the Constant Contact API.
 *
 * @return void
 */
function hook_ik_constant_contact_contact_update_data_alter(array $data, object &$body) {
  $body->company_name = $data['company'] ?? '';
}

/**
 * Alter mergevars before they are sent to Constant Contact.
 *
 * @param array $mergevars
 *   The current mergevars.
 * @param \Drupal\webform\WebformSubmissionInterface $submission
 *   The webform submission entity used to populate the mergevars.
 * @param \Drupal\webform\Plugin\WebformHandlerInterface $handler
 *   The webform submission handler used to populate the mergevars.
 *
 * @ingroup webform_ik_constant_contact
 */
function hook_ik_constant_contact_lists_mergevars_alter(&$mergevars, WebformSubmissionInterface $submission, WebformHandlerInterface $handler) {

}
