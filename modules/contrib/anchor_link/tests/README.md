# CKEditor Anchor Link — varbase-e2e test suite

Browser-driven BDD tests (Playwright + Cucumber-js, via
[varbase-e2e](https://www.npmjs.com/package/varbase-e2e)). Every scenario drives
the site through the browser only.

## Layout

| Path | Purpose |
|------|---------|
| `features/drupal/` | Gherkin `.feature` files for the Drupal Standard profile. |
| `step-definitions/` | `anchor_link.steps.js` — module-specific steps (login, editor driving, anchor assertions). |
| `selectors/` | Named-selector JSON registries (`anchor_link.json`, shared Claro presets). |
| `recipes/anchor_link_test/` | Recipe that creates the "Anchor Test" CKEditor 5 format used by the suite. |
| `reports/`, `screenshots/`, `videos/` | Run artefacts. |

## Running locally (DDEV)

```bash
# Install the module and apply the test recipe on a Standard site, then:
LAUNCH_URL="https://<your-site>.ddev.site:<port>" \
  node ./node_modules/.bin/cucumber-js --config cucumber.js --tags "not @wip"
```

The first feature (`01-01-01-users-login`) signs in as the Webmaster and
provisions the per-role testing users. The `anchor_test` text format is created
by applying `tests/recipes/anchor_link_test` (see `.gitlab-ci.yml`).
