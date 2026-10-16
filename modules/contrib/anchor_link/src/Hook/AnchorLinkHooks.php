<?php

namespace Drupal\anchor_link\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for anchor_link.
 */
class AnchorLinkHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      // Main module help for the CKEditor Anchor Link module.
      case 'help.page.anchor_link':
        $output = '';
        $output .= '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('Adds the anchor link dialog and the invisible anchor features to CKEditor 5.') . '</p>';
        $output .= '<ul>';
        $output .= '<li>' . $this->t('A balloon to name an anchor on the selected text, or to place an invisible anchor.') . '</li>';
        $output .= '<li>' . $this->t('Editing and removal of an anchor from the same balloon.') . '</li>';
        $output .= '<li>' . $this->t('Anchors written as an id on the a element, with the name attribute read for older content.') . '</li>';
        $output .= '</ul>';
        return $output;

      default:
    }
  }

}
