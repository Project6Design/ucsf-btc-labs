<?php

namespace Drupal\Tests\photoswipe\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the photoswipe display setting on an referenced media entity.
 *
 * @group photoswipe
 */
class MediaReferenceTest extends WebDriverTestBase {
  use TestFileCreationTrait, EntityReferenceTestTrait, MediaTypeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'test_page_test',
    'file',
    'media',
    'image',
    'node',
    'field_ui',
    'photoswipe',
  ];

  /**
   * A user with admin permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * A user with authenticated permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->publicFilesDirectory = 'public://';
    $this->config('system.site')->set('page.front', '/test-page')->save();

    $this->user = $this->drupalCreateUser([]);
    $this->adminUser = $this->drupalCreateUser([]);
    $this->adminUser->addRole($this->createAdminRole('admin', 'admin'));
    $this->adminUser->save();
    $this->drupalLogin($this->adminUser);

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
  }

  /**
   * Helper function to create a media image.
   */
  public function createMediaImage() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('/media/add/image');
    $this->assertNotEmpty($image_upload_field = $page->find('css', '#edit-field-media-image-0-upload'));
    $image = $this->getTestFiles('image')[0];
    $image_upload_field->attachFile($this->container->get('file_system')->realpath($image->uri));
    $session->waitForElementVisible('css', '.image-preview');
    $page->fillField('edit-name-0-value', 'image-test.png');
    $page->fillField('Alternative text', 'Alt text');
    $page->pressButton('edit-submit');
    $session->pageTextContains('image-test.png has been created.');
  }

  /**
   * Tests if the Photoswipe field formatter settings exist.
   */
  public function testPhotoswipeFieldFormatterSettingsExist() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->createMediaType('image', ['id' => 'image', 'new_revision' => TRUE]);
    $this->createEntityReferenceField('node', 'article', 'media_image', 'media_image', 'media', 'default', ['target_bundles' => ['image']]);
    $this->createMediaImage();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', 'article')
      ->setComponent('media_image', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [],
      ])
      ->save();
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent('media_image', [
        'type' => 'photoswipe_field_formatter',
        'settings' => [],
      ])
      ->save();

    // Go to manage display page.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $session->pageTextContains('Photoswipe');
    // Check if the photoswipe field formatter is selected:
    $session->elementAttributeContains('css', '#edit-fields-media-image-type > option[value="photoswipe_field_formatter"]', 'selected', 'selected');
    // Check if all formatter settings exist, and have the correct default
    // selected setting:
    $page->pressButton('edit-fields-media-image-settings-edit');
    $session->waitForElementVisible('css', 'select[id*=edit-fields-media-image-settings-edit-form-settings-photoswipe-node-style-first]');
    $session->elementExists('css', 'select[id*="edit-fields-media-image-settings-edit-form-settings-photoswipe-node-style-first"]');
    $session->elementExists('css', 'select[id*="edit-fields-media-image-settings-edit-form-settings-photoswipe-node-style"]:not([id*=first])');
    $session->elementExists('css', 'select[id*="edit-fields-media-image-settings-edit-form-settings-photoswipe-image-style"]');
    $session->elementTextEquals('css', 'select[id*="edit-fields-media-image-settings-edit-form-settings-photoswipe-node-style-first"] > option[selected="selected"]', 'No override (use default thumbnail image style)');
    $session->elementTextEquals('css', 'select[id*="edit-fields-media-image-settings-edit-form-settings-photoswipe-node-style"]:not([id*=first]) > option[selected="selected"]', 'None (Original image)');
    $session->elementTextEquals('css', 'select[id*="edit-fields-media-image-settings-edit-form-settings-photoswipe-image-style"] > option[selected="selected"]', 'None (Original image)');
    // @todo Why does the following select not have a selected field?:
    // $session->elementTextEquals('css', 'select[id*=edit-fields-media-image-settings-edit-form-settings-photoswipe-caption] > option[selected="selected"]', 'Image title tag');
    // Check if changing a setting and submitting the display,
    // won't break anything:
    $page->selectFieldOption('fields[media_image][settings_edit_form][settings][photoswipe_node_style_first]', 'large');
    $page->pressButton('Update');
    $page->pressButton('edit-submit');
    $session->pageTextContains('Your settings have been saved.');
  }

  /**
   * Tests the photoswipe formatter on node display.
   */
  public function testPhotoswipeFieldFormatterOnNodeDisplay() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->createMediaType('image', ['id' => 'image', 'new_revision' => TRUE]);
    $this->createEntityReferenceField('node', 'article', 'media_image', 'media_image', 'media', 'default', ['target_bundles' => ['image']]);
    $this->createMediaImage();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', 'article')
      ->setComponent('media_image', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [],
      ])
      ->save();
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent('media_image', [
        'type' => 'photoswipe_field_formatter',
        'settings' => [],
      ])
      ->save();
    // Create the node with a test file uploaded:
    $this->drupalGet('node/add/article');
    $title = 'My test content';
    $page->fillField('title[0][value]', $title);
    $page->fillField('edit-media-image-0-target-id', 'image-test.png');
    $page->pressButton('edit-submit');
    $session->pageTextContains("Article {$title} has been created.");
    $this->getSession()->wait(5000, 'typeof window.jQuery == "function"');
    $this->drupalGet('/node/1');
    // // Check if all necessary js and css files are loaded:
    $session->elementExists('css', 'link[href*="//cdnjs.cloudflare.com/ajax/libs/photoswipe/4.1.3/photoswipe.css"]');
    $session->elementExists('css', 'link[href*="//cdnjs.cloudflare.com/ajax/libs/photoswipe/4.1.3/default-skin/default-skin.css"]');
    $session->elementExists('css', 'script[src*="//cdnjs.cloudflare.com/ajax/libs/photoswipe/4.1.3/photoswipe.min.js"]');
    $session->elementExists('css', 'script[src*="//cdnjs.cloudflare.com/ajax/libs/photoswipe/4.1.3/photoswipe-ui-default.min.js"]');
    // // Check if the fallback wrapper is loaded with the correct
    // // classes and attributes:
    // Wait for the JavaScript to initialize the fallback wrapper:
    $session->waitForElement('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper');
    $session->waitForElement('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper');
    $session->elementExists('css', '.photoswipe-gallery');
    $session->elementExists('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper');
    $session->elementAttributeExists('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper', 'data-pswp-uid');
    // Check if the anker element is set with the correct classes, wrappers and
    // attributes:
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe');
    $session->elementExists('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper > a[href*="image-test.png"].photoswipe');
    $session->elementAttributeExists('css', 'a[href*="image-test.png"].photoswipe', 'data-size');
    $session->elementAttributeContains('css', 'a[href*="image-test.png"].photoswipe', 'data-overlay-title', 'Alt text');
    // Check if the image is loaded with the correct defaults and wrappers:
    $session->elementExists('css', 'img[src*="image-test.png"]');
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementExists('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper > a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
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
    $page = $this->getSession()->getPage();

    $this->createMediaType('image', ['id' => 'image', 'new_revision' => TRUE]);
    $this->createEntityReferenceField('node', 'article', 'media_image', 'media_image', 'media', 'default', ['target_bundles' => ['image']]);
    $this->createMediaImage();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', 'article')
      ->setComponent('media_image', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [],
      ])
      ->save();
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent('media_image', [
        'type' => 'photoswipe_field_formatter',
        'settings' => [],
      ])
      ->save();
    // Create the node with a test file uploaded:
    $this->drupalGet('node/add/article');
    $title = 'My test content';
    $page->fillField('title[0][value]', $title);
    $page->fillField('edit-media-image-0-target-id', 'image-test.png');
    $page->pressButton('edit-submit');
    $session->pageTextContains("Article {$title} has been created.");
    $this->getSession()->wait(5000, 'typeof window.jQuery == "function"');
    $this->drupalGet('/node/1');
    // Check if the image is loaded with the correct defaults and wrappers:
    $session->elementExists('css', 'img[src*="image-test.png"]');
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementExists('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper > a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    // Unpublish media_entity:
    $this->drupalGet('/media/1/edit');
    $page->uncheckField('edit-status-value');
    $page->pressButton('edit-submit');
    // Logout:
    $this->drupalLogout();
    // Check if image is not rendered anymore:
    $session->elementNotExists('css', 'img[src*="image-test.png"]');
    $session->elementNotExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementNotExists('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper > a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
  }

  /**
   * Tests if the access permissions are correct for an authenticated user.
   */
  public function testPhotoswipeFieldFormatterNodeDisplayPermissionAuthenticated() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->createMediaType('image', ['id' => 'image', 'new_revision' => TRUE]);
    $this->createEntityReferenceField('node', 'article', 'media_image', 'media_image', 'media', 'default', ['target_bundles' => ['image']]);
    $this->createMediaImage();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', 'article')
      ->setComponent('media_image', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [],
      ])
      ->save();
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent('media_image', [
        'type' => 'photoswipe_field_formatter',
        'settings' => [],
      ])
      ->save();
    // Create the node with a test file uploaded:
    $this->drupalGet('node/add/article');
    $title = 'My test content';
    $page->fillField('title[0][value]', $title);
    $page->fillField('edit-media-image-0-target-id', 'image-test.png');
    $page->pressButton('edit-submit');
    $session->pageTextContains("Article {$title} has been created.");
    $this->getSession()->wait(5000, 'typeof window.jQuery == "function"');
    $this->drupalGet('/node/1');
    // Check if the image is loaded with the correct defaults and wrappers:
    $session->elementExists('css', 'img[src*="image-test.png"]');
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementExists('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper > a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    // Unpublish media_entity:
    $this->drupalGet('/media/1/edit');
    $page->uncheckField('edit-status-value');
    $page->pressButton('edit-submit');
    // Logout and login as authenticated user:
    $this->drupalLogout();
    $this->drupalLogin($this->user);
    // Check if image is not rendered anymore:
    $session->elementNotExists('css', 'img[src*="image-test.png"]');
    $session->elementNotExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementNotExists('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper > a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
  }

  /**
   * Tests the photoswipe formatter on node preview.
   */
  // Public function testPhotoswipeFieldFormatterOnNodePreview() {
  //   $session = $this->assertSession();
  //   $page = $this->getSession()->getPage();
  // $this->createMediaType('image', ['id' => 'image', 'new_revision' => TRUE]);
  //   $this->createEntityReferenceField('node', 'article', 'media_image', 'media_image', 'media', 'default', ['target_bundles' => ['image']]);
  //   $this->createMediaImage();
  // /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
  //   $display_repository = \Drupal::service('entity_display.repository');
  //   $display_repository->getFormDisplay('node', 'article')
  //     ->setComponent('media_image', [
  //       'type' => 'entity_reference_autocomplete',
  //       'settings' => [],
  //     ])
  //     ->save();
  //   $display_repository->getViewDisplay('node', 'article')
  //     ->setComponent('media_image', [
  //       'type' => 'photoswipe_field_formatter',
  //       'settings' => [],
  //     ])
  //     ->save();
  //   // Create the node with a test file uploaded:
  //   $this->drupalGet('node/add/article');
  //   $title = 'My test content';
  //   $page->fillField('title[0][value]', $title);
  //   $page->fillField('edit-media-image-0-target-id', 'image-test.png');
  //   $page->pressButton('edit-preview');
  //   // Check if all necessary js and css files are loaded:
  //   $session->elementExists('css', 'link[href*="/libraries/photoswipe/dist/photoswipe.css"]');
  //   $session->elementExists('css', 'link[href*="/libraries/photoswipe/dist/default-skin/default-skin.css"]');
  //   $session->elementExists('css', 'script[src*="/libraries/photoswipe/dist/photoswipe.min.js"]');
  //   $session->elementExists('css', 'script[src*="/libraries/photoswipe/dist/photoswipe-ui-default.min.js"]');
  //   // Check if the fallback wrapper is loaded with the correct
  //   // classes and attributes:
  //   // Wait for the JavaScript to initialize the fallback wrapper:
  //   $session->waitForElement('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper');
  //   $session->elementExists('css', '.photoswipe-gallery');
  //   $session->elementExists('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper');
  //   $session->elementAttributeExists('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper', 'data-pswp-uid');
  //   // Check if the anker element is set with the correct classes, wrappers and
  //   // attributes:
  //   $session->elementExists('css', 'a[href*="image-test.png"].photoswipe');
  //   $session->elementExists('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper > a[href*="image-test.png"].photoswipe');
  //   $session->elementAttributeExists('css', 'a[href*="image-test.png"].photoswipe', 'data-size');
  //   $session->elementAttributeContains('css', 'a[href*="image-test.png"].photoswipe', 'data-overlay-title', 'Alt text');
  //   // Check if the image is loaded with the correct defaults and wrappers:
  //   $session->elementExists('css', 'img[src*="image-test.png"]');
  //   $session->elementExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
  //   $session->elementExists('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper > a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
  //   // Uploaded pictures are not broken during testing, but only on later
  //   // inspection. See https://www.drupal.org/project/drupal/issues/3272192.
  //   $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'width', '40');
  //   $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'height', '20');
  //   // @todo Check the photoswipe functionalities here.
  // }.

  /**
   * Tests the photoswipe formatter on node display with two media fields.
   */
  public function testTwoPhotoswipeFieldFormatterOnNodeDisplay() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->createMediaType('image', ['id' => 'image', 'new_revision' => TRUE]);
    $this->createEntityReferenceField('node', 'article', 'media_image', 'media_image', 'media', 'default', ['target_bundles' => ['image']]);
    $this->createEntityReferenceField('node', 'article', 'media_image_two', 'media_image_two', 'media', 'default', ['target_bundles' => ['image']]);
    $this->createMediaImage();

    // Create Display for media_image:
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', 'article')
      ->setComponent('media_image', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [],
      ])
      ->save();
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent('media_image', [
        'type' => 'photoswipe_field_formatter',
        'settings' => [],
      ])
      ->save();
    // Create Display for media_image_two:
    $display_repository->getFormDisplay('node', 'article')
      ->setComponent('media_image_two', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [],
      ])
      ->save();
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent('media_image_two', [
        'type' => 'photoswipe_field_formatter',
        'settings' => [],
      ])
      ->save();
    // Create the node with a test file uploaded:
    $this->drupalGet('node/add/article');
    $title = 'My test content';
    $page->fillField('title[0][value]', $title);
    $page->fillField('edit-media-image-0-target-id', 'image-test.png');
    $page->fillField('edit-media-image-two-0-target-id', 'image-test.png');
    $page->pressButton('edit-submit');
    $session->pageTextContains("Article {$title} has been created.");
    $this->drupalGet('/node/1');
    // Check if all necessary js and css files are loaded:
    $session->elementExists('css', 'link[href*="//cdnjs.cloudflare.com/ajax/libs/photoswipe/4.1.3/photoswipe.css"]');
    $session->elementExists('css', 'link[href*="//cdnjs.cloudflare.com/ajax/libs/photoswipe/4.1.3/default-skin/default-skin.css"]');
    $session->elementExists('css', 'script[src*="//cdnjs.cloudflare.com/ajax/libs/photoswipe/4.1.3/photoswipe.min.js"]');
    $session->elementExists('css', 'script[src*="//cdnjs.cloudflare.com/ajax/libs/photoswipe/4.1.3/photoswipe-ui-default.min.js"]');
    // Check if the fallback wrapper is loaded with the correct
    // classes and attributes:
    // Wait for the JavaScript to initialize the fallback wrapper:
    $session->waitForElement('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper');
    $session->elementExists('css', '.photoswipe-gallery');
    $session->elementExists('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper');
    $session->elementAttributeExists('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper', 'data-pswp-uid');
    // Check if the anker element is set with the correct classes, wrappers and
    // attributes:
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe');
    $session->elementExists('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper > a[href*="image-test.png"].photoswipe');
    $session->elementAttributeExists('css', 'a[href*="image-test.png"].photoswipe', 'data-size');
    $session->elementAttributeContains('css', 'a[href*="image-test.png"].photoswipe', 'data-overlay-title', 'Alt text');
    // Check if the image is loaded with the correct defaults and wrappers:
    $session->elementExists('css', 'img[src*="image-test.png"]');
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementExists('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper > a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    // Check that two of each element exist:
    $session->elementsCount('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper', 2);
    $session->elementsCount('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper > a[href*="image-test.png"].photoswipe', 2);
    $session->elementsCount('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper > a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]', 2);
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
    $page = $this->getSession()->getPage();

    $this->createMediaType('image', ['id' => 'image', 'new_revision' => TRUE]);
    $this->createEntityReferenceField('node', 'article', 'media_image', 'media_image', 'media', 'default', ['target_bundles' => ['image']], -1);
    $this->createMediaImage();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', 'article')
      ->setComponent('media_image', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [],
      ])
      ->save();
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent('media_image', [
        'type' => 'photoswipe_field_formatter',
        'settings' => [],
      ])
      ->save();
    // Create the node with a test file uploaded:
    $this->drupalGet('node/add/article');
    $title = 'My test content';
    $page->fillField('title[0][value]', $title);
    $page->fillField('edit-media-image-0-target-id', 'image-test.png');
    $page->pressButton('edit-media-image-add-more');
    $session->waitForElementVisible('css', 'input[name="media_image[1][target_id]"]');
    $page->fillField('media_image (value 2)', 'image-test.png');
    $page->pressButton('edit-submit');
    $this->drupalGet('/node/1');
    // Check if all necessary js and css files are loaded:
    $session->elementExists('css', 'link[href*="//cdnjs.cloudflare.com/ajax/libs/photoswipe/4.1.3/photoswipe.css"]');
    $session->elementExists('css', 'link[href*="//cdnjs.cloudflare.com/ajax/libs/photoswipe/4.1.3/default-skin/default-skin.css"]');
    $session->elementExists('css', 'script[src*="//cdnjs.cloudflare.com/ajax/libs/photoswipe/4.1.3/photoswipe.min.js"]');
    $session->elementExists('css', 'script[src*="//cdnjs.cloudflare.com/ajax/libs/photoswipe/4.1.3/photoswipe-ui-default.min.js"]');
    // Check if the fallback wrapper is loaded with the correct
    // classes and attributes:
    $session->elementExists('css', 'div.photoswipe-gallery');
    // Check, that there is no fallback wrapper:
    $session->elementNotExists('css', 'div.photoswipe-gallery--fallback-wrapper');
    // Check, that there is no fallback wrapper span:
    $session->elementNotExists('css', '.photoswipe-gallery--fallback-wrapper');
    $session->elementAttributeExists('css', 'div.photoswipe-gallery', 'data-pswp-uid');
    // Check if the anker element is set with the correct classes, wrappers and
    // attributes for the first picture:
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe');
    $session->elementExists('css', 'div.photoswipe-gallery div > a[href*="image-test.png"].photoswipe');

    $session->elementAttributeExists('css', 'a[href*="image-test.png"].photoswipe', 'data-size');
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
    $session->elementsCount('css', 'div.photoswipe-gallery div > a[href*="image-test.png"].photoswipe', 2);
    $session->elementsCount('css', 'div.photoswipe-gallery div > a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]', 2);
    // Uploaded pictures are not broken during testing, but only on later
    // inspection. See https://www.drupal.org/project/drupal/issues/3272192.
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'width', '40');
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'height', '20');
    // @todo Check the photoswipe functionalities here.
  }

}
