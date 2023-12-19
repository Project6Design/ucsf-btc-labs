<?php

namespace Drupal\Tests\sharemessage\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;

/**
 * Check if Share Message is exposed as block.
 *
 * @group sharemessage
 */
class ShareMessageExposeToBlockTest extends ShareMessageTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['contextual', 'node', 'token', 'field_ui', 'image'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->adminPermissions[] = 'access contextual links';
    $this->adminPermissions[] = 'administer nodes';
    $this->adminPermissions[] = 'access content';
    $this->adminPermissions[] = 'bypass node access';
    $this->adminPermissions[] = 'view all revisions';
    $this->adminPermissions[] = 'administer node display';
    parent::setUp();
  }

  /**
   * Test case that check if Share Message is exposed as block.
   */
  public function testShareMessageExposeToBlock() {
    // First enable the bartik theme to place the Share Message block afterwards.
    $theme = 'bartik';
    \Drupal::service('theme_installer')->install([$theme]);
    $this->config('system.theme')->set('default', $theme)->save();

    // Create an article content type and an article node.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'image_test',
      'type' => 'image',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'image_test',
      'bundle' => 'article',
      'settings' => [
        'file_extensions' => 'jpg png',
      ],
    ])->save();

    $files[] = $this->getTestFiles('image');
    $uri = 'public://file.png';
    \Drupal::service('file_system')->copy($files[0][0]->uri, $uri);
    $file = File::create(['uri' => $uri]);
    $file->save();

    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Share Message article',
      'body' => [
        'value' => 'Test context to show block on routes with node parameter.',
      ],
      'image_test' => $file,
    ]);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->titleEquals('Share Message article | Drupal');
    $this->assertSession()->responseNotContains('<h2>Share Message test block</h2>');

    // Create another Share Message.
    $sharemessage = [
      'label' => 'Share Message Test Label',
      'id' => 'sharemessage_test_label',
      'message_short' => 'AddThis sharemessage short description.',
      'message_long' => '[node:title]',
      'image_url' => '[node:image_test:entity:url]',
      'image_width' => '[node:image_test:width]',
      'image_height' => '[node:image_test:height]',
    ];
    $this->drupalGet('admin/config/services/sharemessage/add');
    $this->submitForm($sharemessage, t('Save'));
    // Check for confirmation message and listing of the Share Message entity.
    $this->assertSession()->pageTextContains(t('Share Message @label has been added.', ['@label' => $sharemessage['label']]));
    $this->assertSession()->pageTextContains($sharemessage['label']);

    // Enable twitter and tweet services for AddThis.
    $this->drupalGet('admin/config/services/sharemessage/addthis-settings');
    $edit = ['default_services[]' => ['twitter', 'tweet']];
    $this->submitForm($edit, 'Save configuration');

    // Add a block that will contain the created Share Message.
    $block = [
      'settings[label]' => 'Share Message test block',
      'settings[sharemessage]' => $sharemessage['id'],
      'region' => 'content',
    ];
    $this->drupalGet('admin/structure/block/add/sharemessage_block/' . $theme);
    $this->submitForm($block, t('Save block'));

    // Check the Share Message block is now displayed on the article node.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->titleEquals('Share Message article | Drupal');
    $this->assertSession()->responseContains('<h2>Share Message test block</h2>');
    $sharemessage_values = $sharemessage;
    $sharemessage_values['message_long'] = 'Share Message article';
    $this->assertShareButtons($sharemessage_values, 'addthis_16x16_style', TRUE);

    $image = \Drupal::service('image.factory')->get($file->getFileUri());
    $image_width = $image->getWidth();
    $image_height = $image->getHeight();
    $this->assertOGTags('og:image', $file->createFileUrl(FALSE));
    $this->assertOGTags('og:image:width', $image_width);
    $this->assertOGTags('og:image:height', $image_height);

    // Edit, create new review and check the Share Message block.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $edit = [
      'revision' => TRUE,
      'title[0][value]' => 'Share Message article edit',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->titleEquals('Share Message article edit | Drupal');
    $this->assertSession()->responseContains('<h2>Share Message test block</h2>');
    $sharemessage_values['message_long'] = 'Share Message article edit';
    $this->assertShareButtons($sharemessage_values, 'addthis_16x16_style', TRUE);
    // Visit the old article node's revision and check the Share message block.
    $this->drupalGet('node/' . $node->id() . '/revisions/' . $node->getRevisionId() . '/view');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->titleEquals('Share Message article | Drupal');
    $this->assertSession()->responseContains('<h2>Share Message test block</h2>');
    $sharemessage_values['message_long'] = '';
    $this->assertShareButtons($sharemessage_values, 'addthis_16x16_style', TRUE);

    // Verify that the block is in the submitted region of the bartik theme.
    $this->drupalGet('admin/structure/block/list/' . $theme);
    $this->assertSession()->pageTextContains($block['settings[label]']);

    // Go to front page and check whether Share Message is displayed.
    $this->drupalGet('');
    $this->assertShareButtons($sharemessage_values, 'addthis_16x16_style', TRUE);

    // Check the twitter template placeholder.
    $twitter_template = 'var addthis_share = { templates: { twitter: "AddThis sharemessage short description." } }';
    $this->assertSession()->responseContains($twitter_template);

    // Check for the contextual links presence.
    $sharemessage_contextual_id = 'data-contextual-id="block:block=sharemessage:langcode=en|sharemessage:sharemessage=sharemessage_test_label:langcode=en"';
    $this->assertSession()->responseContains($sharemessage_contextual_id);

    // Logout the user and check for the Share Message block.
    $this->drupalLogout();
    $this->drupalGet('filter/tips');
    $this->assertShareButtons($sharemessage_values, 'addthis_16x16_style', TRUE);

    // Create an authenticated user.
    $permissions = [];
    $admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($admin_user);
    $this->drupalGet('');
    $this->assertShareButtons($sharemessage_values, 'addthis_16x16_style', TRUE);

    // A normal user must not see contextual links.
    $this->assertSession()->responseNotContains($sharemessage_contextual_id);
  }

}
