<?php

namespace Drupal\Tests\sharemessage\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Sharemessage javascript tests.
 *
 * @group sharemessage
 */
class ShareMessageJavascriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Install bartik theme.
    \Drupal::service('theme_installer')->install(['bartik']);
    $theme_settings = $this->config('system.theme');
    $theme_settings->set('default', 'bartik')->save();
    \Drupal::service('module_installer')->install(['sharemessage', 'sharemessage_demo']);
  }

  /**
   * Tests Addthis integration.
   */
  public function testAddThis() {
    // Set a fake profile ID and then verify that it is set.
    \Drupal::configFactory()->getEditable('sharemessage.addthis')
      ->set('addthis_profile_id', 'amazing_pub_id')
      ->save();

    $this->drupalGet('');
    $session = $this->getSession();
    $session->wait(2000, "typeof _ate !== 'undefined'");
    $pub = $session->evaluateScript('_ate.pub();');
    $this->assertEquals($pub, 'amazing_pub_id');
  }

}
