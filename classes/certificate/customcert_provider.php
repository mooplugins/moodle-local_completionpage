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
 * customcert_provider for local_completionpage.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_completionpage\certificate;

/**
 * Certificate provider for mod_customcert.
 */
class customcert_provider implements provider_interface {
    /**
     * Whether this certificate provider can be used on the site.
     *
     * @return bool
     */
    public function is_available(): bool {
        global $DB;

        return $DB->get_manager()->table_exists('customcert_issues')
            && $DB->get_manager()->table_exists('customcert');
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

        $modid = $DB->get_field('modules', 'id', ['name' => 'customcert']);
        if (!$modid) {
            return [];
        }

        $sql = "SELECT cm.id AS cmid, c.id AS customcertid, c.name AS certname, ci.id AS issueid
                  FROM {customcert_issues} ci
                  JOIN {customcert} c ON c.id = ci.customcertid
                  JOIN {course_modules} cm ON cm.instance = c.id AND cm.module = :modid
                 WHERE ci.userid = :userid
                   AND c.course = :courseid
                   AND cm.course = :courseid2
                   AND cm.deletioninprogress = 0
              ORDER BY c.name ASC";

        $records = $DB->get_records_sql($sql, [
            'modid' => $modid,
            'userid' => $userid,
            'courseid' => $courseid,
            'courseid2' => $courseid,
        ]);

        $certs = [];
        foreach ($records as $record) {
            // Use my_certificates.php so download still works if the activity is
            // visible but availability restricts view.php; hidden CMs are filtered
            // out in certificate_manager.
            $pdfurl = (new \moodle_url('/mod/customcert/my_certificates.php', [
                'userid' => $userid,
                'downloadcert' => 1,
                'certificateid' => $record->customcertid,
            ]))->out(false);

            $certs[] = [
                'name' => format_string($record->certname),
                'downloadurl' => $pdfurl,
                'previewurl' => $pdfurl,
                'plugin' => 'customcert',
                'cmid' => (int) $record->cmid,
                'issueid' => (int) $record->issueid,
            ];
        }

        return $certs;
    }
}
