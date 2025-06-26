<?php

namespace Drupal\Tests\url_embed\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\filter\Entity\FilterFormat;

/**
 * Base class for all url_embed tests.
 */
abstract class UrlEmbedTestBase extends BrowserTestBase {

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
   * The test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * The test Flickr URL.
   */
  const FLICKR_URL = 'https://www.flickr.com/photos/peste76/49945030047/in/explore-2020-05-29/';

  /**
   * The expected output of the Flickr URL in a link field.
   */
  const FLICKR_OUTPUT_FIELD = '<a data-flickr-embed="true" href="https://www.flickr.com/photos/peste76/49945030047/" title="Ephemeral by der_peste (on/off), on Flickr"><img src="https://live.staticflickr.com/65535/49945030047_413c0dd459_b.jpg" width="1024" height="683" alt="Ephemeral"></a><script async src="https://embedr.flickr.com/assets/client-code.js" charset="utf-8"></script>';


  /**
   * The expected output of the Flickr URL in a link field.
   */
  const FLICKR_OUTPUT_FIELD_RESPONSIVE = '<div class="responsive-embed" style="--responsive-embed-ratio: 66.699"><a data-flickr-embed="true" href="https://www.flickr.com/photos/peste76/49945030047/" title="Ephemeral by der_peste (on/off), on Flickr"><img src="https://live.staticflickr.com/65535/49945030047_413c0dd459_b.jpg" width="1024" height="683" alt="Ephemeral"></a><script async src="https://embedr.flickr.com/assets/client-code.js" charset="utf-8"></script>';


  /**
   * The expected output of the Flickr URL in a WYSIWYG.
   */
  const FLICKR_OUTPUT_WYSIWYG = '<a data-flickr-embed="true" href="https://www.flickr.com/photos/peste76/49945030047/" title="Ephemeral by der_peste (on/off), on Flickr"><img src="https://live.staticflickr.com/65535/49945030047_413c0dd459_b.jpg" width="1024" height="683" alt="Ephemeral"></a><script async src="https://embedr.flickr.com/assets/client-code.js" charset="utf-8"></script>';

  /**
   * A set up for all tests.
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
        'url_embed' => [
          'status' => 1,
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

}
