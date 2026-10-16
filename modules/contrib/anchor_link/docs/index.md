# CKEditor Anchor Link Documentation

A Drupal module that adds anchor support and a richer link dialog to CKEditor 5,
so editors can create in-page jump targets and link to them without touching
the HTML source.

## What is CKEditor Anchor Link?

CKEditor Anchor Link gives CKEditor 5 in Drupal an **Anchor** toolbar button.
Editors place a named anchor anywhere in the body text, then link to it with a
fragment (`#my-anchor`) from the same page or from anywhere else on the site.

An anchor is written as an `id` on the `<a>` element. The `name` attribute is
also read, so content authored before `id` took over keeps working.

### Key features

- **Anchor toolbar button**: insert, edit and remove named anchors from the
  CKEditor 5 toolbar.
- **Invisible anchor markers**: anchors with no text are flagged in the editor
  so authors can see and select them, while staying invisible on the front end.
- **Ids survive on real links**: an `<a href>` that also carries an `id` keeps
  both, so a link can be a jump target too.
- **Source editing safe**: anchor markup round-trips through the Source editing
  view without being stripped.
- **Backwards compatible**: the legacy `name` attribute is supported alongside
  `id`, with a post update that widens the allowed HTML of formats already
  using the plugin.
- **Linkit integration**: an optional matcher suggests `#fragment` targets in
  the Linkit autocomplete.
- **Themeable marker**: the "INVISIBLE ANCHOR" label can be reworded or
  restyled entirely from CSS.

## Getting started

### For content editors

If you write content and need in-page navigation:

- [Installation and Setup](1-users/0-installation.md) - Get the module and the
  editor library in place
- [Inserting Anchors](1-users/1-inserting-anchors.md) - Use the Anchor button
  in CKEditor 5
- [Linking to Anchors](1-users/2-linking-to-anchors.md) - Point links at a
  fragment, on the same page or across the site
- [Common Use Cases](1-users/3-use-cases.md) - Tables of contents, FAQ jump
  lists, long-form policy pages

### For site administrators

If you configure text formats and editor toolbars:

- [Configuration](2-admins/0-configuration.md) - Add the Anchor button to a
  text format
- [Text Formats and Allowed HTML](2-admins/1-text-formats.md) - What
  `filter_html` has to permit, and why
- [Linkit Integration](2-admins/2-linkit-integration.md) - Enable and configure
  the anchor matcher
- [Upgrading](2-admins/3-upgrading.md) - Library versions, post updates and the
  CKEditor 4 to 5 path

### For developers

If you extend or integrate with the module:

- [Architecture](3-developers/0-architecture.md) - How the module, the CKEditor
  5 plugin and the library fit together
- [Hooks and Plugins](3-developers/1-hooks-and-plugins.md) - The hook
  implementations and plugin classes the module ships
- [The Linkit Matcher](3-developers/2-linkit-matcher.md) - How anchor
  suggestions are produced

### Testing

- [Testing Overview](4-testing/0-overview.md) - The two test layers
- [Automated Functional Acceptance Testing](4-testing/1-automated-functional-acceptance-testing.md) -
  The varbase-e2e browser suite
- [Running Tests](4-testing/2-running-tests.md) - Locally, in DDEV and with
  `gitlab-ci-local`
- [GitLab CI](4-testing/3-gitlab-ci.md) - The pipeline that gates every change
- [PHPUnit](4-testing/4-phpunit.md) - Unit coverage

## Requirements

- Drupal 10, 11 or 12
- Drupal core **CKEditor 5** and **Text Editor** modules
- The [vardot/ckeditor5-anchor-drupal](https://github.com/Vardot/ckeditor5-anchor-drupal)
  plugin library (`^1.0.3 || ^2.0.5`), installed as a `drupal-library`

## Quick links

- [FAQ](faq.md) - Frequently asked questions
- [Project Page](https://www.drupal.org/project/anchor_link) - Drupal.org
  project page
- [Issue Queue](https://www.drupal.org/project/issues/anchor_link) - Report bugs
  and request features
- [Editor library](https://github.com/Vardot/ckeditor5-anchor-drupal) - The
  CKEditor 5 plugin this module loads

## Need help?

- Check the [FAQ](faq.md) for common questions
- Review the documentation section that matches your role
- Search the [issue queue](https://www.drupal.org/project/issues/anchor_link)
  before filing something new
