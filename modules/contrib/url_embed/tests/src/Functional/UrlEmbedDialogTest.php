<?php

namespace Drupal\Tests\url_embed\Functional;

use Drupal\editor\Entity\Editor;

/**
 * Tests the url_embed dialog controller and route.
 *
 * @group url_embed
 */
class UrlEmbedDialogTest extends UrlEmbedTestBase {

  /**
   * Tests the URL embed dialog.
   */
  public function testUrlEmbedDialog() {
    // Ensure that the route is not accessible without specifying all the
    // parameters.
    $this->getEmbedDialog();
    $this->assertSession()->statusCodeEquals(404);
    $this->getEmbedDialog('custom_format');
    $this->assertSession()->statusCodeEquals(404);

    // Ensure that the route is not accessible with an invalid embed button.
    $this->getEmbedDialog('custom_format', 'invalid_button');
    $this->assertSession()->statusCodeEquals(404);

    // Ensure that the route is not accessible with text format without the
    // button configured.
    $this->getEmbedDialog('plain_text', 'url');
    $this->assertSession()->statusCodeEquals(404);

    // Add an empty configuration for the plain_text editor configuration.
    $editor = Editor::create([
      'format' => 'plain_text',
      'editor' => 'ckeditor',
    ]);
    $editor->save();
    $this->getEmbedDialog('plain_text', 'url');
    $this->assertSession()->statusCodeEquals(403);

    // Ensure that the route is accessible with a valid embed button.
    // 'URL' embed button is provided by default by the module and hence the
    // request must be successful.
    $this->getEmbedDialog('custom_format', 'url');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the URL embed button markup.
   */
  public function testUrlEmbedButtonMarkup() {
    // Ensure that the route is not accessible with text format without the
    // button configured.
    $this->getEmbedDialog('plain_text', 'url');
    $this->assertSession()->statusCodeEquals(404);
    // Add an empty configuration for the plain_text editor configuration.
    $editor = Editor::create([
      'format' => 'plain_text',
      'editor' => 'ckeditor',
    ]);
    $editor->save();
    $this->getEmbedDialog('plain_text', 'url');
    $this->assertSession()->statusCodeEquals(403);
    // Ensure that the route is accessible with a valid embed button.
    // 'URL' embed button is provided by default by the module and hence the
    // request must be successful.
    $this->getEmbedDialog('custom_format', 'url');
    $this->assertSession()->statusCodeEquals(200);
    // Ensure form structure of the url_embed_dialog form.
    $this->assertSession()->fieldValueEquals('attributes[data-embed-url]', '');
    // Check that 'Embed' is a primary button.
    $this->assertSession()->elementExists('css','input.button--primary');
    $edit = ['attributes[data-embed-url]' => static::FLICKR_URL];
    $this->submitForm($edit, 'Embed');
  }

  /**
   * Retrieves an embed dialog based on given parameters.
   *
   * @param string $filter_format_id
   *   ID of the filter format.
   * @param string $embed_button_id
   *   ID of the embed button.
   *
   * @return string
   *   The retrieved HTML string.
   */
  public function getEmbedDialog($filter_format_id = NULL, $embed_button_id = NULL) {
    $url = 'url-embed/dialog';
    if (!empty($filter_format_id)) {
      $url .= '/' . $filter_format_id;
      if (!empty($embed_button_id)) {
        $url .= '/' . $embed_button_id;
      }
    }
    return $this->drupalGet($url);
  }
}
