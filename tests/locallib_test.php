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
 * Contains the event tests for the plugin.
 *
 * @package   assignsubmission_onlinetext
 * @copyright Titus
 * @author    Marcus Green
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');
use assignsubmission_timedonline\observer;
class locallib_test extends advanced_testcase {

    // Use the generator helper.
    use mod_assign_test_generator;

    /**
     * Test that end time is calculated with an extreme number of minutes
     * 9999 minutes is 166 hours
     */
    public function test_get_end_time() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $timelimit = 9999;
        $params = [
               'assignsubmission_timedonline_enabled' => 1,
               'assignsubmission_timedonline_timelimit' => $timelimit
         ];
        $assign = $this->create_instance($course, $params);
        $plugin = $assign->get_submission_plugin_by_type('timedonline');

        $this->setUser($student->id);
        // Calling get_endtime writes the record to say the attempt has been started.
        // Ths assume close to zero execution time so might break in a debugger.
        $this->assertEquals((time() + ($timelimit * 60)), $plugin->get_endtime());

    }

}
