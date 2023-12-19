<?php

namespace Drupal\Tests\sharemessage\Functional;

use Drupal\Core\Url;

/**
 * Main Share Message workflow through the admin UI.
 *
 * @group sharemessage
 */
class ShareMessageWorkflowTest extends ShareMessageTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Main Share Message workflow through the admin UI.
   */
  public function testShareMessageWorkflow() {

    // Step 1: Create a Share Message in the UI.
    $this->drupalGet('admin/config/services/sharemessage/add');
    $edit = [
      'label' => 'Share Message Test Label',
      'id' => 'sharemessage_test_label',
      'title' => 'Share Message Test Title',
      'message_long' => 'Share Message Test Long Description',
      'message_short' => 'Share Message Test Short Description',
      'image_url' => 'http://www.example.com/drupal.jpg',
      'share_url' => 'http://www.example.com',
    ];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains(t('Share Message @label has been added.', ['@label' => $edit['label']]));

    // Step 2: Display Share Message and verify AddThis markup
    // and meta header elements.
    $this->drupalGet('sharemessage-test/sharemessage_test_label');
    $this->assertShareButtons($edit, 'addthis_16x16_style', TRUE);

    $this->assertOGTags('og:title', $edit['title']);
    $this->assertOGTags('og:description', $edit['message_long']);
    $this->assertOGTags('og:image', $edit['image_url']);
    $this->assertOGTags('og:url', $edit['share_url']);

    $this->drupalGet('admin/config/services/sharemessage/add');
    // Check if the enforce checkbox is there.
    $this->assertSession()->fieldExists('enforce_usage');

    $edit_2 = [
      'label' => 'Share Message 2 Test Label',
      'id' => 'sharemessage_test_label2',
      'title' => 'Share Message 2 Test Title',
      'message_long' => 'Share Message 2 Test Long Description',
      'message_short' => 'Share Message 2 Test Short Description',
      'image_url' => $edit['image_url'],
      'share_url' => $edit['share_url'],
      'enforce_usage' => 1,
    ];
    $this->submitForm($edit_2, t('Save'));

    /** @var \Drupal\sharemessage\ShareMessageInterface $sharemessage */
    $sharemessage = \Drupal::entityTypeManager()->getStorage('sharemessage')->load('sharemessage_test_label2');
    // Check if the option was saved as expected.
    $this->assertEquals($sharemessage->enforce_usage, TRUE, 'Enforce setting was saved on the entity.');
    $this->drupalGet('sharemessage-test/sharemessage_test_label', ['query' => ['smid' => 'sharemessage_test_label2']]);

    // Check if the og:description tag gets rendered correctly.
    $this->assertOGTags('og:description', $edit_2['message_long']);
    // Check if the og:url tag gets rendered correctly.
    $url = Url::fromUri($edit['share_url'], ['query' => ['smid' => 'sharemessage_test_label2']])->toString();
    $this->assertOGTags('og:url', $url);
    $message_description = 'Suppressing og:url meta tag for overridden sharemessage.';
    $this->assertNoOGTags('og:url', $edit['share_url'], $message_description);

    // Check if the overridden Share Message is rendered correctly.
    $this->assertSession()->responseContains('addthis:description="' . $edit['message_long'] . '"');

    // Disable enforcement of overrides in the global settings.
    $this->config('sharemessage.settings')->set('message_enforcement', FALSE)->save();
    $this->drupalGet('sharemessage-test/sharemessage_test_label', ['query' => ['smid' => 'sharemessage_test_label2']]);

    // Check if the og:description tag no longer displays the override.
    $this->assertOGTags('og:description', $edit['message_long']);
    // Check if the og:url tag no longer displays the override.
    $this->assertOGTags('og:url', $edit['share_url']);
  }

  /**
   * Tests if blocks with tokens are correctly updated when token data changes.
   */
  public function testTokenCacheability() {
    // Step 1: Create a Share Message in the UI.
    $this->drupalGet('admin/config/services/sharemessage/add');
    $edit = [
      'label' => 'Share Message Test Label',
      'id' => 'sharemessage_test_label',
      'title' => 'Share Message Test Title [current-user:name]',
      'message_long' => 'Share Message Test Long Description',
    ];
    $this->submitForm($edit, t('Save'));

    // Add a block that will contain the created Share Message.
    $theme = 'classy';
    $block = [
      'settings[label]' => 'Share Message test block',
      'settings[sharemessage]' => $edit['id'],
      'region' => 'content',
    ];
    $this->drupalGet('admin/structure/block/add/sharemessage_block/' . $theme);
    $this->submitForm($block, t('Save block'));

    $this->drupalGet('user');
    $this->assertSession()->statusCodeEquals(200);
    // Check that the username is displayed.
    $this->assertSession()->responseContains('Share Message Test Title ' . $this->adminUser->getAccountName());

    // Step 2: Edit the username.
    $this->adminUser->name = 'new user';
    $this->adminUser->save();

    $this->drupalGet('user');
    $this->assertSession()->statusCodeEquals(200);
    // Check that the changed username is displayed.
    $this->assertSession()->responseContains('Share Message Test Title ' . $this->adminUser->getAccountName());
  }
}
