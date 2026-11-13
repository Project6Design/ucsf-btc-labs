<?php

namespace Drupal\Tests\tfa\Functional;

/**
 * Tests the multi-step "full setup" flow in TfaSetupForm.
 *
 * @see https://www.drupal.org/project/tfa/issues/3451488
 *
 * @group Tfa
 */
class TfaSetupFormMultiStepTest extends TfaTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Configure a full setup chain with two steps: a validation plugin
    // (tfa_test_plugins_validation) followed by a login plugin
    // (tfa_trusted_browser). Full setup only chains through more than one
    // step when at least one login plugin is configured alongside the
    // default validation plugin.
    $this->config('tfa.settings')
      ->set('enabled', TRUE)
      ->set('default_validation_plugin', 'tfa_test_plugins_validation')
      ->set('allowed_validation_plugins', ['tfa_test_plugins_validation' => 'tfa_test_plugins_validation'])
      ->set('login_plugins', ['tfa_trusted_browser' => 'tfa_trusted_browser'])
      ->set('encryption', $this->encryptionProfile->id())
      ->save();
  }

  /**
   * Tests that full setup advances to the next plugin's form.
   */
  public function testFullSetupAdvancesToNextStep(): void {
    $user = $this->drupalCreateUser(['setup own tfa']);
    $this->drupalLogin($user);
    $assert = $this->assertSession();

    $this->drupalGet('user/' . $user->id() . '/security/tfa/tfa_test_plugins_validation');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Enter your current password');

    $this->submitForm(['current_pass' => $user->passRaw], 'Confirm');
    $assert->statusCodeEquals(200);

    // First step: the test validation plugin's setup form.
    $assert->fieldExists('expected_field');
    $this->submitForm(['expected_field' => 'Expected field content'], 'Verify and save');
    $assert->statusCodeEquals(200);

    // Regression check: the first step's form must not be shown again.
    $assert->fieldNotExists('expected_field');
    $assert->pageTextNotContains('Missing expected value');

    // The second step, the trusted browser setup form, should now be shown.
    $assert->pageTextContains('Trust this browser?');

    $this->submitForm([], 'Save');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('TFA setup complete.');
  }

  /**
   * Tests that a method with no valid setup plugin still 404s up front.
   */
  public function testMethodWithNoSetupPluginReturns404OnFirstRequest(): void {
    $user = $this->drupalCreateUser(['setup own tfa']);
    $this->drupalLogin($user);

    $this->drupalGet('user/' . $user->id() . '/security/tfa/tfa_test_plugins_validation_false');
    $this->assertSession()->statusCodeEquals(404);
  }

}
