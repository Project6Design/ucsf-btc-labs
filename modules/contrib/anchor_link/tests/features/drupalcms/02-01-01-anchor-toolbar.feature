@anchor_link @editor @critical
Feature: The Anchor button appears in Drupal CMS's CKEditor 5 toolbar
  As a content editor on Drupal CMS
  I want the Anchor button on the page editor toolbar
  So that I can create in-page anchors while writing

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: A new Drupal CMS page shows the Anchor button
    When I open a new Drupal CMS page
    Then the "ckeditor editable" element should be visible
    And the CKEditor toolbar should have the "Anchor" button
    And there should be no JavaScript errors
