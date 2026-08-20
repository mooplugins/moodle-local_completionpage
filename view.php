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
 * Learner course completion page.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_completionpage\output\completion_page;
use local_completionpage\service\completion_gate;
use local_completionpage\service\optional_integrations;

$courseid = required_param('courseid', PARAM_INT);

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/completionpage:view', $context);

if (!completion_gate::can_view_page($course, (int) $USER->id)) {
    throw new moodle_exception('errornotcomplete', 'local_completionpage');
}

$PAGE->set_url(new moodle_url('/local/completionpage/view.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('completionpage', 'local_completionpage'));
// Heading is intentionally empty: the first section already shows the completion title.
$PAGE->set_heading('');
$PAGE->add_body_class('local-completionpage-page');
// Same add-to-cart / price-option handlers as homepage catalog cards.
if (optional_integrations::is_ecommerce_ui_available()) {
    $PAGE->requires->js_call_amd('enrol_ecommerce/helper', 'priceoptsEvents');
}

echo $OUTPUT->header();

$renderable = new completion_page($course, (int) $USER->id);
echo $OUTPUT->render_from_template(
    'local_completionpage/completion_page',
    $renderable->export_for_template($OUTPUT)
);

echo $OUTPUT->footer();
