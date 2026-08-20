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
 * hook_callbacks for local_completionpage.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_completionpage;

use core_course\hook\after_form_definition;
use core_course\hook\after_form_definition_after_data;
use core_course\hook\after_form_submission;
use local_completionpage\constants;
use local_completionpage\service\completion_gate;
use local_completionpage\service\config_resolver;
use local_completionpage\service\course_config;
use local_completionpage\service\message_content;

/**
 * Hook callbacks for course edit form integration.
 */
class hook_callbacks {
    /**
     * Add Course Completion Page fields to the course edit form.
     *
     * @param after_form_definition $hook
     */
    public static function after_form_definition(after_form_definition $hook): void {
        global $PAGE;

        $mform = $hook->mform;
        $course = $hook->formwrapper->get_course();

        if (empty($course->id)) {
            return;
        }

        $mform->addElement(
            'header',
            'completionpageheader',
            get_string('coursesettingsheader', 'local_completionpage')
        );

        // Opened from course admin → Course Completion Page: expand and scroll to section.
        if (optional_param('showcompletionpage', 0, PARAM_BOOL)) {
            $mform->setExpanded('completionpageheader');
            $PAGE->requires->js_amd_inline(<<<'JS'
require([], function() {
    const scrollToSection = () => {
        const el = document.getElementById('id_completionpageheader');
        if (!el) {
            return;
        }
        el.scrollIntoView({behavior: 'smooth', block: 'start'});
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => setTimeout(scrollToSection, 50));
    } else {
        setTimeout(scrollToSection, 50);
    }
});
JS
            );
        }

        $tristate = [
            constants::INHERIT => get_string('inherit', 'local_completionpage'),
            constants::DISABLED => get_string('disabled', 'local_completionpage'),
            constants::ENABLED => get_string('enabled', 'local_completionpage'),
        ];

        $mform->addElement('select', 'completionpage_enabled', get_string('course_enabled', 'local_completionpage'), $tristate);
        $mform->addHelpButton('completionpage_enabled', 'enable', 'local_completionpage');

        $mform->addElement(
            'static',
            'completionpage_sectionsintro',
            '',
            get_string('course_sections_intro', 'local_completionpage')
        );

        $mform->addElement(
            'select',
            'completionpage_sectionmessage',
            get_string('course_section_message', 'local_completionpage'),
            $tristate
        );
        $mform->addHelpButton('completionpage_sectionmessage', 'section_message', 'local_completionpage');

        $editoroptions = message_content::editor_options((int) $course->id);

        $mform->addElement(
            'editor',
            'completionpage_customheadline',
            get_string('course_customheadline', 'local_completionpage'),
            null,
            $editoroptions
        );
        $mform->addHelpButton('completionpage_customheadline', 'course_customheadline', 'local_completionpage');
        $mform->hideIf('completionpage_customheadline', 'completionpage_sectionmessage', 'eq', constants::DISABLED);

        $mform->addElement(
            'editor',
            'completionpage_custommessage',
            get_string('course_custommessage', 'local_completionpage'),
            null,
            $editoroptions
        );
        $mform->addHelpButton('completionpage_custommessage', 'course_custommessage', 'local_completionpage');
        $mform->hideIf('completionpage_custommessage', 'completionpage_sectionmessage', 'eq', constants::DISABLED);

        $mform->addElement(
            'select',
            'completionpage_sectioncertificates',
            get_string('course_section_certificates', 'local_completionpage'),
            $tristate
        );
        $mform->addHelpButton('completionpage_sectioncertificates', 'section_certificates', 'local_completionpage');

        $mform->addElement(
            'select',
            'completionpage_sectionfeedback',
            get_string('course_section_feedback', 'local_completionpage'),
            $tristate
        );
        $mform->addHelpButton('completionpage_sectionfeedback', 'section_feedback', 'local_completionpage');

        $feedbackoptions = [0 => get_string('course_feedbackcmid_none', 'local_completionpage')];
        $feedbackoptions += course_config::get_feedback_activity_options((int) $course->id);
        $mform->addElement(
            'select',
            'completionpage_feedbackcmid',
            get_string('course_feedbackcmid', 'local_completionpage'),
            $feedbackoptions
        );
        $mform->addHelpButton('completionpage_feedbackcmid', 'course_feedbackcmid', 'local_completionpage');
        $mform->hideIf('completionpage_feedbackcmid', 'completionpage_sectionfeedback', 'eq', constants::DISABLED);

        $returnurl = completion_gate::page_url((int) $course->id)->out(false);
        $mform->addElement(
            'static',
            'completionpage_returnurl',
            get_string('course_returnurl', 'local_completionpage'),
            \html_writer::tag('code', s($returnurl))
        );
        $mform->addHelpButton('completionpage_returnurl', 'course_returnurl', 'local_completionpage');
        $mform->hideIf('completionpage_returnurl', 'completionpage_sectionfeedback', 'eq', constants::DISABLED);

        $mform->addElement(
            'select',
            'completionpage_sectionsuggested',
            get_string('course_section_suggested', 'local_completionpage'),
            $tristate
        );
        $mform->addHelpButton('completionpage_sectionsuggested', 'section_suggested', 'local_completionpage');

