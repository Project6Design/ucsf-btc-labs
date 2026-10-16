@anchor_link @access @security
Feature: Content format administration is protected on Drupal CMS
  As a site owner
  I want only privileged users to manage the content format
  So that the editor configuration cannot be tampered with

  Scenario: An anonymous user cannot reach the content format configuration
    Given I am an anonymous user
    When I am on "/admin/config/content/formats/manage/content_format"
    Then the "drupal access denied heading" element should be visible

  Scenario: The Webmaster can reach the content format configuration
    Given I am a logged in user with the "Webmaster" user
    When I am on "/admin/config/content/formats/manage/content_format"
    Then the "format edit form" element should be visible
    And there should be no JavaScript errors
