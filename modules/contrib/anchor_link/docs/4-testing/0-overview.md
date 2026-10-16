# Testing Overview

CKEditor Anchor Link ships two complementary layers of automated tests.

## Test layers

| Layer | Tooling | Location | What it covers |
|-------|---------|----------|----------------|
| **Functional acceptance** | [varbase-e2e](https://www.npmjs.com/package/varbase-e2e) (Playwright + Cucumber-js) | `tests/features/`, `tests/step-definitions/` | Real-browser, black-box behaviour: the toolbar button, inserting anchors, source-editing round trips, links that also carry an id, rendered output, accessibility and text format access control. |
| **PHPUnit** | DrupalCI | `tests/src/Unit/` | White-box coverage of the CKEditor 5 plugin definition alter, in isolation. |

The layers overlap on purpose. PHPUnit pins the alter logic precisely;
varbase-e2e drives a fully built site the way an editor would, through a real
Chromium browser, which is the only way to catch the editor-side regressions
that matter most for this module.

## Why the browser layer carries the weight

Most of this module's behaviour only exists once CKEditor 5 has booted and the
`vardot/ckeditor5-anchor-drupal` library is loaded. Ids being stripped from
links, the anchor flag rendering twice, the `ck-anchor` class leaking into saved
content: none of these are visible to a PHP-level test. They need a real editor
in a real browser, driven end to end through save and render.

## What the acceptance suite proves

Driven entirely through the browser, with no Drush or shell calls inside the
scenarios, the suite asserts that:

- the module is wired into CKEditor 5 and the **Anchor** button appears in the
  live toolbar;
- inserting an anchor produces the expected markup, and it survives a save;
- anchor markup is preserved through a **Source editing** round trip;
- **a link that also carries an id keeps it**, which is the regression behind
  the Critical issue fixed by library 2.0.5;
- rendered anchors are **accessible**, with no serious violations on the key
  surfaces;
- **text format administration is protected**, so an unprivileged user cannot
  reach the format configuration.

## Suite layout

| Path | Purpose |
|------|---------|
| `tests/features/drupal/` | Gherkin `.feature` files for the Drupal Standard profile. |
| `tests/step-definitions/` | `anchor_link.steps.js` - module-specific steps: login, driving the editor, asserting anchor markup. |
| `tests/selectors/` | Named-selector JSON registries (`anchor_link.json`, shared Claro presets). |
| `tests/recipes/anchor_link_test/` | Recipe that creates the "Anchor Test" CKEditor 5 format the suite uses. |
| `tests/src/Unit/` | PHPUnit unit tests. |
| `tests/reports/`, `tests/screenshots/`, `tests/videos/` | Run artefacts, produced by a run and not committed. |

## Feature files

| Feature | Tags | Covers |
|---------|------|--------|
| `01-01-01-users-login` | `@setup` | Signs in as the Webmaster and provisions the per-role testing users. Runs first; the rest depend on it. |
| `01-02-01-anchor-smoke` | `@smoke` | The module is wired into CKEditor 5. |
| `02-01-01-anchor-toolbar` | `@editor @critical` | The Anchor button appears in the live toolbar. |
| `02-02-01-anchor-insert` | `@editor @critical` | Inserting an anchor with the Anchor button. |
| `02-04-01-anchor-accessibility` | `@a11y` | Rendered anchors are accessible. |
| `03-01-01-anchor-source-editing` | `@editor @critical` | Anchor markup is preserved through Source editing. |
| `03-01-02-anchor-link-with-href` | `@editor @critical` | A link that also carries an id keeps it. |
| `04-01-01-access-control` | `@access @security` | Text format administration is protected. |

Every feature also carries the `@anchor_link` tag.

## Requirements

- Node.js **>= 20** (varbase-e2e 2.0 declares `engines.node ">=20"`)
- A running Drupal site with `anchor_link` enabled, the editor library present,
  and the `anchor_test` format provisioned by the test recipe
- Chromium, installed by `playwright install --with-deps chromium`

## Next steps

- [Automated Functional Acceptance Testing](1-automated-functional-acceptance-testing.md)
- [Running Tests](2-running-tests.md)
- [GitLab CI](3-gitlab-ci.md)
- [PHPUnit](4-phpunit.md)
