Feature: Edit a page
  As an author
  I want to edit a page in the CMS
  So that I correct errors and provide new information

  Background:
    Given I am logged in
    And I go to "/admin/pages"
    And I wait for "1000"
    Then I should see "About Us" in CMS Tree

  # TODO:
  # - Move delays inside step definitions
  @javascript
  Scenario: I can open a page for editing from the pages tree
    When I follow "About Us"
    And I wait for "1000"
    Then I should see an edit page form

  # TODO:
  # - Move delays inside step definitions
  @javascript
  Scenario: I can edit title and content and see the changes on draft
    When I follow "About Us"
    And I wait for "1000"
    Then I should see an edit page form

    When I fill in "Title" with "About Us!"
    And I fill in content form with "my new content"
    And I press "Save Draft" button
    # The two following can cause problems, as the flash message disappears quickly
    And I wait for "1000"
    Then I should see "Saved." notice

    When I follow "About Us"
    And I wait for "1000"
    Then the "Title" field should contain "About Us!"
    And the content form should contain "my new content"