# Linking to Anchors

Once an anchor exists, any link can jump to it. This page covers linking within
a page and across pages.

## Linking within the same page

1. Select the text that should become the link.
2. Click the **Link** button.
3. Enter the anchor name prefixed with `#`, for example `#pricing`.
4. Save.

Clicking that link scrolls the reader to the anchor without loading a new page.

## Linking from another page

Combine the path and the fragment:

```
/our-services#pricing
```

An absolute URL works the same way:

```
https://example.com/our-services#pricing
```

The anchor has to exist on the **target** page. Linking to `#pricing` from a
different page only works if the destination actually contains that anchor.

## Using the Linkit autocomplete

If your site has [Linkit](https://www.drupal.org/project/linkit) enabled and the
anchor matcher is configured, typing in the Link dialog offers anchor
suggestions.

Type `#` followed by a name, or just the name, and pick the suggestion from the
**Anchor links (within the same page)** group. The matcher fills in the
fragment for you.

The matcher only offers a suggestion for something that could actually be a
fragment. It stays quiet when what you typed is clearly a link to somewhere
else, so it does not clutter the autocomplete:

| What you type | Suggests an anchor? | Why |
|---------------|--------------------|-----|
| `pricing` | Yes | Could be an id |
| `#pricing` | Yes | Explicit fragment |
| `https://example.com` | No | Has a scheme |
| `/about/team` | No | Has a path separator |
| `two words` | No | Ids carry no whitespace |
| (empty) | No | Nothing to suggest |

See [Linkit Integration](../2-admins/2-linkit-integration.md) for the
administrator side.

## Testing your links

Anchor links are easy to get subtly wrong, so check them:

1. Save the content and view the rendered page.
2. Click the link. The page should scroll to the target.
3. Check the browser address bar: it should show `#pricing`.
4. Reload with the fragment in the URL. The browser should land on the anchor
   directly, which is what happens when someone follows a shared link.

## Troubleshooting

**The link does nothing.**
The anchor name and the fragment do not match. They are case-sensitive:
`#Pricing` will not find `id="pricing"`.

**It jumps to the wrong place.**
The id is used more than once on the page. Make anchor names unique.

**It works in the editor preview but not on the live page.**
The anchor markup was stripped on save. The text format's allowed HTML does not
permit the `id` attribute on `<a>`. See
[Text Formats and Allowed HTML](../2-admins/1-text-formats.md).

**The target is hidden behind a sticky header.**
That is a theme concern, not a module one. The usual fix is `scroll-margin-top`
on the anchor target in your theme's CSS.

## Next steps

- [Common use cases](3-use-cases.md)
- [FAQ](../faq.md)
