<?php

namespace Drupal\sharemessage\Tests\Plugin;

use Drupal\Tests\sharemessage\Functional\ShareMessageTestBase;

/**
 * Test class for Share Message Sharrre specific plugin.
 *
 * @group sharemessage
 */
class ShareMessageSocialSharePrivacyTest extends ShareMessageTestBase {

  /**
   * Test case for Social Share Privacy settings form saving.
   */
  public function testSocialSharePrivacySettingsFormSave() {
    // Set initial SocialSharePrivacy settings.
    $this->drupalGet('admin/config/services/sharemessage/socialshareprivacy-settings');
    $default_settings = [
      'services[]' => [
        'gplus',
        'facebook',
      ],
    ];
    $this->drupalPostForm(NULL, $default_settings, t('Save configuration'));

    // Set a new Share Message.
    $sharemessage = [
      'label' => 'ShareMessage Test SocialSharePrivacy',
      'id' => 'sharemessage_test_socialshareprivacy_label',
      'plugin' => 'socialshareprivacy',
      'title' => 'SocialSharePrivacy test',
    ];
    $this->drupalPostForm('admin/config/services/sharemessage/add', $sharemessage, t('Save'));
    $this->drupalGet('admin/config/services/sharemessage/manage/sharemessage_test_socialshareprivacy_label');
    $override_settings = '//details[starts-with(@data-drupal-selector, "edit-settings")]';
    $this->assertFieldByXPath($override_settings);
    $this->assertText('Social Share Privacy is a jQuery plugin that lets you add social share buttons to your website that don\'t allow the social sites to track your users.');

    // Assert that the initial settings are saved correctly.
    $this->drupalGet('sharemessage-test/sharemessage_test_socialshareprivacy_label');
    $this->assertRaw('"facebook":{"status":true');
    $this->assertRaw('"gplus":{"status":true');
    $this->assertRaw('"twitter":{"status":false');

    // Set new Social Share Privacy settings.
    $this->drupalGet('admin/config/services/sharemessage/socialshareprivacy-settings');
    $default_settings = [
      'services[]' => [
        'gplus',
        'twitter',
      ],
    ];
    $this->drupalPostForm(NULL, $default_settings, t('Save configuration'));

    // Check the saving of the new Social Share Privacy settings is correctly.
    $this->drupalGet('sharemessage-test/sharemessage_test_socialshareprivacy_label');
    $this->assertRaw('"twitter":{"status":true');
    $this->assertRaw('"gplus":{"status":true');
    $this->assertRaw('"facebook":{"status":false');
  }

}
