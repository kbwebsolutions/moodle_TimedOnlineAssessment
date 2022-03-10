<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Test autosave which is triggered from the timer via js.
 *
 * @package   assignsubmission_onlinetext
 * @copyright Titus
 * @author    Marcus Green
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace assignsubmission_timedonline;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');

class autosave_test extends \advanced_testcase {

    // Use the generator helper.
    use \mod_assign_test_generator;

    /**
     * Test that the response text is stored including its html tags
     */
    public function test_autosave() {
        global $DB, $CFG;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student1 = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $params = [
               'assignsubmission_timedonline_enabled' => 1,
               'assignsubmission_timedonline_timelimit' => 1
         ];
        $assign = $this->create_instance($course, $params);

        $this->setAdminUser();

        $submission1 = $assign->get_user_submission($student1->id, true);

        // Ensure they have run out of time so the draft will be submitted.
        $submissionparams = [
            'id' => $submission1->id,
            'timecreated' => (time() - 1626948853)
        ];
        $DB->update_record('assign_submission', $submissionparams);
        $submissiontext = '<p>My submissiontext</p>';
        $_POST['responsetext'] = $submissiontext;
        $_POST['assignmentid'] = $submission1->assignment;
        $this->setUser($student1);

        require_once($CFG->dirroot . '/mod/assign/submission/timedonline/autosave.php');

        $submission = $DB->get_records('assignsubmission_timedonline');
        $this->assertEquals($submissiontext, reset($submission)->responsetext);

    }

}
