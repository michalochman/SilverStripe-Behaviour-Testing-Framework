Feature: Edit a page
  As an author
  I want to edit a page in the CMS
  So that I correct errors and provide new information

  # TODO:
  # - Move selectors inside step definitions
  # - Move delays inside step definitions
  @javascript
  Scenario: I can open a page for editing from the pages tree
    Given I am logged in
    And I click "Pages" in the CMS menu
    And I should see "About Us" in the "#sitetree" element
    When I follow "About Us"
    And I wait for "1000"
    Then I should see an edit page form

  # The excessive verbosity in the following scenario is caused by:
  # 1) The fact that SilverStripe is using tinyMCE, which hides default textarea
  # 2) Selenium may not interact with invisible elements
  # TODO:
  # - Move the verbosity inside step definitions
  # - Move selectors inside step definitions
  # - Move delays inside step definitions
  @javascript
  Scenario: I can edit title and content and see the changes on draft
    Given I am logged in
    And I click "Pages" in the CMS menu
    And I should see "About Us" in the "#sitetree" element
    When I follow "About Us"
    And I wait for "1000"
    Then I should see an edit page form
    When I fill in "Form_EditForm_Content" tinyMCE with "my new content"
    # The two following can cause problems, as the flash message disappears quickly
    And I press "Form_EditForm_action_save"
    And I wait for "500"
    Then I should see "Saved" in the "#statusMessage" element
    When I follow "About Us"
    And I wait for "1000"
    Then the "#Form_EditForm_Content" element should contain "my new content"