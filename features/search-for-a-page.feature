Feature: Search for a page
  As an author
  I want to search for a page in the CMS
  So that I can efficiently navigate nested content structures

  # TODO:
  # - Move delays inside step definitions
  @javascript
  Scenario: I can search for a page by its title
    Given I am logged in
    And I go to "/admin/pages"
    And I wait for "1000"
    Then I should see "About Us" in CMS Tree
    And I should see "Contact Us" in CMS Tree

    When I expand Filter CMS Panel
    And I fill in "Content" with "About Us"
    And I press "Apply Filter" button
    And I wait for "1000"
    Then I should see "About Us" in CMS Tree
    But I should not see "Contact Us" in CMS Tree
