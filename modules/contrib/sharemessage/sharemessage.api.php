<?php

/**
 * @file
 * Hooks provided by Share Message module.
 */

use Drupal\sharemessage\ShareMessageInterface;

/**
 * Allow other modules to alter Share Message token context.
 *
 * @param \Drupal\sharemessage\ShareMessageInterface $sharemessage
 *   Currently loaded Share Message object.
 * @param array $context
 *   Token Context.
 */
function hook_sharemessage_token_context_alter(ShareMessageInterface $sharemessage, &$context) {
  // Alter Share Message title.
  $sharemessage->title = 'Altered Title';

  // Add taxonomy_vocabulary object type in a $context array.
  $context['taxonomy_vocabulary'] = \Drupal::routeMatch()->getParameter('taxonomy_vocabulary');
}
