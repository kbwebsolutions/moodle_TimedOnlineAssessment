@assignsubmission_timedonline @timedonline_sweep_start
Feature: In an assignment, confirm that that submission via
         sweep does not happen before the add submission
         button is clicked.

  Background:
    Given the following "courses" exist:
        | fullname | shortname | format | enablecompletion |
        | Course 1 | C1        | topics | 1                |
    And the following "users" exist:
        | username | email         |
        | teacher1 | t@example.com |
        | student1 | s@example.com |
    And the following "course enrolments" exist:
        | user     | course | role           |
        | teacher1 | C1     | editingteacher |
        | student1 | C1     | student        |
  @javascript
  Scenario: Access as student but don't click add button
    Given the following "activities" exist:
        | activity | idnumber | course | name        | intro             | assignsubmission_timedonline_enabled | id_assignsubmission_timedonline_questiontext | id_submissiondrafts | blindmarking |
        | assign   | assign1  | C1     | Assignment1 | Exam Instructions | 1                                    | Answer this question                         | Yes                 | 1            |

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Assignment1"
    And I navigate to "Edit settings" in current page administration
    And I expand all fieldsets
    And I set the field "Time limit (minutes)" to "1"
    And I set the field "Text of question seen by students" to "Answer this question"
    And I press "Save and return to course"
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Assignment1"
    # The cron should run every minute so 180 seconds is more than enough time.
    And I wait "180" seconds
    And I am on "Course 1" course homepage
    And I follow "Assignment1"
    # Nothing should have been submitted in the background
    Then I should see "No attempt"
