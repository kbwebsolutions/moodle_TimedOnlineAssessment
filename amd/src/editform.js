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
*
*
* @package assignsubmission_timedonline
* @author  Marcus Green
* @copyright 2021 Titus {@link http://www.tituslearning.com}
* @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
export const init = () => {
    var checkbox = document.getElementById("id_assignsubmission_timedonline_enabled");
    var editor = document.getElementById("fitem_id_assignsubmission_timedonline_questiontext");
    if(checkbox.checked == false){
        editor.hidden = true;
    } else {
        // Require students to click the submit button.
        document.getElementById('id_submissiondrafts').value = 1;
    }
    checkbox.addEventListener("click", function() {
        if(checkbox.checked == false){
            editor.hidden = true;
            //Require students to click the submit button.
            document.getElementById('id_submissiondrafts').value = 1;
        } else {
            editor.hidden = false;
        }
    });
};