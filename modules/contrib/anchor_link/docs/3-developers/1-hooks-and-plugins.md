# Hooks and Plugins

Reference for the classes the module ships.

## Hook class

### `Drupal\anchor_link\Hook\AnchorLinkHooks`

Registered in `anchor_link.services.yml` with autowiring:

```yaml
services:
  Drupal\anchor_link\Hook\AnchorLinkHooks:
    class: Drupal\anchor_link\Hook\AnchorLinkHooks
    autowire: true
```

Uses `StringTranslationTrait`.

#### `help($route_name, RouteMatchInterface $route_match)`

```php
#[Hook('help')]
public function help($route_name, RouteMatchInterface $route_match)
```

Returns the module's help markup for the `help.page.anchor_link` route.

#### The plugin definition guards its own attributes

`anchor_link.ckeditor5.yml` declares a `htmlSupport.disallow` for `id` and
`name` on `<a>` in the plugin's own configuration, so General HTML Support
leaves the anchor attributes to the anchor plugin - but only in editors where
the Anchor button is enabled. It also loads `link.LinkEditing` and
`link.LinkUI` alongside the anchor plugin, so a toolbar carrying the Anchor
button without the Link button still gets a working editor.

The unit test covers this definition shape. See
[PHPUnit](../4-testing/4-phpunit.md).

## Plugin classes

### `src/Plugin/CKEditor4To5Upgrade/Anchor.php`

Implements the CKEditor 4 to 5 upgrade path, mapping the old Anchor button and
its settings onto the CKEditor 5 plugin. This is what lets a site running the
8.x branches move to 3.0.x without losing its toolbar configuration.

### `src/Plugin/CKEditorPlugin/`

`Anchor.php`, `Link.php` and `Unlink.php` are the legacy **CKEditor 4** plugin
classes, paired with the assets under `js/anchor/`, `js/link/` and `js/unlink/`.
They exist for sites that have not yet migrated. They are not used by CKEditor 5.

### `src/Plugin/Linkit/Matcher/CKEditorAnchorLinkMatcher.php`

The Linkit matcher. Documented separately in
[The Linkit Matcher](2-linkit-matcher.md).

## Post update functions

### `anchor_link_post_update_allow_anchor_name_attribute()`

Appends `<a name>` to the allowed HTML of every text format whose editor has the
anchor plugin enabled and whose format uses `filter_html`.

It delegates to a private helper:

```php
function _anchor_link_append_to_filter_html_settings(
  string $cke5_plugin_id,
  string $allowed_html_to_append
)
```

The helper is deliberately given its own module-prefixed name, so it never
collides with the identically shaped helper Drupal 10 core ships in
`ckeditor5.post_update.php`.

How it works:

1. Loads every `Editor` entity, skipping those not using `ckeditor5`.
2. Asks the CKEditor 5 plugin manager for the enabled plugin ids of each, which
   works for plugins with and without toolbar items.
3. For each affected editor, loads the filter format, skips it unless
   `filter_html` is enabled, appends the string to `allowed_html`, and saves.

Because it writes configuration, sites managing configuration in code must
export after running it.

## Extension points

The module has no dedicated API, hooks or events of its own. The available
extension points are the standard Drupal ones:

- **`hook_ckeditor5_plugin_info_alter()`** - alter the anchor plugin
  definition from your own module, for example to change its label or the
  elements it declares.
- **`hook_editor_js_settings_alter()`** - alter the settings handed to CKEditor
  5 for a given format.
- **Linkit matcher plugins** - add your own matcher rather than modifying this
  one, if you need different suggestion behaviour.
- **CSS in your admin theme** - restyle or reword the invisible anchor marker.
- **Theme CSS** - handle front-end concerns such as `scroll-margin-top` on
  anchor targets under a sticky header.

If you need something the module does not expose, open an issue in the
[issue queue](https://www.drupal.org/project/issues/anchor_link) rather than
patching locally.

## Next steps

- [The Linkit Matcher](2-linkit-matcher.md)
- [Architecture](0-architecture.md)
