<?php

namespace Drupal\Tests\photoswipe\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the photoswipe display setting on an referenced media entity.
 *
 * @group photoswipe
 */
class MediaReferenceTest extends PhotoswipeJsTestBase {
  use TestFileCreationTrait, EntityReferenceFieldCreationTrait, MediaTypeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'media',
    'media_test_source',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createMediaType('image', ['id' => 'image', 'new_revision' => TRUE]);
  }

  /**
   * Setup a media entity reference field on the "article" node bundle.
   */
  public function setupMediaFieldOnNodeBundle(string $fieldName = 'field_media_image', int $fieldCardinality = 1) {
    $this->createEntityReferenceField('node', 'article', $fieldName, $fieldName, 'media', 'default', ['target_bundles' => ['image']], $fieldCardinality);
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', 'article')
      ->setComponent($fieldName, [
        'type' => 'entity_reference_autocomplete',
        'settings' => [],
      ])
      ->save();
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent($fieldName, [
        'type' => 'photoswipe_field_formatter',
        'settings' => [],
      ])
      ->save();
  }

  /**
   * Helper function to create a media image.
   */
  public function createMedia(?object $image = NULL, string $mediaName = 'image-test.png') {
    if ($image === NULL) {
      $image = $this->getTestFiles('image')[0];
    }
    $file = File::create([
      'uri' => $image->uri,
    ]);
    $file->save();

    $media = Media::create([
      'bundle' => 'image',
      'name' => $mediaName,
      'field_media_image' => [
        'target_id' => $file->id(),
        'alt' => 'Alt text',
        'title' => $mediaName,
      ],
    ]);
    $media->save();

    return $media;
  }

  /**
   * Tests if the Photoswipe field formatter settings exist.
   */
  public function testPhotoswipeFieldFormatterSettingsExist() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->setupMediaFieldOnNodeBundle();
    $this->createMedia();

    // Go to manage display page.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $session->pageTextContains('Photoswipe');
    // Check if the photoswipe field formatter is selected:
    $session->elementAttributeContains('css', '#edit-fields-field-media-image-type > option[value="photoswipe_field_formatter"]', 'selected', 'selected');
    // Check if all formatter settings exist, and have the correct default
    // selected setting:
    $page->pressButton('edit-fields-field-media-image-settings-edit');
    $this->assertNotNull($session->waitForElementVisible('css', 'select[id*=edit-fields-field-media-image-settings-edit-form-settings-photoswipe-thumbnail-style-first]'));
    $session->elementExists('css', 'select[id*="edit-fields-field-media-image-settings-edit-form-settings-photoswipe-thumbnail-style-first"]');
    $session->elementExists('css', 'select[id*="edit-fields-field-media-image-settings-edit-form-settings-photoswipe-thumbnail-style"]:not([id*=first])');
    $session->elementExists('css', 'select[id*="edit-fields-field-media-image-settings-edit-form-settings-photoswipe-image-style"]');
    $session->elementTextEquals('css', 'select[id*="edit-fields-field-media-image-settings-edit-form-settings-photoswipe-thumbnail-style-first"] > option[selected="selected"]', 'No override (use default thumbnail image style)');
    $session->elementTextEquals('css', 'select[id*="edit-fields-field-media-image-settings-edit-form-settings-photoswipe-thumbnail-style"]:not([id*=first]) > option[selected="selected"]', 'None (Original image)');
    $session->elementTextEquals('css', 'select[id*="edit-fields-field-media-image-settings-edit-form-settings-photoswipe-image-style"] > option[selected="selected"]', 'None (Original image)');
    // @codingStandardsIgnoreStart
    // @todo Why does the following select not have a selected field?:
    // $session->elementTextEquals('css', 'select[id*=edit-fields-field-media-image-settings-edit-form-settings-photoswipe-caption] > option[selected="selected"]', 'Image title tag');
    // Check if changing a setting and submitting the display,
    // won't break anything:
    // @codingStandardsIgnoreEnd
    $page->selectFieldOption('fields[field_media_image][settings_edit_form][settings][photoswipe_thumbnail_style_first]', 'large');
    $page->pressButton('Update');
    $page->pressButton('edit-submit');
    $session->pageTextContains('Your settings have been saved.');
  }

  /**
   * Tests the photoswipe formatter on node display.
   */
  public function testPhotoswipeFieldFormatterOnNodeDisplay() {
    $session = $this->assertSession();

    $this->setupMediaFieldOnNodeBundle();
    $media = $this->createMedia();
    // Create the node with a test file uploaded:
    $node = $this->createNode([
      'title' => 'Test',
      'type' => 'article',
      'field_media_image' => [
        'target_id' => $media->id(),
      ],
    ]);
    $node->save();
    $this->drupalGet('/node/' . $node->id());
    // Check if the anker element is set with the correct classes, wrappers and
    // attributes:
    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe');
    $session->elementExists('css', '.photoswipe-gallery a[href*="image-test.png"].photoswipe');
    $session->elementAttributeExists('css', 'a[href*="image-test.png"].photoswipe', 'data-pswp-width');
    // Check if the image is loaded with the correct defaults and wrappers:
    $session->elementExists('css', 'img[src*="image-test.png"]');
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementExists('css', '.photoswipe-gallery a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    // Uploaded pictures are not broken during testing, but only on later
    // inspection. See https://www.drupal.org/project/drupal/issues/3272192.
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'width', '40');
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'height', '20');
    // @todo Check the photoswipe functionalities here.
  }

  /**
   * Tests if the access permissions work correctly for an anonymous user.
   */
  public function testPhotoswipeFieldFormatterNodeDisplayPermissionAnonymous() {
    $session = $this->assertSession();

    $this->setupMediaFieldOnNodeBundle();
    $media = $this->createMedia();
    // Create the node with a test file uploaded:
    $node = $this->createNode([
      'title' => 'Test',
      'type' => 'article',
      'field_media_image' => [
        'target_id' => $media->id(),
      ],
    ]);
    $node->save();
    $this->drupalGet('/node/' . $node->id());
    // Check if the image is loaded with the correct defaults and wrappers:
    $session->elementExists('css', 'img[src*="image-test.png"]');
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementExists('css', '.photoswipe-gallery a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    // Unpublish media_entity:
    $media->setUnpublished()->save();
    // Logout:
    $this->drupalLogout();
    // Check if image is not rendered anymore:
    $session->elementNotExists('css', 'img[src*="image-test.png"]');
    $session->elementNotExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementNotExists('css', '.photoswipe-gallery a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
  }

  /**
   * Tests if the access permissions are correct for an authenticated user.
   */
  public function testPhotoswipeFieldFormatterNodeDisplayPermissionAuthenticated() {
    $session = $this->assertSession();

    $this->setupMediaFieldOnNodeBundle();
    $media = $this->createMedia();
    // Create the node with a test file uploaded:
    $node = $this->createNode([
      'title' => 'Test',
      'type' => 'article',
      'field_media_image' => [
        'target_id' => $media->id(),
      ],
    ]);
    $node->save();
    $this->drupalGet('/node/' . $node->id());
    // Check if the image is loaded with the correct defaults and wrappers:
    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    $session->elementExists('css', 'img[src*="image-test.png"]');
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementExists('css', '.photoswipe-gallery a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    // Unpublish media_entity:
    $media->setUnpublished()->save();
    // Logout and login as authenticated user:
    $this->drupalLogout();
    $this->drupalLogin($this->user);
    // Check if image is not rendered anymore:
    $session->elementNotExists('css', 'img[src*="image-test.png"]');
    $session->elementNotExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementNotExists('css', '.photoswipe-gallery a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
  }

  /**
   * Tests the photoswipe formatter on node display with two media fields.
   */
  public function testTwoPhotoswipeFieldFormatterOnNodeDisplay() {
    $session = $this->assertSession();

    $this->setupMediaFieldOnNodeBundle('field_media_image');
    $this->setupMediaFieldOnNodeBundle('field_media_image_two');

    $media = $this->createMedia();
    $media2 = $this->createMedia();

    $node = $this->createNode([
      'title' => 'Test',
      'type' => 'article',
      'field_media_image' => [
        'target_id' => $media->id(),
      ],
      'field_media_image_two' => [
        'target_id' => $media2->id(),
      ],
    ]);
    $node->save();
    $this->drupalGet('/node/' . $node->id());
    // Check if the anker element is set with the correct classes, wrappers and
    // attributes:
    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe');
    $session->elementExists('css', '.photoswipe-gallery a[href*="image-test.png"].photoswipe');
    $session->elementAttributeExists('css', 'a[href*="image-test.png"].photoswipe', 'data-pswp-width');
    // Check if the image is loaded with the correct defaults and wrappers:
    $session->elementExists('css', 'img[src*="image-test.png"]');
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementExists('css', '.photoswipe-gallery a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    // Check that two of each element exist:
    $session->elementsCount('css', '.photoswipe-gallery', 2);
    $session->elementsCount('css', '.photoswipe-gallery a[href*="image-test.png"].photoswipe', 2);
    $session->elementsCount('css', '.photoswipe-gallery a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]', 2);
    // Uploaded pictures are not broken during testing, but only on later
    // inspection. See https://www.drupal.org/project/drupal/issues/3272192.
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'width', '40');
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'height', '20');
    // @todo Check the photoswipe functionalities here.
  }

  /**
   * Tests upload of multiple media images on one field.
   */
  public function testMultipleImagesOnFieldWithPhotoswipeFieldFormatter() {
    $session = $this->assertSession();

    $this->setupMediaFieldOnNodeBundle('field_media_image', FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $media = $this->createMedia();
    $media2 = $this->createMedia($this->getTestFiles('image')[1], 'image-test2');

    $node = $this->createNode([
      'title' => 'Test',
      'type' => 'article',
      'field_media_image' => [
        0 => [
          'target_id' => $media->id(),
        ],
        1 => [
          'target_id' => $media2->id(),
        ],
      ],
    ]);
    $node->save();
    $this->drupalGet('/node/' . $node->id());
    // Check if the anker element is set with the correct classes, wrappers and
    // attributes for the first picture:
    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe');
    $session->elementExists('css', 'div.photoswipe-gallery div > a[href*="image-test.png"].photoswipe');

    $session->elementAttributeExists('css', 'a[href*="image-test.png"].photoswipe', 'data-pswp-width');
    // Check if the image is loaded with the correct defaults and wrappers for
    // the first picture:
    $session->elementExists('css', 'img[src*="image-test.png"]');
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementExists('css', 'div.photoswipe-gallery div > a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    // Check if the anker element is set with the correct classes, wrappers and
    // attributes for the second picture:
    // Check, that only one photoswipe-gallery wrapper exists:
    $session->elementsCount('css', 'div.photoswipe-gallery', 1);
    // Check that two of each element exist:
    $session->elementsCount('css', 'div.photoswipe-gallery div > a.photoswipe', 2);
    // @todo Check the photoswipe functionalities here.
  }

}
