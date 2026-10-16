# PHPUnit

The module's white-box coverage lives in `tests/src/Unit/`.

## What is covered

`AnchorLinkHooksTest` covers
`Drupal\anchor_link\Hook\AnchorLinkHooks::ckeditor5PluginInfoAlter()`, the alter
that stops core's General HTML Support plugin from claiming the `<a>`
attributes the anchor plugin owns.

```php
/**
 * @coversDefaultClass \Drupal\anchor_link\Hook\AnchorLinkHooks
 *
 * @group anchor_link
 */
class AnchorLinkHooksTest extends UnitTestCase
```

It is a plain `UnitTestCase`: no database, no kernel. That is possible because
`ckeditor5PluginInfoAlter()` is static and takes only the definitions array.

### The two cases

**The anchor attributes are taken away from General HTML Support.** Given a
definitions array containing `ckeditor5_arbitraryHtmlSupport`, the alter must add
the disallow rule:

```php
$this->assertContains([
  'name' => 'a',
  'attributes' => ['id', 'name'],
  'classes' => ['ck-anchor'],
], $disallowed);
```

**Definitions without the plugin are left as they came in.** Given an empty
array, the alter must not invent entries or fail:

```php
$definitions = [];
AnchorLinkHooks::ckeditor5PluginInfoAlter($definitions);
$this->assertSame([], $definitions);
```

The second case guards the `continue` in the loop. Without it, a Drupal version
that does not ship the arbitrary HTML support plugin would take an error inside
the alter.

A helper, `arbitraryHtmlSupportDefinition()`, builds a realistic
`CKEditor5PluginDefinition` for the first case, mirroring the shape core uses.

## Running the tests

From the Drupal root:

```bash
vendor/bin/phpunit -c web/core web/modules/contrib/anchor_link/tests/src/Unit
```

By group:

```bash
vendor/bin/phpunit -c web/core --group anchor_link
```

Under DDEV:

```bash
ddev exec vendor/bin/phpunit -c web/core web/modules/contrib/anchor_link/tests/src/Unit
```

In CI the `phpunit` job from the drupalci templates runs them, with
`allow_failure: false`.

## Where the coverage boundary sits

Most of this module's behaviour cannot be reached from PHPUnit, and trying to
would produce tests that assert nothing useful:

- The **editor behaviour** lives in the `vardot/ckeditor5-anchor-drupal`
  JavaScript library. It needs a browser.
- The **anchor markup round trip** only happens once CKEditor 5 has booted,
  transformed the data view and saved through the filter pipeline.
- The **Linkit matcher** depends on the optional Linkit module, which is why
  `phpstan.neon` excludes `src/Plugin/Linkit/*` from analysis as well.
- The **post update** edits live configuration; it belongs to an update path
  test rather than a unit test.

That boundary is why the acceptance suite carries most of the weight. See
[Testing Overview](0-overview.md).

## Adding a unit test

Put it in `tests/src/Unit/`, namespace `Drupal\Tests\anchor_link\Unit`, tag it
`@group anchor_link`, and prefer logic that can be exercised without a
container. If a change needs the container or the database, a Kernel test is the
right tool; the module does not ship one yet.

If the behaviour you want to pin is visible in the editor or in saved content,
add a Gherkin scenario instead. See
[Automated Functional Acceptance Testing](1-automated-functional-acceptance-testing.md).

## Next steps

- [Testing Overview](0-overview.md)
- [Running Tests](2-running-tests.md)
