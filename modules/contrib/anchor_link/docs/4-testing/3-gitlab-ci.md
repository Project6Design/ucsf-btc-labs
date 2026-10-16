# GitLab CI

Every change to CKEditor Anchor Link is gated by the pipeline defined in
`.gitlab-ci.yml`, which runs on
[git.drupalcode.org](https://git.drupalcode.org/project/anchor_link).

## Shape of the pipeline

The file has two halves:

1. **Drupal CI validation jobs** from the drupalci templates: `composer`,
   `composer-lint`, `cspell`, `eslint`, `phpcs`, `phpstan`, `phpunit`.
2. **A functional-tests browser job** running the varbase-e2e suite against a
   freshly installed Drupal Standard site with the module enabled and the Anchor
   Test recipe applied.

## Includes and variables

```yaml
include:
  - project: $_GITLAB_TEMPLATES_REPO
    ref: $_GITLAB_TEMPLATES_REF
    file:
      - "/includes/include.drupalci.main.yml"
      - "/includes/include.drupalci.variables.yml"
      - "/includes/include.drupalci.workflows.yml"

variables:
  COMPOSER_FLAGS: "--no-strict-lock"
  COMPOSER_MEMORY_LIMIT: "-1"
  YARN_ENABLE_IMMUTABLE_INSTALLS: "false"
```

`YARN_ENABLE_IMMUTABLE_INSTALLS: "false"` is needed because no `yarn.lock` is
committed and Yarn 4 defaults to immutable installs, which would refuse to
create one.

## Everything blocks

Each validation job is explicitly un-masked:

```yaml
composer-lint:
  allow_failure: false
cspell:
  allow_failure: false
eslint:
  allow_failure: false
phpcs:
  allow_failure: false
phpstan:
  allow_failure: false
phpunit:
  allow_failure: false
```

The functional job sets `allow_failure: false` too. Nothing in this pipeline is
advisory, and no command is masked with `|| true`. A red pipeline blocks the
change.

## Reusable fragments

Shared setup lives under `.fragments` and is pulled in with `!reference`:

- **`web_db`** - creates the `files` and `simpletest` directories, fixes
  permissions, points `/var/www/html` at the built docroot, starts Apache and
  exports `SIMPLETEST_DB`.
- **`node_playwright`** - installs Node 20, enables Corepack, runs `yarn
  install`, and installs Chromium with its system dependencies.

`node_playwright` backs `cucumber.js` up before `yarn install` and restores it
afterwards, because varbase-e2e's postinstall can clobber it under Yarn 4.

## The functional-tests job

Outline:

1. `needs: [composer]`, so it reuses the built site rather than rebuilding.
2. Overrides the template's services down to the database.
3. Applies `web_db`, enables `ckeditor5 editor filter anchor_link`, applies the
   `tests/recipes/anchor_link_test` recipe, rebuilds the cache.
4. Applies `node_playwright`.
5. Runs cucumber-js with `--tags "not @wip"` and
   `VARBASE_E2E_REPORT_DISABLE=1`, then generates the reports explicitly.

Artefacts (`tests/reports/`, `tests/screenshots/`, `tests/videos/`) are kept
`when: always` for seven days, with `tests/reports/junit.xml` picked up as a
JUnit report, so a failure can be inspected from the pipeline page.

## Tag pipelines

The pipeline also runs when a tag is pushed. Anything in a job's `before_script`
that assumes a branch checkout has to work on a tag checkout too.

The trap worth knowing: a Composer **path** repository derives the package
version from the checkout. On a tag it reports the tag (`3.0.5`), not
`3.0.x-dev`. A job that hardcodes

```yaml
composer require 'drupal/anchor_link:3.0.x-dev@dev'
```

resolves fine on a branch and fails on a tag with

> Root composer.json requires drupal/anchor_link 3.0.x-dev@dev ... but
> drupal/anchor_link[3.0.5] from path repo has higher repository priority.

This is a setup failure, not a test failure: the suite never starts. Keep
version constraints in CI tag-agnostic.

## Running the pipeline locally first

Before pushing, run the self-contained mirror with `gitlab-ci-local`:

```bash
gitlab-ci-local --file .gitlab-ci-local.yml
```

Push only when every job is green. See [Running Tests](2-running-tests.md).

## Reading a failure

1. Open the pipeline and find the failed job.
2. Read the trace from the bottom. Distinguish a **setup** failure in
   `before_script` from a **test** failure in `script`.
3. For a browser failure, download the artefacts. The failure screenshot is
   prefixed `failed_` and there is a video of the scenario.
4. Reproduce locally with `gitlab-ci-local` before pushing a fix.

## Next steps

- [PHPUnit](4-phpunit.md)
- [Running Tests](2-running-tests.md)
