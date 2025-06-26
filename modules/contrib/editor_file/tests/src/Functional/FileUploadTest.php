<?php

declare(strict_types=1);

namespace Drupal\Tests\editor_file\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\ckeditor5\Traits\SynchronizeCsrfTokenSeedTrait;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\user\RoleInterface;

/**
 * Test file upload.
 *
 * @group editor_file
 * @group ckeditor5
 * @requires module ckeditor5
 * @internal
 */
class FileUploadTest extends BrowserTestBase {

  use TestFileCreationTrait;
  use SynchronizeCsrfTokenSeedTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'editor',
    'filter',
    'ckeditor5',
    'editor_file',
  ];

  /**
   * A user without any particular permissions to be used in testing.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
    ]);
    $this->drupalLogin($this->adminUser);

    $this->createBasicFormat();
    $this->createEditorWithInsetFile([
      'status' => TRUE,
      'directory' => 'inline-images',
      'max_size' => '',
      'extensions' => 'txt csv',
      'scheme' => $this->container->get('config.factory')->get('system.file')->get('default_scheme'),
    ]);
  }

  /**
   * Tests file upload with a allowed extension.
   */
  public function testUploadFileWithAllowedExtension() {
    $this->drupalGet('editor_file/dialog/file/basic_html');
    $content = $this->submitFormData('test.txt');
    $this->assertStringNotContainsString('.messages--error', $content);
  }

  /**
   * Tests file upload with a disallowed extension.
   */
  public function testUploadFileWithDisAllowedExtension() {
    $this->drupalGet('editor_file/dialog/file/basic_html');
    $content = $this->submitFormData('test.php');
    $this->assertStringContainsString('The specified file <em class="placeholder">test.php</em> could not be uploaded.<ul><li>Only files with the following extensions are allowed: <em class="placeholder">txt csv</em>', $content);
  }

  /**
   * Create a basic_html text format for the editor to reference.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createBasicFormat() {
    $basic_html_format = FilterFormat::create([
      'format' => 'basic_html',
      'name' => 'Basic HTML',
      'weight' => 1,
      'filters' => [
        'filter_html_escape' => ['status' => 1],
      ],
      'roles' => [RoleInterface::AUTHENTICATED_ID],
    ]);
    $basic_html_format->save();
  }

  /**
   * Create an editor entity with editor_file_file config.
   *
   * @param array $editor_file_settings
   *   The editor editor_file_file config.
   *
   * @return \Drupal\Core\Entity\EntityBase|\Drupal\Core\Entity\EntityInterface
   *   The text editor entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createEditorWithInsetFile(array $editor_file_settings) {
    $editor = Editor::create([
      'editor' => 'ckeditor5',
      'format' => 'basic_html',
      'settings' => [
        'toolbar' => [
          'items' => [
            'drupalInsertFile',
          ],
        ],
        'plugins' => [
          'editor_file_file' => $editor_file_settings,
        ],
      ],
    ]);
    $editor->save();
    return $editor;
  }

  /**
   * Submit form data.
   *
   * @param string $filename
   *   The name of the file with extension.
   *
   * @return string
   *   The html response;
   */
  private function submitFormData($filename) {
    $page = $this->getSession()->getPage();
    $data = [
      'multipart' => [
        [
          'name' => 'form_id',
          'contents' => 'editor_file_dialog',
        ],
        [
          'name' => 'form_build_id',
          'contents' => $page->find('hidden_field_selector',
          ['hidden_field', 'form_build_id'])->getAttribute('value'),
        ],
        [
          'name' => 'form_token',
          'contents' => $page->find('hidden_field_selector',
          ['hidden_field', 'form_token'])->getAttribute('value'),
        ],
        [
          'name' => 'op',
          'contents' => 'Save',
        ],
        [
          'name'     => 'files[fid]',
          'contents' => 'Test content',
          'filename' => $filename,
        ],
      ],
      'cookies' => $this->getSessionCookies(),
      'http_errors' => FALSE,
    ];
    $this->assertFileDoesNotExist('temporary://' . $filename);

    $response = $this->getHttpClient()->request('POST', Url::fromUri('base:editor_file/dialog/file/basic_html')->setAbsolute()->toString(), $data);
    return (string) $response->getBody();
  }

}
