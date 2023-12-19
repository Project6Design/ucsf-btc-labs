# CKEditor Anchor Link

This plugin module adds the better link dialog and anchor related features
to CKEditor in Drupal 9:

- Dialog to insert links and anchors with some properties.
- Context menu option to edit or remove links and anchors.
- Ability to insert a link with the URL using multiple protocols, including an
  external file if a file manager is integrated.

Most text formats limit HTML tags. If this is the case, it will
 be necessary to whitelist the "name" attribute on the "a" element.

E.g. `<a name href hreflang>`

### Requirements
* Core CKEditor
* Include the [ckeditor5-anchor-drupal](https://www.npmjs.com/package/@northernco/ckeditor5-anchor-drupal) plugin library via your site's composer file as a drupal-library. See the details in composer.libraries.json; you should be able to copy/paste most of that into your composer.json.
