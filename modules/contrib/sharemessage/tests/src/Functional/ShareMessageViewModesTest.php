<?php

namespace Drupal\Tests\sharemessage\Functional;

/**
 * Verifies the output of ShareMessage entities different view modes.
 *
 * @group sharemessage
 */
class ShareMessageViewModesTest extends ShareMessageTestBase {

  /**
   * Checks whether view modes render expected markup.
   */
  public function testShareMessageViewModes() {

    $this->drupalGet('admin/config/services/sharemessage/add');
    $edit = [
      'label' => 'Share Message Test Label',
      'id' => 'sharemessage_test_label',
      'plugin' => 'addthis',
      'title' => 'Share Message Test Title',
      'message_long' => 'Share Message Test Long Description',
      'message_short' => 'Share Message Test Short Description',
      'image_url' => 'http://www.example.com/drupal.jpg',
      'share_url' => 'http://www.example.com',
    ];
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains(t('Share Message @label has been added.', ['@label' => $edit['label']]));

    $this->drupalGet('sharemessage-test/sharemessage_test_label');
    $this->assertShareButtons($edit, 'addthis_16x16_style', TRUE);
    $this->assertOGTags('og:title', $edit['title']);

    $this->drupalGet('sharemessage-test/sharemessage_test_label/only_og_tags');
    $this->assertNoShareButtons($edit);
    $this->assertOGTags('og:title', $edit['title']);

    $this->drupalGet('sharemessage-test/sharemessage_test_label/no_attributes');
    $this->assertShareButtons($edit);
    $this->assertOGTags('og:title', $edit['title']);

    $this->drupalGet('sharemessage-test/sharemessage_test_label/attributes_only');
    $this->assertShareButtons($edit, 'addthis_16x16_style', TRUE);
    $this->assertNoOGTags('og:title', $edit['title']);
  }

}
