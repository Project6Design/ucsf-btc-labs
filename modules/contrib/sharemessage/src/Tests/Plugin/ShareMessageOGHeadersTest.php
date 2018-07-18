<?php

namespace Drupal\sharemessage\Tests\Plugin;

use Drupal\file\Entity\File;
use Drupal\sharemessage\Tests\ShareMessageTestBase;

/**
 * Test class for Share Message OGHeaders specific plugin.
 *
 * @group sharemessage
 */
class ShareMessageOGHeadersTest extends ShareMessageTestBase {

  /**
   * Test case for OGHeaders settings form saving.
   */
  public function testOGHeadersFormSave() {
    // Create Share Message with OG headers as plugin.
    $this->drupalGet('admin/config/services/sharemessage/add');
    file_put_contents('public://file.png', str_repeat('t', 8000000));
    $file = File::create(['uri' => 'public://file.png']);
    $file->save();
    $this->drupalPostAjaxForm(NULL, ['plugin' => 'ogheaders'], 'plugin');
    $this->assertText('Open graph headers are used when users want to use it as a framework or a background tool only.');
    $override_settings = '//details[starts-with(@data-drupal-selector, "edit-settings")]';
    $this->assertFieldByXPath($override_settings);
    $this->assertText('The OG Headers plugin doesn\'t provide any settings.');
    $sharemessage = [
      'label' => 'Share Message Test OG Label',
      'id' => 'sharemessage_test_og_label',
      'plugin' => 'ogheaders',
      'title' => 'OG headers name',
      'message_long' => 'OG headers long description',
      'message_short' => 'OG headers short description',
      'fallback_image' => $file->uuid(),
    ];
    $this->drupalPostForm(NULL, $sharemessage, t('Save'));
    $this->assertText(t('Share Message @label has been added.', ['@label' => $sharemessage['label']]));
    $this->drupalGet('sharemessage-test/sharemessage_test_og_label');
    $url = file_create_url($file->getFileUri());

    // Check for OG headers in meta tags.
    $this->assertOGTags('og:title', 'OG headers name');
    $this->assertOGTags('og:url', $this->url);
    $this->assertOGTags('og:description', 'OG headers long description');
    $this->assertOGTags('og:image', $url);

    // Test special characters in OG tags.
    $this->drupalGet('admin/config/services/sharemessage/add');
    $sharemessage = [
      'label' => 'Special characters test ',
      'id' => 'sharemessage_test_special_characters',
      'title' => 'Test with special characters \' " < > & ',
      'message_long' => 'Long description',
      'message_short' => 'Short description',
    ];
    $this->drupalPostForm(NULL, $sharemessage, t('Save'));
    $this->drupalGet('sharemessage-test/sharemessage_test_special_characters');
    // Test for special characters (such as ', ", <, >, &) in a node title
    // used as token for a Share Message title.
    $this->assertOGTags('og:title', 'Test with special characters &#039; &quot; &lt; &gt; &amp; ');
    $this->assertNoOGTags('og:title', 'Test with special characters \' " < > & ');
  }
}
