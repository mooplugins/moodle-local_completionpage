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
 * coursecertificate_provider for local_completionpage.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_completionpage\certificate;

/**
 * Certificate provider for mod_coursecertificate + tool_certificate.
 */
class coursecertificate_provider implements provider_interface {
    /**
     * Whether this certificate provider can be used on the site.
     *
     * @return bool
     */
    public function is_available(): bool {
        global $DB;

        return $DB->get_manager()->table_exists('tool_certificate_issues')
            && $DB->get_manager()->table_exists('coursecertificate');
    }

    /**
     * Return issued certificates for the user in the course.
     *
     * @param int $courseid
     * @param int $userid
     * @return array
     */
    public function get_user_certificates(int $courseid, int $userid): array {
        global $DB;

        if (!$this->is_available()) {
            return [];
        }

        $modid = $DB->get_field('modules', 'id', ['name' => 'coursecertificate']);
        if (!$modid) {
            return [];
        }

        $sql = "SELECT cm.id AS cmid, cc.name AS certname, tci.id AS issueid, tci.code AS issuecode
                  FROM {tool_certificate_issues} tci
                  JOIN {coursecertificate} cc ON cc.template = tci.templateid AND cc.course = tci.courseid
                  JOIN {course_modules} cm ON cm.instance = cc.id AND cm.module = :modid
                 WHERE tci.userid = :userid
                   AND tci.courseid = :courseid
                   AND cm.course = :courseid2
                   AND cm.deletioninprogress = 0
              ORDER BY cc.name ASC";

        $records = $DB->get_records_sql($sql, [
            'modid' => $modid,
            'userid' => $userid,
            'courseid' => $courseid,
            'courseid2' => $courseid,
        ]);

        $certs = [];
        foreach ($records as $record) {
            $params = ['id' => $record->cmid];
            if (!empty($record->issuecode)) {
                $params['code'] = $record->issuecode;
            }

            $certs[] = [
                'name' => format_string($record->certname),
                'downloadurl' => (new \moodle_url('/mod/coursecertificate/view.php', $params))->out(false),
                'previewurl' => (new \moodle_url('/mod/coursecertificate/view.php', [
                    'id' => $record->cmid,
                ]))->out(false),
                'plugin' => 'coursecertificate',
                'cmid' => (int) $record->cmid,
            ];
        }

        return $certs;
    }
}
