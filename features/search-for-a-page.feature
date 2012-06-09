Feature: Search for a page
  As an author
  I want to search for a page in the CMS
  So that I can efficiently navigate nested content structures

  # TODO:
  # - Move selectors inside step definitions
  # - Move delays inside step definitions
  @javascript
  Scenario: I can search for a page by its title
    Given I am logged in
    And I click "Pages" in the CMS menu
    Then I should see "About Us" in the "#sitetree" element
    When I press "search" button
    And I fill in "SiteTreeSearchTerm" with "About Us"
    And I press "SiteTreeSearchButton"
    And I wait for "1000"
    Then I should see "About Us" in the "#sitetree" element
    And I should not see "Contact Us" in the "#sitetree" element
