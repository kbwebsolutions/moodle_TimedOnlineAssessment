@assignsubmission_timedonline @timedonline_timelimit
Feature: In an assignment, show question after Add susubmission is clicked
    In order to ensure work is submitted when time runs out,
    automate the click on the "add attempt" button.

  Background:
    Given the following "courses" exist:
        | fullname | shortname | format | enablecompletion |
        | Course 1 | C1        | topics | 1                |
    And the following "users" exist:
        | username | email          |
        | teacher1 | t@example.com  |
        | student1 | s1@example.com |
        | student2 | s2@example.com |
        | student3 | s3@example.com |

    And the following "course enrolments" exist:
        | user     | course | role           |
        | teacher1 | C1     | editingteacher |
        | student1 | C1     | student        |
        | student2 | C1     | student        |
        | student3 | C1     | student        |

  @javascript
  Scenario: Submit by hand then check log to ensure no additional javascript submission
    Given the following "activities" exist:
        | activity | idnumber | course | name        | intro             | assignsubmission_timedonline_enabled | assignsubmission_timedonline_timelimit | id_assignsubmission_timedonline_questiontext | id_submissiondrafts | blindmarking |
        | assign   | assign1  | C1     | Assignment1 | Exam Instructions | 1                                    | .40                                    | Answer this question                         | Yes                 | 1            |
    # The timelimit of .1 means 5 seconds to submit

    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Assignment1"
    And I navigate to "Edit settings" in current page administration
    And I expand all fieldsets
    And I set the field "Text of question seen by students" to "Answer this question"
    And I press "Save and return to course"
    And I log out

    # Check that autosubmit works
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Assignment1"
    And I press "Add submission"
    And I set the field "Add your response" to "Student response"
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"
    And I log out

    When I log in as "admin"
    And I wait "41" seconds
    And I navigate to "Reports > Live logs" in site administration
    And I should not see "Timelimit exceeded, response submitted by javascript"
    And I log out

  @javascript
  Scenario: Wait till timelimit expires then auto-submit via js
    Given the following "activities" exist:
        | activity | idnumber | course | name        | intro             | assignsubmission_timedonline_enabled | assignsubmission_timedonline_timelimit | id_assignsubmission_timedonline_questiontext | id_submissiondrafts | blindmarking |
        | assign   | assign1  | C1     | Assignment1 | Exam Instructions | 1                                    | .25                                    | Answer this question                         | Yes                 | 1            |
    # The timelimit of .1 means 5 seconds to submit

    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Assignment1"
    And I navigate to "Edit settings" in current page administration
    And I expand all fieldsets
    And I set the field "Text of question seen by students" to "Answer this question"
    And I press "Save and return to course"
    And I log out

    # Check that autosubmit works
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Assignment1"
    And I press "Add submission"
    And I set the field "Add your response" to "Student response"
    And I wait "8" seconds
    # This shows that autosubmit has happened
    Then I should see "Submitted for grading"
    # This shows that the response is visible (but not editable)
    And I should see "Student response"
    And I should not see "Edit submission"
    And I should not see "Remove submission"
    And I should not see "Add submission"
    And I log out

    # Check that an updated response is autosubmitted
    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Assignment1"
    And I press "Add submission"
    And I set the field "Add your response" to "Student response"
    When I press "Save changes"
    And I press "Edit submission"
    And I set the field "Add your response" to "Updated response"
    And I wait "15" seconds
    # This shows that autosubmit has happened
    Then I should see "Submitted for grading"
    # This shows that the response is visible (but not editable)
    And I should see "Updated response"
    And I should not see "Edit submission"
    And I should not see "Remove submission"
    And I log out

    # Check that an updated response is autosubmitted
    # When the user leaves the page and returns when time has run out
    # But before the submission sweep cron task has run
    When I log in as "student3"
    And I am on "Course 1" course homepage
    And I follow "Assignment1"
    And I press "Add submission"
    And I set the field "Add your response" to "Student response"
    And I am on "Course 1" course homepage
    And I wait "8" seconds
    And I follow "Assignment1"
    And I press "Add submission"
    # It takes a few seconds for the ajaxrequest to complete
    And I wait "3" seconds
    Then I should see "Submitted for grading"
    And I log out
    When I log in as "admin"
    And I navigate to "Reports > Live logs" in site administration
    And I should see "Timelimit exceeded, response submitted by javascript"
