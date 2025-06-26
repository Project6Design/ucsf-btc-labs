<?php

namespace Drupal\Tests\url_embed\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests the url_embed_convert_links filter.
 *
 * @group url_embed
 */
class ConvertUrlToEmbedFilterTest extends BrowserTestBase {

  /**
   * The test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['url_embed', 'node'];

  /**
   * {@inheritdoc}
   */
  public $defaultTheme = 'stark';

  /**
   * Set the configuration up.
   */
  protected function setUp(): void {
    parent::setUp();
    // Create a page content type.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Create a text format and enable the url_embed filter.
    $format = FilterFormat::create([
      'format' => 'custom_format',
      'name' => 'Custom format',
      'filters' => [
        'url_embed_convert_links' => [
          'status' => 1,
          'settings' => ['url_prefix' => ''],
        ],
      ],
    ]);
    $format->save();

    // Create a user with required permissions.
    $this->webUser = $this->drupalCreateUser([
      'access content',
      'create page content',
      'use text format custom_format',
    ]);
    $this->drupalLogin($this->webUser);
  }

  /**
   * Tests the url_embed_convert_links filter.
   *
   * Ensures that iframes are getting rendered when valid urls
   * are passed. Also tests situations when embed fails.
   */
  public function testFilter() {
    $content = 'before https://www.youtube.com/watch?v=I95hSyocMlg after';
    $settings = [];
    $settings['type'] = 'page';
    $settings['title'] = 'Test convert url to embed with sample YouTube url';
    $settings['body'] = [['value' => $content, 'format' => 'custom_format']];
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('<drupal-url data-embed-url="https://www.youtube.com/watch?v=I95hSyocMlg"></drupal-url>');
    $this->assertSession()->pageTextNotContains(strip_tags($content));

    $content = 'before /not-valid/url after';
    $settings = [];
    $settings['type'] = 'page';
    $settings['title'] = 'Test convert url to embed with non valid URL';
    $settings['body'] = [['value' => $content, 'format' => 'custom_format']];
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains($content);

    /** @var \Drupal\filter\FilterFormatInterface $format */
    $format = FilterFormat::load('custom_format');
    $configuration = $format->filters('url_embed_convert_links')->getConfiguration();
    $configuration['settings']['url_prefix'] = 'EMBED ';
    $format->setFilterConfig('url_embed_convert_links', $configuration);
    $format->save();

    $content = 'before https://www.youtube.com/watch?v=I95hSyocMlg after';
    $settings = [];
    $settings['type'] = 'page';
    $settings['title'] = 'Test convert url to embed with sample YouTube url and no prefix';
    $settings['body'] = [['value' => $content, 'format' => 'custom_format']];
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains(strip_tags($content));
    $this->assertSession()->responseNotContains('<drupal-url data-embed-url="https://www.youtube.com/watch?v=I95hSyocMlg"></drupal-url>');

    $content = 'before EMBED https://www.youtube.com/watch?v=I95hSyocMlg after';
    $settings = [];
    $settings['type'] = 'page';
    $settings['title'] = 'Test convert url to embed with sample YouTube url with the prefix';
    $settings['body'] = [['value' => $content, 'format' => 'custom_format']];
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('<drupal-url data-embed-url="https://www.youtube.com/watch?v=I95hSyocMlg"></drupal-url>');
    $this->assertSession()->pageTextNotContains(strip_tags($content));

    $content = 'before Embed https://www.youtube.com/watch?v=I95hSyocMlg after';
    $settings = [];
    $settings['type'] = 'page';
    $settings['title'] = 'Test convert url to embed with sample YouTube url with wrong prefix';
    $settings['body'] = [['value' => $content, 'format' => 'custom_format']];
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains(strip_tags($content));
    $this->assertSession()->responseNotContains('<drupal-url data-embed-url="https://www.youtube.com/watch?v=I95hSyocMlg"></drupal-url>');
  }

}
