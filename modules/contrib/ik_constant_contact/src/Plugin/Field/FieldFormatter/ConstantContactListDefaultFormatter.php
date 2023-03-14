<?php

namespace Drupal\ik_constant_contact\Plugin\Field\FieldFormatter;

use Drupal\options\Plugin\Field\FieldFormatter\OptionsDefaultFormatter;

/**
 * Plugin implementation of the 'constant_contact_lists_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "constant_contact_lists_formatter",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "constant_contact_lists",
 *   }
 * )
 */
class ConstantContactListDefaultFormatter extends OptionsDefaultFormatter {
}