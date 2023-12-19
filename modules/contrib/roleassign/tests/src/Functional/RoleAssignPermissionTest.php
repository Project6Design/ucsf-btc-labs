<?php

namespace Drupal\Tests\roleassign\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;

/**
 * Tests that users can (un)assign roles based on the RoleAssign settings.
 *
 * @group roleassign
 */
class RoleAssignPermissionTest extends BrowserTestBase {

  /**
   * The user object to test (un)assigning roles to.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testAccount;

  /**
   * The user object that has restricted access to assign roles.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $restrictedUser;

  /**
   * The user object that has access to administer users & permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['roleassign'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Add Editor role.
    $this->drupalCreateRole([], 'editor', 'Editor');
    // Add Webmaster role.
    $this->drupalCreateRole([
      'administer users',
      'assign roles',
    ], 'webmaster', 'Webmaster');
    // Add 'protected' SiteAdmin role.
    $this->drupalCreateRole([
      'administer users',
      'administer permissions',
    ], 'siteadmin', 'SiteAdmin');

    // Configure RoleAssign module - only editor & webmaster roles are
    // assignable by restricted users (i.e. webmasters)
    $this->config('roleassign.settings')
      ->set('roleassign_roles', [
        'editor' => 'editor',
        'webmaster' => 'webmaster',
      ])
      ->save();

    // Create a testAccount that we will be trying to assign roles.
    $this->testAccount = $this->drupalCreateUser();

    // Create a test restricted user without "administer permissions" permission
    // but with "assign roles" permission provided by RoleAssign.
    $this->restrictedUser = $this->drupalCreateUser([
      'administer users',
      'assign roles',
    ]);

    // Create a test admin user with "administer users " &
    // "administer permissions" permissions, where RoleAssign will have no
    // effect on.
    $this->adminUser = $this->drupalCreateUser([
      'administer users',
      'administer permissions',
    ]);
  }

  /**
   * Tests that RoleAssign settings are set up correctly.
   */
  public function testRoleAssignSettings() {
    $assignable_roles = array_filter(\Drupal::config('roleassign.settings')->get('roleassign_roles'));
    $this->assertSame([
      'editor' => 'editor',
      'webmaster' => 'webmaster',
    ], $assignable_roles);
  }

  /**
   * Tests that a restricted user can only (un)assign configured roles.
   */
  public function testRoleAssignRestrictedUser() {
    // Login as restricted user to test RoleAssign.
    $this->drupalLogin($this->restrictedUser);

    // Load account edit page.
    $this->drupalGet('user/' . $this->testAccount->id() . '/edit');

    // Check that only assignable roles are displayed.
    $this->assertSession()->pageTextContains('Assignable roles');
    $this->assertSession()->checkboxNotChecked('edit-roles-editor');
    $this->assertSession()->checkboxNotChecked('edit-roles-webmaster');
    $this->assertSession()->fieldNotExists('edit-roles-siteadmin');
    $this->drupalGet('user/' . $this->testAccount->id() . '/edit');

    // Assign the role "editor" to the account.
    $this->submitForm(["roles[editor]" => "editor"], 'Save');
    $this->assertSession()->pageTextContains('The changes have been saved.');
    $this->assertSession()->checkboxChecked('edit-roles-editor');
    $this->assertSession()->checkboxNotChecked('edit-roles-webmaster');
    $this->assertSession()->fieldNotExists('edit-roles-siteadmin');
    $this->userLoadAndCheckRoleAssigned($this->testAccount, 'editor');
    $this->userLoadAndCheckRoleAssigned($this->testAccount, RoleInterface::AUTHENTICATED_ID);
    $this->drupalGet('user/' . $this->testAccount->id() . '/edit');

    // Remove the role "editor" from the account.
    $this->submitForm(["roles[editor]" => FALSE], 'Save');
    $this->assertSession()->pageTextContains('The changes have been saved.');
    $this->assertSession()->checkboxNotChecked('edit-roles-editor');
    $this->assertSession()->checkboxNotChecked('edit-roles-webmaster');
    $this->assertSession()->fieldNotExists('edit-roles-siteadmin');
    $this->userLoadAndCheckRoleAssigned($this->testAccount, 'editor', FALSE);
    $this->userLoadAndCheckRoleAssigned($this->testAccount, RoleInterface::AUTHENTICATED_ID);

    // Try to assign a restricted role programmatically to a new user.
    $values = [
      'name' => $this->randomString(),
      'roles' => ['editor', 'siteadmin'],
    ];
    $code_account = User::create($values);
    $code_account->save();

    // Check that user only gets editor role, but not siteadmin role.
    $this->assertTrue($code_account->hasRole('editor'));
    $this->assertFalse($code_account->hasRole('siteadmin'));
  }

  /**
   * Tests that an admin user with "administer permissions" can add all roles.
   */
  public function testRoleAssignAdminUser() {
    // Login as admin user.
    $this->drupalLogin($this->adminUser);

    // Load account edit page.
    $this->drupalGet('user/' . $this->testAccount->id() . '/edit');

    // Check that Drupal default roles checkboxes are shown.
    $this->assertSession()->pageTextContains('Roles');
    $this->assertSession()->checkboxNotChecked('edit-roles-editor');
    $this->assertSession()->checkboxNotChecked('edit-roles-webmaster');
    $this->assertSession()->checkboxNotChecked('edit-roles-siteadmin');
    $this->drupalGet('user/' . $this->testAccount->id() . '/edit');

    // Assign the role "SiteAdmin" to the account.
    $this->submitForm(["roles[siteadmin]" => "siteadmin"], 'Save');
    $this->assertSession()->pageTextContains('The changes have been saved.');
    $this->assertSession()->checkboxChecked('edit-roles-siteadmin');
    $this->userLoadAndCheckRoleAssigned($this->testAccount, 'siteadmin');
    $this->userLoadAndCheckRoleAssigned($this->testAccount, RoleInterface::AUTHENTICATED_ID);

    // Now log in as restricted user again.
    $this->drupalLogin($this->restrictedUser);
    $this->drupalGet('user/' . $this->testAccount->id() . '/edit');

    // Assign the role "editor" to the account, and test that the assigned
    // "siteadmin" role doesn't get lost.
    $this->submitForm(["roles[editor]" => "editor"], 'Save');
    $this->assertSession()->pageTextContains('The changes have been saved.');
    $this->assertSession()->checkboxChecked('edit-roles-editor');
    $this->assertSession()->fieldNotExists('edit-roles-siteadmin');
    $this->userLoadAndCheckRoleAssigned($this->testAccount, 'editor');
    $this->userLoadAndCheckRoleAssigned($this->testAccount, RoleInterface::AUTHENTICATED_ID);
    $this->userLoadAndCheckRoleAssigned($this->testAccount, 'siteadmin');
  }

  /**
   * Check role on user object.
   *
   * @param object $account
   *   The user account to check.
   * @param string $rid
   *   The role ID to search for.
   * @param bool $is_assigned
   *   (optional) Whether to assert that $rid exists (TRUE) or not (FALSE).
   *   Defaults to TRUE.
   */
  private function userLoadAndCheckRoleAssigned($account, $rid, $is_assigned = TRUE) {
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    $user_storage->resetCache([$account->id()]);
    $account = $user_storage->load($account->id());
    if ($is_assigned) {
      $this->assertFalse(array_search($rid, $account->getRoles()) === FALSE, 'The role is present in the user object.');
    }
    else {
      $this->assertTrue(array_search($rid, $account->getRoles()) === FALSE, 'The role is not present in the user object.');
    }
  }

}
