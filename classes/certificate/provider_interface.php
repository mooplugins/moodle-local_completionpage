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
 * provider_interface for local_completionpage.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_completionpage\certificate;

/**
 * Certificate provider interface.
 */
interface provider_interface {
    /**
     * Whether this certificate provider can be used on the site.
     *
     * @return bool
     */
    public function is_available(): bool;

    /**
     * Return issued certificates for the user in the course.
     *
     * @param int $courseid
     * @param int $userid
     * @return array<int, array{name: string, downloadurl: string, plugin: string, cmid: int}>
     */
    public function get_user_certificates(int $courseid, int $userid): array;
}
