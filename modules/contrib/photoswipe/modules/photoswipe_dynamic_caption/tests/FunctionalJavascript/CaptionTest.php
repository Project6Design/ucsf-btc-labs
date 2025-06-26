<?php

namespace Drupal\Tests\photoswipe_dynamic_caption\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\file\Entity\File;
use Drupal\Tests\photoswipe\FunctionalJavascript\PhotoswipeJsTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the photoswipe_dynamic_caption module.
 *
 * @group photoswipe
 */
class CaptionTest extends PhotoswipeJsTestBase {
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'test_page_test',
    'file',
    'image',
    'node',
    'field_ui',
    'photoswipe',
    'photoswipe_dynamic_caption',
  ];

  /**
   * Tests if caption is visible.
   */
  public function testPhotoswipeCaptionAltText() {
    $session = $this->assertSession();

    // Create the image field on the node 'article':
    $this->createImageField(
          'field_image',
          'node',
          'article',
          ['cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED],
          [],
          [],
          [
            'photoswipe_thumbnail_style_first' => '',
            'photoswipe_thumbnail_style' => '',
            'photoswipe_image_style' => '',
            'photoswipe_reference_image_field' => '',
            'photoswipe_view_mode' => '',
          ],
          'photoswipe_field_formatter',
          '',
          [
            'photoswipe_dynamic_caption' => [
              'photoswipe_caption' => 'alt',
            ],
          ]
        );

    $imageAlt = 'Test alt';

    // Setup an image node:
    $fileFieldEntries = [];
    $file = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ]);
    $file->save();
    $fileFieldEntries[] = [
      'target_id' => $file->id(),
      'alt' => $imageAlt,
      'title' => 'bla',
    ];

    $node = $this->createNode([
      'title' => 'Test',
      'type' => 'article',
      'field_image' => $fileFieldEntries,
    ]);
    $node->save();

    $this->drupalGet('/node/1');

    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    // Check if the image has an 'alt' attribute:
    $session->elementAttributeContains('css', 'a.photoswipe > img', 'alt', $imageAlt);
    // Open the photoswipe layer.
    $this->getSession()->getPage()->find('css', 'a[href*="image-test.png"].photoswipe')->click();
    $this->assertNotNull($session->waitForElementVisible('css', '.pswp'));
    $session->elementTextEquals('css', '.pswp__dynamic-caption', $imageAlt);
  }

  // @todo Add tests for the rest of the possible "photoswipe_caption" values
  // here.
}
