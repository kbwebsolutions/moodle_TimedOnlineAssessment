@gradingform @timedonline_passfailrubric @pfr_edit
Feature: Pass Fail Rubric advanced grading forms can be created and edited
    In order to grade with PassFailRubric and timelimit
    add PassFailRubric with criteria to a timedonline assign instance

  @javascript
  Scenario: I can use passfailrubric grading to grade and edit them later updating students grades
    Given the following "users" exist:
        | username | firstname | lastname | email                |
        | teacher1 | Teacher   | 1        | teacher1@example.com |
        | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
        | fullname | shortname | format |
        | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
        | user     | course | role           |
        | teacher1 | C1     | editingteacher |
        | student1 | C1     | student        |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

    # Create an assignment that uses the TimedOnline submission type
    # And the PassFailRubric grading method and scale.
    And I add a "Assignment" to section "1" and I fill the form with:
        | Assignment name                              | Assignment1          |
        | Description                                  | Exam Instructions    |
        | assignsubmission_timedonline_enabled         | 1                    |
        | Time limit (minutes)                         | .5                   |
        | id_assignsubmission_timedonline_questiontext | Answer this question |
        | id_submissiondrafts                          | Yes                  |
        | blindmarking                                 | 1                    |
        | id_grade_modgrade_type                       | Scale                |
        | id_grade_modgrade_scale                      | refer_fail_pass      |
        | Grading method                               | Pass Fail Rubric     |

    And I go to "Assignment1" advanced grading definition page
    And I set the following fields to these values:
        | Name        | Assignment1          |
        | Description | PFR test description |
    And I click on "Click to edit criterion" "text"
    And I set the field "passfailrubric[criteria][NEWID1][description]" to "Criteria 1"
    And I click on "Add criterion" "button"
    And I set the field "passfailrubric[criteria][NEWID2][description]" to "Criteria 2"

    And I press "Save Pass Fail Rubric and make it ready"
    And I log out

    # Now test as a student
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Assignment1"

    And I should see "Exam Instructions"

    # Confirm that the student can see the PassFailRubric criteria
    And I should see "Criteria 1"
    And I should see "Criteria 2"
    When I press "Add submission"

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

