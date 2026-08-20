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
 * completion_gate for local_completionpage.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_completionpage\service;

/**
 * Determines whether the completion page should be shown.
 */
class completion_gate {
    /**
     * Whether the user may view the completion page for the course.
     *
     * @param \stdClass $course
     * @param int $userid
     * @return bool
     */
    public static function can_view_page(\stdClass $course, int $userid): bool {
        global $CFG;

        if (!isloggedin() || isguestuser()) {
            return false;
        }

        $config = config_resolver::resolve((int) $course->id);
        if (!$config->enabled) {
            return false;
        }

        require_once($CFG->libdir . '/completionlib.php');
        $completion = new \completion_info($course);
        if (!$completion->is_enabled()) {
            return false;
        }

        return $completion->is_course_complete($userid);
    }

    /**
     * Return a human-readable reason when the page is blocked.
     *
     * @param \stdClass $course
     * @param int $userid
     * @return string|null Human-readable block reason.
     */
    public static function get_block_reason(\stdClass $course, int $userid): ?string {
        global $CFG;

        $config = config_resolver::resolve((int) $course->id);
        if (!$config->enabled) {
            return get_string('errornotenabled', 'local_completionpage');
        }

        require_once($CFG->libdir . '/completionlib.php');
        $completion = new \completion_info($course);
        if (!$completion->is_enabled()) {
            return get_string('errorcompletiondisabled', 'local_completionpage');
        }

        if (!$completion->is_course_complete($userid)) {
            return get_string('errornotcomplete', 'local_completionpage');
        }

        return null;
    }

    /**
     * Build the completion page URL for a course.
     *
     * @param int $courseid
     * @return \moodle_url
     */
    public static function page_url(int $courseid): \moodle_url {
        return new \moodle_url('/local/completionpage/view.php', ['courseid' => $courseid]);
    }

    /**
     * Return the course completion timestamp when available.
     *
     * @param int $courseid
     * @param int $userid
     * @return int|null
     */
    public static function get_completion_timestamp(int $courseid, int $userid): ?int {
        global $DB;

        $record = $DB->get_record('course_completions', [
            'course' => $courseid,
            'userid' => $userid,
        ], 'timecompleted', IGNORE_MISSING);

        if (!$record || empty($record->timecompleted)) {
            return null;
        }

        return (int) $record->timecompleted;
    }
}
