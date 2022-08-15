<?php

namespace Drupal\Tests\sharemessage\Functional;

use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests the Share Message extra field functionality through the admin UI.
 *
 * @group sharemessage
 */
class ShareMessageExtraFieldTest extends ShareMessageTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field_ui', 'node', 'views', 'taxonomy', 'token'];

  /**
   * Array containing term entities for this test.
   *
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected $terms = [];

  /**
   * Permissions for the admin user.
   *
   * @var array
   */
  protected $adminPermissions = [
    'access administration pages',
    'administer blocks',
    'administer sharemessages',
    'view sharemessages',
    'administer themes',
    'access content overview',
    'administer content types',
    'administer nodes',
    'access content',
    'bypass node access',
    'administer node display',
    'administer user display',
    'access user profiles',
    'administer taxonomy',
    'administer taxonomy_term display',
  ];

  /**
   * @inheritdoc
   */
  public function setUp() {
    parent::setUp();

    // Setup vocabulary and terms.
    Vocabulary::create([
      'vid' => 'tags',
      'name' => 'Tags',
    ])->save();
    $term = Term::create([
      'vid' => 'tags',
      'name' => 'term0',
    ]);
    $term->save();
    $this->terms[0] = $term;
    $term = Term::create([
      'vid' => 'tags',
      'name' => 'term1',
    ]);
    $term->save();
    $this->terms[1] = $term;
    // Extra field entity types are filtered based on whether they have view
    // displays. Explicitly save one for user and terms here.
    \Drupal::service('entity_display.repository')->getViewDisplay('user', 'user')->save();
    \Drupal::service('entity_display.repository')->getViewDisplay('taxonomy_term', 'tags')->save();
  }

  /**
   * Tests the Share Message extra field functionality with tokens.
   */
  public function testShareMessageExtraFieldToken() {
    // Create article and page content types and contents.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
    $article = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Article SM',
      'body' => [
        'value' => 'Article body text',
      ],
    ]);
    $page = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Page SM',
      'body' => [
        'value' => 'Page body text',
      ],
    ]);

    // Step 1: Create a Share Message in the UI.
    $this->drupalGet('admin/config/services/sharemessage/add');
    // Check the Share Message extra field is per default set to '- None -'.
    $this->assertOptionSelected('edit-entity-type', '');
    $this->assertSession()->pageTextContains('Browse available tokens.');
    $this->assertSession()->responseNotContains('<fieldset data-drupal-selector="edit-bundles"');
    // Use tokens in the Share Message title to display the node's title.
    $sharemessage = [
      'label' => 'Share Message Test Label',
      'id' => 'sharemessage_test_label',
      'title' => '[node:title]',
      'message_long' => 'Share Message Test Long Description',
      'message_short' => 'Share Message Test Short Description',
      'image_url' => 'http://www.example.com/drupal.jpg',
      'share_url' => 'http://www.example.com',
    ];
    $this->drupalPostForm(NULL, $sharemessage, t('Save'));
    $this->assertText(t('Share Message @label has been added.', ['@label' => $sharemessage['label']]), 'Share Message is successfully saved.');
    // Share Message settings for article and page.
    $sharemessage_article = [
      'title' => $article->getTitle(),
      'message_long' => $sharemessage['message_long'],
      'share_url' => $sharemessage['share_url'],
    ];
    $sharemessage_page = [
      'title' => $page->getTitle(),
      'message_long' => $sharemessage['message_long'],
      'share_url' => $sharemessage['share_url'],
    ];
    // Check that in the front page the nodes have no extra fields yet.
    $this->setEntityRawContent('node', $article);
    $this->assertNoShareButtons($sharemessage_article);
    $this->setEntityRawContent('node', $page);
    $this->assertNoShareButtons($sharemessage_page);

    // Step 2: Select 'node' entity type. All content types are displayed.
    $this->drupalGet('admin/config/services/sharemessage/manage/sharemessage_test_label');
    $this->assertOptionSelected('edit-entity-type', '');
    $this->assertSession()->responseNotContains('<fieldset data-drupal-selector="edit-bundles"');
    $this->drupalPostForm(NULL, ['entity_type' => 'node'], 'Save');
    $this->drupalGet('admin/config/services/sharemessage/manage/sharemessage_test_label');
    $this->assertSession()->responseContains('<span class="fieldset-legend">Content type</span>');
    $this->assertSession()->checkboxNotChecked('bundles[article]');
    $this->assertSession()->checkboxNotChecked('bundles[page]');
    // Select no bundle to allow all content types.
    $this->drupalPostForm(NULL, [], t('Save'));
    // Enable the extra fields in the article and page 'Manage display' pages.
    $extra_field = [
      'fields[sharemessage__sharemessage_test_label][region]' => 'content',
      'fields[sharemessage__sharemessage_test_label][weight]' => 105,
    ];
    $this->drupalGet('admin/structure/types/manage/article/display/teaser');
    $this->drupalPostForm(NULL, $extra_field, 'Save');
    $this->drupalGet('admin/structure/types/manage/page/display/teaser');
    $this->drupalPostForm(NULL, $extra_field, 'Save');
    // Check that in the front page the nodes have the extra fields now.
    $this->setEntityRawContent('node', $article);
    $this->assertShareButtons($sharemessage_article, 'addthis_16x16_style', TRUE);
    $this->setEntityRawContent('node', $page);
    $this->assertShareButtons($sharemessage_page, 'addthis_16x16_style', TRUE);

    // Step 3: Select no entity type to disable the Share Message extra field.
    $this->drupalGet('admin/config/services/sharemessage/manage/sharemessage_test_label');
    $this->assertOptionSelected('edit-entity-type', 'node');
    $this->assertSession()->checkboxNotChecked('bundles[article]');
    $this->assertSession()->checkboxNotChecked('bundles[page]');
    $this->drupalPostForm(NULL, ['entity_type' => ''], 'Save');
    $this->drupalGet('admin/config/services/sharemessage/manage/sharemessage_test_label');
    $this->assertSession()->responseNotContains('<fieldset data-drupal-selector="edit-bundles"');
    $this->drupalPostForm(NULL, [], 'Save');
    // Check the extra fields are not shown anymore for any content types.
    $this->drupalGet('admin/structure/types/manage/article/display/teaser');
    $this->assertNoFieldByName('fields[sharemessage__sharemessage_test_label][type]');
    $this->drupalGet('admin/structure/types/manage/page/display/teaser');
    $this->assertNoFieldByName('fields[sharemessage__sharemessage_test_label][type]');
    // Check in the front page, the nodes don't have the extra fields anymore.
    $this->setEntityRawContent('node', $article);
    $this->assertNoShareButtons($sharemessage_article, 'addthis_16x16_style', TRUE);
    $this->setEntityRawContent('node', $article);
    $this->assertNoShareButtons($sharemessage_page, 'addthis_16x16_style', TRUE);

    // Check that the extra field has not been enabled for any bundles.
    $this->drupalGet('admin/config/services/sharemessage/manage/sharemessage_test_label');
    $this->assertOptionSelected('edit-entity-type', '');

    // Step 4: Select 'node' entity type, select just the article bundle.
    $this->drupalPostForm(NULL, ['entity_type' => 'node'], 'Save');
    $this->drupalGet('admin/config/services/sharemessage/manage/sharemessage_test_label');
    $this->assertSession()->checkboxNotChecked('bundles[article]');
    $this->assertSession()->checkboxNotChecked('bundles[page]');
    $this->drupalPostForm(NULL, ['bundles[article]' => 1], t('Save'));
    // Check that Share Message extra field is displayed only for article.
    $this->drupalGet('admin/structure/types/manage/article/display/teaser');
    $this->drupalPostForm(NULL, $extra_field, 'Save');
    $this->drupalGet('admin/structure/types/manage/page/display/teaser');
    $this->assertNoFieldByName('fields[sharemessage__sharemessage_test_label][type]');
    // Check that in the front page, only article node has the extra field now.
    $this->setEntityRawContent('node', $article);
    $this->assertShareButtons($sharemessage_article, 'addthis_16x16_style', TRUE);
    $this->setEntityRawContent('node', $page);
    $this->assertNoShareButtons($sharemessage_page, 'addthis_16x16_style', TRUE);
    // Share Message settings for user.
    $sharemessage_user = [
      'title' => $this->adminUser->getAccountName() . ', ' . $this->adminUser->id(),
      'message_long' => $sharemessage['message_long'],
      'share_url' => $sharemessage['share_url'],
    ];
    // Check in the admin page, the Share Message extra field is not shown yet.
    $this->renderedEntity = NULL;
    $this->drupalGet('user/' . $this->adminUser->id());
    $this->assertNoShareButtons($sharemessage_user);

    // Step 5: Select 'user' entity type. No bundles should be displayed.
    $this->drupalGet('admin/config/services/sharemessage/manage/sharemessage_test_label');
    $this->assertOptionSelected('edit-entity-type', 'node');
    $this->assertSession()->checkboxChecked('bundles[article]');
    $this->assertSession()->checkboxNotChecked('bundles[page]');
    $this->drupalPostForm(NULL, ['entity_type' => 'user'], 'Save');
    $this->drupalGet('admin/config/services/sharemessage/manage/sharemessage_test_label');
    $this->assertSession()->responseNotContains('<fieldset data-drupal-selector="edit-bundles"');
    // Use tokens in the Share Message title to display the user's name and ID.
    $this->drupalPostForm(NULL, ['title' => '[user:name], [user:uid]'], t('Save'));
    // Check the extra fields are not shown anymore for any content types.
    $this->drupalGet('admin/structure/types/manage/article/display/teaser');
    $this->assertNoFieldByName('fields[sharemessage__sharemessage_test_label][type]');
    $this->drupalGet('admin/structure/types/manage/page/display/teaser');
    $this->assertNoFieldByName('fields[sharemessage__sharemessage_test_label][type]');
    // Enable the extra field in the accounts 'Manage display' page.
    $this->drupalGet('admin/config/people/accounts/display');
    $this->drupalPostForm(NULL, ['display_modes_custom[full]' => TRUE], t('Save'));
    $this->drupalGet('admin/config/people/accounts/display/full');
    $this->drupalPostForm(NULL, $extra_field, 'Save');
    // Check in the front page, the nodes don't have the extra fields anymore.
    $this->setEntityRawContent('node', $article);
    $this->assertNoShareButtons($sharemessage_article, 'addthis_16x16_style', TRUE);
    $this->setEntityRawContent('node', $page);
    $this->assertNoShareButtons($sharemessage_page, 'addthis_16x16_style', TRUE);
    // Check in the admin page, the Share Message extra field is shown now.
    $this->renderedEntity = NULL;
    $this->drupalGet('user/' . $this->adminUser->id());
    $this->assertShareButtons($sharemessage_user, 'addthis_16x16_style', TRUE);
    // Share Message settings for taxonomy_term.
    $vocabulary = Vocabulary::load($this->terms[0]->bundle());
    $term = Term::load($this->terms[0]->id());
    $sharemessage_taxonomy = [
      'title' => $vocabulary->label() . ', ' . $term->label(),
      'message_long' => $sharemessage['message_long'],
      'share_url' => $sharemessage['share_url'],
    ];
    // Check in a term page, the Share Message extra field is not shown yet.
    $this->drupalGet('taxonomy/term/' . $term->id());
    $this->assertNoShareButtons($sharemessage_taxonomy);

    // Step 6: Select 'taxonomy_term' entity type. 'Tags' bundle is displayed.
    $this->drupalGet('admin/config/services/sharemessage/manage/sharemessage_test_label');
    $this->assertOptionSelected('edit-entity-type', 'user');
    $this->drupalPostForm(NULL, ['entity_type' => 'taxonomy_term'], 'Save');
    $this->drupalGet('admin/config/services/sharemessage/manage/sharemessage_test_label');
    $this->assertSession()->checkboxNotChecked('bundles[tags]');
    // Use tokens in the Share Message title to display the term's name.
    $this->drupalPostForm(NULL, ['title' => '[term:vocabulary:name], [term:name]'], t('Save'));
    // Check the extra fields are not shown for any content types and user.
    $this->drupalGet('admin/structure/types/manage/article/display/teaser');
    $this->assertNoFieldByName('fields[sharemessage__sharemessage_test_label][type]');
    $this->drupalGet('admin/structure/types/manage/page/display/teaser');
    $this->assertNoFieldByName('fields[sharemessage__sharemessage_test_label][type]');
    $this->drupalGet('admin/config/people/accounts/display/full');
    $this->assertNoFieldByName('fields[sharemessage__sharemessage_test_label][type]');
    // Enable the extra field in the taxonomy terms 'Manage display' page.
    $this->drupalGet('admin/structure/taxonomy/manage/tags/overview/display');
    $this->drupalPostForm(NULL, ['display_modes_custom[full]' => TRUE], t('Save'));
    $this->drupalGet('admin/structure/taxonomy/manage/tags/overview/display/full');
    $this->drupalPostForm(NULL, $extra_field, 'Save');
    // Check in the front page, the nodes don't have the extra fields anymore.
    $this->setEntityRawContent('node', $article);
    $this->assertNoShareButtons($sharemessage_article, 'addthis_16x16_style', TRUE);
    $this->setEntityRawContent('node', $page);
    $this->assertNoShareButtons($sharemessage_page, 'addthis_16x16_style', TRUE);
    $this->renderedEntity = NULL;
    // Check in the admin page, the Share Message extra field is not shown.
    $this->drupalGet('user/' . $this->adminUser->id());
    $this->assertNoShareButtons($sharemessage_user, 'addthis_16x16_style', TRUE);
    // Check in a term page, the Share Message extra field is shown now.
    $this->drupalGet('taxonomy/term/' . $term->id());
    $this->assertShareButtons($sharemessage_taxonomy, 'addthis_16x16_style', TRUE);
    $this->renderedEntity = NULL;

    // Test for special characters (such as ', ", <, >, &) in a node title
    // used as token for a Share Message title.
    $article_special_char = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test with special characters \' " < > & ',
      'body' => [
        'value' => 'Article body text',
      ],
    ]);
    // Use tokens to show special chars in the Share Message title.
    $sharemessage = [
      'label' => 'Special characters test ',
      'id' => 'sharemessage_test_special_characters',
      'title' => '[node:title]',
      'message_long' => 'Long description',
      'message_short' => 'Short description',
    ];
    $this->drupalPostForm('admin/config/services/sharemessage/add', $sharemessage, t('Save'));
    $this->drupalPostForm('admin/config/services/sharemessage/manage/sharemessage_test_special_characters', [
      'entity_type' => 'node',
    ], 'Save');
    // Enable the extra field in the article 'Manage display page'.
    $extra_field = [
      'fields[sharemessage__sharemessage_test_special_characters][region]' => 'content',
      'fields[sharemessage__sharemessage_test_special_characters][weight]' => 105,
    ];
    $this->drupalPostForm('admin/structure/types/manage/article/display/default', $extra_field, 'Save');
    // Check that the Share Message title is properly encoded.
    $sharemessage_article = [
      'title' => 'Test with special characters &#039; &quot; &lt; &gt; &amp; ',
      'message_long' => $sharemessage['message_long'],
    ];
    $this->drupalGet('node/' . $article_special_char->id());
    $this->assertShareButtons($sharemessage_article, 'addthis_16x16_style', TRUE);
  }

}
