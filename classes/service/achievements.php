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
 * achievements for local_completionpage.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_completionpage\service;

use context_course;
use context_system;
use local_completionpage\constants;
use moodle_url;

/**
 * Builds achievements data (badges, grade, time spent) for the completion page.
 *
 * Time spent is read from the configured site source:
 * - local_timespent (recommended default)
 * - logstore_standard session-gap estimate
 */
class achievements {
    /**
     * Build section display data for the completion page.
     *
     * Only includes grade, time spent, and badges when real values exist.
     * Missing items are omitted (no empty-state messages).
     *
     * @param \stdClass $course
     * @param int $userid
     * @return array{
     *   showsection: bool,
     *   showcompletedon: bool,
     *   completedon: string,
     *   hasgrade: bool,
     *   grade: string,
     *   hastimespent: bool,
     *   timespent: string,
     *   hasbadges: bool,
     *   badges: array<int, array<string, mixed>>
     * }
     */
    public static function get_section_data(\stdClass $course, int $userid): array {
        global $DB;

        $data = [
            'showsection' => false,
            'showcompletedon' => false,
            'completedon' => '',
            'hasgrade' => false,
            'grade' => '',
            'hastimespent' => false,
            'timespent' => '',
            'hasbadges' => false,
            'badges' => [],
        ];

        $completion = $DB->get_record('course_completions', [
            'userid' => $userid,
            'course' => (int) $course->id,
        ]);
        if ($completion && !empty($completion->timecompleted)) {
            $data['showcompletedon'] = true;
            $data['completedon'] = userdate(
                (int) $completion->timecompleted,
                get_string('strftimedatefullshort')
            );
        }

        $grade = self::get_course_grade_display($course, $userid);
        if ($grade !== null) {
            $data['hasgrade'] = true;
            $data['grade'] = $grade;
        }

        $timespentseconds = self::get_timespent_seconds((int) $course->id, $userid);
        if ($timespentseconds !== null && $timespentseconds > 0) {
            $data['hastimespent'] = true;
            $data['timespent'] = format_time($timespentseconds);
        }

        $badges = self::get_course_badges($course, $userid);
        if (!empty($badges)) {
            $data['hasbadges'] = true;
            $data['badges'] = $badges;
        }

        $data['showsection'] = $data['showcompletedon']
            || $data['hasgrade']
            || $data['hastimespent']
            || $data['hasbadges'];

        return $data;
    }

    /**
     * Course total grade string, or null when unavailable / hidden / empty.
     *
     * @param \stdClass $course
     * @param int $userid
     * @return string|null
     */
    public static function get_course_grade_display(\stdClass $course, int $userid): ?string {
        global $CFG, $USER;

        // Respect course gradebook visibility for learners.
        if (empty($course->showgrades)) {
            return null;
        }

        $context = context_course::instance((int) $course->id);
        $isown = ((int) $USER->id === $userid);
        if (!$isown && !has_capability('moodle/grade:viewall', $context)) {
            return null;
        }

        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->dirroot . '/grade/querylib.php');

        $grade = grade_get_course_grade($userid, (int) $course->id);
        if (!$grade || empty($grade->item)) {
            return null;
        }

        if (!empty($grade->hidden) && !has_capability('moodle/grade:viewhidden', $context)) {
            return null;
        }

        if ($grade->grade === null || $grade->grade === '' || !is_numeric($grade->grade)) {
            return null;
        }

        $display = trim((string) ($grade->str_long_grade ?? $grade->str_grade ?? ''));
        if ($display === '' || $display === '-') {
            return null;
        }

