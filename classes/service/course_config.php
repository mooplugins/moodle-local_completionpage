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
 * course_config for local_completionpage.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_completionpage\service;

use local_completionpage\constants;

/**
 * Loads and saves per-course configuration records.
 */
class course_config {
    /**
     * Load per-course configuration, with inherit defaults when missing.
     *
     * @param int $courseid
     * @return \stdClass
     */
    public static function get(int $courseid): \stdClass {
        global $DB;

        $record = $DB->get_record('local_completionpage_course', ['courseid' => $courseid]);
        if (!$record) {
            $record = (object) [
                'id' => 0,
                'courseid' => $courseid,
                'enabled' => constants::INHERIT,
                'redirectoverride' => constants::INHERIT,
                'customheadline' => null,
                'custommessage' => null,
                'feedbackcmid' => null,
                'suggestedcourses' => null,
                'sectionmessage' => constants::INHERIT,
                'sectioncertificates' => constants::INHERIT,
                'sectionfeedback' => constants::INHERIT,
                'sectionsuggested' => constants::INHERIT,
                'sectionexit' => constants::INHERIT,
                'sectionachievements' => constants::INHERIT,
                'timemodified' => 0,
            ];
        } else {
            $record->enabled = (int) $record->enabled;
            $record->redirectoverride = (int) $record->redirectoverride;
            $record->feedbackcmid = $record->feedbackcmid !== null ? (int) $record->feedbackcmid : null;
            $record->sectionmessage = (int) $record->sectionmessage;
            $record->sectioncertificates = (int) $record->sectioncertificates;
            $record->sectionfeedback = (int) $record->sectionfeedback;
            $record->sectionsuggested = (int) $record->sectionsuggested;
            $record->sectionexit = (int) $record->sectionexit;
            $record->sectionachievements = isset($record->sectionachievements)
                ? (int) $record->sectionachievements
                : constants::INHERIT;
            $record->timemodified = (int) $record->timemodified;
        }

        return $record;
    }

    /**
     * Save per-course configuration.
     *
     * @param int $courseid
     * @param \stdClass $data
     */
    public static function save(int $courseid, \stdClass $data): void {
        global $DB;

        $existing = $DB->get_record('local_completionpage_course', ['courseid' => $courseid]);
        $record = $existing ?: new \stdClass();
        $record->courseid = $courseid;
        $record->enabled = (int) ($data->enabled ?? constants::INHERIT);
        $record->redirectoverride = (int) ($data->redirectoverride ?? constants::INHERIT);
        $record->customheadline = $data->customheadline ?? null;
        $record->custommessage = $data->custommessage ?? null;
        $record->feedbackcmid = !empty($data->feedbackcmid) ? (int) $data->feedbackcmid : null;
        $record->suggestedcourses = $data->suggestedcourses ?? null;
        $record->sectionmessage = (int) ($data->sectionmessage ?? constants::INHERIT);
        $record->sectioncertificates = (int) ($data->sectioncertificates ?? constants::INHERIT);
        $record->sectionfeedback = (int) ($data->sectionfeedback ?? constants::INHERIT);
        $record->sectionsuggested = (int) ($data->sectionsuggested ?? constants::INHERIT);
        $record->sectionexit = (int) ($data->sectionexit ?? constants::INHERIT);
        $record->sectionachievements = (int) ($data->sectionachievements ?? constants::INHERIT);
        $record->timemodified = time();

        if ($existing) {
            $DB->update_record('local_completionpage_course', $record);
        } else {
            $DB->insert_record('local_completionpage_course', $record);
        }
    }

    /**
     * Delete per-course configuration for a course.
     *
     * @param int $courseid
     */
    public static function delete_for_course(int $courseid): void {
        global $DB;

        message_content::delete_course_files($courseid);
        $DB->delete_records('local_completionpage_course', ['courseid' => $courseid]);
    }

    /**
     * Return Feedback activity options for course settings.
     *
     * @param int $courseid
     * @return array<int, string> cmid => label
     */
    public static function get_feedback_activity_options(int $courseid): array {
        global $DB;

        $modid = $DB->get_field('modules', 'id', ['name' => 'feedback']);
        if (!$modid) {
            return [];
        }

        $sql = "SELECT cm.id, f.name
                  FROM {course_modules} cm
                  JOIN {feedback} f ON f.id = cm.instance
                 WHERE cm.course = :courseid
                   AND cm.module = :modid
                   AND cm.deletioninprogress = 0
              ORDER BY f.name ASC";

        $records = $DB->get_records_sql($sql, ['courseid' => $courseid, 'modid' => $modid]);
        $options = [];
        foreach ($records as $record) {
            $options[(int) $record->id] = format_string($record->name);
        }

        return $options;
    }
}
