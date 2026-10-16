@anchor_link @editor @critical
Feature: The Anchor button appears in the live CKEditor 5 toolbar
  As a content editor
  I want the Anchor button on the editor toolbar
  So that I can create in-page anchors while writing

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: A new article with the Anchor Test format shows the Anchor button
    When I open a new article using the "anchor_test" text format
    Then the "ckeditor editable" element should be visible
    And the CKEditor toolbar should have the "Anchor" button
    And there should be no JavaScript errors
