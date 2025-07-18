<?php

namespace Drupal\Tests\sharemessage\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

abstract class ShareMessageTestBase extends BrowserTestBase {

  use TestFileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['sharemessage', 'sharemessage_test', 'block', 'filter', 'file'];

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
    'administer themes'
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The rendered entity.
   *
   * @var string
   */
  protected $renderedEntity;

  /**
   * An authenticated user to use for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  public function setUp(): void {
    parent::setUp();

    // Create an admin user.
    $this->adminUser = $this->drupalCreateUser($this->adminPermissions);
    $this->drupalLogin($this->adminUser);

    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Prepares the renderer array for a specific context, sets its raw content.
   *
   * @param string $entity_type
   *   The entity type for this view builder.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to render.
   * @param string $view_mode
   *   (optional) The view mode that should be used to render the entity.
   *   Defaults to 'teaser'.
   */
  protected function setEntityRawContent($entity_type, $entity, $view_mode = 'teaser') {
    // The structured array describing the data to be rendered.
    $elements = \Drupal::entityTypeManager()
      ->getViewBuilder($entity_type)
      ->view($entity, $view_mode);

    $content = \Drupal::service('renderer')->renderRoot($elements);
    $this->renderedEntity = (string) $content;
  }

  /**
   * Passes if the markup of the share links wrapper IS found on the loaded page.
   *
   * @param string $sharemessage
   *   The edit array of a ShareMessage as passed to drupalPostForm().
   * @param string $icon_style
   *   (optional) The specified default_icon_style option (addthis_16x16_style or
   *   addthis_32x32_style)
   * @param bool $addthis_attributes
   *   (optional) FALSE if this markup should not contain its AddThis attributes,
   *   TRUE if it should.
   *   Defaults to FALSE.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertShareButtons($sharemessage, $icon_style = 'addthis_16x16_style', $addthis_attributes = FALSE, $message = '') {
    return $this->assertShareButtonHelper($sharemessage, $icon_style, $addthis_attributes, $message, FALSE);
  }

  /**
   * Passes if the markup of the share links wrapper is NOT found on the loaded page.
   *
   * @param string $sharemessage
   *   The edit array of a ShareMessage as passed to drupalPostForm().
   * @param string $icon_style
   *   The specified default_icon_style option (addthis_16x16_style or
   *   addthis_32x32_style)
   * @param bool $addthis_attributes
   *   (optional) FALSE if this markup should not contain its AddThis attributes,
   *   TRUE if it should.
   *   Defaults to FALSE.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoShareButtons($sharemessage, $icon_style = 'addthis_16x16_style', $addthis_attributes = FALSE, $message = '') {
    return $this->assertShareButtonHelper($sharemessage, $icon_style, $addthis_attributes, $message, TRUE);
  }

  /**
   * Helper for assertShareButtons and assertNoShareButtons.
   *
   * @param array $sharemessage
   *   The edit array of a ShareMessage as passed to drupalPostForm().
   * @param string $icon_style
   *   The specified default_icon_style option (addthis_16x16_style or
   *   addthis_32x32_style)
   * @param bool $addthis_attributes
   *   (optional) FALSE if this markup should not contain its AddThis attributes,
   *   TRUE if it should.
   *   Defaults to FALSE.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param bool $not_exists
   *   (optional) TRUE if this markup should not exist, FALSE if it should.
   *   Defaults to TRUE.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertShareButtonHelper($sharemessage, $icon_style = 'addthis_16x16_style', $addthis_attributes = FALSE, $message = '', $not_exists = TRUE) {
    $raw_html_icon_style = '<div class="addthis_toolbox addthis_default_style ' . $icon_style . '"';
    if ($addthis_attributes) {
      // If you are logged out, please do not go to the front page, as the path
      // varies depending on whether you are logged in or not (the front page is
      // '/user', which then redirects away).
      // Enable the module 'filter' and use drupalGet('filter/tips') instead.
      $raw_html_icon_style .= isset($sharemessage['share_url']) ? (' addthis:url="' . $sharemessage['share_url'] . '"') : (' addthis:url="' . $this->getUrl() . '"');
      $raw_html_icon_style .= isset($sharemessage['title']) ? (' addthis:title="' . $sharemessage['title'] . '"') : ' addthis:title=""';
      $raw_html_icon_style .= isset($sharemessage['message_long']) ? (' addthis:description="' . $sharemessage['message_long'] . '"') : ' addthis:description=""';
    }
    $raw_html_icon_style .= '>';

    if (!$message) {
      if (!$not_exists) {
        $message = new FormattableMarkup('Icon style "@raw_html_icon_style" found.', ['@raw_html_icon_style' => $raw_html_icon_style]);
      }
      else {
        $message = new FormattableMarkup('Icon style "@raw_html_icon_style" not found.', ['@raw_html_icon_style' => $raw_html_icon_style]);
      }
    }

    if (isset($this->renderedEntity)) {
      return $not_exists ? $this->assertStringNotContainsString($raw_html_icon_style, $this->renderedEntity, $message) : $this->assertStringContainsString($raw_html_icon_style, $this->renderedEntity, $message);
    }

    if ($not_exists) {
      return $this->assertSession()->responseNotContains($raw_html_icon_style, $message);
    }
    else {
      return $this->assertSession()->responseContains($raw_html_icon_style, $message);
    }
  }

  /**
   * Passes if the markup of the OG meta tags IS found on the loaded page.
   *
   * @param string $property
   *   The OG tag property, for example "og:title" (WITHOUT any kind of quotes).
   * @param string $value
   *   The value/content of the related OG tag property.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertOGTags($property, $value, $message = '') {
    return $this->assertOGTagsHelper($property, $value, $message, FALSE);
  }

  /**
   * Passes if the markup of the OG meta tags is NOT found on the loaded page.
   *
   * @param string $property
   *   The OG tag property, for example "og:title" (WITHOUT any kind of quotes).
   * @param string $value
   *   The value/content of the related OG tag property.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoOGTags($property, $value, $message = '') {
    return $this->assertOGTagsHelper($property, $value, $message, TRUE);
  }

  /**
   * Helper for assertOGTags and assertNoOGTags.
   *
   * @param string $property
   *   The OG tag property, for example "og:title" (WITHOUT any kind of quotes).
   * @param string $value
   *   The value/content of the related OG tag property.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Utility\SafeMarkup::format() to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   * @param bool $not_exists
   *   (optional) TRUE if this OG tag should not be rendered, FALSE if it should.
   *   Defaults to TRUE.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertOGTagsHelper($property, $value, $message = '', $not_exists = TRUE) {
    $meta_tag = '<meta property="' . $property . '" content="' . Xss::filter($value) . '" />';

    if (!$message) {
      if (!$not_exists) {
        $message = new FormattableMarkup('OG tag "@meta_tag" found.', ['@meta_tag' => $meta_tag]);
      }
      else {
        $message = new FormattableMarkup('OG tag "@meta_tag" not found or has not the expected content.', ['@meta_tag' => $meta_tag]);
      }
    }

    if ($not_exists) {
      return $this->assertSession()->responseNotContains($meta_tag);
    }
    else {
      return $this->assertSession()->responseContains($meta_tag);
    }
  }

}
