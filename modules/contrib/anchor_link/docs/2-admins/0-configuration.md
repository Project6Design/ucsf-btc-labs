# Configuration

CKEditor Anchor Link has no settings form of its own. All configuration happens
per text format, in the CKEditor 5 toolbar configuration.

## Adding the Anchor button to a text format

1. Go to **Configuration > Content authoring > Text formats and editors**
   (`/admin/config/content/formats`).
2. Click **Configure** on a format that uses **CKEditor 5** as its text editor.
3. In the **Toolbar configuration**, find the **Anchor link** button in the list
   of available items.
4. Drag it into the active toolbar. Next to the **Link** button is the
   conventional spot.
5. Click **Save configuration**.

The button is now live for every user with access to that format.

## What Drupal changes when you add the button

Adding the button enables the `anchor_link_ckeditor5_anchor` CKEditor 5 plugin
for that editor. The plugin declares the elements it needs:

```yaml
elements:
  - <a>
  - <a id="">
  - <a name="">
```

If the format uses the **Limit allowed HTML tags** filter, Drupal adds these to
the allowed HTML automatically when you save. You do not normally have to edit
the allowed HTML by hand. See
[Text Formats and Allowed HTML](1-text-formats.md) for the detail and for the
cases where you do.

## Which formats should get it

Give the button to formats used for **long-form body content**: articles, basic
pages, policy documents.

Leave it off formats used for short fields, teasers, or comments. Anchors there
create ids in unpredictable places on the page, which makes duplicate ids and
broken jumps more likely.

## Restricting who can create anchors

There is no "administer anchors" permission. Access follows the text format:

1. Go to **People > Permissions** (`/admin/people/permissions`).
2. Find the **Filter** section.
3. Grant **Use the &lt;format name&gt; text format** to the roles that should be
   able to create anchors.

So the way to restrict anchors is to put the Anchor button on a format that only
trusted roles can use.

Note that **Administer text formats and filters** is a restricted permission.
Only trusted administrators should hold it, because a user who can edit a format
can widen its allowed HTML.

## Styling the invisible anchor marker

An empty anchor shows as an "INVISIBLE ANCHOR" marker inside the editor. The
label and its styling come from CSS, so you can reword or restyle it from your
admin theme without patching the module or the library.

The module ships `css/anchor_link.admin.css`, loaded as the plugin's
`admin_library`. To override, target the marker in your own admin theme's CSS
and set a different `content` value, or hide it entirely.

Because the marker exists only in the editor, changing it has no effect on
rendered output.

## Verifying the configuration

After saving the format:

1. Create or edit content that uses it.
2. Confirm the **Anchor** button appears in the live toolbar.
3. Insert an anchor, save, and view the rendered page.
4. View the page source and confirm the `id` is present on the `<a>` element and
   that `class="ck-anchor"` is **not**.

If the `id` is missing from the rendered output, the allowed HTML is the thing to
check first.

## Next steps

- [Text Formats and Allowed HTML](1-text-formats.md)
- [Linkit Integration](2-linkit-integration.md)
- [Upgrading](3-upgrading.md)
