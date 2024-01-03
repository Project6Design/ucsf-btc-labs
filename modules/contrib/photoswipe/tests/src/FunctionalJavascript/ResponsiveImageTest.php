<?php

namespace Drupal\Tests\photoswipe\FunctionalJavascript;

use Drupal\file\Entity\File;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the photoswipe responsive images.
 *
 * @group photoswipe
 */
class ResponsiveImageTest extends WebDriverTestBase {
  use TestFileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'photoswipe_responsive_image_setup',
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

    $this->adminUser = $this->drupalCreateUser();
    $this->adminUser->addRole($this->createAdminRole('admin', 'admin'));
    $this->adminUser->save();
    $this->drupalLogin($this->adminUser);
  }

  protected function setupNodeDisplayingImage() {
    $images = $this->getTestFiles('image');
    $medias = [];
    for ($i = 0; $i < 3; $i++) {
      $file = File::create([
        'uri' => $images[$i]->uri,
      ]);
      $file->save();

      $media = Media::create([
        'bundle' => 'image',
        'name' => 'Test Image Media',
        'field_media_image' => [
          [
            'target_id' => $file->id(),
            'alt' => "count-$i",
            'title' => 'default title',
          ],
        ],
      ]);
      $media->save();
      $medias[] = $media;
    }

    $node = Node::create([
      'title' => 'Test',
      'type' => 'node',
      'field_responsive_image' => $medias,
    ]);
    $node->save();

  }

  /**
   * Tests the responsive image formatter's node_style "hide" option.
   */
  public function testPhotoswipeResponsiveHideOption() {
    $session = $this->assertSession();
    $this->container->get('config.factory')
      ->getEditable('core.entity_view_display.node.node.default')
      ->set('content.field_responsive_image.settings.photoswipe_node_style', 'hide')
      ->set('content.field_responsive_image.settings.photoswipe_node_style_first', 'wide')
      ->save();

    $this->setupNodeDisplayingImage();

    $this->drupalGet('/node/1');
    $session->waitForElement('css', '.photoswipe-gallery');
    $session->elementExists('css', '.photoswipe-gallery a img[alt="count-0"]');
    $session->elementNotExists('css', '.photoswipe-gallery a img[alt="count-1"]');
    $session->elementNotExists('css', '.photoswipe-gallery a img[alt="count-2"]');

    $this->getSession()->getPage()->find('css', '.photoswipe-gallery a.photoswipe:not(.hidden)')->click();
    $session->waitForElementVisible('css', '.pswp');
    $session->elementsCount('css', '.pswp .pswp__container .pswp__item', 3);
    $session->elementTextEquals('css', '.pswp .pswp__scroll-wrap .pswp__counter', '1 / 3');
  }

  /**
   * Tests the responsive image formatter's node_style_first "hide" option.
   */
  public function testPhotoswipeResponsiveHideFirstOption() {
    $session = $this->assertSession();
    $this->container->get('config.factory')
      ->getEditable('core.entity_view_display.node.node.default')
      ->set('content.field_responsive_image.settings.photoswipe_node_style', 'wide')
      ->set('content.field_responsive_image.settings.photoswipe_node_style_first', 'hide')
      ->save();

    $this->setupNodeDisplayingImage();

    $this->drupalGet('/node/1');
    $session->waitForElement('css', '.photoswipe-gallery');
    $session->elementNotExists('css', '.photoswipe-gallery a img[alt="count-0"]');
    $session->elementExists('css', '.photoswipe-gallery a img[alt="count-1"]');
    $session->elementExists('css', '.photoswipe-gallery a img[alt="count-2"]');

    $this->getSession()->getPage()->find('css', '.photoswipe-gallery a.photoswipe:not(.hidden)')->click();
    $session->waitForElementVisible('css', '.pswp');
    $session->elementsCount('css', '.pswp .pswp__container .pswp__item', 3);
    $session->elementTextEquals('css', '.pswp .pswp__scroll-wrap .pswp__counter', '2 / 3');
  }

}
