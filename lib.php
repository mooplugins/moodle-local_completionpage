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
 * Callbacks for local_completionpage.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add a completion page link to course navigation when available.
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context_course $context
 */
function local_completionpage_extend_navigation_course(
    navigation_node $navigation,
    stdClass $course,
    context_course $context
) {
    global $USER;

    if (!has_capability('local/completionpage:view', $context)) {
        return;
    }

    if (!\local_completionpage\service\completion_gate::can_view_page($course, (int) $USER->id)) {
        return;
    }

    $url = new moodle_url('/local/completionpage/view.php', ['courseid' => $course->id]);
    $navigation->add(
        get_string('navlink', 'local_completionpage'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'completionpage'
    );
}

/**
 * Add plugin settings link to course administration.
 *
 * Links to the advanced course edit form (not the simplified setting.php UI)
 * and jumps to the Course Completion Page section.
 *
 * @param settings_navigation $settingsnav
 * @param context $context
 */
function local_completionpage_extend_settings_navigation(settings_navigation $settingsnav, context $context) {
    if ($context->contextlevel !== CONTEXT_COURSE) {
        return;
    }

    if (!has_capability('local/completionpage:configure', $context)) {
        return;
    }

    $coursenode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE);
    if (!$coursenode) {
        return;
    }

    $courseid = $context->instanceid;
    try {
        $course = get_course($courseid);
    } catch (Throwable $e) {
        return;
    }

    $returnurl = (new moodle_url('/course/view.php', ['id' => $courseid]))->out(false);
    $editurl = new moodle_url('/course/edit.php', [
        'id' => $courseid,
        'category' => (int) $course->category,
        'advanced' => 1,
        'returnto' => 0,
        'returnurl' => $returnurl,
        'showcompletionpage' => 1,
    ]);
    // Moodle form header element id for the Course Completion Page section.
    $editurl->set_anchor('id_completionpageheader');

    $coursenode->add(
        get_string('coursesettingsheader', 'local_completionpage'),
        $editurl,
        navigation_node::TYPE_SETTING,
        null,
        'completionpagesettings'
    );
}

/**
 * Serve files embedded in completion message editors.
 *
 * @param stdClass $course
 * @param stdClass|null $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function local_completionpage_pluginfile(
    $course,
    $cm,
    $context,
    $filearea,
    $args,
    $forcedownload,
    array $options = []
) {
    if ($context->contextlevel != CONTEXT_COURSE) {
        return false;
    }

    $allowedareas = [
        \local_completionpage\service\message_content::FILEAREA_HEADLINE,
        \local_completionpage\service\message_content::FILEAREA_MESSAGE,
    ];
    if (!in_array($filearea, $allowedareas, true)) {
        return false;
    }

    global $USER;

    require_login($course);

    // Learners may have local/completionpage:view without moodle/course:view on this site.
    $canaccess = has_capability('local/completionpage:configure', $context)
        || has_capability('moodle/course:view', $context)
        || (
            has_capability('local/completionpage:view', $context)
            && \local_completionpage\service\completion_gate::can_view_page($course, (int) $USER->id)
        );
    if (!$canaccess) {
        return false;
    }

    if (empty($args)) {
        return false;
    }

    $itemid = (int) array_shift($args);
    if ($itemid !== (int) $course->id) {
        return false;
    }

    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_completionpage', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
