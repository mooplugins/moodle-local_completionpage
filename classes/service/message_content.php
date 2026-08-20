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
 * message_content for local_completionpage.
 *
 * @package    local_completionpage
 * @copyright  2026 Mooplugins
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_completionpage\service;

/**
 * Default and formatted completion message headline/body HTML.
 */
class message_content {
    /** @var string File area for the custom headline editor. */
    public const FILEAREA_HEADLINE = 'customheadline';

    /** @var string File area for the custom message editor. */
    public const FILEAREA_MESSAGE = 'custommessage';

    /**
     * Editor options for course completion message fields.
     *
     * @param int $courseid
     * @return array
     */
    public static function editor_options(int $courseid): array {
        global $CFG;

        require_once($CFG->dirroot . '/repository/lib.php');

        return [
            'subdirs' => 0,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $CFG->maxbytes,
            'changeformat' => 0,
            'context' => \context_course::instance($courseid),
            'noclean' => true,
            'trusttext' => true,
            'enable_filemanagement' => true,
            'return_types' => FILE_INTERNAL | FILE_EXTERNAL,
            'autosave' => true,
        ];
    }

    /**
     * Default headline HTML for the completion message section.
     *
     * @return string
     */
    public static function default_headline_html(): string {
        $prefix = s(get_string('coursetitleprefix', 'local_completionpage'));
        $accent = s(get_string('coursetitleaccent', 'local_completionpage'));

        return '<h1 class="fw-bold mb-2">🎉 '
            . $prefix . ' <span class="text-primary">' . $accent . '</span></h1>';
    }

    /**
     * Default congratulations message HTML.
     *
     * @return string
     */
    public static function default_message_html(): string {
        return '<p class="text-muted mb-3">'
            . s(get_string('congratulations', 'local_completionpage'))
            . '</p>';
    }

    /**
     * Prepare stored HTML for an editor field, including draft-area files.
     *
     * @param string|null $stored
     * @param int $courseid
     * @param string $filearea
     * @param string $fieldname
     * @param callable $defaultcallback
     * @return array
     */
    public static function to_editor(
        ?string $stored,
        int $courseid,
        string $filearea,
        string $fieldname,
        callable $defaultcallback
    ): array {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

        $html = $stored;
        if (!self::has_meaningful_html($html)) {
            $html = $defaultcallback();
        }

        $context = \context_course::instance($courseid);
        $options = self::editor_options($courseid);
        $draftitemid = file_get_submitted_draft_itemid($fieldname);
        $text = file_prepare_draft_area(
            $draftitemid,
            $context->id,
            'local_completionpage',
            $filearea,
            $courseid,
            $options,
            (string) $html
        );

        return [
            'text' => $text,
            'format' => FORMAT_HTML,
            'itemid' => $draftitemid,
        ];
    }

    /**
     * Save editor content and embedded files from the draft area.
     *
     * @param mixed $value
     * @param int $courseid
     * @param string $filearea
     * @return string
     */
    public static function from_editor($value, int $courseid, string $filearea): string {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

        if (!is_array($value)) {
            return trim((string) $value);
        }

        $text = $value['text'] ?? '';
        $draftid = (int) ($value['itemid'] ?? 0);
        if (!$draftid) {
            return trim($text);
        }

        $context = \context_course::instance($courseid);
        $options = self::editor_options($courseid);

        return file_save_draft_area_files(
            $draftid,
            $context->id,
            'local_completionpage',
            $filearea,
            $courseid,
            $options,
            $text
        );
    }

    /**
     * Headline HTML for the editor (stored value or default).
     *
     * @param string|null $stored
     * @return string
     */
    public static function editor_headline_html(?string $stored): string {
        if (!self::has_meaningful_html($stored)) {
            return self::default_headline_html();
        }

        return (string) $stored;
    }

    /**
     * Message HTML for the editor (stored value or default).
     *
     * @param string|null $stored
     * @return string
     */
    public static function editor_message_html(?string $stored): string {
        if (!self::has_meaningful_html($stored)) {
            return self::default_message_html();
        }

        return (string) $stored;
    }

    /**
     * Format headline HTML for display on the completion page.
     *
     * @param string|null $stored
     * @param \context $context
     * @return string
     */
    public static function format_headline_html(?string $stored, \context $context): string {
        return self::format_stored_html(
            self::editor_headline_html($stored),
            $context,
            self::FILEAREA_HEADLINE
        );
    }

    /**
     * Format message HTML for display on the completion page.
     *
     * @param string|null $stored
     * @param \context $context
     * @return string
     */
    public static function format_message_html(?string $stored, \context $context): string {
        return self::format_stored_html(
            self::editor_message_html($stored),
            $context,
            self::FILEAREA_MESSAGE
        );
    }

    /**
     * Normalize editor submission for storage (null when empty or unchanged default).
     *
     * @param string|null $html
     * @param string $type headline|message
     * @return string|null
     */
    public static function normalize_for_storage(?string $html, string $type): ?string {
        if (!self::has_meaningful_html($html)) {
            return null;
        }

        $html = trim((string) $html);
        $default = $type === 'headline'
            ? self::default_headline_html()
            : self::default_message_html();

        if (self::normalize_html($html) === self::normalize_html($default)) {
            return null;
        }

        return $html;
    }

    /**
     * Delete embedded editor files for a course.
     *
     * @param int $courseid
     */
    public static function delete_course_files(int $courseid): void {
        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return;
        }
        $fs = get_file_storage();
        foreach ([self::FILEAREA_HEADLINE, self::FILEAREA_MESSAGE] as $filearea) {
            $fs->delete_area_files($context->id, 'local_completionpage', $filearea, $courseid);
        }
    }

    /**
     * Whether HTML contains visible content.
     *
     * @param string|null $html
     * @return bool
     */
    public static function has_meaningful_html(?string $html): bool {
        $text = trim(strip_tags(preg_replace('/\xc2\xa0|&nbsp;|&#160;/i', ' ', (string) $html)));
        return $text !== '';
    }

    /**
     * Rewrite plugin file URLs and format stored HTML for output.
     *
     * @param string $html
     * @param \context $context
     * @param string $filearea
     * @return string
     */
    private static function format_stored_html(string $html, \context $context, string $filearea): string {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

        $html = file_rewrite_pluginfile_urls(
            $html,
            'pluginfile.php',
            $context->id,
            'local_completionpage',
            $filearea,
            $context->instanceid
        );

        return format_text($html, FORMAT_HTML, [
            'context' => $context,
            'noclean' => true,
            'filter' => false,
        ]);
    }

    /**
     * Collapse HTML whitespace for default comparison.
     *
     * @param string $html
     * @return string
     */
    private static function normalize_html(string $html): string {
        return preg_replace('/\s+/', ' ', trim($html));
    }
}
