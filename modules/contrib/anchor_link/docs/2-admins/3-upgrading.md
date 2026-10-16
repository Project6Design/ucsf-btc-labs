# Upgrading

What to watch for when moving between versions.

## Standard upgrade

```bash
composer update drupal/anchor_link --with-dependencies
drush updatedb -y
drush cache:rebuild
```

`--with-dependencies` matters here, because most of the editor behaviour lives
in the `vardot/ckeditor5-anchor-drupal` library rather than in the module. A
module update that leaves the old library in place will not deliver the fixes.

Verify the library version afterwards:

```bash
cat libraries/ckeditor5-anchor-drupal/package.json | grep '"version"'
```

## The module and the library are versioned separately

| Component | Package | Where it lives |
|-----------|---------|----------------|
| Drupal module | `drupal/anchor_link` | `modules/contrib/anchor_link` |
| CKEditor 5 plugin | `vardot/ckeditor5-anchor-drupal` | `libraries/ckeditor5-anchor-drupal` |

The module requires `^1.0.3 || ^2.0.5`. Several editor-side bugs, including ids
being stripped from links and the anchor flag rendering twice, are fixed in the
library, not in the module. If the symptom is visible **inside** the editor, the
library version is the first thing to check.

## Post updates

Run `drush updatedb` after every module update.

The module currently ships one post update:

- `anchor_link_post_update_allow_anchor_name_attribute` - appends `<a name>` to
  the allowed HTML of formats that already have the anchor plugin enabled.

If your site manages configuration in code, export after running it, or the next
configuration import will revert it:

```bash
drush updatedb -y
drush config:export
```

See [Text Formats and Allowed HTML](1-text-formats.md) for the detail.

## Upgrading from CKEditor 4 (the 8.x-1.x and 8.x-2.x branches)

The 3.0.x branch targets CKEditor 5. The module ships a
`CKEditor4To5Upgrade` plugin, so Drupal's own CKEditor 4 to 5 upgrade path knows
how to map the old Anchor button to the new one.

Sequence:

1. Upgrade Drupal core to a version with CKEditor 5.
2. Upgrade `drupal/anchor_link` to 3.0.x.
3. Run `drush updatedb`.
4. For each text format, switch the text editor from CKEditor 4 to CKEditor 5.
   Drupal maps the Anchor button across.
5. Review each format's toolbar and allowed HTML afterwards.

Content authored under CKEditor 4 used the `name` attribute. That is why `name`
is still read: existing anchors keep working without a content migration.

## Verifying an upgrade

Beyond `drush status`, check the behaviour that tends to regress:

1. Open a piece of existing content with anchors and confirm they are still
   there in the editor.
2. Save it without changes, then diff the rendered HTML. Anchors should be
   unchanged and `ck-anchor` should not appear.
3. Insert a link that also carries an id, save, and confirm the id survives.
4. Round-trip an anchor through the Source view.
5. Follow an anchor link on the front end.

These are the same behaviours the automated suite covers, so if you have the
test suite available, run that instead. See
[Running Tests](../4-testing/2-running-tests.md).

## Rolling back

Composer makes the module rollback easy:

```bash
composer require drupal/anchor_link:3.0.4 --with-dependencies
drush cache:rebuild
```

The post update is not reversible, but it also does not need reverting: an extra
`<a name>` in the allowed HTML is harmless on an older module version.

## Next steps

- [Text Formats and Allowed HTML](1-text-formats.md)
- [Architecture](../3-developers/0-architecture.md)
