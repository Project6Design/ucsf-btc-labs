<?php

namespace Drupal\Tests\sharemessage\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\simpletest\AssertContentTrait;

/**
 * Sharemessage javascript tests.
 *
 * @group sharemessage
 */
class ShareMessageJavascriptTest extends JavascriptTestBase {

  use AssertContentTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Install bartik theme.
    \Drupal::service('theme_handler')->install(['bartik']);
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
