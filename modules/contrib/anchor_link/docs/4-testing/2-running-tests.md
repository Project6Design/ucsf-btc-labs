# Running Tests

How to run the suites locally, in DDEV, and as a full pipeline before pushing.

## Prerequisites

- Node.js **>= 20**, Yarn **>= 4.9.3** (the repo pins `yarn@4.9.3` via
  `packageManager`)
- A Drupal site with `anchor_link` enabled and the editor library in
  `libraries/ckeditor5-anchor-drupal`
- The `anchor_test` format applied from the test recipe

Install dependencies and the browser:

```bash
yarn install
./node_modules/.bin/playwright install --with-deps chromium
```

## Provisioning the site under test

```bash
# Enable the editor stack and the module.
drush en -y ckeditor5 editor filter anchor_link

# Create the Anchor Test format, the article type and its body field.
drush recipe /path/to/anchor_link/tests/recipes/anchor_link_test -y

drush cache:rebuild
```

The suite creates its own non-admin users on the first feature, so nothing else
is needed.

## Running the acceptance suite

```bash
LAUNCH_URL="https://my-site.ddev.site" \
  node ./node_modules/.bin/cucumber-js --config cucumber.js --tags "not @wip"
```

Or through the package scripts:

```bash
yarn test                # default browser
yarn test:chromium
yarn test:firefox
yarn test:webkit
```

### Useful tag filters

```bash
# Critical editor behaviour only.
cucumber-js --config cucumber.js --tags "@critical"

# Skip the accessibility pass.
cucumber-js --config cucumber.js --tags "not @a11y"

# Smoke check.
cucumber-js --config cucumber.js --tags "@smoke"
```

Remember that most features depend on the `@setup` feature having run, so
filtering down to a single feature on a fresh site can fail for want of users.
Run the setup feature first, or run the whole suite.

### Generating the reports

```bash
node ./node_modules/@vardot/varbase-e2e/bin/generate-reports.js \
  --json tests/reports/cucumber_report.json \
  --out  tests/reports/cucumber_report.html \
  --format all \
  --pdf-out tests/reports/cucumber_report.pdf
```

## Running in DDEV

Per the workspace rules, use DDEV rather than host tooling:

```bash
ddev composer require drupal/anchor_link
ddev drush en -y anchor_link
ddev drush recipe web/modules/contrib/anchor_link/tests/recipes/anchor_link_test -y
```

Then run the browser suite against the DDEV URL, pointing `LAUNCH_URL` at the
site's `https://<project>.ddev.site`.

## Linting

```bash
yarn spellcheck                       # cspell
yarn lint:yaml                        # eslint, YAML config
./node_modules/.bin/eslint --ext .js . # eslint, JavaScript
```

PHP linting matches what CI runs:

```bash
phpcs --standard=Drupal,DrupalPractice .
phpstan analyse
```

`phpstan.neon` sets level 1 and excludes three areas that cannot be analysed in
a plain environment: the legacy CKEditor 4 plugins (their base class was removed
from core in Drupal 10), the CKEditor 4 to 5 upgrade plugin, and the optional
Linkit integration.

## PHPUnit

```bash
# From the Drupal root.
vendor/bin/phpunit -c web/core web/modules/contrib/anchor_link/tests/src/Unit
```

See [PHPUnit](4-phpunit.md).

## Running the whole pipeline locally

Before pushing anything to `git.drupalcode.org`, run the full pipeline with
[gitlab-ci-local](https://github.com/firecow/gitlab-ci-local) and only push once
every job is green.

The real `.gitlab-ci.yml` includes the drupalci templates and runs inside images
that `gitlab-ci-local` cannot reproduce, so the repo ships a self-contained
mirror:

```bash
# Everything.
gitlab-ci-local --file .gitlab-ci-local.yml

# List the jobs.
gitlab-ci-local --file .gitlab-ci-local.yml --list

# One job.
gitlab-ci-local --file .gitlab-ci-local.yml functional-tests
gitlab-ci-local --file .gitlab-ci-local.yml cspell
```

`.gitlab-ci-local.yml` builds the site itself: a MariaDB service, `drush
runserver`, and the module pulled in through a Composer **path** repository
pointing at the checkout, so the exact working tree is what gets exercised.

A green run looks like:

```
 PASS  cspell
 PASS  eslint
 PASS  functional-tests
```

Never mask a failing job with `|| true`, and keep every job
`allow_failure: false`. A red pipeline blocks the push.

## Troubleshooting

**Editor never becomes ready, steps time out.**
The first authenticated load compiles the admin theme and CKEditor 5 assets.
Warm the node form once, or disable CSS/JS aggregation on the test site, which
is what the pipeline does.

**`cucumber.js` looks modified after `yarn install`.**
varbase-e2e's postinstall can clobber it under Yarn 4. The pipeline backs the
file up before `yarn install` and restores it afterwards; do the same locally if
you hit it.

**Console errors fail a scenario.**
That is the point: `javascript.mode: 'warn'` with `levels: ['error']` reports
browser console errors after each scenario. Read the reported error rather than
disabling the check.

**Anchors are missing from saved content in a scenario.**
The `anchor_test` format was not applied. Re-run the recipe.

## Next steps

- [GitLab CI](3-gitlab-ci.md)
- [PHPUnit](4-phpunit.md)
