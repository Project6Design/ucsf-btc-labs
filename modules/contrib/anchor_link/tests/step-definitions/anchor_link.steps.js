'use strict';

const { Given, Then, When } = require('@cucumber/cucumber');

const {
  friendly,
  gotoUrl,
  waitForPageLoad,
} = require('@vardot/varbase-e2e/tests/step-definitions/varbase-e2e');

/**
 * Run a step body and rethrow any failure as a tester-friendly error.
 */
async function attempt(body, message) {
  try {
    await body();
  } catch (err) {
    throw friendly(message, err);
  }
}

/**
 * Wait until the CKEditor 5 instance bound to the body field is ready.
 */
async function waitForEditor(page) {
  await page.waitForFunction(() => {
    const el = document.querySelector('.ck-editor__editable');
    return el && el.ckeditorInstance;
  }, { timeout: 20000, polling: 100 });
}

/**
 * Provision every non-admin user from worldParameters.users via
 * /admin/people/create. Idempotent - skips a username that already exists.
 *
 * Example: Given I add testing users
 */
Given(/^(?:I |we )?add( the)? testing users$/, async function (theCase) {
  const users = this.parameters.users || {};
  await attempt(async () => {
    for (const [, info] of Object.entries(users)) {
      if (info.isAdmin) continue;
      // Skip a username that already exists, so the step is idempotent.
      await gotoUrl(this.page, `${this.parameters.launchUrl}/admin/people?user=${encodeURIComponent(info.username)}`);
      const exists = await this.page.evaluate((name) =>
        [...document.querySelectorAll('table td a, table td')].some(el => el.textContent.trim() === name), info.username);
      if (exists) continue;
      await gotoUrl(this.page, `${this.parameters.launchUrl}/admin/people/create`);
      const missingRole = await this.page.evaluate((info) => {
        const set = (sel, val) => { const el = document.querySelector(sel); if (el) el.value = val; };
        set('#edit-name', info.username);
        set('#edit-mail', info.email || `${info.username}@example.test`);
        set('#edit-pass-pass1', info.password);
        set('#edit-pass-pass2', info.password);
        for (const role of info.roles || []) {
          const cb = document.querySelector(`input[name="roles[${role}]"]`);
          if (!cb) return role;
          cb.checked = true;
        }
        return null;
      }, info);
      if (missingRole) {
        throw new Error(`The "${missingRole}" role checkbox is not on the user form.`);
      }
      await this.page.evaluate(() => document.querySelector('#edit-submit').click());
      await waitForPageLoad(this.page);
      // The save must actually have produced the account.
      const outcome = await this.page.evaluate(() => ({
        ok: !!document.querySelector('[data-drupal-messages] .messages--status'),
        error: (document.querySelector('[data-drupal-messages] .messages--error') || {}).textContent || '',
      }));
      if (!outcome.ok) {
        throw new Error(`Creating "${info.username}" did not confirm: ${outcome.error.trim().slice(0, 200)}`);
      }
    }
  }, 'Could not provision the testing users');
});

/**
 * Open the article-add form and switch the body field to the named text format,
 * then wait for the CKEditor 5 instance to re-initialize.
 *
 * Example: When I open a new article using the "anchor_test" text format
 */
When(/^(?:I |we )?open a new article using the "([^"]*)" text format$/, async function (format) {
  await attempt(async () => {
    await gotoUrl(this.page, `${this.parameters.launchUrl}/node/add/article`);
    await waitForEditor(this.page);
    const changed = await this.page.evaluate((format) => {
      const sel = document.querySelector('select[name="body[0][format]"]');
      if (!sel) return 'no-selector';
      if (sel.value === format) return 'already';
      // Tag the instance being replaced, so the wait below can tell the new
      // one from it.
      const el = document.querySelector('.ck-editor__editable');
      if (el && el.ckeditorInstance) {
        el.ckeditorInstance.__anchorLinkOutgoing = true;
      }
      sel.value = format;
      sel.dispatchEvent(new Event('change', { bubbles: true }));
      return 'switched';
    }, format);
    if (changed === 'no-selector') throw new Error('Body format selector not found.');
    // The old editor detaches and the new one attaches asynchronously, so wait
    // for an instance that is not the one the switch replaced, rather than for
    // whichever editable happens to exist right now. Waiting on a particular
    // plugin would not do: a format under test may deliberately lack it.
    if (changed === 'switched') {
      await this.page.waitForFunction(() => {
        const el = document.querySelector('.ck-editor__editable');
        const ed = el && el.ckeditorInstance;
        return ed && !ed.__anchorLinkOutgoing;
      }, { timeout: 20000, polling: 100 });
    }
    await waitForPageLoad(this.page, this.minWaitTime && this.minWaitTime.page);
  }, `Could not open a new article using the "${format}" text format`);
});

