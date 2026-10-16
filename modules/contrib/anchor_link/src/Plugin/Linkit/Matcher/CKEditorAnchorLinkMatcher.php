<?php

namespace Drupal\anchor_link\Plugin\Linkit\Matcher;

use Drupal\linkit\MatcherBase;
use Drupal\linkit\Suggestion\DescriptionSuggestion;
use Drupal\linkit\Suggestion\SuggestionCollection;

/**
 * Provides specific linkit matchers for Anchor links.
 *
 * @Matcher(
 *   id = "ckeditor_anchor_link",
 *   label = @Translation("CKEditor Anchor link"),
 * )
 */
class CKEditorAnchorLinkMatcher extends MatcherBase {

  /**
   * {@inheritdoc}
   */
  public function execute($string) {
    $suggestions = new SuggestionCollection();

    $string = ltrim((string) $string, '#');

    // A search naming a scheme or a path is a link to somewhere else, and an
    // id carries no whitespace, so none of those describe a fragment on this
    // page and there is nothing to suggest.
    if ($string === ''
      || preg_match('#^[a-z][a-z0-9+.\-]*:#i', $string)
      || str_contains($string, '/')
      || preg_match('/\s/', $string)) {
      return $suggestions;
    }

    $suggestion = new DescriptionSuggestion();
    $suggestion->setLabel($this->t('#@anchor_link', ['@anchor_link' => $string]))
      ->setPath('#' . $string)
      ->setGroup($this->t('Anchor links (within the same page)'));

    $suggestions->addSuggestion($suggestion);

    return $suggestions;
  }

}
