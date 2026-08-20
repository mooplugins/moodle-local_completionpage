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
 * suggested_courses for local_completionpage.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_completionpage\service;

use context_course;
use moodle_url;

/**
 * Builds suggested course card data for the completion page.
 */
class suggested_courses {
    /**
     * Build suggested course card data from course IDs.
     *
     * When no IDs are configured, visible courses from the same category are used.
     *
     * @param int[] $courseids
     * @param int $currentcourseid
     * @return array<int, array<string, mixed>>
     */
    public static function get_cards(array $courseids, int $currentcourseid = 0): array {
        if (empty($courseids) && $currentcourseid) {
            $courseids = self::get_same_category_courseids($currentcourseid);
        }

        if (empty($courseids)) {
            return [];
        }

        $cards = [];
        foreach ($courseids as $courseid) {
            try {
                $course = get_course($courseid);
            } catch (\Exception $e) {
                continue;
            }

            if (!$course->visible) {
                continue;
            }
            if ($currentcourseid && (int) $course->id === $currentcourseid) {
                continue;
            }

            $cards[] = self::export_landing_card($course);
        }

        return $cards;
    }

    /**
     * Card data matching the landing-page related-course card.
     *
     * @param \stdClass $course
     * @return array<string, mixed>
     */
    public static function export_landing_card(\stdClass $course): array {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . '/course/lib.php');

        $context = context_course::instance((int) $course->id);
        $listelement = new \core_course_list_element($course);

        if (!empty($CFG->courselanding)) {
            $url = (new moodle_url('/course/details.php', ['id' => $course->id]))->out(false);
        } else {
            $url = (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false);
        }

        $imgurl = '';
        foreach ($listelement->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                $imgurl = moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    null,
                    $file->get_filepath(),
                    $file->get_filename()
                )->out(false);
                break;
            }
        }

        $categoryname = '';
        $category = \core_course_category::get($course->category, IGNORE_MISSING);
        if ($category) {
            $categoryname = $category->get_formatted_name();
        }

        $enrolmethod = [
            'ecommerce' => [],
            'button' => [],
            'isenrolled' => is_enrolled($context, $USER->id, '', true),
        ];

        if (
            optional_integrations::is_ecommerce_available()
            && method_exists('\tool_ecommerce\helper', 'course_top_enroll_method')
        ) {
            try {
                $enrolmethod = \tool_ecommerce\helper::course_top_enroll_method((int) $course->id);
                if (!is_array($enrolmethod)) {
                    $enrolmethod = [];
                }
                if (
                    !empty($enrolmethod['ecommerce'])
                    && method_exists('\tool_ecommerce\helper', 'enrollment_limit')
                    && \tool_ecommerce\helper::enrollment_limit()
                ) {
                    $role = $DB->get_record('role', ['shortname' => 'student']);
                    if ($role) {
                        $users = get_enrolled_users($context, '', 0, 'u.id', '', 0, 0, true);
                        $limit = (int) ($enrolmethod['ecommerce']['course_enrollment_limit'] ?? 0);
                        if ($limit > 0 && count($users) >= $limit) {
                            $enrolmethod['ecommerce']['fullybooked'] = true;
                        }
                    }
                }
            } catch (\Throwable $e) {
                debugging($e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        if (empty($enrolmethod['ecommerce']) && empty($enrolmethod['button'])) {
            $enrolmethod['button'] = [
                'url' => $url,
                'label' => get_string('viewcourse', 'local_completionpage'),
            ];
        }

        return [
            'id' => (int) $course->id,
            'url' => $url,
            'imgurl' => $imgurl,
            'placeholderimgurl' => self::get_placeholder_image_url(),
            'name' => format_string($course->fullname),
            'categoryname' => $categoryname,
        ] + $enrolmethod;
    }

    /**
     * Other visible courses in the same category.
     *
     * @param int $courseid Course id.
     * @param int $limit Maximum number of course ids to return.
     * @return int[]
     */
    public static function get_same_category_courseids(int $courseid, int $limit = 3): array {
        global $DB;

        $categoryid = $DB->get_field('course', 'category', ['id' => $courseid]);
        if (!$categoryid) {
            return [];
        }

        $records = $DB->get_records_select(
            'course',
            'category = :categoryid AND visible = 1 AND id <> :courseid',
            ['categoryid' => $categoryid, 'courseid' => $courseid],
            'fullname ASC',
            'id',
            0,
            $limit
        );

        return array_map('intval', array_keys($records));
    }

    /**
     * Placeholder icon when a course has no overview image.
     *
     * @return string
     */
    public static function get_placeholder_image_url(): string {
        global $OUTPUT;

        return $OUTPUT->image_url('image_placeholder', 'local_completionpage')->out(false);
    }
}
