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
 * config_resolver for local_completionpage.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_completionpage\service;

use local_completionpage\constants;

/**
 * Merges site defaults with per-course overrides.
 */
class config_resolver {
    /**
     * Resolve effective plugin configuration for a course.
     *
     * @param int $courseid
     * @return \stdClass
     */
    public static function resolve(int $courseid): \stdClass {
        $course = course_config::get($courseid);

        return (object) [
            'enabled' => self::resolve_bool('enable', $course->enabled),
            'customheadline' => $course->customheadline,
            'custommessage' => $course->custommessage,
            'feedbackcmid' => $course->feedbackcmid ? (int) $course->feedbackcmid : 0,
            'suggestedcourses' => self::parse_course_ids($course->suggestedcourses),
            'sectionmessage' => self::resolve_bool('section_message', $course->sectionmessage),
            'sectioncertificates' => self::resolve_bool('section_certificates', $course->sectioncertificates),
            'sectionfeedback' => self::resolve_bool('section_feedback', $course->sectionfeedback),
            'sectionsuggested' => self::resolve_bool('section_suggested', $course->sectionsuggested),
            'sectionexit' => self::resolve_bool('section_exit', $course->sectionexit),
            'sectionachievements' => self::resolve_bool('section_achievements', $course->sectionachievements),
        ];
    }

    /**
     * Resolve a tri-state course override against a site default.
     *
     * @param string $configkey
     * @param int $override
     * @return bool
     */
    private static function resolve_bool(string $configkey, int $override): bool {
        // DB drivers may return numeric columns as strings; cast before strict compare.
        $override = (int) $override;

        if ($override === constants::ENABLED) {
            return true;
        }
        if ($override === constants::DISABLED) {
            return false;
        }

        return (bool) get_config('local_completionpage', $configkey);
    }

    /**
     * Parse a comma-separated course ID list.
     *
     * @param string|null $raw
     * @return int[]
     */
    public static function parse_course_ids(?string $raw): array {
        if (empty($raw)) {
            return [];
        }

        $ids = [];
        foreach (explode(',', $raw) as $part) {
            $id = (int) trim($part);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Format course IDs as a comma-separated string.
     *
     * @param int[] $ids
     * @return string
     */
    public static function format_course_ids(array $ids): string {
        $ids = array_values(array_filter(array_map('intval', $ids), static function (int $id): bool {
            return $id > 0;
        }));

        return implode(',', $ids);
    }
}
