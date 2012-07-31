@javascript
Feature: Manage files
  As a cms author
  I want to upload and manage files within the CMS
  So that I can insert them into my content efficiently

  Background:
    # Unfortunately, we need this duplication of "Parent" relationship
    # and specifying the path via "Filename".
    #
    # Idea: We could weave the database reset into this through
    # saying 'Given there are ONLY the following...'.
    Given there are the following Folder records
    """
    folder1:
        Filename: assets/folder1
    folder1.1:
        Filename: assets/folder1/folder1.1
        Parent: =>Folder.folder1
    folder2:
        Filename: assets/folder2
        Name: folder2
    """
    # Will need to copy the files from "files_path" into the assets/ folder.
    # Should be part of the fixture logic, with a special case for "File"
    And there are the following File records
    """
    file1:
        Filename: assets/folder1/file1.jpg
        Name: file1.jpg
        Parent: =>Folder.folder1
    file2:
        Filename: assets/folder1/folder1.1/file2.jpg
        Name: file2.jpg
        Parent: =>Folder.folder1.1
    """
    And I am logged in with "ADMIN" permissions
    # Alternative fixture shortcuts, with their titles
    # as shown in admin/security rather than technical permission codes.
    # Just an idea for now, could be handled by YAML fixtures as well
#    And I am logged in with the following permissions
#      - Access to 'Pages' section
#      - Access to 'Files' section
    And I go to "/admin/assets"

  @modal
  Scenario: I can add a new folder
    Given I press "Add folder" button
    And I type "newfolder" into the dialog
    And I confirm the dialog
    Then the "Files" table should contain "newfolder"

  Scenario: I can list files in a folder
    # Slight variation of the "in the '#<selector>' area, specific for GridFields with a title
    Given I click on "folder1" in the "Files" table
    # Maybe we can make these composite steps,
    # so have the "... in the 'folder1' table" separate?
    Then the "folder1" table should contain "file1"
    # Means the previous match has to be exact rather than partial.
    # I think thats a good idea to avoid false positives.
    # Also: I bet there's a smart way in Behat to handle negations like this.
    And the "folder1" table should not contain "file1.1"

  # Requires 'files_path' being set up for Mink,
  # see http://extensions.behat.org/mink/index.html#usage
  Scenario: I can upload a file to a folder
    Given I click on "folder1" in the "Files" table
    And I press "Upload" button
    And I attach the file "testfile.jpg" to "AssetUploadField"
    # Good enough for now, unless you can find an easy way
    # to check for both HTML5 uploads and hidden iframes.
    # We use https://github.com/blueimp/jQuery-File-Upload for this.
    And I wait for "5000"
    And I press "Back to folder" button
    Then the "Files" table should contain "testfile.jpg"

  Scenario: I can edit a file
    Given I click on "folder1" in the "Files" table
    And I click on "file1" in the "folder1" table
    And I fill in "renamedfile" for "Title"
    And I press "Save" button
    And I press "Back" button
    Then the "folder1" table should not contain "testfile"
    And the "folder1" table should contain "renamedfile"

  Scenario: I can delete a file
    Given I click on "folder1" in the "Files" table
    # Note that the file name in DB and filesystem should be reset from the
    # previous scenario at this point
    And I click on "file1" in the "folder1" table
    And I press "Delete" button
    Then the "folder1" table should not contain "file1"

  Scenario: I can change the folder of a file
    Given I click on "folder1" in the "Files" table
    And I click on "file1" in the "folder1" table
    # Should be implemented as a generic preprocessor available to all steps
    And I fill in "=>Folder.folder2.ID" for "ParentID"
    And I press "Save" button
    And I go to "/admin/assets"
    And I click on "folder2" in the "Files" table
    And the "folder2" table should contain "file1"