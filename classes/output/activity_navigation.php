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
 * activity_navigation for local_completionpage.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_completionpage\output;

use local_completionpage\service\finish_button;
use renderer_base;

/**
 * Activity navigation renderable with optional Finish link on the last activity.
 */
class activity_navigation extends \core_course\output\activity_navigation {
    /** @var \action_link|null Finish link on the last activity when completion page is available. */
    public $finishlink = null;

    /**
     * Create activity navigation with optional Finish link.
     *
     * @param \cm_info|null $prevmod
     * @param \cm_info|null $nextmod
     * @param array $activitylist
     */
    public function __construct($prevmod, $nextmod, $activitylist = []) {
        parent::__construct($prevmod, $nextmod, $activitylist);

        if ($this->nextlink !== null) {
            return;
        }

        global $PAGE, $USER;

        if ($PAGE->context->contextlevel != CONTEXT_MODULE || empty($PAGE->course->id)) {
            return;
        }

        $this->finishlink = finish_button::get_action_link($PAGE->course, (int) $USER->id);
    }

    /**
     * Export template data including Finish link when present.
     *
     * @param renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = parent::export_for_template($output);

        if ($this->finishlink) {
            $data->finishlink = $this->finishlink->export_for_template($output);
        }

        return $data;
    }
}
