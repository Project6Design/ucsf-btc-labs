<?php

namespace Drupal\Tests\url_embed\Functional;

use Drupal\filter\Entity\FilterFormat;

/**
 * Tests the url_embed filter.
 *
 * @group url_embed
 */
class UrlEmbedFilterTest extends UrlEmbedTestBase {

  /**
   * Tests the url_embed filter.
   *
   * Ensures that iframes are getting rendered when valid urls
   * are passed. Also tests situations when embed fails.
   */
  public function testFilter() {
    // Tests url embed using sample flickr url.
    $content = '<drupal-url data-embed-url="' . static::FLICKR_URL . '">This placeholder should not be rendered.</drupal-url>';
    $settings = [];
    $settings['type'] = 'page';
    $settings['title'] = 'Test url embed with sample flickr url';
    $settings['body'] = [['value' => $content, 'format' => 'custom_format']];
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains(static::FLICKR_OUTPUT_WYSIWYG);
    $this->assertSession()->pageTextNotContains(strip_tags($content));

    // Ensure that placeholder is not replaced when embed is unsuccessful.
    $content = '<drupal-url data-embed-url="">This placeholder should be rendered since specified URL does not exists.</drupal-url>';
    $settings = [];
    $settings['type'] = 'page';
    $settings['title'] = 'Test that placeholder is retained when specified URL does not exists';
    $settings['body'] = [['value' => $content, 'format' => 'custom_format']];
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextNotContains(strip_tags($content));

    // Test that tag of container element is not replaced when it's not
    // <drupal-url>.
    $content = '<not-drupal-url data-embed-url="' . static::FLICKR_URL . '" data-url-provider="Flickr">this placeholder should not be rendered.</not-drupal-url>';
    $settings = [];
    $settings['type'] = 'page';
    $settings['title'] = 'test url embed with embed-url';
    $settings['body'] = [['value' => $content, 'format' => 'custom_format']];
    $node = $this->drupalCreateNode($settings);
    $this->drupalget('node/' . $node->id());
    $this->assertSession()->responseContains('</not-drupal-url>');
    $content = '<div data-embed-url="' . static::FLICKR_URL . '">this placeholder should not be rendered.</div>';
    $settings = [];
    $settings['type'] = 'page';
    $settings['title'] = 'test url embed with embed-url';
    $settings['body'] = [['value' => $content, 'format' => 'custom_format']];
    $node = $this->drupalCreateNode($settings);
    $this->drupalget('node/' . $node->id());
    $this->assertSession()->responseContains('<div data-embed-url="' . static::FLICKR_URL . '"');

    // Enable the settings option to use a responsive wrapper.
    $format = FilterFormat::load('custom_format');
    $configuration = $format->filters('url_embed')->getConfiguration();
    $configuration['settings']['enable_responsive'] = '1';
    $format->setFilterConfig('url_embed', $configuration);
    $format->save();
    // Tests responsive url embed using sample flickr url.
    $content = '<drupal-url data-embed-url="' . static::FLICKR_URL . '">This placeholder should not be rendered.</drupal-url>';
    $settings = [];
    $settings['type'] = 'page';
    $settings['title'] = 'Test responsive url embed with sample Flickr url';
    $settings['body'] = [['value' => $content, 'format' => 'custom_format']];
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('<div class="responsive-embed" style="--responsive-embed-ratio: 66.699">' . static::FLICKR_OUTPUT_WYSIWYG . '</div>');
    $this->assertSession()->pageTextNotContains(strip_tags($content));
  }

}
