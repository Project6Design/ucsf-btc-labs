<?php

namespace Drupal\Tests\features_ui\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the creation of a features bundle using AJAX.
 */
#[Group('features_ui')]
#[RunTestsInSeparateProcesses]
class FeaturesUiAjaxTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['features', 'features_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->strictConfigSchema = FALSE;
    parent::setUp();
    $user = $this->drupalCreateUser(['administer site configuration', 'export configuration']);
    $this->drupalLogin($user);
  }

  /**
   * Tests feature bundle creation with AJAX saves configuration.
   */
  public function testActionConfigurationWithAjax() {
    $url = Url::fromRoute('features.export');
    $this->drupalGet($url);
    $this->assertSession()->pageTextContains('You have not yet created any bundles.');
    $url = Url::fromRoute('features.assignment');
    $this->drupalGet($url);
    $page = $this->getSession()->getPage();
    $page->find('css', '[name="bundle[bundle_select]"]')
      ->setValue('new');

    $this->assertSession()->waitForElementVisible('css', '[name="bundle[name]"][value=""]')
      ->setValue('foo');
    $this->assertSession()->waitForElementVisible('css', 'button[class="link"][type="button"]')
      ->click();
    $this->assertSession()->waitForElementVisible('css', '[name="bundle[machine_name]"]')
      ->setValue('foo');
    $page = $this->getSession()->getPage();
    $page->find('css', '[name="bundle[description]"]')
      ->setValue($this->randomString());
    $page->find('css', '[value="Save settings"]')
      ->click();

    $url = Url::fromRoute('features.export');
    $this->drupalGet($url);
    $this->assertSession()->pageTextNotContains('You have not yet created any bundles.');
  }

}
