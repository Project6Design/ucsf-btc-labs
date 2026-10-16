# The Linkit Matcher

`Drupal\anchor_link\Plugin\Linkit\Matcher\CKEditorAnchorLinkMatcher` supplies
anchor suggestions to the [Linkit](https://www.drupal.org/project/linkit)
autocomplete.

## Plugin definition

```php
/**
 * @Matcher(
 *   id = "ckeditor_anchor_link",
 *   label = @Translation("CKEditor Anchor link"),
 * )
 */
class CKEditorAnchorLinkMatcher extends MatcherBase
```

It extends `MatcherBase`, so it has no target entity type and issues no
queries. Linkit remains an optional dependency: the class is only instantiated
when Linkit is installed and the matcher is added to a profile.

## `execute($string)`

Takes the current search string and returns a `SuggestionCollection`.

```php
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
```

### The cast

`(string) $string` before `ltrim()` is deliberate. Linkit can call the matcher
with `NULL` when the field is empty, and passing `NULL` to `ltrim()` is
deprecated as of PHP 8.1. Without the cast the site logs a deprecation on every
empty autocomplete request.

### The guard clauses

Each clause rules out a search that cannot describe a fragment on the current
page:

| Clause | Rejects | Example |
|--------|---------|---------|
| `$string === ''` | Nothing typed, or only a `#` | `#` |
| `^[a-z][a-z0-9+.\-]*:` | Anything with a URI scheme | `https://`, `mailto:` |
| `str_contains($string, '/')` | Anything with a path separator | `/about/team` |
| `preg_match('/\s/', ...)` | Whitespace, which an id cannot contain | `two words` |

Returning an empty collection means Linkit simply shows no anchor suggestion,
which is the intended behaviour. Before this guard existed the matcher offered a
`#https://example.com` suggestion for every pasted URL.

The scheme pattern follows RFC 3986: a letter followed by letters, digits, `+`,
`.` or `-`, then a colon.

## The suggestion

A `DescriptionSuggestion` with:

- **label** - `#` plus the cleaned string, so the editor sees what they will get
- **path** - `#` plus the cleaned string, which is what gets inserted
- **group** - "Anchor links (within the same page)", which groups anchor
  suggestions separately from entity results in the autocomplete

## Current limitations

The matcher is a **transformer**, not a **finder**. It turns what the editor
typed into a fragment; it never looks at content to see whether that anchor
exists.

Consequences:

- No discovery. The editor must already know the anchor name.
- No validation. A typo produces a confident suggestion for a target that does
  not exist.
- Same-page scope only, which is why the group is labelled that way.

## Proposed rework

[#3460457 Rework the Suggestion for CKEditor Anchor Link Matcher class for
Linkit](https://www.drupal.org/project/anchor_link/issues/3460457) proposes
replacing this with a matcher that extends Linkit's `EntityMatcher` and queries
real content for anchors.

Sketch of the proposal:

- extend `EntityMatcher` with `target_entity = "node"`;
- query `text_long` and `text_with_summary` fields for the anchor marker,
  one query per bundle to keep the join count down;
- optionally recurse into `entity_reference_revisions` (paragraph) fields, with
  a configurable depth, because each discovered field adds a join;
- configuration for bundle filtering, bundle grouping, result limit, metadata
  and whether unpublished nodes are included;
- access checks on both the query and each loaded entity.

Open questions on the issue: the cost and complexity of the generated SQL on
large sites, whether anchors on the **current unsaved** node should be found
client-side instead, and whether same-page and site-wide suggestions should be
two separate features rather than one matcher.

The maintainer's position on the issue is that it is a good direction and a big
change (an entity query, the field manager and a settings form), so it should
land with test coverage rather than as a quick merge.

If you depend on the current same-page behaviour, note that the rework would
change it. Follow the issue.

## Writing your own matcher

If you need different behaviour now, add a matcher rather than patching this
one:

```php
namespace Drupal\my_module\Plugin\Linkit\Matcher;

use Drupal\linkit\MatcherBase;
use Drupal\linkit\Suggestion\DescriptionSuggestion;
use Drupal\linkit\Suggestion\SuggestionCollection;

/**
 * @Matcher(
 *   id = "my_anchor_matcher",
 *   label = @Translation("My anchor matcher"),
 * )
 */
class MyAnchorMatcher extends MatcherBase {

  public function execute($string) {
    $suggestions = new SuggestionCollection();
    // Build and add your own suggestions.
    return $suggestions;
  }

}
```

Add it to the Linkit profile alongside, or instead of, the one shipped here.

## Next steps

- [Linkit Integration, for administrators](../2-admins/2-linkit-integration.md)
- [Hooks and Plugins](1-hooks-and-plugins.md)