        return $display;
    }

    /**
     * Badges issued to the user for this course, plus site badges they hold.
     *
     * Course badges are listed first. Site badges are included so learners still
     * see awards when only site-level badges exist.
     *
     * @param \stdClass $course
     * @param int $userid
     * @return array<int, array{name: string, description: string, imageurl: string, dateissued: string}>
     */
    public static function get_course_badges(\stdClass $course, int $userid): array {
        global $CFG;

        if (empty($CFG->enablebadges)) {
            return [];
        }

        require_once($CFG->libdir . '/badgeslib.php');

        // All issued badges for the user, then keep this course's badges + site badges.
        $all = badges_get_user_badges($userid, 0) ?: [];
        $records = [];
        foreach ($all as $id => $badge) {
            $badgecourse = !empty($badge->courseid) ? (int) $badge->courseid : 0;
            if ($badgecourse === (int) $course->id || $badgecourse === 0) {
                $records[$id] = $badge;
            }
        }

        if (empty($records)) {
            return [];
        }

        $badges = [];
        foreach ($records as $record) {
            $context = !empty($record->courseid)
                ? context_course::instance((int) $record->courseid)
                : context_system::instance();

            $imageurl = moodle_url::make_pluginfile_url(
                $context->id,
                'badges',
                'badgeimage',
                $record->id,
                '/',
                'f2',
                false
            )->out(false);

            $badges[] = [
                'name' => format_string($record->name),
                'description' => format_string($record->description ?? ''),
                'imageurl' => $imageurl,
                'dateissued' => !empty($record->dateissued)
                    ? userdate((int) $record->dateissued, get_string('strftimedatefullshort'))
                    : '',
                'hasdescription' => trim(strip_tags($record->description ?? '')) !== '',
                'hasdate' => !empty($record->dateissued),
            ];
        }

        return $badges;
    }

    /**
     * Resolve time spent in seconds from the configured source, or null if unknown.
     *
     * Sources (site setting local_completionpage/timespent_source):
     * - timespent: local_timespent aggregate (default)
     * - logs: session-gap estimate from logstore_standard_log
     *
     * @param int $courseid
     * @param int $userid
     * @return int|null
     */
    public static function get_timespent_seconds(int $courseid, int $userid): ?int {
        $source = (string) get_config('local_completionpage', 'timespent_source');
        if (
            $source !== constants::TIMESPENT_SOURCE_LOGS
            && $source !== constants::TIMESPENT_SOURCE_TIMESPENT
        ) {
            $source = constants::TIMESPENT_SOURCE_TIMESPENT;
        }

        if ($source === constants::TIMESPENT_SOURCE_LOGS) {
            return self::get_timespent_from_logs($courseid, $userid);
        }

        // Fall back to log estimate when local_timespent is not installed.
        if (!self::is_timespent_plugin_available()) {
            return self::get_timespent_from_logs($courseid, $userid);
        }

        return self::get_timespent_from_timespent($courseid, $userid);
    }

    /**
     * Whether the recommended local_timespent plugin is available.
     *
     * @return bool
     */
    public static function is_timespent_plugin_available(): bool {
        global $CFG, $DB;

        if (!file_exists($CFG->dirroot . '/local/timespent/version.php')) {
            return false;
        }

        if (!optional_integrations::is_plugin_installed_and_enabled('local_timespent')) {
            return false;
        }

        return $DB->get_manager()->table_exists('local_timespent_aggregate');
    }

    /**
     * Read time spent seconds from local_timespent aggregates.
     *
     * @param int $courseid
     * @param int $userid
     * @return int|null
     */
    private static function get_timespent_from_timespent(int $courseid, int $userid): ?int {
        global $DB;

        if (!self::is_timespent_plugin_available()) {
            return null;
        }

        $record = $DB->get_record('local_timespent_aggregate', [
            'register' => $courseid,
            'userid' => $userid,
            'grandtotal' => 1,
        ]);

        if (!$record || empty($record->duration)) {
            return null;
        }

        $duration = (int) $record->duration;
        return $duration > 0 ? $duration : null;
    }

    /**
     * Estimate time spent from standard log events using a session-gap model.
     *
     * Consecutive course log events within TIMESPENT_SESSION_TIMEOUT are treated
     * as one session. When the gap is larger, the previous session is closed and
     * half the timeout is added (same approach used by local_timespent).
     *
     * This is an estimate only — log volume and quiet time affect accuracy.
     *
     * @param int $courseid
     * @param int $userid
     * @return int|null
     */
    private static function get_timespent_from_logs(int $courseid, int $userid): ?int {
        global $DB;

        if (!$DB->get_manager()->table_exists('logstore_standard_log')) {
            return null;
        }

        $times = $DB->get_fieldset_sql(
            "SELECT timecreated
               FROM {logstore_standard_log}
              WHERE userid = :userid
                AND courseid = :courseid
           ORDER BY timecreated ASC",
            [
                'userid' => $userid,
                'courseid' => $courseid,
            ]
        );

        if (empty($times)) {
            return null;
        }

        $timeout = constants::TIMESPENT_SESSION_TIMEOUT;
        $total = 0;
        $sessionstart = null;
        $prev = null;

        foreach ($times as $timecreated) {
            $timecreated = (int) $timecreated;
            if ($sessionstart === null) {
                $sessionstart = $timecreated;
                $prev = $timecreated;
                continue;
            }

            if (($timecreated - $prev) > $timeout) {
                // Close previous session and start a new one on this event.
                $total += max(0, ($prev - $sessionstart) + (int) ($timeout / 2));
                $sessionstart = $timecreated;
            }
            $prev = $timecreated;
        }

        // Close the final session if it has ended (last event older than timeout).
        if ($sessionstart !== null && $prev !== null && (time() - $prev) > $timeout) {
            $total += max(0, ($prev - $sessionstart) + (int) ($timeout / 2));
        } else if ($sessionstart !== null && $prev !== null && $prev > $sessionstart) {
            // Active/recent session — count observed span only.
            $total += max(0, $prev - $sessionstart);
        }

        return $total > 0 ? $total : null;
    }
}
