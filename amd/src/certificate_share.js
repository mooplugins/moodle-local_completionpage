/**
 * Certificate share actions on the completion page.
 *
 * @module local_completionpage/certificate_share
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
