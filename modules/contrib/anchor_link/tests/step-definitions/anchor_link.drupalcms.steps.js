'use strict';

/**
 * @file
 * Drupal CMS specific step definitions for the CKEditor Anchor Link suite.
 *
 * On the Drupal CMS distribution the content field (`field_content` on the
 * "Utility page" type) is locked to Drupal CMS's own `content_format`, so there
 * is no format selector to switch and no `article` type. These steps drive the
 * `page` node form, whose CKEditor 5 instance uses `content_format` - the
 * varbase-e2e CI job adds the Anchor button to that format in its before_script.
 *
 * The generic editor / anchor assertions (CKEditor toolbar, insert an anchor,
 * editor data, anchor in output) are shared with the Drupal Core suite in
 * anchor_link.steps.js and reused as-is.
 */

const { When } = require('@cucumber/cucumber');
const {
  friendly,
  gotoUrl,
  waitForPageLoad,
} = require('@vardot/varbase-e2e/tests/step-definitions/varbase-e2e');

async function attempt(body, message) {
  try {
    await body();
  } catch (err) {
    throw friendly(message, err);
  }
}

async function waitForEditor(page) {
  await page.waitForFunction(() => {
    const el = document.querySelector('.ck-editor__editable');
    return el && el.ckeditorInstance;
  }, { timeout: 20000, polling: 100 });
}

/**
 * Open the Drupal CMS "Utility page" add form and wait for its CKEditor 5
 * instance (the `field_content` editor using `content_format`).
 *
 * Example: When I open a new Drupal CMS page
 */
When(/^(?:I |we )?open a new Drupal CMS page$/, async function () {
  await attempt(async () => {
    // Navigate with varbase-e2e's gotoUrl (friendly navigation, settles on
    // domcontentloaded) and then wait for the CKEditor 5 instance to attach.
    // We deliberately do not smart-settle on network idle here: the Drupal CMS
    // page form keeps a background autosave connection open, so "network idle"
    // never arrives - the editor instance attaching is the real readiness cue.
    await gotoUrl(this.page, `${this.parameters.launchUrl}/node/add/page`);
    await waitForEditor(this.page);
  }, 'Could not open a new Drupal CMS page');
});

/**
 * Fill the required Drupal CMS page fields (title + description), sync every
 * CKEditor instance back to its source textarea and submit, remembering the
 * created node URL for later assertions.
 *
 * Example: When I save the Drupal CMS page titled "Anchor demo"
 */
When(/^(?:I |we )?save the Drupal CMS page titled "([^"]*)"$/, async function (title) {
  await attempt(async () => {
    await this.page.evaluate((t) => {
      const setVal = (sel, v) => { const el = document.querySelector(sel); if (el && !el.value) el.value = v; };
      const title = document.querySelector('input[name="title[0][value]"]');
      if (title) title.value = t;
      // field_description is a required plain-text field on the Utility page.
      setVal('[name="field_description[0][value]"]', `${t} description.`);
      // Sync CKEditor data into the underlying source textareas before submit.
      document.querySelectorAll('.ck-editor__editable').forEach((el) => {
        const inst = el.ckeditorInstance;
        if (inst && inst.sourceElement) inst.updateSourceElement();
      });
    }, title);
    await this.page.evaluate(() => {
      const form = document.querySelector('#node-page-form, form[id^="node-page-form"]');
      const submit = form && (form.querySelector('#edit-submit') || form.querySelector('input[name="op"][value="Save"]'));
      if (form && submit) form.requestSubmit(submit);
      else if (submit) submit.click();
    });
    await waitForPageLoad(this.page, this.minWaitTime && this.minWaitTime.page);
    this.anchorNodeUrl = this.page.url().split('?')[0];
  }, `Could not save the Drupal CMS page titled "${title}"`);
});
