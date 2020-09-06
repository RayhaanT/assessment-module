@mod @mod_amcat
Feature: A teacher can password protect a amcat
  In order to avoid undesired accesses to amcat activities
  As a teacher
  I need to set a password to access the amcat

  Scenario: Accessing as student to a protected amcat
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "amcat" to section "1" and I fill the form with:
      | Name | Test amcat |
      | Description | Test amcat description |
      | Password protected amcat | Yes |
      | id_password | moodle_rules |
    And I follow "Test amcat"
    And I follow "Add a content page"
    And I set the following fields to these values:
      | Page title | First page name |
      | Page contents | First page contents |
      | Description | The first one |
    And I press "Save page"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    When I follow "Test amcat"
    Then I should see "Test amcat is a password protected amcat"
    And I should not see "First page contents"
    And I set the field "userpassword" to "moodle"
    And I press "Continue"
    And I should see "Login failed, please try again..."
    And I should see "Test amcat is a password protected amcat"
    And I set the field "userpassword" to "moodle_rules"
    And I press "Continue"
    And I should see "First page contents"
