@setup @anchor_link
Feature: Reach the CKEditor Anchor Link Drupal CMS test site
  As the site owner
  I want the Webmaster session working against the format listing
  So that the editor and access-control scenarios have what they expect

  Scenario: The Webmaster signs in and checks the formats
    Given I am a logged in user with the "Webmaster" user
    When I am on "/admin/config/content/formats"
    Then I should see "Plain text"
    And there should be no JavaScript errors
