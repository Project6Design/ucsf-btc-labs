<?php

namespace Drupal\Tests\sharemessage_demo\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the demo module for Share Message.
 *
 * @group sharemessage
 */
class ShareMessageDemoTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'bartik';

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = [
    'path',
    'block',
    'filter',
    'sharemessage',
    'sharemessage_demo',
  ];

  /**
   * Asserts translation jobs can be created.
   */
  public function testInstalled() {
    $admin_user = $this->drupalCreateUser([
      'access content overview',
      'administer content types',
      'administer blocks',
      'view sharemessages',
      'administer sharemessages',
      'access administration pages',
      'link to any page',
    ]);

    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/structure/block');
    $this->assertSession()->pageTextContains(t('Share Message'));
    $this->clickLink(t('Configure'), 0);

    $this->drupalGet('admin/structure/types');
    $this->assertSession()->pageTextContains(t('Shareable content'));

    // Search for the Share Message block on the demo node.
    $this->drupalGet('admin/content');
    $this->clickLink(t('Share Message demo'));
    $this->assertSession()->pageTextContains(t('Welcome to the Share Message demo module!'));
    $this->assertSession()->pageTextContains(t('Share Message'));
    // Assert the demo links are correct.
    $node = $this->getNodeByTitle('Share Message demo');
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->linkByHrefExists('admin/config/services/sharemessage/sharemessage-settings');
    $this->assertSession()->linkByHrefExists('admin/config/services/sharemessage/manage/share_message_addthis_demo');
    $this->assertSession()->linkByHrefExists('admin/config/services/sharemessage');
    $this->assertSession()->linkByHrefExists('admin/structure/block/manage/sharemessage_addthis');

    // Asserts that the buttons are displayed.
    $this->assertSession()->responseContains('addthis_button_preferred_1');
    $this->assertSession()->responseContains('addthis_button_preferred_2');
    $this->assertSession()->responseContains('addthis_button_preferred_3');
    $this->assertSession()->responseContains('addthis_button_preferred_4');
    $this->assertSession()->responseContains('addthis_button_preferred_5');
    $this->assertSession()->responseContains('addthis_button_compact');

    // Test OG headers for image, video and url.
    $this->assertSession()->responseContains('<meta property="og:image" content="https://www.drupal.org/files/drupal%208%20logo%20Stacked%20CMYK%20300.png" />');
    $this->assertSession()->responseContains('<meta property="og:video" content="https://www.youtube.com/watch?v=ktCgVopf7D0?fs=1" />');
    $this->assertSession()->responseContains('<meta property="og:video:width" content="360" />');
    $this->assertSession()->responseContains('<meta property="og:video:height" content="270" />');
    $this->assertSession()->responseContains('<meta property="og:url" content="' . $this->getUrl() . '" />');

    // Test that Sharrre plugin works.
    $this->assertSession()->pageTextContains('Share Message - Sharrre');
    $this->assertSession()->responseContains('<div id="block-sharemessage-sharrre" class="block block-sharemessage block-sharemessage-block">');
    $this->assertSession()->responseContains('"services":{"googlePlus":"googlePlus","facebook":"facebook","twitter":"twitter"}');

    // Test that Social Share Privacy plugin works.
    $this->assertSession()->pageTextContains('Share Message - Social Share Privacy');
    $this->assertSession()->responseContains('<div id="block-sharemessage-socialshareprivacy" class="block block-sharemessage block-sharemessage-block">');
    $this->assertSession()->responseContains('"twitter":{"status":true');
    $this->assertSession()->responseContains('"facebook":{"status":true');
  }

}
