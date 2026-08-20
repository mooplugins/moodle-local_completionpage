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
 * feedback_link for local_completionpage.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_completionpage\service;

/**
 * Resolves linked mod_feedback activity and submission state.
 */
class feedback_link {
    /**
     * Build section display data for the completion page.
     *
     * When $feedbackcmid is set, that activity is used. Otherwise the first
     * visible, open Feedback activity in the course is auto-selected.
     *
     * @param int $courseid
     * @param int $userid
     * @param int $feedbackcmid Explicit override (0 = auto-detect)
     * @return array{showsection: bool, alreadysubmitted: bool, feedbackurl: string, feedbackname: string}
     */
    public static function get_section_data(int $courseid, int $userid, int $feedbackcmid = 0): array {
        $empty = [
            'showsection' => false,
            'alreadysubmitted' => false,
            'feedbackurl' => '',
            'feedbackname' => '',
        ];

        global $CFG, $DB;

        // Soft-guard: Feedback module may be missing or disabled on generic Moodle sites.
        if (!file_exists($CFG->dirroot . '/mod/feedback/lib.php')) {
            return $empty;
        }
        if (!$DB->record_exists('modules', ['name' => 'feedback', 'visible' => 1])) {
            return $empty;
        }

        if (!function_exists('feedback_is_already_submitted')) {
            require_once($CFG->dirroot . '/mod/feedback/lib.php');
        }

        if (!class_exists('\mod_feedback_completion')) {
            $completionclass = $CFG->dirroot . '/mod/feedback/classes/completion.php';
            if (!file_exists($completionclass)) {
                return $empty;
            }
            require_once($completionclass);
        }

        $cmids = [];
        if ($feedbackcmid > 0) {
            $cmids[] = $feedbackcmid;
        } else {
            $cmids = self::get_candidate_feedback_cmids($courseid, $userid);
        }

        foreach ($cmids as $cmid) {
            $section = self::build_section_for_cm($courseid, $userid, (int) $cmid);
            if ($section['showsection']) {
                return $section;
            }
        }

        return $empty;
    }

    /**
     * Visible Feedback course-module IDs in course order.
     *
     * @param int $courseid
     * @param int $userid
     * @return int[]
     */
    public static function get_candidate_feedback_cmids(int $courseid, int $userid): array {
        try {
            $modinfo = get_fast_modinfo($courseid, $userid);
        } catch (\Exception $e) {
            return [];
        }

        $instances = $modinfo->get_instances_of('feedback');
        if (empty($instances)) {
            return [];
        }

        $cmids = [];
        foreach ($instances as $cm) {
            if ($cm->uservisible) {
                $cmids[] = (int) $cm->id;
            }
        }

        return $cmids;
    }

    /**
     * Build feedback section data for a specific course module.
     *
     * @param int $courseid
     * @param int $userid
     * @param int $feedbackcmid
     * @return array{showsection: bool, alreadysubmitted: bool, feedbackurl: string, feedbackname: string}
     */
    private static function build_section_for_cm(int $courseid, int $userid, int $feedbackcmid): array {
        global $DB;

        $empty = [
            'showsection' => false,
            'alreadysubmitted' => false,
            'feedbackurl' => '',
            'feedbackname' => '',
        ];

        try {
            $modinfo = get_fast_modinfo($courseid, $userid);
            $cm = $modinfo->get_cm($feedbackcmid);
        } catch (\Exception $e) {
            return $empty;
        }

        if ($cm->modname !== 'feedback' || !$cm->uservisible) {
            return $empty;
        }

        $feedback = $DB->get_record('feedback', ['id' => $cm->instance], '*', MUST_EXIST);
        $completion = new \mod_feedback_completion($feedback, $cm, $courseid);

        if (!$completion->is_open() || !$completion->can_complete()) {
            return $empty;
        }

        $alreadysubmitted = feedback_is_already_submitted($feedback->id, $courseid);
        if ($alreadysubmitted) {
            return [
                'showsection' => true,
                'alreadysubmitted' => true,
                'feedbackurl' => '',
                'feedbackname' => format_string($feedback->name),
            ];
        }

        if (!$completion->can_submit()) {
            return $empty;
        }

        return [
            'showsection' => true,
            'alreadysubmitted' => false,
            'feedbackurl' => (new \moodle_url('/mod/feedback/complete.php', [
                'id' => $cm->id,
                'courseid' => $courseid,
            ]))->out(false),
            'feedbackname' => format_string($feedback->name),
        ];
    }
}
