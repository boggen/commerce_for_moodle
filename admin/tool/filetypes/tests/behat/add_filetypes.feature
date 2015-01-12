@tool @tool_filetypes
Feature: Add customised file types
  In order to support a file mime type which doesn't exist in Moodle
  As an administrator
  I need to add a new customised file type

  Scenario: Add a new file type
    Given I log in as "admin"
    And I navigate to "File types" node in "Site administration > Server"
    And I press "Add"
    # Try setting all the form fields, not just the optional ones.
    And I set the following fields to these values:
      | Extension                  | frog                                      |
      | MIME type                  | application/x-frog                        |
      | File icon                  | archive                                   |
      | Type groups                | document                                  |
      | Description type           | Custom description specified in this form |
      | Custom description         | Froggy file                               |
      | Default icon for MIME type | 1                                         |
    When I press "Save changes"
    Then I should see "Froggy file" in the "application/x-frog" "table_row"
    And I should see "document" in the "application/x-frog" "table_row"
    And I should see "frog" in the "application/x-frog" "table_row"
    And "//img[contains(@src, 'archive')]" "xpath_element" should exist in the "application/x-frog" "table_row"

  Scenario: Update an existing file type
    Given I log in as "admin"
    And I navigate to "File types" node in "Site administration > Server"
    When I click on "Edit 7z" "link"
    And I set the following fields to these values:
      | Extension | doc |
    And I press "Save changes"
    Then I should see "File extensions must be unique"
    And I set the following fields to these values:
      | Extension | frog |
    And I press "Save changes"
    And I should see "frog" in the "application/x-7z-compressed" "table_row"

  Scenario: Delete an existing file type
    Given I log in as "admin"
    And I navigate to "File types" node in "Site administration > Server"
    When I click on "Delete 7z" "link"
    Then I should see "Are you absolutely sure you want to remove .7z?"
    And I press "Yes"
    And I should see "Deleted" in the "7z" "table_row"

  Scenario: Delete a custom file type
    Given I log in as "admin"
    And I navigate to "File types" node in "Site administration > Server"
    And I press "Add"
    And I set the following fields to these values:
      | Extension                  | frog                                      |
      | MIME type                  | application/x-frog                        |
    And I press "Save changes"
    When I click on "Delete frog" "link"
    And I press "Yes"
    Then I should not see "frog"

  Scenario: Revert changes to deleted file type
    Given I log in as "admin"
    And I navigate to "File types" node in "Site administration > Server"
    When I click on "Delete 7z" "link"
    And I press "Yes"
    And I follow "Restore 7z to Moodle defaults"
    And I press "Yes"
    Then I should not see "Deleted" in the "7z" "table_row"

  Scenario: Revert changes to updated file type
    Given I log in as "admin"
    And I navigate to "File types" node in "Site administration > Server"
    And I click on "Edit 7z" "link"
    And I set the following fields to these values:
      | File icon | document |
    And I press "Save changes"
    When I follow "Restore 7z to Moodle defaults"
    And I press "Yes"
    Then "//img[contains(@src, 'archive')]" "xpath_element" should exist in the "7z" "table_row"

  @javascript
  Scenario: Create a resource activity which contains a customised file type
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And I log in as "admin"
    And I navigate to "File types" node in "Site administration > Server"
    And I press "Add"
    And I set the following fields to these values:
      | Extension          | frog                                      |
      | MIME type          | application/x-frog                        |
      | File icon          | archive                                   |
      | Description type   | Custom description specified in this form |
      | Custom description | Froggy file                               |
    And I press "Save changes"
    # Create a resource activity and add it to a course
    And I am on homepage
    And I follow "Course 1"
    And I turn editing mode on
    When I add a "File" to section "1"
    And I set the following fields to these values:
      | Name        | An example of customised file type |
      | Description | File description                   |
    And I upload "admin/tool/filetypes/tests/fixtures/test.frog" file to "Select files" filemanager
    And I expand all fieldsets
    And I set the field "Show type" to "1"
    And I press "Save and return to course"
    Then I should see "Froggy file"
    And the "src" attribute of ".modtype_resource a img" "css_element" should contain "archive"