        $mform->addElement(
            'course',
            'completionpage_suggestedcourses',
            get_string('course_suggestedcourses', 'local_completionpage'),
            [
                'multiple' => true,
                'exclude' => [(int) $course->id],
                'includefrontpage' => false,
            ]
        );
        $mform->addHelpButton('completionpage_suggestedcourses', 'course_suggestedcourses', 'local_completionpage');
        $mform->hideIf('completionpage_suggestedcourses', 'completionpage_sectionsuggested', 'eq', constants::DISABLED);

        $mform->addElement(
            'select',
            'completionpage_sectionachievements',
            get_string('course_section_achievements', 'local_completionpage'),
            $tristate
        );
        $mform->addHelpButton('completionpage_sectionachievements', 'section_achievements', 'local_completionpage');

        $mform->addElement(
            'select',
            'completionpage_sectionexit',
            get_string('course_section_exit', 'local_completionpage'),
            $tristate
        );
        $mform->addHelpButton('completionpage_sectionexit', 'section_exit', 'local_completionpage');
    }

    /**
     * Populate Course Completion Page fields after form data load.
     *
     * @param after_form_definition_after_data $hook
     */
    public static function after_form_definition_after_data(after_form_definition_after_data $hook): void {
        $course = $hook->formwrapper->get_course();
        if (empty($course->id)) {
            return;
        }

        $record = course_config::get((int) $course->id);
        $mform = $hook->mform;

        $courseid = (int) $course->id;
        $mform->setDefault('completionpage_enabled', $record->enabled);
        $mform->setDefault(
            'completionpage_customheadline',
            message_content::to_editor(
                $record->customheadline,
                $courseid,
                message_content::FILEAREA_HEADLINE,
                'completionpage_customheadline',
                [message_content::class, 'default_headline_html']
            )
        );
        $mform->setDefault(
            'completionpage_custommessage',
            message_content::to_editor(
                $record->custommessage,
                $courseid,
                message_content::FILEAREA_MESSAGE,
                'completionpage_custommessage',
                [message_content::class, 'default_message_html']
            )
        );
        $mform->setDefault('completionpage_feedbackcmid', $record->feedbackcmid ?? 0);
        $mform->setDefault(
            'completionpage_suggestedcourses',
            config_resolver::parse_course_ids($record->suggestedcourses)
        );
        $mform->setDefault('completionpage_sectionmessage', $record->sectionmessage);
        $mform->setDefault('completionpage_sectioncertificates', $record->sectioncertificates);
        $mform->setDefault('completionpage_sectionfeedback', $record->sectionfeedback);
        $mform->setDefault('completionpage_sectionsuggested', $record->sectionsuggested);
        $mform->setDefault('completionpage_sectionexit', $record->sectionexit);
        $mform->setDefault('completionpage_sectionachievements', $record->sectionachievements);
    }

    /**
     * Persist Course Completion Page fields after course form submit.
     *
     * @param after_form_submission $hook
     */
    public static function after_form_submission(after_form_submission $hook): void {
        $data = $hook->get_data();
        if (empty($data->id)) {
            return;
        }

        $existing = course_config::get((int) $data->id);
        $sectionmessagedisabled = (int) ($data->completionpage_sectionmessage ?? constants::INHERIT) === constants::DISABLED;

        $customheadline = $existing->customheadline;
        $custommessage = $existing->custommessage;
        if (!$sectionmessagedisabled) {
            if (property_exists($data, 'completionpage_customheadline')) {
                $customheadline = message_content::normalize_for_storage(
                    message_content::from_editor(
                        $data->completionpage_customheadline,
                        (int) $data->id,
                        message_content::FILEAREA_HEADLINE
                    ),
                    'headline'
                );
            }
            if (property_exists($data, 'completionpage_custommessage')) {
                $custommessage = message_content::normalize_for_storage(
                    message_content::from_editor(
                        $data->completionpage_custommessage,
                        (int) $data->id,
                        message_content::FILEAREA_MESSAGE
                    ),
                    'message'
                );
            }
        }

        course_config::save((int) $data->id, (object) [
            'enabled' => $data->completionpage_enabled ?? constants::INHERIT,
            'customheadline' => $customheadline,
            'custommessage' => $custommessage,
            'feedbackcmid' => $data->completionpage_feedbackcmid ?? 0,
            'suggestedcourses' => config_resolver::format_course_ids(
                is_array($data->completionpage_suggestedcourses ?? null)
                    ? $data->completionpage_suggestedcourses
                    : config_resolver::parse_course_ids($data->completionpage_suggestedcourses ?? '')
            ),
            'sectionmessage' => $data->completionpage_sectionmessage ?? constants::INHERIT,
            'sectioncertificates' => $data->completionpage_sectioncertificates ?? constants::INHERIT,
            'sectionfeedback' => $data->completionpage_sectionfeedback ?? constants::INHERIT,
            'sectionsuggested' => $data->completionpage_sectionsuggested ?? constants::INHERIT,
            'sectionexit' => $data->completionpage_sectionexit ?? constants::INHERIT,
            'sectionachievements' => $data->completionpage_sectionachievements ?? constants::INHERIT,
        ]);
    }
}
