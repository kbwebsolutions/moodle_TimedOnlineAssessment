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
 * The assignsubmission_timedonline submission_created event.
 *
 * @package    assignsubmission_timedonline
 * @author     Marcus Green
 * @copyright  Titus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace assignsubmission_timedonline\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The assignsubmission_timedonline submission_created event class.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 * }
 *
 * @package    assignsubmission_timedonline
 * @author     Marcus Green
 * @copyright  Titus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_started extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $descriptionstring = "The user with id '$this->userid' started an attempt at a Timed Online assignment";
            return $descriptionstring;
    }
    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventattemptstarted', 'assignsubmission_timedonline');
    }
    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        $params = [];
        $params['id'] = $this->contextinstanceid;
        return new \moodle_url("/mod/assign/view.php", $params);
    }

    public static function get_objectid_mapping() {
        // No mapping available for 'assignsubmission_timedonline'.
        return array('db' => 'assignsubmission_timedonline', 'restore' => \core\event\base::NOT_MAPPED);
    }
}
