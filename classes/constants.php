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
 * constants for local_completionpage.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_completionpage;

/**
 * Plugin constants.
 */
final class constants {
    /** Inherit site default. */
    public const INHERIT = -1;

    /** Disabled. */
    public const DISABLED = 0;

    /** Enabled. */
    public const ENABLED = 1;

    /** Time spent source: local_timespent plugin (default). */
    public const TIMESPENT_SOURCE_TIMESPENT = 'timespent';

    /** Time spent source: estimate from standard log store. */
    public const TIMESPENT_SOURCE_LOGS = 'logs';

    /** Session gap timeout (seconds) used for logstore estimates. */
    public const TIMESPENT_SESSION_TIMEOUT = 900;

    /** Tri-state options for course settings. */
    public const TRI_STATE_OPTIONS = [
        self::INHERIT => 'inherit',
        self::DISABLED => 'disabled',
        self::ENABLED => 'enabled',
    ];
}