/**
 * Assert the CKEditor toolbar exposes a button whose tooltip matches the label.
 *
 * Example: Then the CKEditor toolbar should have the "Anchor" button
 */
Then(/^the CKEditor toolbar should have the "([^"]*)" button$/, async function (label) {
  await attempt(async () => {
    await this.page.waitForFunction(
      (label) => [...document.querySelectorAll('.ck-toolbar button.ck-button')]
        .some(b => new RegExp(label, 'i').test(b.getAttribute('data-cke-tooltip-text') || b.textContent || '')),
      label,
      { timeout: 15000, polling: 100 },
    );
  }, `Expected the CKEditor toolbar to have a "${label}" button`);
});

/**
 * Insert an anchor around the given text via the Anchor balloon: set the editor
 * content, select it all, click the Anchor button, fill the "Anchor name" field
 * and submit. The Anchor plugin wraps the selection in `<a id="…">`; the
 * ck-anchor class stays in the editing view and never reaches the data.
 *
 * Example: When I insert an anchor named "section-one" around the text "Jump here"
 */
When(/^(?:I |we )?insert an anchor named "([^"]*)" around the text "([^"]*)"$/, async function (name, text) {
  await attempt(async () => {
    await waitForEditor(this.page);
    await this.page.evaluate((text) => {
      const ed = document.querySelector('.ck-editor__editable').ckeditorInstance;
      ed.setData(`<p>${text}</p>`);
      ed.editing.view.focus();
      ed.model.change(w => w.setSelection(ed.model.document.getRoot(), 'in'));
    }, text);
    await this.page.evaluate(() => {
      const btn = [...document.querySelectorAll('.ck-toolbar button.ck-button')]
        .find(b => /anchor/i.test(b.getAttribute('data-cke-tooltip-text') || ''));
      if (btn) btn.click();
    });
    const input = this.page.locator('.ck-balloon-panel input[type="text"]').first();
    await input.waitFor({ state: 'visible', timeout: 10000 });
    await input.fill(name);
    await this.page.evaluate(() => {
      const el = document.querySelector('.ck-balloon-panel input[type="text"]');
      const form = el && el.closest('form');
      if (form) form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    });
    await this.page.waitForTimeout(300);
  }, `Could not insert an anchor named "${name}"`);
});

/**
 * Replace the editor content with the given HTML, so a scenario can start from
 * markup an editor would otherwise have to be driven to produce.
 *
 * Example: When I set the editor data to "<p><a id=\"x\">y</a></p>"
 */
When(/^(?:I |we )?set the editor data to "(.*)"$/, async function (html) {
  await attempt(async () => {
    await waitForEditor(this.page);
    await this.page.evaluate((html) => {
      const ed = document.querySelector('.ck-editor__editable').ckeditorInstance;
      ed.setData(html);
    }, html.replace(/\\"/g, '"'));
    await this.page.waitForTimeout(300);
  }, 'Could not set the editor data');
});

/**
 * Assert the live editor data (getData) does NOT contain the given fragment.
 *
 * Example: Then the editor data should not contain "ck-anchor"
 */
Then(/^the editor data should not contain "([^"]*)"$/, async function (fragment) {
  await attempt(async () => {
    await this.page.waitForFunction(
      (fragment) => {
        const el = document.querySelector('.ck-editor__editable');
        const ed = el && el.ckeditorInstance;
        return ed && !ed.getData().includes(fragment);
      },
      fragment,
      { timeout: 10000, polling: 100 },
    );
  }, `Expected the editor data not to contain "${fragment}"`);
});

/**
 * Assert the live editor data (getData) contains the given HTML fragment.
 *
 * Example: Then the editor data should contain "class=\"ck-anchor\""
 */
Then(/^the editor data should contain "([^"]*)"$/, async function (fragment) {
  await attempt(async () => {
    await this.page.waitForFunction(
      (fragment) => {
        const el = document.querySelector('.ck-editor__editable');
        const ed = el && el.ckeditorInstance;
        return ed && ed.getData().includes(fragment);
      },
      fragment,
      { timeout: 10000, polling: 100 },
    );
  }, `Expected the editor data to contain "${fragment}"`);
});

/**
 * Set the article title and save the node, remembering the canonical URL of the
 * created node so later steps can assert the rendered output.
 *
 * Example: When I save the article titled "Anchor demo"
 */
