@anchor_link @editor @critical
Feature: An editor without the Anchor button keeps the anchors it is given
  As a site owner running several text formats
  I want a format that does not use the Anchor button to leave anchors alone
  So that installing the module never costs me the anchors in that content

  Background:
    Given I am a logged in user with the "Webmaster" user

  Scenario: General HTML Support keeps the id and the name where the plugin is absent
    When I open a new article using the "no_anchor_test" text format
    And I set the editor data to "<p><a id=\"kept-id\">by id</a> and <a name=\"kept-name\">by name</a></p>"
    Then the editor data should contain "kept-id"
    And the editor data should contain "kept-name"
    When I save the article titled "Anchor outside the plugin"
    And I view the article I created
    Then the page should contain an anchor with id "kept-id"
    And there should be no JavaScript errors
