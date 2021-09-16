@assignsubmission_timedonline @timedonline_submission_sweep
Feature: In an assignment, submit when timelimit runs out
    In order to ensure work is submitted if there is no browser session
    (e.g. network failure), sweep text from editor_atto_autosave table
    and commit "in the background" based on a cron task defaulting to execute
    once a minute.

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
  Scenario: Submit attempt in background
    Given the following "activities" exist:
        | activity | idnumber | course | name        | intro             | assignsubmission_timedonline_enabled | id_assignsubmission_timedonline_questiontext | id_submissiondrafts | blindmarking |
        | assign   | assign1  | C1     | Assignment1 | Exam Instructions | 1                                    | Answer this question                         | Yes                 | 1            |

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Assignment1"
    And I navigate to "Edit settings" in current page administration
    And I expand all fieldsets
    And I set the field "Time limit (minutes)" to "3"
    And I set the field "Text of question seen by students" to "Answer this question"
    And I press "Save and return to course"
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Assignment1"
    When I press "Add submission"
    And I set the field "Add your response" to "Student response"
    # Wait 2 minutes to ensure the atto_auto_save table has been written to
    And I wait "180" seconds
    And I am on "Course 1" course homepage
    And I log out
    # Wait 3 minutes to ensure the submission_sweep cron task has run and made a submission
    And I wait "90" seconds

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Assignment1"
    And I should see "Submitted for grading"
