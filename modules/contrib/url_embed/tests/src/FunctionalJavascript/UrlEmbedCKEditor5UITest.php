<?php

namespace Drupal\Tests\url_embed\FunctionalJavascript;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\user\RoleInterface;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Tests that URLs can be embedded with CKEditor5.
 *
 * @group url_embed
 */
class UrlEmbedCKEditor5UITest extends WebDriverTestBase {

  use CKEditor5TestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * The user to use during testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ckeditor5',
    'url_embed'
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'CKEditor 5 with URL Embed',
      'roles' => [],
      'filters' => [
        'url_embed' => [
          'status' => TRUE,
          'settings' => [],
        ],
        'url_embed_convert_links' => [
          'status' => TRUE,
          'settings' => [],
        ],
      ],
    ])->save();
    Editor::create([
      'format' => 'test_format',
      'editor' => 'ckeditor5',
      'settings' => [
        'toolbar' => [
          'items' => ['urlembed'],
        ],
      ],
    ])->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));
    $this->drupalCreateContentType(['type' => 'blog']);
  }

  /**
   * Demonstrates that URLs can be submitted and validated in CKEditor 5.
   */
  public function testFormUi() {
    $invalid_url = 'https://invalidprovider.com';
    $youtube_url = 'https://www.youtube.com/watch?v=3pX4iPEPA9A';
    /** @var \Drupal\FunctionalJavascriptTests\WebDriverWebAssert $assert */
    $assert = $this->assertSession();
    $this->user = $this->drupalCreateUser([
      'use text format test_format',
      'create blog content',
    ]);
    $this->drupalLogin($this->user);
    $this->drupalGet('/node/add/blog');
    $this->waitForEditor();
    $this->pressEditorButton('Url Embed');
    $this->assertNotEmpty($assert->waitForElementVisible('css', '#drupal-modal #url-embed-dialog-form'));
    $input = $assert->waitForElementVisible('css', 'input[name="attributes[data-embed-url]"]');
    // Insert unsupported URL into Drupal dialog form.
    $input->setValue($invalid_url);
    $assert->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Embed');
    $this->assertNotEmpty($assert->waitForText('This is not a supported URL to embed.'));
    // Insert supported URL into Drupal dialog form.
    $input->setValue($youtube_url);
    $assert->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Embed');
    $this->assertNotEmpty($assert->waitForElementVisible('css', '[data-drupal-url-preview="ready"]'));
    $xpath = new \DOMXPath($this->getEditorDataAsDom());
    $drupal_media = $xpath->query('//drupal-url')[0];
    // The upcasted version of the CKEditor content should be
    // <drupal-url data-embed-url="https://www.youtube.com/watch?v=3pX4iPEPA9A" data-url-provider="YouTube"> </drupal-url>
    $this->assertSame($youtube_url, $drupal_media->getAttribute('data-embed-url'));
    $this->assertSame('YouTube', $drupal_media->getAttribute('data-url-provider'));
  }
}
