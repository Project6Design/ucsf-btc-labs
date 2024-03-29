<?php

namespace Drupal\Tests\devel\Functional;

/**
 * Tests devel requirements.
 *
 * @group devel
 */
class DevelRequirementsTest extends DevelBrowserTestBase {

  /**
   * Tests that the status page shows a warning when evel is enabled.
   */
  public function testStatusPage(): void {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/reports/status');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->pageTextContains('Devel module enabled');
    $this->assertSession()->pageTextContains('The Devel module provides access to internal debugging information; therefore it\'s recommended to disable this module on sites in production.');
  }

}
