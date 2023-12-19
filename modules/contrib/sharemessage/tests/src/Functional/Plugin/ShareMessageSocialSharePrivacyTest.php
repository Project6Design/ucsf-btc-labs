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
    $this->submitForm($default_settings, t('Save configuration'));

    // Set a new Share Message.
    $sharemessage = [
      'label' => 'ShareMessage Test SocialSharePrivacy',
      'id' => 'sharemessage_test_socialshareprivacy_label',
      'plugin' => 'socialshareprivacy',
      'title' => 'SocialSharePrivacy test',
    ];
    $this->drupalGet('admin/config/services/sharemessage/add');
    $this->submitForm($sharemessage, t('Save'));
    $this->drupalGet('admin/config/services/sharemessage/manage/sharemessage_test_socialshareprivacy_label');
    $override_settings = '//details[starts-with(@data-drupal-selector, "edit-settings")]';
    $this->xpath($override_settings);
    $this->assertSession()->pageTextContains('Social Share Privacy is a jQuery plugin that lets you add social share buttons to your website that don\'t allow the social sites to track your users.');

    // Assert that the initial settings are saved correctly.
    $this->drupalGet('sharemessage-test/sharemessage_test_socialshareprivacy_label');
    $this->assertSession()->responseContains('"facebook":{"status":true');
    $this->assertSession()->responseContains('"gplus":{"status":true');
    $this->assertSession()->responseContains('"twitter":{"status":false');

    // Set new Social Share Privacy settings.
    $this->drupalGet('admin/config/services/sharemessage/socialshareprivacy-settings');
    $default_settings = [
      'services[]' => [
        'gplus',
        'twitter',
      ],
    ];
    $this->submitForm($default_settings, t('Save configuration'));

    // Check the saving of the new Social Share Privacy settings is correctly.
    $this->drupalGet('sharemessage-test/sharemessage_test_socialshareprivacy_label');
    $this->assertSession()->responseContains('"twitter":{"status":true');
    $this->assertSession()->responseContains('"gplus":{"status":true');
    $this->assertSession()->responseContains('"facebook":{"status":false');
  }

}
