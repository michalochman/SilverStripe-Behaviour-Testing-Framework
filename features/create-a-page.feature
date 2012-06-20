Feature: Create a page
  As an author
  I want to create a page in the CMS
  So that I can grow my website

  # TODO:
  # - Move delays inside step definitions
  @javascript
  Scenario: I can create a page from the pages section
    Given I am logged in
    And I go to "/admin/pages"
    And I should see "Add new" button in CMS Content Toolbar
  	When I press "Add new" button
    And I check "Page"
  	And I press "Create" button
  	Then I should see an edit page form
