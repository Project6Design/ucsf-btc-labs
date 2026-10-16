# Text Formats and Allowed HTML

Anchors are attributes on `<a>` elements, so they only survive a save if the
text format permits those attributes. This page explains what has to be allowed
and how the module interacts with core's HTML filtering.

## The attributes involved

| Attribute | Purpose |
|-----------|---------|
| `id` | The anchor itself. This is the jump target. |
| `name` | Legacy form of the same thing, still read for backwards compatibility. |

## Limit allowed HTML tags

When a format enables the **Limit allowed HTML tags and correct faulty HTML**
filter, only the listed tags and attributes survive. The anchor plugin declares
what it needs, and Drupal merges those into the allowed HTML when you save the
format.

A format with the Anchor button typically ends up with something like:

```
<a href hreflang id name>
```

You normally do not edit this by hand. Two cases where you might:

- The format was configured before the Anchor button was added and the merge did
  not run, for example because the configuration was imported rather than saved
  through the form.
- The allowed HTML is managed in exported configuration and you want it explicit.

To check, edit the format and read the **Allowed HTML tags** field. If `id` is
absent from the `<a>` entry, anchors will be stripped on save.

## The `name` attribute and the post update

Support for the legacy `name` attribute was added after some sites had already
been using the plugin. Those formats' allowed HTML predates it, so the module
ships a post update:

```
anchor_link_post_update_allow_anchor_name_attribute
```

It finds every text editor that has the anchor plugin enabled, and for each one
whose format uses `filter_html`, appends `<a name>` to the allowed HTML.

Run it as part of a normal update:

```bash
drush updatedb
```

The post update only touches formats that already have the plugin enabled. It
does not enable anything, and it does not widen formats that never used anchors.

### Configuration management note

Because the post update edits `filter.format.*` configuration, running it
changes active configuration. On a site that manages configuration in code, run
`drush updatedb` and then export:

```bash
drush updatedb -y
drush config:export
```

Otherwise the next `config:import` reverts the change and anchors start being
stripped again.

## General HTML Support

Drupal core's **Arbitrary HTML support** plugin (General HTML Support in
CKEditor terms) can also claim `<a>` attributes, which conflicts with the anchor
plugin.

The module resolves this by altering that plugin's definition to **disallow** it
from handling the attributes the anchor plugin owns:

- `a` with attributes `id` and `name`

This happens in `hook_ckeditor5_plugin_info_alter()`. The practical effect is
that a format with **Full HTML** style arbitrary HTML support and the Anchor
button both enabled behaves correctly, with the anchor plugin in charge of
anchors.

You do not need to configure this, but it is worth knowing if you are debugging
attribute loss on a Full HTML format.

## Editor class versus saved markup

The `ck-anchor` class is an editor concern. It marks anchors so CKEditor can
show the "INVISIBLE ANCHOR" flag and let authors select empty anchors.

It is **not** written to the saved content. If you see `class="ck-anchor"` in
your rendered HTML, the editor library is out of date. Update to
`vardot/ckeditor5-anchor-drupal` 2.0.5 or newer.

## Troubleshooting

**Anchors disappear on save.**
`id` is not in the allowed HTML for `<a>`. Re-save the text format through the
form so the merge runs, or add it explicitly.

**Anchors work on Full HTML but not on a restricted format.**
Same cause. Full HTML does not filter, so it hides the problem.

**`ck-anchor` appears in rendered output.**
Editor library too old. Check
`libraries/ckeditor5-anchor-drupal/package.json` for the installed version.

**Legacy `name` anchors stopped working after an upgrade.**
The post update has not run, or ran and was then reverted by a configuration
import. See the note above.

## Next steps

- [Configuration](0-configuration.md)
- [Upgrading](3-upgrading.md)
