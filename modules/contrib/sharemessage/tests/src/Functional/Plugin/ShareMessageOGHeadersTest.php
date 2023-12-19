<?php

namespace Drupal\Tests\sharemessage\Functional\Plugin;

use Drupal\file\Entity\File;
use Drupal\Tests\sharemessage\Functional\ShareMessageTestBase;

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
    $files[] = $this->getTestFiles('image');
    $uri = 'public://file.png';
    \Drupal::service('file_system')->copy($files[0][0]->uri, $uri);
    $file = File::create(['uri' => $uri]);
    $file->save();
    $sharemessage = [
      'label' => 'Share Message Test OG Label',
      'id' => 'sharemessage_test_og_label',
      'plugin' => 'ogheaders',
      'title' => 'OG headers name',
      'message_long' => 'OG headers long description',
      'message_short' => 'OG headers short description',
      'fallback_image' => $file->uuid(),
    ];
    $this->drupalGet('admin/config/services/sharemessage/add');
    $this->submitForm($sharemessage, t('Save'));
    $this->assertSession()->pageTextContains(t('Share Message @label has been added.', ['@label' => $sharemessage['label']]));
    $this->drupalGet('admin/config/services/sharemessage/manage/sharemessage_test_og_label');
    $override_settings = '//details[starts-with(@data-drupal-selector, "edit-settings")]';
    $this->xpath($override_settings);
    $this->assertSession()->pageTextContains('Open graph headers are used when users want to use it as a framework or a background tool only.');
    $this->assertSession()->pageTextContains('The OG Headers plugin doesn\'t provide any settings.');
    $this->drupalGet('sharemessage-test/sharemessage_test_og_label');
    $url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());

    $image = \Drupal::service('image.factory')->get($file->getFileUri());
    $image_width = $image->getWidth();
    $image_height = $image->getHeight();

    // Check for OG headers in meta tags.
    $this->assertOGTags('og:title', 'OG headers name');
    $this->assertOGTags('og:url', $this->getUrl());
    $this->assertOGTags('og:description', 'OG headers long description');
    $this->assertOGTags('og:image', $url);
    $this->assertOGTags('og:image:width', $image_width);
    $this->assertOGTags('og:image:height', $image_height);

    // Test special characters in OG tags.
    $this->drupalGet('admin/config/services/sharemessage/add');
    $sharemessage = [
      'label' => 'Special characters test ',
      'id' => 'sharemessage_test_special_characters',
      'title' => 'Test with special characters \' " < > & ',
      'message_long' => 'Long description',
      'message_short' => 'Short description',
    ];
    $this->submitForm($sharemessage, t('Save'));
    $this->drupalGet('sharemessage-test/sharemessage_test_special_characters');
    // Test for special characters (such as ', ", <, >, &) in a node title
    // used as token for a Share Message title.
    $this->assertOGTags('og:title', 'Test with special characters &#039; &quot; &lt; &gt; &amp; ');
    $this->assertNoOGTags('og:title', 'Test with special characters \' " < > & ');
  }
}
