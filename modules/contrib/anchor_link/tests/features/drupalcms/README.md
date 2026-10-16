# Drupal CMS feature suite

Scenarios for the **Drupal CMS** distribution (run by the
`varbase-e2e-drupal-cms-test` CI job). Drupal CMS locks its `field_content`
field to its own `content_format`, so these features drive the "Utility page"
(`node/add/page`) editor — the CI job adds the Anchor button to `content_format`
in its `before_script`. The Drupal Core suite lives in `../drupal/`.
