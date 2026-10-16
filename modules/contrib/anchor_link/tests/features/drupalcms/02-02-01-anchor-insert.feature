@anchor_link @editor @critical
Feature: Inserting an anchor on a Drupal CMS page
  As a content editor on Drupal CMS
  I want to wrap text in a named anchor
  So that other pages or links can jump straight to it

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: Insert an anchor on a Drupal CMS page and confirm it survives save and render
    When I open a new Drupal CMS page
    And I insert an anchor named "cms-section" around the text "Jump to this CMS section"
    Then the editor data should not contain "ck-anchor"
    And the editor data should contain "cms-section"
    When I save the Drupal CMS page titled "Anchor CMS insert demo"
    Then the page should contain an anchor with id "cms-section"
    And the page should contain exactly 1 anchor with id "cms-section"
    And there should be no JavaScript errors
