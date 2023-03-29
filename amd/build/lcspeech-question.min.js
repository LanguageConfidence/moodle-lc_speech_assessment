/**
 * @package   qtype_lcspeech
 * @copyright 2023 Speech Assessment
 */

define(['core/log', 'core/modal_factory', 'qtype_lcspeech/recorder'], function(Log, ModalFactory, MediaRecorder) {
    console.log('MediaRecorder=', MediaRecorder);

    /**
     * Verify that the question type can work. If not, show a warning.
     *
     * @return {string} 'ok' if it looks OK, else 'nowebrtc' or 'nothttps' if there is a problem.
     */
    function checkCanWork() {
        if (!(navigator.mediaDevices && typeof MediaRecorder !== 'undefined')) {
            return 'nowebrtc';
        }

        if (!(location.protocol === 'https:' || location.host.indexOf('localhost') !== -1)) {
            return 'nothttps';
        }

        return 'ok';
    }

    /**
     * Object for actually doing the recording.
     *
     * The recorder can be in one of 4 states, which is stored in a data-state
     * attribute on the button. The states are:
     *  - new:       there is no recording yet. Button shows 'Start recording'.
     *  - recording: buttons shows a countdown of remaining time. Media is being recorded.
     *  - saving:    buttons shows a progress indicator.
     *  - recorded:  button shows 'Record again'.
     *
     * @param {(AudioSettings)} type
     * @param {HTMLMediaElement} mediaElement
     * @param {HTMLMediaElement} noMediaPlaceholder
     * @param {HTMLButtonElement} button
     * @param {string} filename the name of the audio (.wav)
     * @param {Object} owner
     * @param {Object} settings
     * @param {Object} questionDiv
     * @constructor
     */
    function Recorder(type, mediaElement, noMediaPlaceholder,
                      button, filename, owner, settings, questionDiv) {
        /**
         * @type {Recorder} reference to this recorder, for use in event handlers.
         */
        var recorder = this;

        /**
         * @type {MediaStream} during recording, the stream of incoming media.
         */
        var mediaStream = null;

        /**
         * @type {MediaRecorder} the recorder that is capturing stream.
         */
        var mediaRecorder = null;

        /**
         * @type {number} time left in seconds, so we can auto-stop at the time limit.
         */
        var secondsRemaining = 0;

        /**
         * @type {number} intervalID returned by setInterval() while the timer is running.
         */
        var countdownTicker = 0;

        button.addEventListener('click', handleButtonClick);
        this.uploadMediaToServer = uploadMediaToServer; // Make this method available.

        /**
         * Handles clicks on the start/stop button.
         *
         * @param {Event} e
         */
        function handleButtonClick(e) {
            Log.debug('Start/stop button clicked.');
            e.preventDefault();
            switch (button.dataset.state) {
                case 'new':
                case 'recorded':
                    startRecording();
                    break;
                case 'recording':
                    stopRecording();
                    break;
            }
        }

        /**
         * Start recording (because the button was clicked).
         */
        function startRecording() {

            if (type.hidePlayerDuringRecording) {
                mediaElement.parentElement.classList.add('hide');
                noMediaPlaceholder.classList.remove('hide');
                noMediaPlaceholder.textContent = '\u00a0';
            } else {
                mediaElement.parentElement.classList.remove('hide');
                noMediaPlaceholder.classList.add('hide');
            }

            // Change look of recording button.
            button.classList.remove('btn-outline-warning');
            button.classList.add('btn-danger');

            // Disable other question buttons when current widget stared recording.
            disableAllButtons();

            Log.debug('Audio question: Starting recording with media constraints');
            Log.debug(type.mediaConstraints);
            navigator.mediaDevices.getUserMedia(type.mediaConstraints)
                .then(handleCaptureStarting)
                .catch(handleCaptureFailed);
        }

        /**
         * Callback once getUserMedia has permission from the user to access the recording devices.
         *
         * @param {MediaStream} stream the stream to record.
         */
        function handleCaptureStarting(stream) {
            mediaStream = stream;

            // Initialize MediaRecorder events and start recording.
            Log.debug('Audio question: creating recorder');
            var audioContext = new AudioContext();
            var source = audioContext.createMediaStreamSource(stream);
            mediaRecorder = new MediaRecorder(source, {numChannels: 1});

            Log.debug('Audio question: starting recording.');
            mediaRecorder.record();

            // Setup the UI for during recording.
            mediaElement.srcObject = stream;
            mediaElement.muted = true;
            if (!type.hidePlayerDuringRecording) {
                mediaElement.play();
                mediaElement.controls = false;
            }
            button.dataset.state = 'recording';
            startCountdownTimer();

            // Make button clickable again, to allow stopping recording.
            button.disabled = false;
            button.focus();
        }


        /**
         * Start recording (because the button was clicked or because we have reached a limit).
         */
        function stopRecording() {
            // Disable the button while things change.
            button.disabled = true;

            // Stop the count-down timer.
            stopCountdownTimer();

            // Update the button.
            button.classList.remove('btn-danger');
            button.classList.add('btn-outline-warning');

            // Ask the recording to stop.
            Log.debug('Audio question: stopping recording.');
            mediaRecorder.stop();

            // Also stop each individual MediaTrack.
            var tracks = mediaStream.getTracks();
            for (var i = 0; i < tracks.length; i++) {
                tracks[i].stop();
            }

            mediaRecorder.exportWAV(handleRecordingHasStopped);
        }

        /**
         * Callback that is called by the media system once recording has finished.
         */
        function handleRecordingHasStopped(blob) {
            if (button.dataset.state === 'new') {
                // This can happens if an error occurs when recording is starting. Do nothing.
                return;
            }

            // Set source of audio player.
            Log.debug('Audio question: recording stopped.');
            Log.debug('Created blob', blob);
            mediaElement.srcObject = null;
            mediaElement.src = URL.createObjectURL(blob);

            // Show audio player with controls enabled, and unmute.
            mediaElement.muted = false;
            mediaElement.controls = true;
            mediaElement.parentElement.classList.remove('hide');
            noMediaPlaceholder.classList.add('hide');
            mediaElement.focus();

            // Encure the button while things change.
            button.disabled = true;
            button.classList.remove('btn-danger');
            button.classList.add('btn-outline-warning');
            button.dataset.state = 'recorded';

            owner.notifyRecordingComplete(recorder);
        }

        /**
         * Function that handles errors from the recorder.
         *
         * @param {DOMException} error
         */
        function handleCaptureFailed(error) {
            Log.debug('Audio question: error received');
            Log.debug(error);

            setPlaceholderMessage('recordingfailed');
            setButtonLabel('recordagain');
            button.classList.remove('btn-danger');
            button.classList.add('btn-outline-warning');
            button.dataset.state = 'new';

            if (mediaRecorder) {
                mediaRecorder.stop();
            }

            // Changes 'CertainError' -> 'gumcertain' to match language string names.
            var stringName = 'gum' + error.name.replace('Error', '').toLowerCase();

            owner.showAlert(stringName);
            enableAllButtons();
        }

        /**
         * Start the countdown timer from settings.timeLimit.
         */
        function startCountdownTimer() {
            secondsRemaining = settings.timeLimit;

            updateTimerDisplay();
            countdownTicker = setInterval(updateTimerDisplay, 1000);
        }

        /**
         * Stop the countdown timer.
         */
        function stopCountdownTimer() {
            if (countdownTicker !== 0) {
                clearInterval(countdownTicker);
                countdownTicker = 0;
            }
        }

        /**
         * Update the countdown timer, and stop recording if we have reached 0.
         */
        function updateTimerDisplay() {
            var secs = secondsRemaining % 60;
            var mins = Math.round((secondsRemaining - secs) / 60);
            setButtonLabel('recordinginprogress', pad(mins) + ':' + pad(secs));

            if (secondsRemaining === -1) {
                stopRecording();
            }
            secondsRemaining -= 1;
        }

        /**
         * Zero-pad a string to be at least two characters long.
         *
         * Used fro
         * @param {number} val, e.g. 1 or 10
         * @return {string} e.g. '01' or '10'.
         */
        function pad(val) {
            var valString = val + '';

            if (valString.length < 2) {
                return '0' + valString;
            } else {
                return valString;
            }
        }

        /**
         * Upload the recorded media back to Moodle.
         */
        function uploadMediaToServer() {
            setButtonLabel('uploadpreparing');

            var fetchRequest = new XMLHttpRequest();

            // Get media of audio tag.
            fetchRequest.open('GET', mediaElement.src);
            fetchRequest.responseType = 'blob';
            fetchRequest.addEventListener('load', handleRecordingFetched);
            fetchRequest.send();
        }

        /**
         * Callback called once we have the data from the media element.
         *
         * @param {ProgressEvent} e
         */
        function handleRecordingFetched(e) {
            var fetchRequest = e.target;
            if (fetchRequest.status !== 200) {
                // No data.
                return;
            }

            // Blob is now the media that the audio tag's src pointed to.
            var blob = fetchRequest.response;

            // Create FormData to send to PHP filepicker-upload script.
            var formData = new FormData();
            formData.append('repo_upload_file', blob, filename);
            formData.append('sesskey', M.cfg.sesskey);
            formData.append('repo_id', settings.uploadRepositoryId);
            formData.append('itemid', settings.draftItemId);
            formData.append('savepath', '/');
            formData.append('ctx_id', settings.contextId);
            formData.append('overwrite', 1);

            var uploadRequest = new XMLHttpRequest();
            uploadRequest.addEventListener('readystatechange', handleUploadReadyStateChanged);
            uploadRequest.upload.addEventListener('progress', handleUploadProgress);
            uploadRequest.addEventListener('error', handleUploadError);
            uploadRequest.addEventListener('abort', handleUploadAbort);
            uploadRequest.open('POST', M.cfg.wwwroot + '/repository/repository_ajax.php?action=upload');
            uploadRequest.send(formData);
        }

        /**
         * Callback for when the upload completes.
         * @param {ProgressEvent} e
         */
        function handleUploadReadyStateChanged(e) {
            var uploadRequest = e.target;
            if (uploadRequest.readyState === 4 && uploadRequest.status === 200) {
                // When request finished and successful.
                setButtonLabel('recordagain');
                enableAllButtons();
            } else if (uploadRequest.status === 404) {
                setPlaceholderMessage('uploadfailed404');
                enableAllButtons();
            }
        }

        /**
         * Callback for updating the upload progress.
         * @param {ProgressEvent} e
         */
        function handleUploadProgress(e) {
            setButtonLabel('uploadprogress', Math.round(e.loaded / e.total * 100) + '%');
        }

        /**
         * Callback for when the upload fails with an error.
         */
        function handleUploadError() {
            setPlaceholderMessage('uploadfailed');
            enableAllButtons();
        }

        /**
         * Callback for when the upload fails with an error.
         */
        function handleUploadAbort() {
            setPlaceholderMessage('uploadaborted');
            enableAllButtons();
        }

        /**
         * Display a progress message in the upload progress area.
         *
         * @param {string} langString
         * @param {Object|String} a optional variable to populate placeholder with
         */
        function setButtonLabel(langString, a) {
            button.innerText = M.util.get_string(langString, 'qtype_lcspeech', a);
        }

        /**
         * Display a message in the upload progress area.
         *
         * @param {string} langString
         * @param {Object|String} a optional variable to populate placeholder with
         */
        function setPlaceholderMessage(langString, a) {
            noMediaPlaceholder.textContent = M.util.get_string(langString, 'qtype_lcspeech', a);
            mediaElement.parentElement.classList.add('hide');
            noMediaPlaceholder.classList.remove('hide');
        }

        /**
         * Select best options for the recording codec.
         *
         * @returns {Object}
         */
        function getRecordingOptions() {
            var options = {};

            // Get the relevant bit rates from settings.
            options.audioBitsPerSecond = parseInt(settings.audioBitRate, 10);
            options.mimeType = 'audio/wav';

            return options;
        }

        /**
         * Enable all buttons in the question.
         */
        function enableAllButtons() {
            disableOrEnableButtons(true);
            owner.notifyButtonStatesChanged();
        }

        /**
         * Disable all buttons in the question.
         */
        function disableAllButtons() {
            disableOrEnableButtons(false);
        }

        /**
         * Disables/enables other question buttons when current widget started recording/finished recording.
         *
         * @param {boolean} enabled true if the button should be enabled.
         */
        function disableOrEnableButtons(enabled = false) {
            questionDiv.querySelectorAll('button, input[type=submit], input[type=button]').forEach(
                function(button) {
                    button.disabled = !enabled;
                }
            );
        }
    }

    /**
     * Object that controls the settings for recording audio.
     *
     * @constructor
     */
    function AudioSettings() {
        this.name = 'audio';
        this.hidePlayerDuringRecording = true;
        this.mediaConstraints = {
            audio: true
        };
        this.mimeTypes = [
            'audio/webm;codecs=opus',
            'audio/ogg;codecs=opus'
        ];
    }

    /**
     * Represents one Speech Assessment question.
     *
     * @param {string} questionId id of the outer question div.
     * @param {Object} settings like audio bit rate.
     * @constructor
     */
    function lcspeechQuestion(questionId, settings) {
        var questionDiv = document.getElementById(questionId);

        // Check if the RTC API can work here.
        var result = checkCanWork();
        if (result === 'nothttps') {
            questionDiv.querySelector('.https-warning').classList.remove('hide');
            return;
        } else if (result === 'nowebrtc') {
            questionDiv.querySelector('.no-webrtc-warning').classList.remove('hide');
            return;
        }

        // We may have more than one widget in a question.
        var recorderElements = questionDiv.querySelectorAll('.audio-widget');
        recorderElements.forEach(function(widget) {
            // Get the key UI elements.
            var type = widget.dataset.mediaType;
            var button = widget.querySelector('.record-button button');
            var mediaElement = widget.querySelector('.media-player ' + type);
            var noMediaPlaceholder = widget.querySelector('.no-recording-placeholder');
            var filename = widget.dataset.recordingFilename;

            // Get the appropriate options.
            var typeInfo = new AudioSettings();

            // Make the callback functions available.
            this.showAlert = showAlert;
            this.notifyRecordingComplete = notifyRecordingComplete;
            this.notifyButtonStatesChanged = setSubmitButtonState;

            // Create the recorder.
            new Recorder(typeInfo, mediaElement, noMediaPlaceholder, button,
                    filename, this, settings, questionDiv);
        });
        setSubmitButtonState();

        /**
         * Set the state of the question's submit button.
         *
         * If any recorder does not yet have a recording, then disable the button.
         * Otherwise, enable it.
         */
        function setSubmitButtonState() {
            var anyRecorded = false;
            questionDiv.querySelectorAll('.audio-widget').forEach(function(widget) {
                if (widget.querySelector('.record-button button').dataset.state === 'recorded') {
                    anyRecorded = true;
                }
            });
            var submitButton = questionDiv.querySelector('input.submit[type=submit]');
            if (submitButton) {
                submitButton.disabled = !anyRecorded;
           }
        }

        /**
         * Show a modal alert.
         *
         * @param {string} subject Subject is the content of the alert (which error the alert is for).
         * @return {Promise}
         */
        function showAlert(subject) {
            return ModalFactory.create({
                type: ModalFactory.types.ALERT,
                title: M.util.get_string(subject + '_title', 'qtype_lcspeech'),
                body: M.util.get_string(subject, 'qtype_lcspeech'),
            }).then(function(modal) {
                modal.show();
                return modal;
            });
        }

        /**
         * Callback called when the recording is completed.
         *
         * @param {Recorder} recorder the recorder.
         */
        function notifyRecordingComplete(recorder) {
            recorder.uploadMediaToServer();
        }
    }

    return {
        /**
         * Initialise a Speech Assessment question.
         *
         * @param {string} questionId id of the outer question div.
         * @param {Object} settings like audio bit rate.
         */
        init: function(questionId, settings) {
            M.util.js_pending('init-' + questionId);
            new lcspeechQuestion(questionId, settings);
            M.util.js_complete('init-' + questionId);
        }
    };
});
