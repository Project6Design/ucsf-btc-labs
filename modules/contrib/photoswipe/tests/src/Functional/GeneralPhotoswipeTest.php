<?php

namespace Drupal\Tests\photoswipe\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * This class provides methods specifically for testing something.
 *
 * @group photoswipe
 */
class GeneralPhotoswipeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'photoswipe',
    'node',
    'test_page_test',
  ];

  /**
   * A user with authenticated permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * A user with admin permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config('system.site')->set('page.front', '/test-page')->save();
    $this->user = $this->drupalCreateUser([]);
    $this->adminUser = $this->drupalCreateUser([]);
    $this->adminUser->addRole($this->createAdminRole('admin', 'admin'));
    $this->adminUser->save();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests if the module installation, won't break the site.
   */
  public function testInstallation() {
    $session = $this->assertSession();
    $this->drupalGet('<front>');
    $session->statusCodeEquals(200);
  }

  /**
   * Tests if uninstalling the module, won't break the site.
   */
  public function testUninstallation() {
    // Go to uninstallation page an uninstall photoswipe:
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('/admin/modules/uninstall');
    $session->statusCodeEquals(200);
    $page->checkField('edit-uninstall-photoswipe');
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    // Confirm uninstall:
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('The selected modules have been uninstalled.');
  }

  /**
   * Tests functionality of the settings page.
   */
  public function testSettingsPage() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    // Go to the front page and check if photoswipe.css is not loaded
    // (The library shouldn't be loaded in the front page):
    $this->drupalGet('<front>');
    $session->statusCodeEquals(200);
    $session->elementNotExists('css', 'link[href*="//cdnjs.cloudflare.com/ajax/libs/photoswipe/4.1.3/photoswipe.css"]');
    // Go to the settings page and enable loading on non admin pages:
    $this->drupalGet('/admin/config/media/photoswipe');
    $session->statusCodeEquals(200);
    $page->checkField('edit-photoswipe-always-load-non-admin');
    $page->pressButton('edit-submit');
    // Go to the front page again and check if the css file is loaded
    // (The library shouldn't be loaded in the front page):
    $this->drupalGet('<front>');
    $session->elementNotExists('css', 'link[href*="//cdnjs.cloudflare.com/ajax/libs/photoswipe/4.1.3/photoswipe.css"]');
    $session->statusCodeEquals(200);
  }

  /**
   * Tests that the status page is getting the library installed version.
   */
  public function testHookRequirements(): void {
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    // Get Drupal Status Page.
    $this->drupalGet('/admin/reports/status');
    // Verify that the Photoswipe plugin is shown on the page.
    $session->pageTextContains('Photoswipe plugin');

    /** @var \Drupal\Core\Asset\LibrariesDirectoryFileFinder $library_file_finder */
    $library_file_finder = \Drupal::service('library.libraries_directory_file_finder');
    $library_path = $library_file_finder->find('photoswipe');
    if ($library_path !== FALSE) {
      // Library installed in libraries dir :)
      // So we get library version from photoswipe.json file.
      $package_json_content = file_get_contents(DRUPAL_ROOT . '/libraries/photoswipe/photoswipe.json');
      $package_json = json_decode($package_json_content, FALSE);
      $installed_version = $package_json->version;
    }
    else {
      // Library from CDN :(
      /** @var \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery */
      $library_discovery = \Drupal::service('library.discovery');
      $library_definition = $library_discovery->getLibraryByName('photoswipe', 'photoswipe');
      $installed_version = $library_definition['version'];
    }
    // Verify that the Photoswipe version (either local or CDN) is shown on the page.
    $session->pageTextContains($installed_version);

  }

}
