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
 * certificate_manager for local_completionpage.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_completionpage\service;

use local_completionpage\certificate\coursecertificate_provider;
use local_completionpage\certificate\customcert_provider;
use local_completionpage\certificate\provider_interface;
use local_completionpage\certificate\simplecertificate_provider;

/**
 * Aggregates certificates from supported plugins.
 */
class certificate_manager {
    /**
     * Return available certificate providers.
     *
     * @return provider_interface[]
     */
    private static function get_providers(): array {
        return [
            new customcert_provider(),
            new coursecertificate_provider(),
            new simplecertificate_provider(),
        ];
    }

    /**
     * Return issued certificates for the user in the course.
     *
     * Certificates from hidden (or otherwise not user-visible) activities are omitted
     * so the completion page certificate section stays hidden with the activity.
     *
     * @param int $courseid
     * @param int $userid
     * @return array<int, array{name: string, downloadurl: string, plugin: string, cmid: int}>
     */
    public static function get_user_certificates(int $courseid, int $userid): array {
        $all = [];
        foreach (self::get_providers() as $provider) {
            if (!$provider->is_available()) {
                continue;
            }
            $all = array_merge($all, $provider->get_user_certificates($courseid, $userid));
        }

        if (empty($all)) {
            return [];
        }

        $modinfo = get_fast_modinfo($courseid, $userid);
        $visible = [];
        foreach ($all as $cert) {
            $cmid = (int) ($cert['cmid'] ?? 0);
            if (!$cmid || !isset($modinfo->cms[$cmid])) {
                continue;
            }
            if (!$modinfo->cms[$cmid]->uservisible) {
                continue;
            }
            $visible[] = $cert;
        }

        return $visible;
    }
}
