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
 * Site administration settings for local_completionpage.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_completionpage',
        get_string('settingsheading', 'local_completionpage')
    );

    $settings->add(new admin_setting_configcheckbox(
        'local_completionpage/enable',
        get_string('settings_enable', 'local_completionpage'),
        get_string('settings_enable_desc', 'local_completionpage', get_string('enable_help', 'local_completionpage')),
        1
    ));

    $settings->add(new admin_setting_heading(
        'local_completionpage/sections',
        get_string('settings_sections_heading', 'local_completionpage'),
        get_string('settings_sections_heading_desc', 'local_completionpage')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_completionpage/section_message',
        get_string('settings_section_message', 'local_completionpage'),
        get_string('section_message_help', 'local_completionpage'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_completionpage/section_certificates',
        get_string('settings_section_certificates', 'local_completionpage'),
        get_string('section_certificates_help', 'local_completionpage'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_completionpage/section_feedback',
        get_string('settings_section_feedback', 'local_completionpage'),
        get_string('section_feedback_help', 'local_completionpage'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_completionpage/section_suggested',
        get_string('settings_section_suggested', 'local_completionpage'),
        get_string('section_suggested_help', 'local_completionpage'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_completionpage/section_achievements',
        get_string('settings_section_achievements', 'local_completionpage'),
        get_string('section_achievements_help', 'local_completionpage'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_completionpage/section_exit',
        get_string('settings_section_exit', 'local_completionpage'),
        get_string('section_exit_help', 'local_completionpage'),
        1
    ));

    $timespentavailable = \local_completionpage\service\achievements::is_timespent_plugin_available();
    $timespentoptions = [
        \local_completionpage\constants::TIMESPENT_SOURCE_LOGS =>
            get_string('settings_timespent_source_logs', 'local_completionpage'),
    ];
    if ($timespentavailable) {
        $timespentoptions[\local_completionpage\constants::TIMESPENT_SOURCE_TIMESPENT] =
            get_string('settings_timespent_source_timespent', 'local_completionpage');
    }

    $timespentdesc = get_string('settings_timespent_source_desc', 'local_completionpage');
    if (!$timespentavailable) {
        $timespentdesc .= \html_writer::div(
            get_string('settings_timespent_recommend', 'local_completionpage'),
            'alert alert-info mt-2 mb-0'
        );
    }

    $timespentdefault = $timespentavailable
        ? \local_completionpage\constants::TIMESPENT_SOURCE_TIMESPENT
        : \local_completionpage\constants::TIMESPENT_SOURCE_LOGS;

    $settings->add(new admin_setting_configselect(
        'local_completionpage/timespent_source',
        get_string('settings_timespent_source', 'local_completionpage'),
        $timespentdesc,
        $timespentdefault,
        $timespentoptions
    ));

    $ADMIN->add('localplugins', $settings);
}
