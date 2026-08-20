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
 * activity_navigation_renderer for local_completionpage.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_completionpage\output;

use moodle_url;

/**
 * Renders activity navigation with Finish link support (avoids core overrides).
 */
class activity_navigation_renderer {
    /**
     * Render activity navigation for a module page.
     *
     * Mirrors core_renderer::activity_navigation() but uses the plugin renderable.
     *
     * @param \core_renderer $output
     * @return string
     */
    public static function render(\core_renderer $output): string {
        $page = $output->get_page();
        $context = $page->context;

        if (
            ($page->pagelayout !== 'incourse' && $page->pagelayout !== 'frametop')
            || $context->contextlevel != CONTEXT_MODULE
        ) {
            return '';
        }

        if ($page->cm->is_stealth()) {
            return '';
        }

        $course = $page->cm->get_course();
        $courseformat = course_get_format($course);

        if (
            $page->theme->usescourseindex && $courseformat->uses_course_index()
            && $page->pagelayout !== 'frametop'
        ) {
            return '';
        }

        $modules = get_fast_modinfo($course->id)->get_cms();

        $mods = [];
        $activitylist = [];
        foreach ($modules as $module) {
            if (
                !$module->uservisible || $module->is_stealth() || empty($module->url)
                || !$module->is_of_type_that_can_display()
            ) {
                continue;
            }
            $mods[$module->id] = $module;

            if ($module->id == $page->cm->id) {
                continue;
            }

            $modname = $module->get_formatted_name();
            if (!$module->visible) {
                $modname .= ' ' . get_string('hiddenwithbrackets');
            }

            $linkurl = new moodle_url($module->url, ['forceview' => 1]);
            $activitylist[$linkurl->out(false)] = $modname;
        }

        if (count($mods) <= 1) {
            return '';
        }

        $modids = array_keys($mods);
        $position = array_search($page->cm->id, $modids);

        $prevmod = null;
        $nextmod = null;

        if ($position > 0) {
            $prevmod = $mods[$modids[$position - 1]];
        }

        if ($position < (count($modids) - 1)) {
            $nextmod = $mods[$modids[$position + 1]];
        }

        $activitynav = new activity_navigation($prevmod, $nextmod, $activitylist);
        $renderer = $page->get_renderer('core', 'course');

        return $renderer->render($activitynav);
    }
}
