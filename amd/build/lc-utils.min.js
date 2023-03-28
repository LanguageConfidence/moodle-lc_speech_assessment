/**
 * @package   qtype_lcspeech
 * @copyright 2023 Speech Assessment
 */

define(['core/log', 'core/modal_factory', 'qtype_lcspeech/recorder'], function(Log, ModalFactory, MediaRecorder) {
    console.log('MediaRecorder=', MediaRecorder);

    function lcspeechQuestion() {
        console.log(`Hello world`);
        alert('OK')
    }

    return {
        /**
         * Initialise a Speech Assessment question.
         *
         * @param {string} questionId id of the outer question div.
         * @param {Object} settings like audio bit rate.
         */
        init: function() {
            M.util.js_pending('init-');
            new lcspeechQuestion();
            M.util.js_complete('init-');
        }
    };
});
