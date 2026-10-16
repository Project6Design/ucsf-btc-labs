@setup @anchor_link
Feature: Provision the CKEditor Anchor Link test site
  As the site owner
  I want one user per role created through the admin UI
  So that the editor, rendering and access-control scenarios have what they expect

  Scenario: The Webmaster signs in and provisions the testing users
    Given I am a logged in user with the "Webmaster" user
    And I add testing users
    When I am on "/admin/config/content/formats"
    Then I should see "Anchor Test"
    And there should be no JavaScript errors
