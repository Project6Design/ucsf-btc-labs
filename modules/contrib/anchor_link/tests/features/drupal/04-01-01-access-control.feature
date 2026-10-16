@anchor_link @access @security
Feature: Text format administration is protected
  As a site owner
  I want only privileged users to manage the Anchor Test format
  So that the editor configuration cannot be tampered with

  Scenario: An anonymous user cannot reach the format configuration
    Given I am an anonymous user
    When I am on "/admin/config/content/formats/manage/anchor_test"
    Then the "drupal access denied heading" element should be visible

  Scenario: An authenticated user without permission cannot reach the format configuration
    Given I am a logged in user with the "Authenticated user" user
    When I am on "/admin/config/content/formats/manage/anchor_test"
    Then the "drupal access denied heading" element should be visible

  Scenario: The Webmaster can reach the format configuration
    Given I am a logged in user with the "Webmaster" user
    When I am on "/admin/config/content/formats/manage/anchor_test"
    Then the "format edit form" element should be visible
    And there should be no JavaScript errors
