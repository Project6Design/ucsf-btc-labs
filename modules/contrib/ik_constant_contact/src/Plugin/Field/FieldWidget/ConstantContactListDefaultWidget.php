<?php

namespace Drupal\ik_constant_contact\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;

/**
 * Plugin implementation of the 'constant_contact_lists_default' widget.
 *
 * @FieldWidget(
 *   id = "constant_contact_lists_default",
 *   label = @Translation("Select list"),
 *   field_types = {
 *     "constant_contact_lists"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ConstantContactListDefaultWidget extends OptionsSelectWidget {
}