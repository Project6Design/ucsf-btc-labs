@smoke @anchor_link
Feature: Smoke - the Anchor button is wired into Drupal CMS's content format
  As a site administrator
  I want the Anchor button on Drupal CMS's content_format editor
  So that the CKEditor Anchor Link module works on the Drupal CMS distribution

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: The content format edit page advertises the Anchor link button
    When I am on "/admin/config/content/formats/manage/content_format"
    Then the "format edit form" element should be visible
    And I should see "Anchor link"
    And there should be no JavaScript errors
