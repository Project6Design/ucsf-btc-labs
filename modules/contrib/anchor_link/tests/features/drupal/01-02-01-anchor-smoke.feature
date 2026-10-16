@smoke @anchor_link
Feature: Smoke - the Anchor Link module is wired into CKEditor 5
  As a site administrator
  I want the Anchor button and Anchor Test format to be present
  So that the CKEditor Anchor Link module can be exercised

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: The Text formats page lists the Anchor Test format
    When I am on "/admin/config/content/formats"
    Then I should see "Anchor Test"
    And there should be no JavaScript errors

  Scenario: The Anchor Test format edit page advertises the Anchor link button
    When I am on "/admin/config/content/formats/manage/anchor_test"
    Then the "format edit form" element should be visible
    And I should see "Anchor link"
    And there should be no JavaScript errors
