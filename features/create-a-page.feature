Feature: Create a page
  As an author
  I want to create a page in the CMS
  So that I can grow my website

  # TODO:
  # - Move selectors inside step definitions
  # - Move delays inside step definitions
  @javascript
  Scenario: I can create a page from the pages section
    Given I am logged in
  	And I click "Pages" in the CMS menu
  	And I press "addpage" button
    And I submit "Form_AddPageOptionsForm" form
    And I wait for "1000"
  	Then I should see an edit page form