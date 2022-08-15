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
  public static $modules = [
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
    $this->assertText(t('Share Message'));
    $this->clickLink(t('Configure'), 0);

    $this->drupalGet('admin/structure/types');
    $this->assertText(t('Shareable content'));

    // Search for the Share Message block on the demo node.
    $this->drupalGet('admin/content');
    $this->clickLink(t('Share Message demo'));
    $this->assertText(t('Welcome to the Share Message demo module!'));
    $this->assertText(t('Share Message'));
    // Assert the demo links are correct.
    $node = $this->getNodeByTitle('Share Message demo');
    $this->drupalGet('node/' . $node->id());
    $this->assertLinkByHref('admin/config/services/sharemessage/sharemessage-settings');
    $this->assertLinkByHref('admin/config/services/sharemessage/manage/share_message_addthis_demo');
    $this->assertLinkByHref('admin/config/services/sharemessage');
    $this->assertLinkByHref('admin/structure/block/manage/sharemessage_addthis');

    // Asserts that the buttons are displayed.
    $this->assertRaw('addthis_button_preferred_1');
    $this->assertRaw('addthis_button_preferred_2');
    $this->assertRaw('addthis_button_preferred_3');
    $this->assertRaw('addthis_button_preferred_4');
    $this->assertRaw('addthis_button_preferred_5');
    $this->assertRaw('addthis_button_compact');

    // Test OG headers for image, video and url.
    $this->assertRaw('<meta property="og:image" content="https://www.drupal.org/files/drupal%208%20logo%20Stacked%20CMYK%20300.png" />');
    $this->assertRaw('<meta property="og:video" content="https://www.youtube.com/watch?v=ktCgVopf7D0?fs=1" />');
    $this->assertRaw('<meta property="og:video:width" content="360" />');
    $this->assertRaw('<meta property="og:video:height" content="270" />');
    $this->assertRaw('<meta property="og:url" content="' . $this->getUrl() . '" />');

    // Test that Sharrre plugin works.
    $this->assertText('Share Message - Sharrre');
    $this->assertRaw('<div id="block-sharemessage-sharrre" class="block block-sharemessage block-sharemessage-block">');
    $this->assertRaw('"services":{"googlePlus":"googlePlus","facebook":"facebook","twitter":"twitter"}');

    // Test that Social Share Privacy plugin works.
    $this->assertText('Share Message - Social Share Privacy');
    $this->assertRaw('<div id="block-sharemessage-socialshareprivacy" class="block block-sharemessage block-sharemessage-block">');
    $this->assertRaw('"twitter":{"status":true');
    $this->assertRaw('"facebook":{"status":true');
  }

}
