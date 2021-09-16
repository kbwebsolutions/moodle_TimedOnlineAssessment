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
 * The auto_submit event
 *
 * @package    assignsubmission_timedonline
 * @copyright  Titus 2021
 * @author     Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace assignsubmission_timedonline\event;

defined('MOODLE_INTERNAL') || die();
/**
 * The timedonline submission auto_submit class
 * assignmet submitted by javascript when the time
 * runs out.
 * @author    Marcus Green
 * @copyright 2021 Titus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class auto_submitted extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    public static function get_name() {
        return get_string('autosubmit', 'assignsubmission_timedonline');
    }

    public function get_description() {
        return $this->other;
    }

}