When(/^(?:I |we )?save the article titled "([^"]*)"$/, async function (title) {
  await attempt(async () => {
    await this.page.evaluate((t) => {
      const el = document.querySelector('#edit-title-0-value');
      if (el) el.value = t;
    }, title);
    await this.page.evaluate(() => document.querySelector('#edit-submit').click());
    await waitForPageLoad(this.page, this.minWaitTime && this.minWaitTime.page);
    this.anchorNodeUrl = this.page.url().split('?')[0];
  }, `Could not save the article titled "${title}"`);
});

/**
 * Navigate to the node created earlier in the scenario.
 *
 * Example: When I view the article I created
 */
When(/^(?:I |we )?view the article I created$/, async function () {
  await attempt(async () => {
    if (!this.anchorNodeUrl) throw new Error('No article has been created yet in this scenario.');
    await gotoUrl(this.page, this.anchorNodeUrl);
    await waitForPageLoad(this.page, this.minWaitTime && this.minWaitTime.page);
  }, 'Could not view the created article');
});

/**
 * Assert the rendered page contains an anchor with the given id.
 *
 * Example: Then the page should contain an anchor with id "section-one"
 */
Then(/^the page should contain an anchor with id "([^"]*)"$/, async function (id) {
  await attempt(async () => {
    await this.page.waitForFunction(
      (id) => [...document.querySelectorAll(`a#${CSS.escape(id)}`)]
        .some(a => !a.closest('.ck-editor')),
      id,
      { timeout: 10000, polling: 100 },
    );
  }, `Expected the page to contain an anchor with id "${id}"`);
});

/** Resolve a varbase-e2e named selector from the world registry. */
function resolveName(world, name) {
  const css = world.__selectorsCss || {};
  const key = name.trim();
  if (Object.prototype.hasOwnProperty.call(css, key)) {
    return css[key];
  }
  throw new Error(`Unknown named selector "${key}". Run "Then print css selectors" to see all registered names.`);
}

/**
 * Assert a named selector is visible / hidden / attached / focused.
 *
 * Example: Then the "anchor toolbar button" element should be visible
 */
Then(/^the "([^"]*)" element should be (visible|hidden|attached|focused)(?: within (\d+) seconds?)?$/, async function (name, state, sec) {
  const sel = resolveName(this, name);
  const loc = this.page.locator(sel);
  const timeout = sec ? Number(sec) * 1000 : 10000;
  await attempt(async () => {
    if (state === 'focused') {
      await this.page.waitForFunction(s => document.activeElement && document.activeElement.matches(s), sel, { timeout });
    } else {
      await loc.first().waitFor({ state, timeout });
    }
  }, `Expected "${name}" (${sel}) to be ${state}`);
});

/**
 * Assert the first element matching a named selector contains the given text.
 *
 * Example: Then the "drupal page heading" element should contain text "Anchor Test"
 */
Then(/^the "([^"]*)" element should contain text "([^"]*)"(?: within (\d+) seconds?)?$/, async function (name, text, sec) {
  const sel = resolveName(this, name);
  const timeout = sec ? Number(sec) * 1000 : 10000;
  await attempt(async () => {
    await this.page.waitForFunction(
      ([s, t]) => { const el = document.querySelector(s); return el && el.textContent.includes(t); },
      [sel, text],
      { timeout, polling: 100 },
    );
  }, `Expected "${name}" (${sel}) to contain text "${text}"`);
});

/**
 * Assert how many anchors carry the given id, wherever the theme renders them.
 * An anchor is meant to appear once, and a duplicate is a bug of its own.
 *
 * Example: Then the page should contain exactly 1 anchor with id "section-one"
 */
Then(/^the page should contain exactly (\d+) anchors? with id "([^"]*)"$/, async function (expected, id) {
  const target = Number(expected);
  await attempt(async () => {
    await this.page.waitForFunction(
      ({ id, target }) => [...document.querySelectorAll(`a[id="${id}"]`)]
        .filter(a => !a.closest('.ck-editor')).length === target,
      { id, target },
      { timeout: 10000, polling: 100 },
    );
  }, `Expected exactly ${target} anchor(s) with id "${id}"`);
});

/**
 * Assert the count of elements matching a named selector.
 *
 * Example: Then the "anchor in output" element should have a count of 1
 */
Then(/^the "([^"]*)" element should have a count of (\d+)(?: within (\d+) seconds?)?$/, async function (name, expected, sec) {
  const sel = resolveName(this, name);
  const target = Number(expected);
  const timeout = sec ? Number(sec) * 1000 : 10000;
  const loc = this.page.locator(sel);
  const deadline = Date.now() + timeout;
  let last = -1;
  await attempt(async () => {
    while (Date.now() < deadline) {
      last = await loc.count();
      if (last === target) return;
      await this.page.waitForTimeout(100);
    }
    throw new Error(`count was ${last}`);
  }, `Expected "${name}" (${sel}) count to be ${target}`);
});
