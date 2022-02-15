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
 * The submission_swept event
 *
 * @package    assignsubmission_timedonline
 * @copyright  Titus
 * @author     Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace assignsubmission_timedonline\event;

defined('MOODLE_INTERNAL') || die();
/**
 * The timedonline submission swept event class
 * Log things that happen when
 * the cron task is triggered and unsubmitted text
 * is "swept" or auto-submitted by the cron
 * @author    Marcus Green
 * @copyright 2021 Titus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class submission_swept extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    public static function get_name() {
        return get_string('submissionswept', 'assignsubmission_timedonline');
    }

    public function get_description() {
        return $this->other;
    }

}
