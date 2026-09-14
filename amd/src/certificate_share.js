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
 * Certificate share actions on the completion page.
 *
 * @module     local_completionpage/certificate_share
 * @author     BitKea Technologies LLP
 * @copyright  2026 BitKea Technologies LLP
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/str', 'core/toast'], function(Str, Toast) {
    /**
     * @param {string} text
     * @return {Promise<void>}
     */
    const copyText = async(text) => {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(text);
            return;
        }
        const input = document.createElement('textarea');
        input.value = text;
        input.setAttribute('readonly', '');
        input.style.position = 'absolute';
        input.style.left = '-9999px';
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
    };

    /**
     * Initialise share/copy handlers.
     */
    const init = () => {
        document.querySelectorAll('[data-ccp-copy]').forEach((el) => {
            el.addEventListener('click', async(e) => {
                e.preventDefault();
                const url = el.getAttribute('data-ccp-copy');
                if (!url) {
                    return;
                }
                try {
                    await copyText(url);
                    const message = await Str.get_string('linkcopied', 'local_completionpage');
                    Toast.add(message, {type: 'success'});
                } catch (error) {
                    // Fallback: open the certificate URL.
                    window.open(url, '_blank', 'noopener');
                }
            });
        });
    };

    return {init};
});
