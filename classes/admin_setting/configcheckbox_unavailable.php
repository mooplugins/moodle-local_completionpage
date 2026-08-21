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
 * Read-only admin checkbox with an unavailable notice.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_completionpage\admin_setting;

/**
 * Checkbox that cannot be edited while a required plugin is missing.
 */
class configcheckbox_unavailable extends \admin_setting_configcheckbox {
    /** @var string */
    private $noticemessage;

    /**
     * Create a read-only checkbox admin setting.
     *
     * @param string $name
     * @param string $visiblename
     * @param string $description
     * @param string $defaultsetting
     * @param string $noticemessage
     */
    public function __construct(
        string $name,
        string $visiblename,
        string $description,
        string $defaultsetting,
        string $noticemessage
    ) {
        parent::__construct($name, $visiblename, $description, $defaultsetting);
        $this->noticemessage = $noticemessage;
    }

    /**
     * Persist the default/current value so upgrade settings can complete
     * while the dependent plugin is missing. The UI stays read-only.
     *
     * @param mixed $data
     * @return string
     */
    public function write_setting($data) {
        // Incoming form data is ignored; this setting is not editable while unavailable.
        unset($data);

        $current = $this->get_setting();
        if ($current === null) {
            return parent::write_setting($this->get_defaultsetting());
        }

        // Keep the stored value unchanged while unavailable.
        return '';
    }

    /**
     * Render a disabled checkbox and keep the current stored value.
     *
     * @param string $data
     * @param string $query
     * @return string
     */
    public function output_html($data, $query = '') {
        global $OUTPUT;

        if (is_null($data)) {
            $data = $this->get_defaultsetting();
        }

        $context = (object) [
            'id' => $this->get_id(),
            'name' => $this->get_full_name(),
            'no' => $this->no,
            'value' => $this->yes,
            'checked' => (string) $data === $this->yes,
            'readonly' => true,
        ];

        $element = $OUTPUT->render_from_template('core_admin/setting_configcheckbox', $context);
        $element .= \html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => $this->get_full_name(),
            'value' => (string) $data === $this->yes ? $this->yes : $this->no,
        ]);
        $element .= \html_writer::div($this->noticemessage, 'text-muted mt-1');

        return format_admin_setting($this, $this->visiblename, $element, $this->description, true, '', null, $query);
    }
}
