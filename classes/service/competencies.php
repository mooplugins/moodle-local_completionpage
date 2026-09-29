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
 * Competencies section data for local_completionpage.
 *
 * @package    local_completionpage
 * @author     BitKea Technologies LLP
 * @copyright  2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_completionpage\service;

use context_course;
use core_competency\api;
use core_competency\course_competency;
use core_competency\course_module_competency;
use core_competency\user_competency_course;
use moodle_url;

/**
 * Builds competency list data for the completion page.
 *
 * Hidden when competencies are disabled site-wide, or when the course has no
 * linked competencies. Shows each course competency with the learner's
 * proficiency status and any visible linked activities.
 */
class competencies {
    /**
     * Build section display data for the completion page.
     *
     * @param \stdClass $course
     * @param int $userid
     * @return array{
     *   showsection: bool,
     *   total: int,
     *   proficientcount: int,
     *   summary: string,
     *   competencies: array<int, array<string, mixed>>,
     *   viewallurl: string
     * }
     */
    public static function get_section_data(\stdClass $course, int $userid): array {
        $empty = [
            'showsection' => false,
            'total' => 0,
            'proficientcount' => 0,
            'summary' => '',
            'competencies' => [],
            'viewallurl' => '',
        ];

        if (!self::is_available()) {
            return $empty;
        }

        $courseid = (int) $course->id;
        $context = context_course::instance($courseid);

        // Learners need at least course competency view (standard student role has this when enabled).
        $canview = has_any_capability(
            ['moodle/competency:coursecompetencyview', 'moodle/competency:coursecompetencymanage'],
            $context
        );
        if (!$canview) {
            return $empty;
        }

        $coursecompetencies = course_competency::list_course_competencies($courseid);
        if (empty($coursecompetencies)) {
            return $empty;
        }

        $competencies = course_competency::list_competencies($courseid);
        $userrows = user_competency_course::get_records(['courseid' => $courseid, 'userid' => $userid]);
        $bycompetency = [];
        foreach ($userrows as $ucc) {
            $bycompetency[(int) $ucc->get('competencyid')] = $ucc;
        }

        $modinfo = get_fast_modinfo($course, $userid);
        $activitylabel = get_string('competencyactivities', 'local_completionpage');
        $coursesourcelabel = get_string('competencyfromcourse', 'local_completionpage');

        $items = [];
        $proficientcount = 0;

        foreach ($coursecompetencies as $coursecompetency) {
            $competencyid = (int) $coursecompetency->get('competencyid');
            if (!isset($competencies[$competencyid])) {
                continue;
            }

            $competency = $competencies[$competencyid];
            $ucc = $bycompetency[$competencyid] ?? null;
            $isproficient = $ucc && (int) $ucc->get('proficiency') === 1;
            if ($isproficient) {
                $proficientcount++;
            }

            $description = $competency->get('description');
            $descriptionformat = (int) $competency->get('descriptionformat');
            $descriptionhtml = '';
            if ($description !== '' && $description !== null) {
                $descriptionhtml = format_text($description, $descriptionformat, [
                    'context' => $context,
                    'overflowdiv' => false,
                    'filter' => true,
                ]);
            }

            $idnumber = trim((string) $competency->get('idnumber'));
            $activities = self::get_visible_linked_activities($competencyid, $courseid, $modinfo);
            $courseoutcome = (int) $coursecompetency->get('ruleoutcome');
            $hascourseoutcome = $courseoutcome !== course_competency::OUTCOME_NONE;

            $items[] = [
                'name' => format_string($competency->get('shortname'), true, ['context' => $context]),
                'idnumber' => $idnumber,
                'hasidnumber' => $idnumber !== '',
                'descriptionhtml' => $descriptionhtml,
                'hasdescription' => $descriptionhtml !== '',
                'proficient' => $isproficient,
                'statuslabel' => $isproficient
                    ? get_string('competencyproficient', 'local_completionpage')
                    : get_string('competencynotproficient', 'local_completionpage'),
                'hascourseoutcome' => $hascourseoutcome,
                'coursesourcelabel' => $coursesourcelabel,
                'activities' => $activities,
                'hasactivities' => !empty($activities),
                'activitieslabel' => $activitylabel,
            ];
        }

        if (empty($items)) {
            return $empty;
        }

        $total = count($items);
        $summary = get_string('competenciessummary', 'local_completionpage', (object) [
            'proficient' => $proficientcount,
            'total' => $total,
        ]);

        return [
            'showsection' => true,
            'total' => $total,
            'proficientcount' => $proficientcount,
            'summary' => $summary,
            'competencies' => $items,
            'viewallurl' => (new moodle_url('/admin/tool/lp/coursecompetencies.php', [
                'courseid' => $courseid,
            ]))->out(false),
        ];
    }

    /**
     * Visible course activities linked to a competency for this learner.
     *
     * @param int $competencyid
     * @param int $courseid
     * @param \course_modinfo $modinfo
     * @return array<int, array{name: string, url: string}>
     */
    private static function get_visible_linked_activities(
        int $competencyid,
        int $courseid,
        \course_modinfo $modinfo
    ): array {
        $activities = [];
        $cmids = course_module_competency::list_course_modules($competencyid, $courseid);

        foreach ($cmids as $cmid) {
            $cmid = (int) $cmid;
            if (!isset($modinfo->cms[$cmid])) {
                continue;
            }

            $cminfo = $modinfo->cms[$cmid];
            // Match core tool_lp student view: hide activities the learner cannot access.
            if (!$cminfo->uservisible || empty($cminfo->url)) {
                continue;
            }

            $activities[] = [
                'name' => $cminfo->get_formatted_name(),
                'url' => $cminfo->url->out(false),
            ];
        }

        return $activities;
    }

    /**
     * Whether core competencies are enabled on this site.
     *
     * @return bool
     */
    public static function is_available(): bool {
        return class_exists(api::class) && api::is_enabled();
    }
}
