# CKEditor Anchor Link

This plugin module adds the better link dialog and anchor related features
to CKEditor in Drupal:

- Dialog to insert links and anchors with some properties.
- Context menu option to edit or remove links and anchors.
- Ability to insert a link with the URL using multiple protocols, including an
  external file if a file manager is integrated.

Most text formats limit HTML tags. If this is the case, it will
 be necessary to whitelist the "name" attribute on the "a" element.

E.g. `<a name href hreflang>`

## Table of contents

- Requirements
- Installation
- Maintainers

## Requirements
* Core CKEditor 5
* Include the [ckeditor5-anchor-drupal](https://www.npmjs.com/package/@northernco/ckeditor5-anchor-drupal) plugin library via your site's composer file as a drupal-library. See the details in composer.libraries.json; you should be able to copy/paste most of that into your composer.json.


## Installation

### OPTION #1


This plugin uses an external CKEditor5 library: Anchor Link CKEditor plugin

The library can be managed via composer, but you must require `wikimedia/composer-merge-plugin`:
```
composer require wikimedia/composer-merge-plugin
```

and expand the extra section of your composer.json like so (you may need to adjust the `"include"` directory, e.g. your web files may start with docroot instead of web in which case it would start with docroot/modules):
```
    "extra": {
        "merge-plugin": {
            "include": [
                "web/modules/contrib/anchor_link/composer.libraries.json"
            ]
        }
    }
```

then, you can require the library like:
```
composer require drupal/anchor_link:^3.0
```

In some cases you may need to run the above command twice so that the merge plugin picks up the library after the library information from the module is added.


### OPTION #2

Install with Composer (recommended) or manually, by following these steps.

[Downloading third-party libraries using Composer](https://www.drupal.org/docs/develop/using-composer/manage-dependencies#third-party-libraries)


Define npm-asset repository in the `composer.json` file, to allow downloading the CKEditor Anchor Link JavaScript library to the correct folder:

```
composer config repositories.assets composer https://asset-packagist.org
composer config --json extra.installer-types '["npm-asset", "bower-asset"]'
composer config --json extra.installer-paths.web\/libraries\/ckeditor5-anchor-drupal '["npm-asset/northernco--ckeditor5-anchor-drupal"]'
composer config --unset extra.installer-paths.web\/libraries\/\{\$name\}
composer config --json extra.installer-paths.web\/libraries\/\{\$name\} '["type:drupal-library", "type:bower-asset", "type:npm-asset"]'
```

Download the CKEditor Anchor Link module and install it:

```
composer require npm-asset/northernco--ckeditor5-anchor-drupal
composer require drupal/anchor_link:~3.0
drush en anchor_link
```

## Maintainers
- Mohammed Razem - [mohammed-j-razem](https://www.drupal.org/u/mohammed-j-razem)
- Dylan Donkersgoed - [dylan-donkersgoed](https://www.drupal.org/u/dylan-donkersgoed)
- Rajab Natshah - [rajab-natshah](https://www.drupal.org/u/rajab-natshah)
