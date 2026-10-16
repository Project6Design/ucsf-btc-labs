@anchor_link @editor @critical
Feature: A link that also carries an id keeps it
  As a content editor
  I want a link that has both an href and an id to keep its id
  So that the link stays a jump target as well as a link

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: A link authored with an href and an id keeps both
    When I open a new article using the "anchor_test" text format
    And I set the editor data to "<p><a href=\"https://example.com\" id=\"keep-me\">Link with id</a></p>"
    Then the editor data should contain "keep-me"
    And the editor data should contain "https://example.com"
    And the editor data should not contain "ck-anchor"
    When I save the article titled "Anchor with href demo"
    And I view the article I created
    Then the page should contain an anchor with id "keep-me"
    And there should be no JavaScript errors
