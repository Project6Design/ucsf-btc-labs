# Installation and Setup

This guide covers installing CKEditor Anchor Link and getting the Anchor button
into your editor toolbar.

## Requirements

### Core requirements

- Drupal 10, 11 or 12
- PHP 8.1 or higher (8.3 recommended)

### Required Drupal core modules

- **CKEditor 5** (`ckeditor5`)
- **Text Editor** (`editor`)

Both are enabled automatically as dependencies.

### Required library

The editor behaviour lives in a separate JavaScript package:

- [vardot/ckeditor5-anchor-drupal](https://github.com/Vardot/ckeditor5-anchor-drupal)
  `^1.0.3 || ^2.0.5`

It must end up in `libraries/ckeditor5-anchor-drupal/`, because
`anchor_link.libraries.yml` loads
`/libraries/ckeditor5-anchor-drupal/build/anchor-drupal.js`.

### Optional contributed modules

- [Linkit](https://www.drupal.org/project/linkit) - enables the anchor
  autocomplete matcher. Not required for the Anchor button itself.

## Installation methods

### Method 1: Using Composer (recommended)

```bash
composer require drupal/anchor_link
drush en anchor_link
```

Composer pulls in `vardot/ckeditor5-anchor-drupal` as a dependency. For it to
land in `libraries/` rather than `vendor/`, your project needs a
`drupal-library` installer path. The module ships
`composer.libraries.json` as a reference you can copy from:

```json
{
  "extra": {
    "installer-types": ["library"],
    "installer-paths": {
      "web/libraries/{$name}": ["type:drupal-library"]
    }
  }
}
```

If your project was created from `drupal/recommended-project`, this is already
configured.

### Method 2: Using Drush

If the module is already in your codebase:

```bash
drush en anchor_link
drush cache:rebuild
```

### Method 3: Manual installation

1. Download the module from [Drupal.org](https://www.drupal.org/project/anchor_link)
2. Extract it into `modules/contrib`
3. Download the
   [editor library](https://github.com/Vardot/ckeditor5-anchor-drupal/releases)
   and extract it to `libraries/ckeditor5-anchor-drupal`
4. Go to **Administration > Extend** (`/admin/modules`)
5. Find **CKEditor Anchor Link**, tick it and click **Install**
6. Clear the site cache

Manual installation is the fragile path: the library version has to match what
`composer.json` requires, and nothing checks it for you. Prefer Composer.

## Verifying the install

After enabling the module:

```bash
drush pm:list --filter=anchor_link --status=enabled
ls libraries/ckeditor5-anchor-drupal/build/anchor-drupal.js
```

The status report at `/admin/reports/status` carries a **CKEditor Anchor Link
library** row: *Installed* when the library resolves, or a warning naming the
path it looked in. If the row is missing right after an update, rebuild the
cache - the row comes from the module's install file, which is picked up on
rebuild.

Then confirm the button is available:

1. Go to **Configuration > Content authoring > Text formats and editors**
   (`/admin/config/content/formats`)
2. Edit a format that uses CKEditor 5
3. The **Anchor link** button should be in the list of available toolbar items

If the button is listed but does nothing in a live editor, the library file is
missing or the browser cached an older aggregate. Rebuild the cache and reload
with a cold cache.

## Next steps

- [Add the button to a text format](../2-admins/0-configuration.md)
- [Insert your first anchor](1-inserting-anchors.md)
