Feature: Manage users
  As a site administrator
  I want to create and manage user accounts on my site
  So that I can control access to the CMS

  Background:
    Given there are the following Permission records
      """
      admin:
        Code: ADMIN
      security-admin:
        Code: CMS_ACCESS_SecurityAdmin
      """
    And there are the following Group records
      """
      admingroup:
        Title: Admin Group
        Code: admin
        Permissions: =>Permission.admin
      staffgroup:
        Title: Staff Group
        Code: staffgroup
      """
    And there are the following Member records
      """
      admin:
        FirstName: Admin
        Email: admin@test.com
        Groups: =>Group.admingroup
      staffmember:
        Email: staffmember@test.com
        Groups: =>Group.staffgroup
      """
    # General purpose shortcut, will be used often.
    # See SapphireTest->logInWithPermission()
    And I am logged in with "ADMIN" permissions
    And I go to "/admin/security"

  Scenario: I can list all users regardless of group
    # Should look for <a> inside .ui-tabs-nav
    When I click the "Users" tab
    # Note: This GridField is not labelled in the UI
    Then I should see "admin@test.com" in the "#Root_Users" area
    And I should see "staffmember@test.com" in the "#Root_Users" area

  Scenario: I can list all users in a specific group
    When I click the "Groups" tab
    # Should do a full node content match in this area
    # TODO Please check how performant this is
    And I click on "Admin Group" in the "#Root_Users" area
    Then I should see "admin@test.com" in the "#Root_Users" area
    And I should not see "staffmember@test.com" in the "#Root_Users" area

  Scenario: I can add a user to the system
    When I click the "Users" tab
    And I click the "Add Member" button
    # Should be built-in to behat, but validate that it takes the correct form
    And I fill in the following
      | First Name | John |
      | Surname | Doe |
      | Email | john.doe@test.com |
    And I click the "Create" button
    # Should check for .message and .good CSS classes
    Then I should see a success message with "Saved member"

    When I go to "admin/security/"
    Then I should see "john.doe@test.com" in the "#Root_Users" area

  Scenario: I can edit an existing user and add him to an existing group
    When I click on the "Users" tab
    And I click on "staffmember@test.com" in the "#Root_Users" area
    # Chosen.js dropdown field. Ideally we can bypass this and
    # work transparently with hidden form fields
    And I select "Admin Group" from "Groups"
    And I additionally select "Administrators" from "Groups"
    And I press the "Save" button

    When I go to "admin/security"
    And I click on the "Groups" tabs
    And I click on "Admin Group" in the "#Root_Users" area
    Then I should see "staffmember@test.com"

  Scenario: I can delete an existing user
    When I click on the "Users" tab
    And I click on "staffmember@test.com" in the "#Root_Users" area
    And I press the "Delete" button
    Then I should see "admin@test.com"
    And I should not see "staffmember@test.com"