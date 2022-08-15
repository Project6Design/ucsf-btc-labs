<?php

namespace Drupal\Tests\sharemessage\Functional\Plugin;

use Drupal\Tests\sharemessage\Functional\ShareMessageTestBase;

/**
 * Test class for Share Message Sharrre specific plugin.
 *
 * @group sharemessage
 */
class ShareMessageSharrreTest extends ShareMessageTestBase {

  /**
   * Test case for Sharrre settings form saving.
   */
  public function testSharrreSettingsFormSave() {
    // Set initial Sharrre settings.
    $this->drupalGet('admin/config/services/sharemessage/sharrre-settings');
    $default_settings = [
      'default_services[]' => [
        'googlePlus',
        'facebook',
      ],
      'library_url' => '//cdn.jsdelivr.net/sharrre/1.3.4/jquery.sharrre-1.3.4.min.js',
      'shorter_total' => FALSE,
      'enable_hover' => FALSE,
      'enable_counter' => FALSE,
      'enable_tracking' => FALSE,
    ];
    $this->drupalPostForm(NULL, $default_settings, t('Save configuration'));

    // Set a new Share Message.
    $sharemessage = [
      'label' => 'ShareMessage Test Sharrre',
      'id' => 'sharemessage_test_sharrre_label',
      'plugin' => 'sharrre',
      'title' => 'Sharrre test',
    ];
    $this->drupalPostForm('admin/config/services/sharemessage/add', $sharemessage, t('Save'));
    $this->drupalGet('admin/config/services/sharemessage/manage/sharemessage_test_sharrre_label');
    $override_settings = '//details[starts-with(@data-drupal-selector, "edit-settings")]';
    $this->assertFieldByXPath($override_settings);
    $this->assertText('Sharrre is a jQuery plugin that allows you to create nice widgets sharing for Facebook, Twitter, Google Plus (with PHP script) and more.');

    // Assert that the initial settings are saved correctly.
    $this->drupalGet('sharemessage-test/sharemessage_test_sharrre_label');
    $this->assertRaw('"services":{"googlePlus":"googlePlus","facebook":"facebook"}');
    $this->assertRaw('"shorter_total":false,"enable_hover":false,"enable_counter":false,"enable_tracking":false');

    // Set new Sharrre settings.
    $this->drupalGet('admin/config/services/sharemessage/sharrre-settings');
    $default_settings = [
      'default_services[]' => [
        'googlePlus',
        'facebook',
        'twitter',
        'linkedin',
        'pinterest',
      ],
      'library_url' => '//cdn.jsdelivr.net/sharrre/1.3.4/jquery.sharrre-1.3.4.min.js',
      'shorter_total' => TRUE,
      'enable_hover' => TRUE,
      'enable_counter' => TRUE,
      'enable_tracking' => FALSE,
    ];
    $this->drupalPostForm(NULL, $default_settings, t('Save configuration'));

    // Check that the saving of the new Sharrre settings works correctly.
    $this->drupalGet('sharemessage-test/sharemessage_test_sharrre_label');
    $this->assertRaw('"services":{"googlePlus":"googlePlus","facebook":"facebook","twitter":"twitter","linkedin":"linkedin","pinterest":"pinterest"}');
    $this->assertNoRaw('"services":{"googlePlus":"googlePlus","facebook":"facebook"}');
    $this->assertRaw('"shorter_total":true,"enable_hover":true,"enable_counter":true,"enable_tracking":false');
    $this->assertNoRaw('"shorter_total":false,"enable_hover":false,"enable_counter":false,"enable_tracking":false');
  }
}
