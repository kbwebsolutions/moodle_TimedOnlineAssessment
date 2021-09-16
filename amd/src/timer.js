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
* @author  Marcus Green and Steve Anatai
* @copyright 2021 Titus {@link http://www.tituslearning.com}
* @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
import * as Str from 'core/str';
import * as config from 'core/config';
import $ from 'jquery';
export const init = (endTime, assignmentId, instanceId) => {
    var isSubmitted = false;
    function makeTimer(endTime) {

        var endTime = endTime;
        var now = new Date();

        var now = (Date.parse(now) / 1000);
        var timeLeft = endTime - now;

        if (timeLeft < 0 && (!isSubmitted)) {
            updateClock(0, 0);
            window.onbeforeunload = null;
            var responseText = document.getElementById("id_responsetext_editor").value;
            isSubmitted = true;
            doSubmit(assignmentId, responseText);
            return;
        }
        if (isSubmitted) {
            return;
        }
        // Days and hours are not used at the moment.
        var days = Math.floor(timeLeft / 86400);
        var hours = Math.floor((timeLeft - (days * 86400)) / 3600);

        var minutes = Math.floor(timeLeft / 60);
        var seconds = Math.floor((timeLeft - (minutes * 60)));

        if (hours < "10") {
            hours = "0" + hours;
        }
        if (minutes < "10") {
            minutes = "0" + minutes;
        }
        if (seconds < "10") {
            seconds = "0" + seconds;
        }
        updateClock(minutes, seconds);
    }
    function updateClock(minutes, seconds) {
        $.when(Str.get_string('minutes', 'assignsubmission_timedonline')).done(function (localisedString) {
            document.getElementById("minutes").innerHTML = minutes + "<span>" + localisedString + "</span>";
        });
        $.when(Str.get_string('seconds', 'assignsubmission_timedonline')).done(function (localisedString) {
            document.getElementById("seconds").innerHTML = seconds + "<span>" + localisedString + "</span>";
        });

    }
    function doSubmit(assignmentId, responseText) {
        $.ajax({
            url: config.wwwroot + "/mod/assign/submission/timedonline/autosave.php",
            type: "post",
            dataType: "json",
            data: {
                responsetext: responseText,
                assignmentid: assignmentId
            },
            complete: function () {
                window.location = config.wwwroot + "/mod/assign/view.php?id=" + instanceId;
            }
        });
    }
    setInterval(function () { makeTimer(endTime, assignmentId, instanceId); }, 0);
};