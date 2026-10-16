@anchor_link @a11y
Feature: Rendered anchors are accessible
  As a site owner
  I want anchored content to pass an accessibility audit
  So that the Anchor button does not introduce a11y regressions

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: An article carrying an anchor passes an accessibility audit
    When I open a new article using the "anchor_test" text format
    And I insert an anchor named "a11y-anchor" around the text "Accessible anchor target"
    And I save the article titled "Anchor accessibility demo"
    Then the page should contain an anchor with id "a11y-anchor"
    And the page should have exactly one h1
    And every link should have an accessible name
    And there should be no JavaScript errors
