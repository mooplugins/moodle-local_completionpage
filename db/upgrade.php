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
 * Upgrade steps for local_completionpage.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Perform upgrade steps for local_completionpage.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_completionpage_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025073000) {
        $table = new xmldb_table('local_completionpage_course');
        $field = new xmldb_field('sectionachievements', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '-1', 'sectionexit');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025073000, 'local', 'completionpage');
    }

    if ($oldversion < 2026080400) {
        // Marketplace packaging release — no schema changes.
        upgrade_plugin_savepoint(true, 2026080400, 'local', 'completionpage');
    }

    if ($oldversion < 2026080401) {
        // Time spent source setting added — no schema changes.
        upgrade_plugin_savepoint(true, 2026080401, 'local', 'completionpage');
    }

    return true;
}
