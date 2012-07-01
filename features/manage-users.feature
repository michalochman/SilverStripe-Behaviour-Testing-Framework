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
    And I am logged in with "ADMIN" permissions
    And I go to "/admin/security"

  @javascript
  Scenario: I can list all users regardless of group
    When I click the "Users" CMS tab
    # Note: This GridField is not labelled in the UI
    Then I should see "admin@test.com" in the "#Root_Users" element
    And I should see "staffmember@test.com" in the "#Root_Users" element

  @javascript
  Scenario: I can list all users in a specific group
    When I click the "Groups" CMS tab
    # Should do a full node content match in this area
    # TODO Please check how performant this is
    And I click "Admin Group" in the "#Root_Users" element
    Then I should see "admin@test.com" in the "#Root_Users" element
    And I should not see "staffmember@test.com" in the "#Root_Users" element

  @javascript
  Scenario: I can add a user to the system
    When I click the "Users" CMS tab
    And I press "Add Member" button
    And I fill in the following:
      | First Name | John |
      | Surname | Doe |
      | Email | john.doe@test.com |
    And I press "Create" button
    Then I should see "Saved member" message

    When I go to "admin/security/"
    Then I should see "john.doe@test.com" in the "#Root_Users" element

  @javascript
  Scenario: I can edit an existing user and add him to an existing group
    When I click the "Users" CMS tab
    And I click "staffmember@test.com" in the "#Root_Users" element
    # Chosen.js dropdown field. Ideally we can bypass this and
    # work transparently with hidden form fields
    And I select "Admin Group" from "Groups"
    And I additionally select "Administrators" from "Groups"
    And I press "Save" button

    When I go to "admin/security"
    And I click the "Groups" CMS tab
    And I click "Admin Group" in the "#Root_Users" element
    Then I should see "staffmember@test.com"

  @javascript
  Scenario: I can delete an existing user
    When I click the "Users" CMS tab
    And I click "staffmember@test.com" in the "#Root_Users" element
    And I press "Delete" button
    Then I should see "admin@test.com"
    And I should not see "staffmember@test.com"