<?php

namespace Drupal\Tests\sharemessage\Functional\Plugin;

use Drupal\Tests\sharemessage\Functional\ShareMessageTestBase;

/**
 * Test class for Share Message AddThis specific plugin.
 *
 * @group sharemessage
 */
class ShareMessageAddthisTest extends ShareMessageTestBase {

  /**
   * Test case for AddThis settings form saving.
   */
  public function testAddThisSettingsFormSave() {
    // Set initial AddThis settings.
    $default_settings = [
      'default_services[]' => [
        'facebook',
        'facebook_like',
      ],
      'default_additional_services' => FALSE,
      'default_icon_style' => 'addthis_16x16_style',
    ];
    $this->drupalGet('admin/config/services/sharemessage/addthis-settings');
    $this->submitForm($default_settings, t('Save configuration'));

    // Set a new Share Message.
    $this->drupalGet('admin/config/services/sharemessage/add');
    $this->assertSession()->pageTextContains('AddThis plugin for Share Message module.');
    $override_settings = '//details[starts-with(@data-drupal-selector, "edit-settings")]';
    $this->xpath($override_settings);
    $sharemessage = [
      'label' => 'ShareMessage Test AddThis',
      'id' => 'sharemessage_test_addthis_label',
      'plugin' => 'addthis',
      'title' => 'AddThis test',
    ];
    $this->drupalGet('admin/config/services/sharemessage/add');
    $this->submitForm($sharemessage, t('Save'));

    // Assert that the initial settings are saved correctly.
    $this->drupalGet('sharemessage-test/sharemessage_test_addthis_label');
    $this->assertShareButtons($sharemessage, $default_settings['default_icon_style'], TRUE);
    $this->assertSession()->responseContains('<a class="addthis_button_facebook">');
    $this->assertSession()->responseContains('<a class="addthis_button_facebook_like">');
    $this->assertSession()->responseNotContains('<a class="addthis_button_compact">');

    // Set new AddThis settings.
    $default_settings = [
      'default_services[]' => [
        'facebook',
        'linkedin',
        'twitter'
      ],
      'default_additional_services' => TRUE,
      'default_icon_style' => 'addthis_32x32_style',
    ];
    $this->drupalGet('admin/config/services/sharemessage/addthis-settings');
    $this->submitForm($default_settings, t('Save configuration'));

    // Check that the saving of the new AddThis settings works correctly.
    $this->drupalGet('sharemessage-test/sharemessage_test_addthis_label');
    $this->assertShareButtons($sharemessage, $default_settings['default_icon_style'], TRUE);
    $this->assertSession()->responseContains('<a class="addthis_button_facebook">');
    $this->assertSession()->responseNotContains('<a class="addthis_button_facebook_like">');
    $this->assertSession()->responseContains('<a class="addthis_button_linkedin">');
    $this->assertSession()->responseContains('<a class="addthis_button_twitter">');
    $this->assertSession()->responseContains('<a class="addthis_button_compact">');
  }

}
