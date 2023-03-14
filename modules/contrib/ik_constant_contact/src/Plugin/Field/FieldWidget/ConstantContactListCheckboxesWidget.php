<?php

namespace Drupal\ik_constant_contact\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;

/**
 * Plugin implementation of the 'constant_contact_lists_default' widget.
 *
 * @FieldWidget(
 *   id = "constant_contact_lists_checkbox",
 *   label = @Translation("Check boxes/radio buttons"),
 *   field_types = {
 *     "constant_contact_lists"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ConstantContactListCheckboxesWidget extends OptionsButtonsWidget {
}