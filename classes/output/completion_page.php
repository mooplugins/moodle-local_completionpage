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
 * completion_page for local_completionpage.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_completionpage\output;

use local_completionpage\service\achievements;
use local_completionpage\service\certificate_manager;
use local_completionpage\service\completion_gate;
use local_completionpage\service\config_resolver;
use local_completionpage\service\feedback_link;
use local_completionpage\service\message_content;
use local_completionpage\service\suggested_courses;
use moodle_url;
use renderable;
use renderer_base;
use templatable;

/**
 * Completion page renderable.
 */
class completion_page implements renderable, templatable {
    /** @var \stdClass */
    protected $course;

    /** @var int */
    protected $userid;

    /** @var \stdClass */
    protected $config;

    /**
     * Create a completion page renderable.
     *
     * @param \stdClass $course
     * @param int $userid
     */
    public function __construct(\stdClass $course, int $userid) {
        $this->course = $course;
        $this->userid = $userid;
        $this->config = config_resolver::resolve((int) $course->id);
    }

    /**
     * Export template data for the completion page.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $courseid = (int) $this->course->id;
        $completedon = completion_gate::get_completion_timestamp($courseid, $this->userid);
        $context = \context_course::instance($courseid);

        $data = [
            'headlinehtml' => message_content::format_headline_html($this->config->customheadline, $context),
            'messagehtml' => message_content::format_message_html($this->config->custommessage, $context),
            'coursename' => format_string($this->course->fullname),
            'completedon' => $completedon ? userdate($completedon, get_string('strftimedate')) : '',
            'requirementsmet' => get_string('requirementsmet', 'local_completionpage'),
            'footerencouragement' => get_string('footerencouragement', 'local_completionpage'),
            'showmessage' => false,
            'showcertificates' => false,
            'showachievements' => false,
            'showfeedback' => false,
            'showsuggested' => false,
            'showexit' => false,
        ];

        if ($this->config->sectionmessage) {
            $data['showmessage'] = true;
            $data['courselabel'] = get_string('courselabel', 'local_completionpage');
            $data['completedonlabel'] = get_string('completedon', 'local_completionpage');
            $data['hascompletedon'] = !empty($data['completedon']);
        }

        if ($this->config->sectioncertificates) {
            $certificates = certificate_manager::get_user_certificates($courseid, $this->userid);
            if (!empty($certificates)) {
                $user = \core_user::get_user($this->userid);
                $recipient = $user ? fullname($user) : '';
                $issuedon = $completedon
                    ? userdate($completedon, get_string('strftimedate'))
                    : '';

                $data['showcertificates'] = true;
                $data['certificatesheading'] = get_string('certificatesheading', 'local_completionpage');
                $data['certificatesdesc'] = get_string('certificatesdesc', 'local_completionpage');
                $data['downloadlabel'] = get_string('downloadcertificate', 'local_completionpage');
                $data['previewlabel'] = get_string('previewcertificate', 'local_completionpage');
                $data['certificateoftitle'] = get_string('certificateoftitle', 'local_completionpage');
                $data['certificatepresentedto'] = get_string('certificatepresentedto', 'local_completionpage');
                $coursename = $data['coursename'];
                $data['certificates'] = array_values(array_map(static function (array $cert) use (
                    $recipient,
                    $issuedon,
                    $data,
                    $coursename
                ): array {
                    $cert['previewurl'] = $cert['previewurl'] ?? $cert['downloadurl'];
                    $cert['recipient'] = $recipient;
                    $cert['issuedon'] = $issuedon;
                    $cert['hasissuedon'] = $issuedon !== '';
                    $cert['coursename'] = $coursename;
                    $cert['downloadlabel'] = $data['downloadlabel'];
                    $cert['previewlabel'] = $data['previewlabel'];
                    $cert['certificateoftitle'] = $data['certificateoftitle'];
                    $cert['certificatepresentedto'] = $data['certificatepresentedto'];
                    $cert['haspdfpreview'] = !empty($cert['previewurl']) && in_array(
                        $cert['plugin'] ?? '',
                        ['customcert', 'simplecertificate', 'coursecertificate'],
                        true
                    );
                    return $cert;
                }, $certificates));
                $data['hascertificates'] = true;
            }
        }

        if ($this->config->sectionachievements) {
            $achievements = achievements::get_section_data($this->course, $this->userid);
            if ($achievements['showsection']) {
                $data['showachievements'] = true;
                $data['achievementsheading'] = get_string('achievementsheading', 'local_completionpage');
                $data['achievementsdesc'] = get_string('achievementsdesc', 'local_completionpage');
                $data['showcompletedon'] = !empty($achievements['showcompletedon']);
                $data['completedonlabel'] = get_string('completedonlabel', 'local_completionpage');
                $data['completedon'] = $achievements['completedon'];
                $data['hasgrade'] = !empty($achievements['hasgrade']);
                $data['gradelabel'] = get_string('gradelabel', 'local_completionpage');
                $data['grade'] = $achievements['grade'];
                $data['hastimespent'] = !empty($achievements['hastimespent']);
                $data['timespentlabel'] = get_string('timespentlabel', 'local_completionpage');
                $data['timespent'] = $achievements['timespent'];
                $data['hasbadges'] = !empty($achievements['hasbadges']);
                $data['badgesheading'] = get_string('badgesheading', 'local_completionpage');
                $data['badges'] = $achievements['badges'];
            }
        }

        if ($this->config->sectionfeedback) {
            $feedback = feedback_link::get_section_data($courseid, $this->userid, $this->config->feedbackcmid);
            if ($feedback['showsection']) {
                $data['showfeedback'] = true;
                $data['feedbackheading'] = get_string('feedbackheading', 'local_completionpage');
                $data['feedbackdesc'] = get_string('feedbackdesc', 'local_completionpage');
                $data['feedbackname'] = $feedback['feedbackname'];
                $data['alreadysubmitted'] = $feedback['alreadysubmitted'];
                $data['feedbackthanks'] = get_string('feedbackthanks', 'local_completionpage');
                if (!$feedback['alreadysubmitted']) {
                    $data['feedbackurl'] = $feedback['feedbackurl'];
                    $data['givefeedbacklabel'] = get_string('givefeedback', 'local_completionpage');
                    $data['feedbackctahint'] = get_string('feedbackctahint', 'local_completionpage');
                    $data['feedbackactivitylabel'] = get_string('feedbackactivitylabel', 'local_completionpage');
                }
            }
        }

        if ($this->config->sectionsuggested) {
            $cards = suggested_courses::get_cards($this->config->suggestedcourses, $courseid);
            if (!empty($cards)) {
                $data['showsuggested'] = true;
                $data['suggestedheading'] = get_string('suggestedheading', 'local_completionpage');
                $data['suggesteddesc'] = get_string('suggesteddesc', 'local_completionpage');
                $data['browseallurl'] = (new moodle_url('/course/index.php'))->out(false);
                $data['browsealllabel'] = get_string('browseallcourses', 'local_completionpage');
                $data['viewcourselabel'] = get_string('viewcourse', 'local_completionpage');
                $data['courses'] = array_values($cards);
                $data['hascourses'] = true;
            }
        }

        if ($this->config->sectionexit) {
            $data['showexit'] = true;
            $data['exitnavlabel'] = get_string('exitnavlabel', 'local_completionpage');
            $data['exitoptions'] = [
                [
                    'key' => 'dashboard',
                    'url' => (new moodle_url('/my/'))->out(false),
                    'title' => get_string('exitdashboard', 'local_completionpage'),
                    'description' => get_string('exitdashboarddesc', 'local_completionpage'),
                    'iconsvg' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" '
                        . 'stroke="currentColor" stroke-width="2" stroke-linecap="round" '
                        . 'stroke-linejoin="round">'
                        . '<rect x="3" y="3" width="8" height="8" rx="1.5"/>'
                        . '<rect x="13" y="3" width="8" height="8" rx="1.5"/>'
                        . '<rect x="3" y="13" width="8" height="8" rx="1.5"/>'
                        . '<rect x="13" y="13" width="8" height="8" rx="1.5"/></svg>',
                ],
                [
                    'key' => 'mycourses',
                    'url' => (new moodle_url('/my/courses.php'))->out(false),
                    'title' => get_string('exitcourses', 'local_completionpage'),
                    'description' => get_string('exitcoursesdesc', 'local_completionpage'),
                    'iconsvg' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" '
                        . 'stroke="currentColor" stroke-width="2" stroke-linecap="round" '
                        . 'stroke-linejoin="round">'
                        . '<path d="M2 4h7a3 3 0 013 3v13a2.5 2.5 0 00-2.5-2.5H2V4z"/>'
                        . '<path d="M22 4h-7a3 3 0 00-3 3v13a2.5 2.5 0 012.5-2.5H22V4z"/></svg>',
                ],
                [
                    'key' => 'browse',
                    'url' => (new moodle_url('/course/index.php'))->out(false),
                    'title' => get_string('exitbrowse', 'local_completionpage'),
                    'description' => get_string('exitbrowsedesc', 'local_completionpage'),
                    'iconsvg' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" '
                        . 'stroke="currentColor" stroke-width="2" stroke-linecap="round" '
                        . 'stroke-linejoin="round"><circle cx="12" cy="12" r="9"/>'
                        . '<path d="M16.2 7.8l-2.1 6.3-6.3 2.1 2.1-6.3 6.3-2.1z"/></svg>',
                ],
            ];
        }

        return $data;
    }
}
