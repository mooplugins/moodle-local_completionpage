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
 * Detects optional integrations used by local_completionpage.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_completionpage\service;

use local_completionpage\certificate\customcert_provider;

/**
 * Reports which optional plugins are missing on the site.
 */
class optional_integrations {
    /** Moodle plugins directory URL for Custom certificate. */
    public const URL_CUSTOMCERT = 'https://moodle.org/plugins/mod_customcert';

    /** Download URL for Time spent. */
    public const URL_TIMESPENT = 'https://github.com/mooplugins/moodle-local_timespent';

    /** Product URL for Moodle eCommerce. */
    public const URL_ECOMMERCE = 'https://www.mooplugins.com/';

    /**
     * Whether a plugin is installed, upgraded, and enabled.
     *
     * @param string $frankenstyle Plugin component name (for example, tool_ecommerce).
     * @return bool
     */
    public static function is_plugin_installed_and_enabled(string $frankenstyle): bool {
        $pluginman = \core_plugin_manager::instance();
        $plugin = $pluginman->get_plugin_info($frankenstyle);

        if (!$plugin || !$plugin->is_installed_and_upgraded()) {
            return false;
        }

        // Local plugins and others without an enable toggle return null (treat as enabled).
        return $plugin->is_enabled() !== false;
    }

    /**
     * Whether Custom certificate (mod_customcert) is installed, enabled, and ready.
     *
     * @return bool
     */
    public static function is_customcert_available(): bool {
        return (new customcert_provider())->is_available();
    }

    /**
     * Whether E-commerce (tool_ecommerce) is installed, enabled, and ready.
     *
     * Pricing and add-to-cart on suggested course cards use this integration only
     * when this returns true.
     *
     * @return bool
     */
    public static function is_ecommerce_available(): bool {
        return self::is_plugin_installed_and_enabled('tool_ecommerce')
            && class_exists('\tool_ecommerce\helper');
    }

    /**
     * Whether enrol_ecommerce is installed and enabled (companion to tool_ecommerce).
     *
     * @return bool
     */
    public static function is_ecommerce_enrol_available(): bool {
        return self::is_plugin_installed_and_enabled('enrol_ecommerce');
    }

    /**
     * Whether the full ecommerce UI (pricing + add-to-cart handlers) can be used.
     *
     * @return bool
     */
    public static function is_ecommerce_ui_available(): bool {
        global $CFG;

        return self::is_ecommerce_available()
            && self::is_ecommerce_enrol_available()
            && file_exists($CFG->dirroot . '/enrol/ecommerce/amd/src/helper.js');
    }

    /**
     * Whether a completion-page section can be shown / configured.
     *
     * Sections that hard-depend on an optional plugin return false when that
     * plugin is missing or disabled. Other sections always return true.
     *
     * @param string $section One of: message, certificates, feedback, suggested, achievements, exit.
     * @return bool
     */
    public static function is_section_available(string $section): bool {
        switch ($section) {
            case 'certificates':
                return self::is_customcert_available();
            case 'message':
            case 'feedback':
            case 'suggested':
            case 'achievements':
            case 'exit':
                return true;
            default:
                return true;
        }
    }

    /**
     * HTML explaining why a section setting is unavailable.
     *
     * @param string $pluginname Display name of the required plugin.
     * @param string $url Download / product URL.
     * @return string
     */
    public static function render_section_unavailable_notice(string $pluginname, string $url): string {
        $link = \html_writer::link(
            $url,
            get_string('settings_integrations_link', 'local_completionpage'),
            ['target' => '_blank', 'rel' => 'noopener noreferrer']
        );

        return \html_writer::div(
            get_string('settings_section_unavailable', 'local_completionpage', (object) [
                'plugin' => $pluginname,
                'link' => $link,
            ]),
            'text-muted'
        );
    }

    /**
     * Coming-soon notice for Moodle eCommerce under suggested courses settings.
     *
     * @return string
     */
    public static function render_ecommerce_coming_soon_notice(): string {
        $link = \html_writer::link(
            self::URL_ECOMMERCE,
            get_string('settings_ecommerce_comingsoon_link', 'local_completionpage'),
            ['target' => '_blank', 'rel' => 'noopener noreferrer']
        );

        $message = get_string('settings_ecommerce_comingsoon', 'local_completionpage', $link);

        return \html_writer::div($message, 'alert alert-info mt-2 mb-0');
    }
}
