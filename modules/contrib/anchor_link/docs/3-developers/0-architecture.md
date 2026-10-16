# Architecture

How the Drupal module, the CKEditor 5 plugin and the JavaScript library fit
together.

## The three pieces

```
drupal/anchor_link  (this module)
  |
  |-- declares a CKEditor 5 plugin  ......  anchor_link.ckeditor5.yml
  |-- declares an asset library  .........  anchor_link.libraries.yml
  |-- alters core plugin definitions  ....  src/Hook/AnchorLinkHooks.php
  |-- supplies a Linkit matcher  .........  src/Plugin/Linkit/Matcher/
  |-- widens allowed HTML on update  .....  anchor_link.post_update.php
  |
  '-- loads ->  vardot/ckeditor5-anchor-drupal  (the editor behaviour)
                  libraries/ckeditor5-anchor-drupal/build/anchor-drupal.js
```

The division of labour matters when debugging: **anything visible inside the
editing surface is the library's job**; anything to do with configuration,
filtering, updates or Drupal integration is the module's.

## Repository layout

| Path | Purpose |
|------|---------|
| `anchor_link.info.yml` | Module definition. Depends on `ckeditor5` and `editor`. |
| `anchor_link.ckeditor5.yml` | Declares the `anchor_link_ckeditor5_anchor` CKEditor 5 plugin, its toolbar item and the HTML elements it supports. |
| `anchor_link.libraries.yml` | `cke5_anchor_link` (the library JS) and `admin.cke5_anchor_link` (admin CSS). |
| `anchor_link.services.yml` | Registers the hook class with autowiring. |
| `anchor_link.post_update.php` | Post update that widens `filter_html` allowed HTML. |
| `anchor_link.module` | Thin legacy file; hooks live in `src/Hook/`. |
| `src/Hook/AnchorLinkHooks.php` | `hook_help()`. |
| `src/Plugin/CKEditor5PluginXToY/`, `src/Plugin/CKEditor4To5Upgrade/Anchor.php` | CKEditor 4 to 5 upgrade mapping. |
| `src/Plugin/CKEditorPlugin/` | Legacy CKEditor 4 plugin classes (`Anchor`, `Link`, `Unlink`). |
| `src/Plugin/Linkit/Matcher/CKEditorAnchorLinkMatcher.php` | Linkit matcher for anchor suggestions. |
| `css/anchor_link.admin.css` | Admin-side styling, including the invisible anchor marker. |
| `js/` | Legacy CKEditor 4 plugin assets and translations. |
| `tests/` | varbase-e2e feature suite, step definitions, selectors, test recipe and PHPUnit tests. |

## The CKEditor 5 plugin declaration

`anchor_link.ckeditor5.yml` is where Drupal learns the plugin exists:

```yaml
anchor_link_ckeditor5_anchor:
  ckeditor5:
    plugins:
      - anchorDrupal.Anchor
  drupal:
    label: Anchor link
    library: anchor_link/cke5_anchor_link
    admin_library: anchor_link/admin.cke5_anchor_link
    toolbar_items:
      anchor:
        label: Anchor link
    elements:
      - <a>
      - <a id="">
      - <a name="">
      - <a target="">
      - <a rel="">
      - <a class="ck-anchor">
```

Three things to note:

- `anchorDrupal.Anchor` is the export from the external library. The module
  itself contains no CKEditor 5 source.
- `elements` is what lets Drupal merge the needed attributes into a format's
  allowed HTML when the button is added.
- `admin_library` loads only while configuring a format, which is where the
  invisible anchor marker styling is needed.

## Hook implementations

Hooks are OOP hooks (`#[Hook]` attributes) in `src/Hook/AnchorLinkHooks.php`,
registered as an autowired service.

### `hook_help()`

Provides the module's help text at `/admin/help/page/anchor_link`.

### Keeping General HTML Support away from the anchors

Core's General HTML Support would otherwise claim the `<a>` attributes the
anchor plugin owns. The plugin definition carries the guard itself, so it only
applies in editors where the Anchor button is enabled:

```yaml
ckeditor5:
  config:
    htmlSupport:
      disallow:
        - name: a
          attributes:
            - id
            - name
```

An editor without the Anchor button is left alone, so its anchors stay with
General HTML Support. The definition also loads `link.LinkEditing` and
`link.LinkUI` with the anchor plugin, because the library's AnchorUI requires
them - the editor attaches whatever the toolbar holds.

## The `ck-anchor` class

`ck-anchor` is an **editor-only** marker. The library uses it to identify
anchors in the editing view so it can render the "INVISIBLE ANCHOR" flag and
make empty anchors selectable.

It is declared in the plugin's `elements` so the editor can use it, but it is
not written to the data view. Saved content contains the `id` (or `name`), not
the class. If it leaks into saved content, that is a library bug.

## Anchors in saved markup

```html
<!-- named span of text -->
<a id="pricing">Pricing</a>

<!-- empty anchor, renders nothing -->
<a id="pricing"></a>

<!-- a link that is also an anchor -->
<a id="see-also" href="/related">Related</a>
```

`name` is read as well as `id`, for content authored before `id` took over.

## Next steps

- [Hooks and Plugins](1-hooks-and-plugins.md)
- [The Linkit Matcher](2-linkit-matcher.md)
- [Testing Overview](../4-testing/0-overview.md)
