<?php

namespace Drupal\Tests\photoswipe\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\file\Entity\File;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the photoswipe responsive images.
 *
 * @group photoswipe
 */
class ResponsiveImageTest extends PhotoswipeJsTestBase {
  use TestFileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'responsive_image',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createImageField(
      'field_responsive_image',
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
      'photoswipe_responsive_field_formatter',
    );
  }

  /**
   * Sets up a node containing an image field with the responsive formatter.
   *
   * @todo Move to PhotoswipeTestBase and adjust Media and Image tests.
   */
  protected function setupNodeDisplayingImage() {
    $images = $this->getTestFiles('image');
    $fileFieldEntries = [];
    for ($i = 0; $i < 3; $i++) {
      $file = File::create([
        'uri' => $images[$i]->uri,
      ]);
      $file->save();
      $fileFieldEntries[] = [
        'target_id' => $file->id(),
        'alt' => 'count-' . $i,
        'title' => '',
      ];
    }

    $node = $this->createNode([
      'title' => 'Test',
      'type' => 'article',
      'field_responsive_image' => $fileFieldEntries,
    ]);
    $node->save();
  }

  /**
   * Tests the responsive image formatter's node_style "hide" option.
   */
  public function testPhotoswipeResponsiveHideOption() {
    $session = $this->assertSession();
    $this->container->get('config.factory')
      ->getEditable('core.entity_view_display.node.article.default')
      ->set('content.field_responsive_image.settings.photoswipe_thumbnail_style', 'hide')
      ->set('content.field_responsive_image.settings.photoswipe_thumbnail_style_first', 'wide')
      ->save();

    $this->setupNodeDisplayingImage();

    $this->drupalGet('/node/1');
    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    $session->elementExists('css', 'div.photoswipe-gallery a.photoswipe');
    // We have 3 field items, so 3 anchor tags should exist:
    $session->elementsCount('css', 'div.photoswipe-gallery a.photoswipe', 3);
    // Only the first image element should exist, as the other two should be
    // unset:
    $session->elementExists('css', 'div.photoswipe-gallery a.photoswipe img[alt="count-0"]');
    $session->elementNotExists('css', 'div.photoswipe-gallery a.photoswipe img[alt="count-1"]');
    $session->elementNotExists('css', 'div.photoswipe-gallery a.photoswipe img[alt="count-2"]');

    $this->getSession()->getPage()->find('css', '.photoswipe-gallery a.photoswipe:not(.hidden)')->click();
    $this->assertNotNull($session->waitForElementVisible('css', '.pswp'));
    $session->elementExists('css', '#pswp__items img.pswp__img');
    $session->elementTextEquals('css', '.pswp .pswp__scroll-wrap .pswp__counter', '1 / 3');
  }

  /**
   * Tests the responsive image formatter's node_style_first "hide" option.
   */
  public function testPhotoswipeResponsiveHideFirstOption() {
    $session = $this->assertSession();
    $this->container->get('config.factory')
      ->getEditable('core.entity_view_display.node.article.default')
      ->set('content.field_responsive_image.settings.photoswipe_thumbnail_style', 'wide')
      ->set('content.field_responsive_image.settings.photoswipe_thumbnail_style_first', 'hide')
      ->save();

    $this->setupNodeDisplayingImage();

    $this->drupalGet('/node/1');
    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    $session->elementExists('css', 'div.photoswipe-gallery a.photoswipe');
    // We have 3 field items, so 3 anchor tags should exist:
    $session->elementsCount('css', 'div.photoswipe-gallery a.photoswipe', 3);
    // Only the last two image element should exist, as the other one should be
    // unset:
    $session->elementNotExists('css', 'div.photoswipe-gallery a.photoswipe img[alt="count-0"]');
    $session->elementExists('css', 'div.photoswipe-gallery a.photoswipe img[alt="count-1"]');
    $session->elementExists('css', 'div.photoswipe-gallery a.photoswipe img[alt="count-2"]');

    $this->getSession()->getPage()->find('css', 'div.photoswipe-gallery > div > div:nth-child(2) > a.photoswipe:not(.hidden)')->click();
    $session->waitForElementVisible('css', '.pswp');
    $session->elementExists('css', '#pswp__items img.pswp__img');
    $session->elementTextEquals('css', '.pswp .pswp__scroll-wrap .pswp__counter', '2 / 3');
  }

}
