<?php

namespace Drupal\Tests\photoswipe\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the photoswipe display setting on an image file.
 *
 * @group photoswipe
 */
class ImageFileTest extends WebDriverTestBase {
  use TestFileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'test_page_test',
    'file',
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
   * Create a new image field.
   *
   * Create a new image field.
   * Modified Version of Drupal\Tests\image\Kernel\ImageFieldCreationTrait
   * "createImageField" function.
   *
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $entity_type_id
   *   The entity type that this field will be added to.
   * @param string $bundle_id
   *   The entity type bundle that this field will be added to.
   * @param array $storage_settings
   *   (optional) A list of field storage settings that will be added to the
   *   defaults.
   * @param array $field_settings
   *   (optional) A list of instance settings that will be added to the instance
   *   defaults.
   * @param array $widget_settings
   *   (optional) Widget settings to be added to the widget defaults.
   * @param array $formatter_settings
   *   (optional) Formatter settings to be added to the formatter defaults.
   * @param string $formatter_type
   *   (optional) The formatter type, defaults to 'photoswipe_field_formatter'.
   * @param string $description
   *   (optional) A description for the field. Defaults to ''.
   */
  protected function createImageField($name, $entity_type_id = 'node', $bundle_id = 'article', array $storage_settings = [], array $field_settings = [], array $widget_settings = [], array $formatter_settings = [], $formatter_type = 'photoswipe_field_formatter', $description = '') {
    FieldStorageConfig::create([
      'field_name' => $name,
      'entity_type' => $entity_type_id,
      'type' => 'image',
      'settings' => $storage_settings,
      'cardinality' => !empty($storage_settings['cardinality']) ? $storage_settings['cardinality'] : 1,
    ])->save();

    $field_config = FieldConfig::create([
      'field_name' => $name,
      'label' => $name,
      'entity_type' => $entity_type_id,
      'bundle' => $bundle_id,
      'required' => !empty($field_settings['required']),
      'settings' => $field_settings,
      'description' => $description,
    ]);
    $field_config->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay($entity_type_id, $bundle_id)
      ->setComponent($name, [
        'type' => 'image_image',
        'settings' => $widget_settings,
      ])
      ->save();

    $display_repository->getViewDisplay($entity_type_id, $bundle_id)
      ->setComponent($name, [
        'type' => $formatter_type,
        'settings' => $formatter_settings,
      ])
      ->save();

    return $field_config;
  }

  // /**
  //  * Tests if the Photoswipe field formatter settings exist.
  //  */
  // public function testPhotoswipeFieldFormatterSettingsExist() {
  //   $session = $this->assertSession();
  //   $page = $this->getSession()->getPage();
  // $field_settings = ['alt_field_required' => 0];
  //   $this->createImageField('field_test', 'node', 'article', [
  //     'uri_scheme' => 'public',
  //     'required' => 'true',
  //   ], $field_settings);
  //   // Go to manage display page.
  //   $this->drupalGet('admin/structure/types/manage/article/display');
  //   $session->pageTextContains('Photoswipe');
  //   // Check if the photoswipe field formatter is selected:
  //   $session->elementAttributeContains('css', '#edit-fields-field-test-type > option[value="photoswipe_field_formatter"]', 'selected', 'selected');
  //   // Check if all formatter settings exist, and have the correct default
  //   // selected setting:
  //   $page->pressButton('edit-fields-field-test-settings-edit');
  //   $session->waitForElementVisible('css', 'select[id*=edit-fields-field-test-settings-edit-form-settings-photoswipe-node-style-first]');
  //   $session->elementExists('css', 'select[id*="edit-fields-field-test-settings-edit-form-settings-photoswipe-node-style-first"]');
  //   $session->elementExists('css', 'select[id*="edit-fields-field-test-settings-edit-form-settings-photoswipe-node-style"]:not([id*=first])');
  //   $session->elementExists('css', 'select[id*="edit-fields-field-test-settings-edit-form-settings-photoswipe-image-style"]');
  //   $session->elementTextEquals('css', 'select[id*="edit-fields-field-test-settings-edit-form-settings-photoswipe-node-style-first"] > option[selected="selected"]', 'No special style.');
  //   $session->elementTextEquals('css', 'select[id*="edit-fields-field-test-settings-edit-form-settings-photoswipe-node-style"]:not([id*=first]) > option[selected="selected"]', 'None (original image)');
  //   $session->elementTextEquals('css', 'select[id*="edit-fields-field-test-settings-edit-form-settings-photoswipe-image-style"] > option[selected="selected"]', 'None (original image)');
  //   // @todo Why does the following select not have a selected field?:
  //   // $session->elementTextEquals('css', 'select[id*=edit-fields-field-test-settings-edit-form-settings-photoswipe-caption] > option[selected="selected"]', 'Image title tag');
  //   // Check if changing a setting and submitting the display,
  //   // won't break anything:
  //   $page->selectFieldOption('fields[field_test][settings_edit_form][settings][photoswipe_node_style_first]', 'large');
  //   $page->pressButton('Update');
  //   $page->pressButton('edit-submit');
  //   $session->pageTextContains('Your settings have been saved.');
  // }

