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
 * finish_button for local_completionpage.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_completionpage\service;

/**
 * Builds the Finish activity-navigation link for the last course activity.
 */
class finish_button {
    /**
     * Return an action link to the completion page when the learner may finish.
     *
     * @param \stdClass $course
     * @param int $userid
     * @return \action_link|null
     */
    public static function get_action_link(\stdClass $course, int $userid): ?\action_link {
        global $OUTPUT;

        if (!completion_gate::can_view_page($course, $userid)) {
            return null;
        }

        $url = completion_gate::page_url((int) $course->id);
        $label = get_string('finishbutton', 'local_completionpage') . ' ' . $OUTPUT->rarrow();

        $attributes = [
            'class' => 'btn btn-link',
            'id' => 'finish-activity-link',
        ];

        return new \action_link($url, $label, null, $attributes);
    }
}
