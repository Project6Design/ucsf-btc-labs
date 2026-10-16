# Inserting Anchors

An anchor is a named point in your content that a link can jump to. This page
covers creating and managing them from the CKEditor 5 toolbar.

## Before you start

The **Anchor link** button has to be in the toolbar of the text format you are
using. If you do not see it, ask a site administrator to
[add it to the format](../2-admins/0-configuration.md).

## Inserting an anchor

1. Edit a piece of content and place the cursor where the anchor belongs, or
   select the text you want to name.
2. Click the **Anchor** button in the toolbar.
3. Type an anchor **name** (the id), for example `pricing`.
4. Save the dialog.

The anchor is now in the content. Save the node to persist it.

### With a selection

If text was selected when you clicked the button, the anchor wraps that text.
The text stays visible and readable on the front end; only the `id` is added.

### Without a selection

If nothing was selected, an **empty anchor** is inserted at the cursor. In the
editor it shows as an "INVISIBLE ANCHOR" marker so you can see and select it.
On the rendered page it produces no visible output at all: it is just a jump
target.

## Naming anchors

An anchor name becomes an HTML `id`, so it has to behave like one:

- Use letters, digits, hyphens and underscores: `section-2`, `faq_shipping`
- **No spaces** - use a hyphen instead
- Make it unique on the page; two elements with the same id is invalid HTML and
  browsers will only jump to the first
- Keep it stable, because it becomes part of a shareable URL
  (`/page#section-2`). Renaming an anchor breaks every link and bookmark that
  pointed at it.

Lowercase, hyphen-separated names (`shipping-and-returns`) are the convention
and the least surprising choice.

## Editing an anchor

1. Click the anchor (or the "INVISIBLE ANCHOR" marker) in the editor.
2. Click the **Anchor** button, or use the balloon that appears.
3. Change the name and save.

Remember to update any links that referenced the old name.

## Removing an anchor

Select the anchor and use the remove option in the anchor balloon, or the
context menu. Removing the anchor from text you selected earlier leaves the text
in place and only drops the `id`.

For an empty anchor, select the "INVISIBLE ANCHOR" marker and delete it.

## Anchors on real links

A link can be an anchor at the same time. An `<a>` element that carries both an
`href` and an `id` keeps both when you save:

```html
<a id="see-also" href="/related-page">Related page</a>
```

This is useful when a link in a list is also the target of a jump from
elsewhere in the document. Earlier versions of the module dropped the `id` in
this case; that is fixed.

## Source editing

If your format has the **Source** button, you can write anchor markup by hand:

```html
<h2 id="pricing">Pricing</h2>
<a id="fine-print"></a>
```

Anchor markup survives a round trip through the Source view: switch to source,
switch back, and the anchors are still there. Note that the `ck-anchor` class is
an editor-only marker and is not saved to the rendered content.

## What gets saved

For a named span of text:

```html
<a id="pricing">Pricing</a>
```

For an empty anchor:

```html
<a id="pricing"></a>
```

The editor-only `ck-anchor` class does not reach the saved content, so your
front-end markup stays clean.

## Next steps

- [Link to the anchors you created](2-linking-to-anchors.md)
- [See worked examples](3-use-cases.md)
