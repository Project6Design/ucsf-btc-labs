# Frequently Asked Questions

Common questions about CKEditor Anchor Link.

## General

### What does this module do?

It adds an **Anchor** button to the CKEditor 5 toolbar in Drupal. Editors place
named anchors in content and link to them with a fragment (`#my-anchor`), so a
link can jump to a point on a page instead of only to the top of it.

### Is it free?

Yes. GPL-2.0-or-later, like Drupal itself.

### Which Drupal versions are supported?

The 3.0.x branch supports Drupal 10, 11 and 12, and targets CKEditor 5. The
8.x-1.x and 8.x-2.x branches were for CKEditor 4.

### Do I need Linkit?

No. Linkit is optional. It only adds an autocomplete that offers anchor
suggestions in the link field. The Anchor button works without it.

---

## Installation

### Why is there a separate JavaScript library?

The CKEditor 5 plugin itself is
[vardot/ckeditor5-anchor-drupal](https://github.com/Vardot/ckeditor5-anchor-drupal),
a standalone package. The Drupal module registers it, integrates it with text
formats and filtering, and ships the Drupal-side plumbing. This is the normal
shape for a CKEditor 5 integration.

### The button is listed but nothing happens when I click it.

The library is missing or is not where `anchor_link.libraries.yml` expects it:

```bash
ls libraries/ckeditor5-anchor-drupal/build/anchor-drupal.js
```

If it is absent, your project is missing the `drupal-library` installer path.
See [Installation](1-users/0-installation.md).

### Composer put the library in `vendor/` instead of `libraries/`.

Add the `drupal-library` installer path shown in `composer.libraries.json` to
your project's `composer.json`, then re-run `composer install`.

### Which library version do I need?

`^1.0.3 || ^2.0.5`. Several editor-side fixes ship in the library rather than the
module, so update it with `--with-dependencies`.

---

## Using anchors

### What characters can an anchor name contain?

Treat it as an HTML `id`: letters, digits, hyphens and underscores. No spaces.
Lowercase with hyphens (`shipping-and-returns`) is the convention.

### Can two anchors have the same name?

They should not. Duplicate ids are invalid HTML and the browser will only ever
jump to the first one.

### What is "INVISIBLE ANCHOR" in my editor?

An empty anchor, flagged so you can see and select it while editing. It renders
nothing on the front end. Its label and styling come from CSS, so a site can
reword or restyle it. See [Configuration](2-admins/0-configuration.md).

### The invisible anchor flag shows twice.

A library bug, fixed in `vardot/ckeditor5-anchor-drupal` 2.0.5. Update the
library.

### Can a link also be an anchor?

Yes. An `<a>` with both `href` and `id` keeps both. Earlier versions dropped the
id in that case; that is fixed.

### Does renaming an anchor break links?

Yes, every link and bookmark pointing at the old name. Anchor names end up in
shared URLs, so pick them carefully and keep them stable.

### Why does my anchor link not scroll to the right place?

Usually a sticky header covering the target. That is a theme concern; the usual
fix is `scroll-margin-top` on the anchor target in your theme's CSS.

---

## Text formats and filtering

### My anchors disappear when I save.

The text format strips the `id` attribute. Re-save the format through the
configuration form so Drupal merges the plugin's required elements into the
allowed HTML, or add `id` to the `<a>` entry by hand. See
[Text Formats and Allowed HTML](2-admins/1-text-formats.md).

### Anchors work on Full HTML but not on my restricted format.

Same cause. Full HTML does not filter, so it hides the problem.

### Why is `class="ck-anchor"` in my rendered HTML?

`ck-anchor` is an editor-only marker. Library 2.0.5 and newer never write it to
saved content, so on a current site it should not appear.

Two reasons you might still see it:

- **The library is older than 2.0.5.** Update it.
- **The content was saved before the update.** The fix stops new writes; it does
  not rewrite content that already exists. Those anchors still work - the class
  does nothing on the front end - and they lose it the next time an editor saves
  the page. There is nothing you have to do.

### The status report does not list the library after an update.

Rebuild the cache. The report is assembled from the module's install file, and
that file is only picked up once the caches are rebuilt after it changes. Both
`drush updatedb` and the update page rebuild the cache at the end, so a normal
update run resolves it; a code-only deployment that skips the rebuild does not.

### What does the post update do?

`anchor_link_post_update_allow_anchor_name_attribute` appends `<a name>` to the
allowed HTML of formats that already have the anchor plugin enabled, so anchors
authored with the legacy `name` attribute keep working. Run `drush updatedb`.

### The post update ran, then anchors broke again.

Your site manages configuration in code and a `config:import` reverted the
change. Run `drush updatedb` then `drush config:export`, and commit the result.

### Does it conflict with General HTML Support?

No. The module alters core's arbitrary HTML support plugin so it does not claim
the `<a>` attributes the anchor plugin owns. This is automatic.

---

## Linkit

### How do I get anchor suggestions in the link field?

Enable Linkit, add the **CKEditor Anchor link** matcher to the Linkit profile
your format uses, and make sure the format's Linkit filter points at that
profile. See [Linkit Integration](2-admins/2-linkit-integration.md).

### Why does it not suggest anything for a URL I pasted?

By design. The matcher stays quiet when the search string has a scheme, a `/`,
or whitespace, because none of those can be a fragment. Otherwise every pasted
URL produced a nonsense `#https://...` suggestion.

### Does it find anchors that actually exist?

Not today. The current matcher turns what you type into a fragment; it does not
search content. A reworked matcher that queries content is proposed in
[#3460457](https://www.drupal.org/project/anchor_link/issues/3460457).

### I get a PHP deprecation warning from the matcher.

`ltrim()` receiving `NULL` on PHP 8.1 or newer, when the field is empty. Fixed
in 3.0.5.

---

## Upgrading

### How do I upgrade from CKEditor 4?

Upgrade core to a CKEditor 5 version, move to `drupal/anchor_link` 3.0.x, run
`drush updatedb`, then switch each text format's editor to CKEditor 5. The
module ships a CKEditor 4 to 5 upgrade plugin, so the Anchor button maps across.
See [Upgrading](2-admins/3-upgrading.md).

### Will my old `name`-based anchors keep working?

Yes. The `name` attribute is still read, which is exactly why. No content
migration is needed.

### Do I need to update the library when I update the module?

Yes. Use `composer update drupal/anchor_link --with-dependencies`. Many fixes are
in the library, not the module.

---

## Development and testing

### How do I run the tests?

Two suites: a varbase-e2e browser suite and PHPUnit unit tests. See
[Running Tests](4-testing/2-running-tests.md).

### Can I run the CI pipeline locally?

Yes, with `gitlab-ci-local` against the self-contained `.gitlab-ci-local.yml`.
Run it and get every job green before pushing. See
[GitLab CI](4-testing/3-gitlab-ci.md).

### Does the module provide hooks or an API?

No dedicated API. Use the standard extension points:
`hook_ckeditor5_plugin_info_alter()`, `hook_editor_js_settings_alter()`, your own
Linkit matcher, and CSS. See
[Hooks and Plugins](3-developers/1-hooks-and-plugins.md).

### Where do I report a bug?

The [issue queue](https://www.drupal.org/project/issues/anchor_link). If the
symptom is inside the editing surface, check the library version first and say
which version you have.
