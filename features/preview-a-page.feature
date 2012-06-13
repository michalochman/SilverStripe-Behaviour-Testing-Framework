Feature: Preview a page
  As an author
  I want to preview the page I'm editing in the CMS
  So that I can see how it would look like to my visitors

  @javascript
  Scenario: I can show a preview of the current page from the pages section
    Given I am logged in
    And I go to "/admin/pages"
    And I wait for "1000"
    Then I should see "About Us" in CMS Tree

    When I follow "About Us"
    And I wait for "1000"
    And I press "Preview »" button
    And I wait for "1000"
    Then I can see the preview panel
    And the preview contains "About Us"

  # TODO:
  # - Only tests correctly on fresh database
  # - We should continue testing against it after we have fixtures ready
#  @javascript
#  Scenario: I can see an updated preview when editing content
#    Given I am logged in
#    And I go to "/admin/pages"
#    And I wait for "1000"
#    Then I should see "About Us" in CMS Tree
#
#    When I follow "About Us"
#    And I wait for "1000"
#    When I put "my new content" into the "Content" field
#    And I save the page
#    And I open the preview
#    Then I can see the content "You can fill"
#    #    Then I cannot see the content "my new content"
#    #    When I click the "Draft Site" link
#    #    Then I can see the content "my new content"
#    And I close the preview
#    Then I can see a "Content" field