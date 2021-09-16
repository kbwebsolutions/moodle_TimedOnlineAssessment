@assignsubmission_timedonline @timedonline_reveal_question
Feature: In an assignment, show question after Add susubmission is clicked
    In order to constrain student time for attempt
    As a student
    I should not see the question to answer until after submit is clicked.
    Hide the metadata/exam instructions (from the description field), once
    submission is clicked.

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
  Scenario: Hide question till after Add submission
    Given the following "activities" exist:
        | activity | idnumber | course | name        | intro             | assignsubmission_timedonline_enabled | id_assignsubmission_timedonline_questiontext | id_submissiondrafts | blindmarking |
        | assign   | assign1  | C1     | Assignment1 | Exam Instructions | 1                                    | Answer this question                         | Yes                 | 1            |

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Assignment1"
    And I navigate to "Edit settings" in current page administration
    And I expand all fieldsets
    And I set the field "Time limit (minutes)" to "X"
    And I set the field "Text of question seen by students" to "Answer this question"
    And I press "Save and return to course"
    And I should see "- You must enter a number here."
    And I set the field "Time limit (minutes)" to "2"
    And I press "Save and return to course"
    And I log out

    # Check that the question is hidden until the attempt is started
    # And that "hand" submission works (as opposed to autosubmit)
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Assignment1"
    # The question is not in the html at this point
    And I should not see "Answer this question"
    And I should see "Exam Instructions"
    When I press "Add submission"
    Then I should see "Answer this question"
    # The exam instructions are hidden by javascript
    And I should not see "Exam Instructions"
    And I set the field "Add your response" to "Student response"
    When I press "Save changes"
    Then I should see "Student response"
    And I press "Edit submission"
    And I set the field "Add your response" to "Updated response"
    When I press "Save changes"
    And I press "Submit assignment"
    And I should see "Are you sure you want to submit your work for grading? You will not be able to make any more changes."
    And I press "Continue"

    Then I should see "Updated response"
    And I should see "Submitted for grading"
    And I should not see "Edit submission"
    And I should not see "Remove submission"
