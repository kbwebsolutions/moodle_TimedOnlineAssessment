@assignsubmission_timedonline @timedonline_backup_restore
Feature: Preserve settings when course goes through backup and restore
    In order to reuse assignments with this plugin
    I need be able to backup courses courses with this plugin.

  Background:
    Given the following "courses" exist:
        | fullname | shortname | format |
        | Course 1 | C1        | topics |
    Given the following "activities" exist:
        | activity | idnumber | course | name        | intro             | assignsubmission_timedonline_enabled | id_assignsubmission_timedonline_questiontext | id_submissiondrafts | blindmarking |
        | assign   | assign1  | C1     | Assignment1 | Exam Instructions | 1                                    | Answer this question                         | Yes                 | 1            |

    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Assignment1"
    And I navigate to "Edit settings" in current page administration
    And I expand all fieldsets
    And I set the field "Time limit (minutes)" to "1"
    And I set the field "Text of question seen by students" to "Answer this question"
    And I press "Save and return to course"

  @javascript
  Scenario: Test that backed up that timedonline settings are restored correctly
    When I backup "Course 1" course using this options:
        | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
        | Schema | Course name | Course 2 |
    And I follow "Assignment1"
    And I navigate to "Edit settings" in current page administration
    And I expand all fieldsets
    And the field "Time limit (minutes)" matches value "1"
    And the field "Text of question seen by students" matches value "Answer this question"
