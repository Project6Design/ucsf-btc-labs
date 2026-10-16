@anchor_link @editor @critical
Feature: Anchor markup is preserved through Source editing
  As a content editor
  I want the anchor's id and class to survive the editor round-trip
  So that anchors created in source or by the button are not stripped

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: An anchor authored in the editor keeps its id and leaves no editor class behind
    When I open a new article using the "anchor_test" text format
    And I insert an anchor named "kept-anchor" around the text "Round-trip anchor"
    Then the editor data should not contain "ck-anchor"
    And the editor data should contain "kept-anchor"
    When I save the article titled "Anchor source demo"
    And I view the article I created
    Then the page should contain an anchor with id "kept-anchor"
    And there should be no JavaScript errors
