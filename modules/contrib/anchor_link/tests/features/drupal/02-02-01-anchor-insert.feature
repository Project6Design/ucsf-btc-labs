@anchor_link @editor @critical
Feature: Inserting an anchor with the Anchor button
  As a content editor
  I want to wrap text in a named anchor
  So that other pages or links can jump straight to it

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: Insert an anchor and confirm it survives save and render
    When I open a new article using the "anchor_test" text format
    And I insert an anchor named "section-one" around the text "Jump to this section"
    Then the editor data should not contain "ck-anchor"
    And the editor data should contain "section-one"
    When I save the article titled "Anchor insert demo"
    Then the page should contain an anchor with id "section-one"
    And the page should contain exactly 1 anchor with id "section-one"
    And there should be no JavaScript errors
