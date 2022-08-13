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
    $this->drupalPostForm('admin/config/services/sharemessage/addthis-settings', $default_settings, t('Save configuration'));

    // Set a new Share Message.
    $this->drupalGet('admin/config/services/sharemessage/add');
    $this->assertText('AddThis plugin for Share Message module.');
    $override_settings = '//details[starts-with(@data-drupal-selector, "edit-settings")]';
    $this->assertFieldByXPath($override_settings);
    $sharemessage = [
      'label' => 'ShareMessage Test AddThis',
      'id' => 'sharemessage_test_addthis_label',
      'plugin' => 'addthis',
      'title' => 'AddThis test',
    ];
    $this->drupalPostForm('admin/config/services/sharemessage/add', $sharemessage, t('Save'));

    // Assert that the initial settings are saved correctly.
    $this->drupalGet('sharemessage-test/sharemessage_test_addthis_label');
    $this->assertShareButtons($sharemessage, $default_settings['default_icon_style'], TRUE);
    $this->assertRaw('<a class="addthis_button_facebook">');
    $this->assertRaw('<a class="addthis_button_facebook_like">');
    $this->assertNoRaw('<a class="addthis_button_compact">');

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
    $this->drupalPostForm('admin/config/services/sharemessage/addthis-settings', $default_settings, t('Save configuration'));

    // Check that the saving of the new AddThis settings works correctly.
    $this->drupalGet('sharemessage-test/sharemessage_test_addthis_label');
    $this->assertShareButtons($sharemessage, $default_settings['default_icon_style'], TRUE);
    $this->assertRaw('<a class="addthis_button_facebook">');
    $this->assertNoRaw('<a class="addthis_button_facebook_like">');
    $this->assertRaw('<a class="addthis_button_linkedin">');
    $this->assertRaw('<a class="addthis_button_twitter">');
    $this->assertRaw('<a class="addthis_button_compact">');
  }

}
