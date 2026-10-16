# Linkit Integration

[Linkit](https://www.drupal.org/project/linkit) turns the CKEditor link field
into an autocomplete. CKEditor Anchor Link ships a Linkit **matcher** that adds
anchor suggestions to that autocomplete.

This integration is entirely optional. The Anchor button works without Linkit.

## What the matcher does

The matcher is a Linkit plugin with the id `ckeditor_anchor_link`, labelled
**CKEditor Anchor link**.

When an editor types in the Linkit-enabled link field, the matcher offers a
suggestion that turns what they typed into a fragment. Typing `pricing` or
`#pricing` yields a suggestion whose path is `#pricing`, grouped under
**Anchor links (within the same page)**.

## Enabling it

1. Install and enable Linkit.
2. Go to **Configuration > Content authoring > Linkit profiles**
   (`/admin/config/content/linkit`).
3. Edit the profile used by your text format, or add one.
4. Open the **Matchers** tab.
5. Click **Add matcher**, choose **CKEditor Anchor link**, and save.
6. Make sure the text format's **Link** field is configured to use that Linkit
   profile: edit the format and enable the **Linkit enabled fields** filter,
   selecting the profile.

Clear caches, then test in a live editor.

## When a suggestion is offered

The matcher deliberately stays quiet when what the editor typed cannot be a
fragment, so the autocomplete is not cluttered with a nonsense `#...` entry
every time someone pastes a URL.

No suggestion is made when the search string:

- is empty;
- names a scheme, such as `https:` or `mailto:`;
- contains a path separator (`/`);
- contains whitespace, since an id cannot.

Otherwise a leading `#` is stripped and the remainder becomes the fragment.

| Typed | Suggestion |
|-------|-----------|
| `pricing` | `#pricing` |
| `#pricing` | `#pricing` |
| `faq-returns` | `#faq-returns` |
| `https://example.com` | none |
| `/about` | none |
| `two words` | none |

## Limitations

The current matcher suggests a fragment based on **what the editor typed**. It
does not search the site for anchors that actually exist. In practice that means:

- there is no discovery: an editor has to know the anchor name;
- there is no validation: a typo produces a suggestion for an anchor that is not
  there;
- the suggestion is scoped to the current page by design, which is why the group
  is labelled "within the same page".

A reworked matcher that queries content for real anchors, with bundle filtering,
result limits, unpublished handling and configurable recursion into paragraph
fields, is under discussion in
[#3460457](https://www.drupal.org/project/anchor_link/issues/3460457). It is not
part of the module yet.

## Troubleshooting

**No anchor suggestions appear.**
Check in order: Linkit is enabled; the matcher is added to the profile; the
format's Linkit filter is on and points at that profile; caches are rebuilt.

**Suggestions appear for URLs.**
The matcher should not do this. If it does, the module is older than the fix in
3.0.5.

**A PHP deprecation warning appears when the field is empty.**
Fixed in 3.0.5, which casts the search string before trimming it.

## Next steps

- [The Linkit Matcher, for developers](../3-developers/2-linkit-matcher.md)
- [Configuration](0-configuration.md)