  /**
   * Tests the photoswipe formatter on node display.
   */
  public function testPhotoswipeFieldFormatterOnNodeDisplay() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $field_settings = ['alt_field_required' => 1];
    $this->createImageField('field_test', 'node', 'article', [
      'uri_scheme' => 'public',
      'required' => 'true',
    ], $field_settings);
    // Create the node with a test file uploaded:
    $this->drupalGet('node/add/article');
    $title = 'My test content';
    $page->fillField('title[0][value]', $title);
    $this->assertNotEmpty($image_upload_field = $page->find('css', '#edit-field-test-0-upload'));
    $image = $this->getTestFiles('image')[0];
    $image_upload_field->attachFile($this->container->get('file_system')->realpath($image->uri));
    $session->waitForElementVisible('css', '.image-preview');
    $session->pageTextContains('Alternative text');
    $page->fillField('Alternative text', 'Alt text');
    $page->pressButton('edit-submit');
    $session->pageTextContains("Article {$title} has been created.");
    $this->drupalGet('/node/1');
    // $this->getSession()->wait(5000, "document.readyState === 'complete'");
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
    // Uploaded pictures are not broken during testing, but only on later
    // inspection. See https://www.drupal.org/project/drupal/issues/3272192.
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'width', '40');
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'height', '20');
    // @todo Check the photoswipe functionalities here.
    $this->createScreenshot(\Drupal::root() . '/sites/default/files/simpletest/ImageFileTest-testPhotoswipeFieldFormatterOnNodeDisplay-beforeClick.png');
    // Open the photoswipe layer.
    $this->getSession()->getPage()->find('css', 'a[href*="image-test.png"].photoswipe')->click();
    $session->waitForElementVisible('css', '.pswp');
    $this->createScreenshot(\Drupal::root() . '/sites/default/files/simpletest/ImageFileTest-testPhotoswipeFieldFormatterOnNodeDisplay-afterClick.png');
  }

  /**
   * Tests the photoswipe formatter on node preview.
   */
  // Public function testPhotoswipeFieldFormatterOnNodePreview() {
  //   $session = $this->assertSession();
  //   $page = $this->getSession()->getPage();
  // $field_settings = ['alt_field_required' => 1];
  //   $this->createImageField('field_test', 'node', 'article', [
  //     'uri_scheme' => 'public',
  //     'required' => 'true',
  //   ], $field_settings);
  //   // Create the node with a test file uploaded:
  //   $this->drupalGet('node/add/article');
  //   $title = 'My test content';
  //   $page->fillField('title[0][value]', $title);
  //   $this->assertNotEmpty($image_upload_field = $page->find('css', '#edit-field-test-0-upload'));
  //   $image = $this->getTestFiles('image')[0];
  //   $image_upload_field->attachFile($this->container->get('file_system')->realpath($image->uri));
  //   $session->waitForElementVisible('css', '.image-preview');
  //   $session->pageTextContains('Alternative text');
  //   $page->fillField('Alternative text', 'Alt text');
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
   * Tests the photoswipe formatter on node display with two image fields.
   */
  public function testTwoPhotoswipeFieldFormatterOnNodeDisplay() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $field_settings = ['alt_field_required' => 0];
    $this->createImageField('field_test', 'node', 'article', [
      'uri_scheme' => 'public',
      'required' => 'true',
    ], $field_settings);
    $this->createImageField('field_test2', 'node', 'article', [
      'uri_scheme' => 'public',
      'required' => 'true',
    ], $field_settings);
    // Create the node with a test file uploaded:
    $this->drupalGet('node/add/article');
    $title = 'My test content';
    $page->fillField('title[0][value]', $title);
    $this->assertNotEmpty($image_upload_field = $page->find('css', '#edit-field-test-0-upload'));
    $this->assertNotEmpty($image_upload_field_2 = $page->find('css', '#edit-field-test2-0-upload'));
    $image = $this->getTestFiles('image')[0];
    $image_realpath = $this->container->get('file_system')->realpath($image->uri);
    $image_upload_field->attachFile($image_realpath);
    // Wait 2 seconds between attaching files, this is necessary, because
    // otherwise, the second file will not get attached, see
    // https://www.drupal.org/project/drupal/issues/3272424
    $page->waitFor(2, function () {
      return FALSE;
    });
    $image_upload_field_2->attachFile($image_realpath);
    $this->assertNotEmpty($session->waitForElementVisible('css', 'input[id*="edit-field-test-0-alt"]'));
    $this->assertNotEmpty($session->waitForElementVisible('css', 'input[id*="edit-field-test2-0-alt"]'));
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
    // Check if the image is loaded with the correct defaults and wrappers:
    $session->elementExists('css', 'img[src*="image-test.png"]');
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementExists('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper > a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'width', '40');
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'height', '20');
    // Check if the anker element is set with the correct classes, wrappers and
    // attributes for the second picture:
    $session->elementExists('css', 'a[href*="image-test_0.png"].photoswipe');
    $session->elementExists('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper > a[href*="image-test_0.png"].photoswipe');
    $session->elementAttributeExists('css', 'a[href*="image-test_0.png"].photoswipe', 'data-size');
    // Check if the image is loaded with the correct defaults and wrappers for
    // the second picture:
    $session->elementExists('css', 'img[src*="image-test_0.png"]');
    $session->elementExists('css', 'a[href*="image-test_0.png"].photoswipe > img[src*="image-test_0.png"]');
    $session->elementExists('css', '.photoswipe-gallery.photoswipe-gallery--fallback-wrapper > a[href*="image-test_0.png"].photoswipe > img[src*="image-test_0.png"]');
    // Uploaded pictures are not broken during testing, but only on later
    // inspection. See https://www.drupal.org/project/drupal/issues/3272192.
    $session->elementAttributeContains('css', 'img[src*="image-test_0.png"]', 'width', '40');
    $session->elementAttributeContains('css', 'img[src*="image-test_0.png"]', 'height', '20');
    // @todo Check the photoswipe functionalities here.
  }

  /**
   * Tests upload of multiple images on one field.
   */
  public function testMultipleImagesOnFieldWithPhotoswipeFieldFormatter() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $field_settings = ['alt_field_required' => 0];
    $storage_settings = [
      'cardinality' => -1,
      'uri_scheme' => 'public',
      'required' => 'true',
    ];
    $this->createImageField('field_test', 'node', 'article', $storage_settings, $field_settings);
    // Create the node with a test file uploaded:
    $this->drupalGet('node/add/article');
    $title = 'My test content';
    $page->fillField('title[0][value]', $title);
    $this->assertNotEmpty($image_upload_field = $page->find('css', '#edit-field-test-0-upload'));
    $image = $this->getTestFiles('image')[0];
    $image_realpath = $this->container->get('file_system')->realpath($image->uri);
    $image_upload_field->attachFile($image_realpath);
    $session->waitForElementVisible('css', 'input[id*="edit-field-test-1-upload"]');
    $this->assertNotEmpty($image_upload_field2 = $page->find('css', 'input[id*="edit-field-test-1-upload"]'));
    $image_upload_field2->attachFile($image_realpath);
    $session->waitForElementVisible('css', 'input[id*="edit-field-field-test-1-alt"]');
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
    $session->elementExists('css', '.photoswipe-gallery');
    // Check, that there is no fallback wrapper:
    $session->elementNotExists('css', 'div.photoswipe-gallery--fallback-wrapper');
    // Check, that there is no fallback wrapper span:
    $session->elementNotExists('css', '.photoswipe-gallery--fallback-wrapper');
    $session->elementAttributeExists('css', 'div.photoswipe-gallery', 'data-pswp-uid');
    // Check if the anker element is set with the correct classes, wrappers and
    // attributes for the first picture:
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe');
    $session->elementExists('css', '.photoswipe-gallery div > a[href*="image-test.png"].photoswipe');

    $session->elementAttributeExists('css', 'a[href*="image-test.png"].photoswipe', 'data-size');
    // Check if the image is loaded with the correct defaults and wrappers for
    // the first picture:
    $session->elementExists('css', 'img[src*="image-test.png"]');
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementExists('css', '.photoswipe-gallery div > a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'width', '40');
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'height', '20');
    // Check if the anker element is set with the correct classes, wrappers and
    // attributes for the second picture:
    $session->elementExists('css', 'a[href*="image-test_0.png"].photoswipe');
    $session->elementExists('css', '.photoswipe-gallery div> a[href*="image-test_0.png"].photoswipe');
    $session->elementAttributeExists('css', 'a[href*="image-test_0.png"].photoswipe', 'data-size');
    // Check if the image is loaded with the correct defaults and wrappers for
    // the second picture:
    $session->elementExists('css', 'img[src*="image-test_0.png"]');
    $session->elementExists('css', 'a[href*="image-test_0.png"].photoswipe > img[src*="image-test_0.png"]');
    $session->elementExists('css', '.photoswipe-gallery div> a[href*="image-test_0.png"].photoswipe > img[src*="image-test_0.png"]');
    // Uploaded pictures are not broken during testing, but only on later
    // inspection. See https://www.drupal.org/project/drupal/issues/3272192.
    $session->elementAttributeContains('css', 'img[src*="image-test_0.png"]', 'width', '40');
    $session->elementAttributeContains('css', 'img[src*="image-test_0.png"]', 'height', '20');
    // @todo Check the photoswipe functionalities here.
  }

  /**
   * Tests the responsive photoswipe formatter on node display.
   *
   * @todo Implement this!
   */
  public function todoTestResponsivePhotoswipeFieldFormatterOnNodeDisplay() {
  }

}
