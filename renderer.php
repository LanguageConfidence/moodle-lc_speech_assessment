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
 * The Speech Assessment question type question renderer class.
 *
 * @package   qtype_lcspeech
 * @copyright 2023 Speech Assessment
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot . '/question/type/lcspeech/lib.php');


/**
 * Generates output for Speech Assessment questions.
 *
 * @copyright 2023 Speech Assessment
 */
class qtype_lcspeech_renderer extends qtype_renderer {

    public $urls = [];
    public $range = '';

    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        qtype_lcspeech_ensure_api_config_is_set();

        $question = $qa->get_question();
        $output = '';

        $existingfiles = $qa->get_last_qt_files('recording', $options->context->id);
        if (!$options->readonly) {
            // Prepare a draft file area to store the recordings.
            $draftitemid = $qa->prepare_response_files_draft_itemid('recording', $options->context->id);

            // Add a hidden form field with the draft item id.
            $output .= html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => $qa->get_qt_field_name('recording'), 'value' => $draftitemid
            ]);

            // Warning for browsers that won't work.
            $output .= $this->cannot_work_warnings();
        }

        if ($qa->get_state() == question_state::$invalid) {
            $output .= html_writer::nonempty_tag(
                'div',
                $question->get_validation_error([]),
                ['class' => 'validationerror']
            );
        }

        // Replace all the placeholders with the corresponding recording or player widget.
        $questiontext = $question->format_questiontext($qa);
        foreach ($question->widgetplaceholders as $placeholder => [$title, $mediatype]) {
            $filename = \qtype_lcspeech::get_media_filename($title, $mediatype);
            $existingfile = $question->get_file_from_response($filename, $existingfiles);
            if ($options->readonly) {
                if ($existingfile) {
                    $thisitem = $this->playback_ui($qa->get_response_file_url($existingfile), $mediatype, $filename);
                } else {
                    $thisitem = $this->no_recording_message();
                }
            } else {
                if ($existingfile) {
                    $recordingurl = moodle_url::make_draftfile_url($draftitemid, '/', $filename);
                    $state = 'recorded';
                    $label = get_string('recordagain', 'qtype_lcspeech');
                } else {
                    $recordingurl = null;
                    $state = 'new';
                    $label = get_string('startrecording', 'qtype_lcspeech');
                }

                // Recording UI.
                $thisitem = $this->recording_ui($filename, $recordingurl, $state, $label, $mediatype);
            }

            $questiontext = str_replace($placeholder, $thisitem, $questiontext);
        }

        $output .= html_writer::tag('div', $questiontext, ['class' => 'qtext']);

        if (!$options->readonly) {
            // Initialise the JavaScript.
            $repositories = repository::get_instances(
                ['type' => 'upload', 'currentcontext' => $options->context]
            );
            if (empty($repositories)) {
                throw new moodle_exception('errornouploadrepo', 'moodle');
            }
            $uploadrepository = reset($repositories); // Get the first (and only) upload repo.
            $setting = [
                'speechPhrase' => (int) $question->speechphrase,
                'timeLimit' => (int) $question->timelimitinseconds,
                'audioBitRate' => (int) get_config('qtype_lcspeech', 'audiobitrate'),
                'maxUploadSize' => $question->get_upload_size_limit($options->context),
                'uploadRepositoryId' => (int) $uploadrepository->id,
                'contextId' => $options->context->id,
                'draftItemId' => $draftitemid,
            ];
            $this->page->requires->strings_for_js($this->strings_for_js(), 'qtype_lcspeech');
            $this->page->requires->js_call_amd('qtype_lcspeech/recorder');
            $this->page->requires->js_call_amd(
                'qtype_lcspeech/lcspeech-question',
                'init',
                [$qa->get_outer_question_div_unique_id(), $setting]
            );
        }
        return $output;
    }

    /**
     * These messages are hidden unless revealed by the JavaScript.
     *
     * @return string HTML for the 'this can't work here' messages.
     */
    protected function cannot_work_warnings() {
        return '
                <div class="hide alert alert-danger https-warning">
                    <h5>' . get_string('insecurewarningtitle', 'qtype_lcspeech') . '</h5>
                    <p>' . get_string('insecurewarning', 'qtype_lcspeech') . '</p>
                </div>
                <div class="hide alert alert-danger no-webrtc-warning">
                    <h5>' . get_string('nowebrtctitle', 'qtype_lcspeech') . '</h5>
                    <p>' . get_string('nowebrtc', 'qtype_lcspeech') . '</p>
                </div>';
    }

    /**
     * Generate the HTML for the recording UI.
     *
     * Note: the JavaScript relies on a lot of the CSS class names here.
     *
     * @param string $filename the filename to use for this recording.
     * @param string|null $recordingurl URL for the recording, if there is one, else null.
     * @param string $state value for the data-state attribute of the record button.
     * @param string $label label for the record button.
     * @param string $mediatype 'audio'.
     * @return string HTML to output.
     */
    protected function recording_ui(
        string $filename,
        ?string $recordingurl,
        string $state,
        string $label,
        string $mediatype
    ) {
        if ($recordingurl) {
            $mediaplayerhideclass = '';
            $norecordinghideclass = 'hide ';
        } else {
            $mediaplayerhideclass = 'hide ';
            $norecordinghideclass = '';
        }
        // Set the 'No recording' language string.
        $norecordinglangstring = get_string('norecording', 'qtype_lcspeech');
        $note = get_string('note', 'qtype_lcspeech');

        return '
            <span class="' . $mediatype . '-widget" data-media-type="' . $mediatype . '" data-recording-filename="' . $filename . '">
                <span class="' . $norecordinghideclass . 'no-recording-placeholder">' . $norecordinglangstring . '</span>
                <span class="' . $mediaplayerhideclass . 'media-player">
                    <' . $mediatype . ' controls>
                        <source src="' . $recordingurl . '">
                    </' . $mediatype . '>
                </span>
                <span class="record-button">
                    <button type="button" class="btn btn-outline-warning osep-smallbutton"
                            data-state="' . $state . '">' . $label . '</button>
                </span>
            </span>
            <div style="margin-top:10px "><p class="note">Note: ' . $note . '</p></div>';
    }

    /**
     * Render the playback UI - e.g. when the question is reviewed.
     *
     * @param string $recordingurl URL for the recording.
     * @param string $mediatype 'audio'.
     * @param string $filename the name of the audio file.
     * @return string HTML to output.
     */
    protected function playback_ui($recordingurl, string $mediatype, string $filename) {
        // Prepare download link of icon and the title based on mimetype.
        $downloadlink = html_writer::link($recordingurl, $this->pix_icon(
            'f/' . $mediatype,
            get_string('downloadrecording', 'qtype_lcspeech', $filename),
            null,
            ['class' => 'download-icon-' . $mediatype]
        ));

        return '<div class="mb-2 mt-2"><label><b>Your Answer</b></label></div>
            <span class="' . $mediatype . '-widget">
                <span class="media-player">
                    <' . $mediatype . ' controls>
                        <source src="' . $recordingurl . '">
                    </' . $mediatype . '>
                </span>
                ' . $downloadlink . '
            </span>';
    }

    /**
     * Render a message to say there is no recording.
     *
     * @return string HTML to output.
     */
    protected function no_recording_message() {
        return '
            <span class="playback-widget">
                <span class="no-recording-placeholder">' .
            get_string('norecording', 'qtype_lcspeech') .
            '</span>
            </span>';
    }


    protected function build_pronunciation_feedback($resultwords) {
        $feedback = '';

        $words = array_filter(array_map(function ($word) {
            return array(
                'label' => $word['word_text'],
                'mean' => $word['word_score'],
                'phones' => array_filter($word['phonemes'], function ($phoneme) {
                    return $phoneme['ipa_label'] !== 'SIL';
                }),
            );
        }, $resultwords), function ($word) {
            return count($word['phones']) > 0;
        });
        foreach ($words as $word) {
            $wordfeedback = '';
            foreach ($word['phones'] as $phoneme) {
                if ($phoneme['phoneme_score'] >= 60) {
                    $scorecolour = 'green';
                } else if ($phoneme['phoneme_score'] >= 30) {
                    $scorecolour = 'orange';
                } else {
                    $scorecolour = 'red';
                }
                $phone_label = $phoneme['ipa_label'];
                $scorecolourclass = 'qtype_lcspeech_phoneme_label_' . $scorecolour;
                $wordfeedback .= '<div class="qtype_lcspeech_phoneme"><div class="qtype_lcspeech_phoneme_label ' . $scorecolourclass . '">' . $phone_label . '</div><div class="qtype_lcspeech_phoneme_score">' . $phoneme['phoneme_score'] . '%</div></div>';
            }

            $feedback .= '<div class="qtype_lcspeech_word"><div class="qtype_lcspeech_word_label">' . $word['label'] . '</div><div class="qtype_lcspeech_phonemes">' . $wordfeedback . '</div></div>';
        }

        return $feedback;
    }

    public function specific_feedback_pronunciation(question_attempt $qa) {
        qtype_lcspeech_ensure_api_config_is_set();

        $question = $qa->get_question();
        $qubaid = $qa->get_usage_id();
        $slot = $qa->get_slot();
        $fs = get_file_storage();
        $componentname = $question->qtype->plugin_name();

        // Add correct audio URL to the $urls array if exist
        if (isset($question->correctionAudios) && !empty($question->correctionAudios)) {
            foreach ($question->correctionAudios as $corr ) {
                $draftfiles = $fs->get_area_files($question->contextid, $componentname,'correction_audio', $corr->unique_item_id, 'id', false);
                if ($draftfiles) {
                    foreach ($draftfiles as $file) {
                        if ($file->is_directory()) {
                            continue;
                        }
                        $url = moodle_url::make_pluginfile_url($question->contextid, $componentname,'correction_audio', "$qubaid/$slot/{$corr->unique_item_id}", '/',  $file->get_filename());

                        array_push($this->urls, $url->out());
                    }
                }
            }
        }

        $question = $qa->get_question();
        $response = $qa->get_last_qt_data();
        if (!empty($response) && $question->is_complete_response($response)) {
            $files = $response['recording']->get_files();
            $allfeedback = '';
            foreach ($files as $file) {
                $audio = $file->get_content();
                $result = $question->get_score_for_audio($audio);

                $feedback = '';

                $feedbackpronuncation = $this->build_pronunciation_feedback($result['words']);

                // Add feedback range if exist
                if (isset($question->feedbackRange) && !empty($question->feedbackRange)) {
                    foreach ($question->feedbackRange as $r) {
                        if ($this->nBetween($result['overall_score'], (int)$r->to_range, (int)$r->from_range)) {
                            $this->range = $r->feedback;
                        }

                        if ((int)$r->to_range === $result['overall_score'] || (int)$r->from_range === $result['overall_score']) {
                            $this->range = $r->feedback;
                        }
                    }
                }

                $allfeedback .= '<div><div class="qtype_lcspeech_average_score">' . get_string('lbl_overallscore', 'qtype_lcspeech') . ': ' . $result['overall_score'] . '</div></div>';

                $allfeedback .= $this->render_tabs($question);
                $allfeedback .= '<div class="tab-content" id="myTabContent">';

                $pronunciationfeedback = '
                    <div id="collapsePro2Feedback-' . $question->id . '" class="tab-pane fade show active" aria-labelledby="tabPro2Feedback-' . $question->id . '" role="tabpanel">
                        <div class="card-body box-border" style="margin: 15px 0 15px 0;">
                        <div class="qtype_lcspeech_words">' . $feedbackpronuncation . '</div>
                        </div>
                    </div>
                ';

                $allfeedback .= $pronunciationfeedback;
                $allfeedback .= '</div>';
            }

            return $question->format_text($allfeedback, FORMAT_HTML, $qa, 'question', 'answerfeedback', null);
        }
        return '';
    }

    public function specific_feedback_scripted(question_attempt $qa) {
        qtype_lcspeech_ensure_api_config_is_set();

        $question = $qa->get_question();
        $qubaid = $qa->get_usage_id();
        $slot = $qa->get_slot();
        $fs = get_file_storage();
        $componentname = $question->qtype->plugin_name();

        $question = $qa->get_question();
        $response = $qa->get_last_qt_data();
        if (!empty($response) && $question->is_complete_response($response)) {
            $files = $response['recording']->get_files();
            $allfeedback = '';
            foreach ($files as $file) {
                $audio = $file->get_content();
                $result = $question->get_score_for_audio($audio);

                $feedbackpronuncation = $this->build_pronunciation_feedback($result['pronunciation']['words']);

                // Speaking score
                $allfeedback .= '<div><div class="box-border" style="padding: 0 24px 0 24px; margin-bottom: 10px;"><div class="section-header qtype_lcspeech_average_score">' . get_string('lbl_speakingscore', 'qtype_lcspeech');
                $allfeedback .= '<span style="float: right;color: black;font-size: 14px;padding-top: 10px;">' . $this->get_config_scoring_option_display($question) . '</span></div>';
                $speakingscore = $this->render_speaking_score_scripted($result, $question);
                $allfeedback .= $speakingscore;
                $allfeedback .= '</div>';

                $allfeedback .= $this->render_tabs($question);
                $allfeedback .= '<div class="tab-content" id="myTabContent">';

                // Metadata - Content Relevance
                $enablemetadata = get_config('qtype_lcspeech', 'enablelcbetafeatures');
                if ($enablemetadata) {
                    $metadata = $this->render_metadata($result, $question);
                    $allfeedback .= '
                        <div id="collapseMetaFeedback-' . $question->id . '" class="tab-pane fade show active" aria-labelledby="tabMetaFeedback-' . $question->id . '" role="tabpanel">
                            <div class="qtype_lcspeech_words">' . $metadata . '</div>
                        </div>
                    ';
                }

                $pronunciationfeedback = '
                    <div id="collapsePro2Feedback-' . $question->id . '" class="tab-pane fade' . ($enablemetadata ? '' : ' show active') . '" aria-labelledby="tabPro2Feedback-' . $question->id . '" role="tabpanel">
                        <div class="card-body box-border" style="margin: 15px 0 15px 0;">
                        <div class="qtype_lcspeech_words">' . $feedbackpronuncation . '</div>
                        </div>
                    </div>
                ';
                $allfeedback .= $pronunciationfeedback;

                // Fluency feedback
                $fluencyfeedback = $this->feedback_fluency($result, $question);
                $allfeedback .= $fluencyfeedback;

                // end
                $allfeedback .= '</div>';
            }
            return $question->format_text($allfeedback, FORMAT_HTML, $qa, 'question', 'answerfeedback', null);
        }
        return '';
    }

    public function specific_feedback(question_attempt $qa) {
        $question = $qa->get_question();
        // var_dump($question->speechtype);

        if ($question->speechtype == 'scripted') {
            return $this->specific_feedback_scripted($qa);
        } else if ($question->speechtype == 'unscripted') {
            return $this->specific_feedback_unscripted($qa);;
        } else if ($question->speechtype == 'pronunciation') {
            return $this->specific_feedback_pronunciation($qa);;
        }
        return null;
    }

    public function specific_feedback_unscripted(question_attempt $qa) {
        qtype_lcspeech_ensure_api_config_is_set();

        $question = $qa->get_question();
        $qubaid = $qa->get_usage_id();
        $slot = $qa->get_slot();
        $fs = get_file_storage();
        $componentname = $question->qtype->plugin_name();

        $question = $qa->get_question();
        $response = $qa->get_last_qt_data();
        if (!empty($response) && $question->is_complete_response($response)) {
            $files = $response['recording']->get_files();
            $allfeedback = '';
            foreach ($files as $file) {
                $audio = $file->get_content();
                $result = $question->get_score_for_audio($audio);

                $feedbackpronuncation = $this->build_pronunciation_feedback($result['pronunciation']['words']);

                // Speaking score unscripted
                $allfeedback .= '<div><div class="box-border" style="padding: 0 24px 0 24px; margin-bottom: 10px;">
                        <div class="section-header qtype_lcspeech_average_score">' . get_string('lbl_speakingscore', 'qtype_lcspeech');
                $allfeedback .= '<span style="float: right;color: black;font-size: 14px;padding-top: 10px;">' . $this->get_config_scoring_option_display($question) . '</span></div>';
                $speakingscore = $this->render_speaking_score_unscripted($result, $question);
                $allfeedback .= $speakingscore;
                $allfeedback .= '</div>';

                $showgrammar = isset($result['grammar']['metrics']['mistake_count']) || isset($result['grammar']['metrics']['grammatical_complexity']);
                $allfeedback .= $this->render_tabs($question, $showgrammar);
                $allfeedback .= '<div class="tab-content" id="myTabContent">';

                // Metadata - Content Relevance
                $enablemetadata = get_config('qtype_lcspeech', 'enablelcbetafeatures');
                if ($enablemetadata) {
                    $metadata = $this->render_metadata($result, $question);
                    $allfeedback .= '
                        <div id="collapseMetaFeedback-' . $question->id . '" class="tab-pane fade show active" aria-labelledby="tabMetaFeedback-' . $question->id . '" role="tabpanel">
                            <div class="qtype_lcspeech_words">' . $metadata . '</div>
                        </div>
                    ';
                }

                $pronunciationfeedback = '
                    <div id="collapsePro2Feedback-' . $question->id . '" class="tab-pane fade' . ($enablemetadata ? '' : ' show active') . '" aria-labelledby="tabPro2Feedback-' . $question->id . '" role="tabpanel">
                        <div class="card-body box-border" style="margin: 15px 0 15px 0;">
                        <div class="qtype_lcspeech_words">' . $feedbackpronuncation . '</div>
                        </div>
                    </div>
                ';

                $allfeedback .= $pronunciationfeedback;

                // Fluency feedback
                $fluencyfeedback = $this->feedback_fluency($result, $question);
                $allfeedback .= $fluencyfeedback;

                // Grammar.
                if ($showgrammar) {
                    $grammar = $this->render_grammar($result, $question);
                    $allfeedback .= $grammar;
                }

                // Vocabulary.
                $vocabulary = $this->render_vocabulary($result, $question);
                $allfeedback .= $vocabulary;

                $allfeedback .= '</div>';
            }

            return $question->format_text($allfeedback, FORMAT_HTML, $qa, 'question', 'answerfeedback', null);
        }
        return '';
    }

    protected function formatted_fluency_feedback_transcript($tagged) {
        // replace \" -> '
        $ftaggged = "<discourse-marker description=\"test\"></discourse-marker>" . $tagged;
        $ftaggged = str_replace("\"", "'", $ftaggged);
        // filler-word
        // $ftaggged = str_replace("filler-word", "filler-word filler-word='filler word'", $tagged);
        // word-repetition
        // $ftaggged = str_replace("word-repetition", "word-repetition word-repetition='word repetition'", $tagged);
        // <i class="icon fa fa-clock-o" aria-hidden="true"></i>
        $ftaggged = str_replace("</speech-pause>", "[pause]</speech-pause> ", $ftaggged);

        // echo  $ftaggged;
        return $ftaggged;
    }

    protected function fluency_feedback_transcript_append_child($dom, $nodes, $title, $content) {
        foreach ($nodes as $d) {
            switch ($title) {
                case 'Connective':
                    $vmakers = $d->attributes->item(0)->value;
                    $content = get_string('lbl_usedfor', 'qtype_lcspeech') . ": " . $vmakers;
                    break;
                case 'Speech pause':
                    $vmakers = $d->attributes->item(0)->value;
                    $content = get_string('lbl_durationseconds', 'qtype_lcspeech') . ": " . $vmakers;
                    break;
                default:
                    break;
            }

            $nodespan = $dom->createElement("span");
            $nodediv1 = $dom->createElement("p", $title);
            $nodediv1->setAttribute("class", "t-title");
            $nodediv2 = $dom->createElement("p", $content);
            $nodediv2->setAttribute("class", "t-content");
            $nodespan->appendChild($nodediv1);
            $nodespan->appendChild($nodediv2);
            $d->appendChild($nodespan);
            $d->setAttribute("class", "t-tooltips");
        }
    }

    protected function build_fluency_feedback_transcript($html) {
        // $html = "Well, this is a very good test. The test is about English <discourse-marker description=\"adding information\">and</discourse-marker> specifically about IELTS test. <speech-pause duration_seconds=\"2.0\"></speech-pause><discourse-marker description=\"contrasting\">However</discourse-marker>, it is, <filler-word>uh</filler-word>, more <word-repetition>like like</word-repetition> a mock test. Which is not referring to your actual results, but it can only refer to your practice results <discourse-marker description=\"indicating purpose\">in order to</discourse-marker> <word-repetition>prepare for you prepare for you</word-repetition>, <filler-word>uh</filler-word>, before the test";
        $taggedfeedback = $this->formatted_fluency_feedback_transcript($html);
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($taggedfeedback);
        $discoursemarkers = $dom->getElementsByTagName("discourse-marker");
        $this->fluency_feedback_transcript_append_child($dom, $discoursemarkers
            , get_string('lbl_connectives', 'qtype_lcspeech'), null);

        $fillerword = $dom->getElementsByTagName("filler-word");
        $this->fluency_feedback_transcript_append_child($dom, $fillerword
            , get_string('lbl_fillerword', 'qtype_lcspeech'), get_string('lbl_usedforfillerword', 'qtype_lcspeech'));

        $wordrepetition = $dom->getElementsByTagName("word-repetition");
        $this->fluency_feedback_transcript_append_child($dom, $wordrepetition
            , get_string('lbl_wordrepetition', 'qtype_lcspeech'), get_string('lbl_usedforwordrepetition', 'qtype_lcspeech'));

        $speechpause = $dom->getElementsByTagName("speech-pause");
        $this->fluency_feedback_transcript_append_child($dom, $speechpause
            , get_string('lbl_speechpause', 'qtype_lcspeech'), null);

        return $dom->saveHTML();
    }

    protected function feedback_fluency($response, $question) {
        $content = '<div class="feedback-card box-border">
                <div>
                    <span class="bold-text">' . get_string('lbl_speechrate', 'qtype_lcspeech') . '</span>
                    <span>'  . $response['fluency']['metrics']['speech_rate'] . '</span>
                </div>
                <div>' . $response['fluency']['feedback']['speech_rate']['feedback_text'] . '</div>
            </div>
            <div class="feedback-card box-border">
                <div >
                    <span class="bold-text">' . get_string('lbl_numberofpauses', 'qtype_lcspeech') . '</span>
                    <span>'  . $response['fluency']['metrics']['pauses'] . '</span>
                </div>
                <div>' . $response['fluency']['feedback']['pauses']['feedback_text'] . '</div>
            </div>
            <div class="feedback-card box-border">
                <div >
                    <span class="bold-text">' . get_string('lbl_numberoffillerwords', 'qtype_lcspeech') . '</span>
                    <span>'  . $response['fluency']['metrics']['filler_words'] . '</span>
                </div>
                <div>' . $response['fluency']['feedback']['filler_words']['feedback_text'] . '</div>
            </div>
        ';

        $taggedfeedback = $response['fluency']['feedback']['tagged_transcript'];
        $iconnective = substr_count($taggedfeedback, '</discourse-marker>');
        $ifillerword = substr_count($taggedfeedback, '</filler-word>');
        $iwordrepetition = substr_count($taggedfeedback, '</word-repetition>');
        $ispeechpause = substr_count($taggedfeedback, '</speech-pause>');

        if (get_config('qtype_lcspeech', 'enablelcbetafeatures')) {
            $taggedfeedback = $this->build_fluency_feedback_transcript($taggedfeedback);
            $content .= '<div class="feedback-card box-border">
                    <div >
                        <span class="bold-text">' . get_string('lbl_taggedtranscript', 'qtype_lcspeech') . '</span>
                        <div style="margin-top: 0px;">
                            <div style="display: contents;margin-right: 5px;">
                                <div class="c-feedback discourse-marker">' . $iconnective . '</div> ' . get_string('lbl_connectives', 'qtype_lcspeech') . '&nbsp;&nbsp;&nbsp;&nbsp;
                            </div>
                            <div style="display: contents;margin-right: 5px;">
                                <div class="c-feedback word-repetition">' . $ifillerword . '</div> ' . get_string('lbl_wordrepetition', 'qtype_lcspeech') . '&nbsp;&nbsp;&nbsp;&nbsp;
                            </div>
                            <div style="display: contents;margin-right: 5px;">
                                <div class="c-feedback filler-word">' . $iwordrepetition . '</div> ' . get_string('lbl_fillerword', 'qtype_lcspeech') . '&nbsp;&nbsp;&nbsp;&nbsp;
                            </div>
                            <div style="display: contents;">
                                <div class="c-feedback speech-pause">' . $ispeechpause . '</div>' . get_string('lbl_pauses', 'qtype_lcspeech') . '
                            </div>
                        </div>
                    </div>
                    <div>' . $taggedfeedback . '</div>
                </div>
            ';
        }

        $feedback = '';
        $feedback .= '<div id="collapseFluency' . $question->id . '" class="tab-pane fade" role="tabpanel" aria-labelledby="tabFluency-' . $question->id . '">
                <div class="qtype_lcspeech_words">' . $content . '</div>
            </div>
        ';
        return $feedback;
    }

    protected function render_tabs($question, $showgrammar = true) {
        $tabs = '
            <ul class="nav nav-tabs mt-4" role="tablist">
        ';

        $enablemetadata = get_config('qtype_lcspeech', 'enablelcbetafeatures');
        $showContentRelevance = $enablemetadata && $question->speechtype != 'pronunciation';

        if ($showContentRelevance)
            $tabs .= '
                <li class="nav-item" role="presentation" id="tabMetaFeedback-' . $question->id . '">
                    <button class="nav-link active" data-toggle="tab" data-target="#collapseMetaFeedback-' . $question->id . '" type="button" role="tab" aria-controls="collapseProFeedback-' . $question->id . '" aria-selected="true">
                        ' . get_string('lbl_contentrelevance', 'qtype_lcspeech')  . '
                    </button>
                </li>
            ';

        $tabs .= '
            <li class="nav-item" role="presentation" id="tabPro2Feedback-' . $question->id . '">
                <button class="nav-link' . ($showContentRelevance ? '' : ' active') . '" data-toggle="tab" data-target="#collapsePro2Feedback-' . $question->id . '" type="button" role="tab" aria-controls="collapsePro2Feedback-' . $question->id . '" aria-selected=' . ($showContentRelevance ? '"false"' : '"true"') . '>
                    ' . get_string('lbl_pronunciation', 'qtype_lcspeech')  . '
                </button>
            </li>
        ';
        
        if ($question->speechtype != 'pronunciation') {
            $tabs .= '
                <li class="nav-item" role="presentation" id="tabFluency-' . $question->id . '">
                    <button class="nav-link" data-toggle="tab" data-target="#collapseFluency' . $question->id . '" type="button" role="tab" aria-controls="collapseFluency' . $question->id . '" aria-selected="false">
                        ' . get_string('lbl_fluencyfeedback', 'qtype_lcspeech')  . '
                    </button>
                </li>
            ';
        }

        if ($question->speechtype == 'unscripted') {
            if ($showgrammar) {
                $tabs .= '
                    <li class="nav-item" role="presentation" id="tabGrammar-' . $question->id . '">
                        <button class="nav-link" data-toggle="tab" data-target="#collapseGrammar' . $question->id . '" type="button" role="tab" aria-controls="collapseGrammar' . $question->id . '" aria-selected="false">
                            ' . get_string('lbl_grammarfeedback', 'qtype_lcspeech')  . '
                        </button>
                    </li>
                ';
            }
            $tabs .= '
                <li class="nav-item" role="presentation" id="tabVocabulary-' . $question->id . '">
                    <button class="nav-link" data-toggle="tab" data-target="#collapseVocabulary' . $question->id . '" type="button" role="tab" aria-controls="collapseVocabulary' . $question->id . '" aria-selected="false">
                        ' . get_string('lbl_vocabularyfeedback', 'qtype_lcspeech')  . '
                    </button>
                </li>
            ';
        }
        $tabs .= '</ul>';

        return $tabs;
    }

    protected function render_metadata($response, $question) {
        $content = "";

        if ($question->speechtype == 'scripted') {
            $content = '
                <div class="metadata-card box-border">
                    <div><span class="bold-text">' . get_string('lbl_vocabularyfeedback', 'qtype_lcspeech') . '</span></div>
                    <div>' . $response['metadata']['predicted_text'] . '</div>
                </div>
                <div class="metadata-card box-border">
                    <div><span class="bold-text">' . get_string('lbl_expectedtext', 'qtype_lcspeech') . '</span></div>
                    <div>' . $response['pronunciation']['expected_text'] . '</div>
                </div>
                <div class="metadata-card box-border">
                    <div><span class="bold-text">' . get_string('lbl_relevancescore', 'qtype_lcspeech') . '</span></div>
                    <div>' . $response['metadata']['content_relevance'] . '</div>
                </div>
            ';
        }

        if ($question->speechtype == 'unscripted') {
            $content = '
                <div class="feedback-card box-border">
                    <div><span class="bold-text">' . get_string('lbl_thepredictedtext', 'qtype_lcspeech') . '</span></div>
                    <div>' . $response['metadata']['predicted_text'] . '</div>
                </div>
            ';

            if (isset($response['metadata']['content_relevance']) && isset($response['metadata']['content_relevance_feedback'])) {
                $labelstyle = 'comlexity_label_' . $response['metadata']['content_relevance'];
                $content .= '
                    <div class="feedback-card box-border">
                        <div>
                            <span class="bold-text">' . get_string('lbl_thepredictedtext', 'qtype_lcspeech') . '</span>
                            <span class="' . $labelstyle . '">' . ucwords(strtolower(str_replace("_", " ", $response['metadata']['content_relevance']))) . '</span>
                        </div>
                        <div>' . $response['metadata']['content_relevance_feedback'] . '</div>
                    </div>
                ';
            }

            if (isset($response['metadata']['valid_answer'])) {
                $validanswer = str_replace('_', ' ', $response['metadata']['valid_answer']);

                $content .= '
                    <div class="metadata-card box-border">
                        <div><span class="bold-text">' . get_string('lbl_validanswer', 'qtype_lcspeech') . '</span></div>
                        <div>' . $validanswer . '</div>
                    </div>
                ';
            }
        }

        return $content;
    }

    protected function render_grammar($result, $question) {
        $labelstyle = 'comlexity_label_' . $result['grammar']['metrics']['grammatical_complexity'];
        $content = '';
        if (isset($result['grammar']['metrics']['mistake_count'])) {
            $content .= '<div class="feedback-card box-border">
                    <div>
                        <span class="bold-text">' . get_string('lbl_grammarmistakeccount', 'qtype_lcspeech') . '</span>
                        <span>' . $result['grammar']['metrics']['mistake_count'] . '</span>
                    </div>
                </div>
                ';
        }

        if (isset($result['grammar']['metrics']['grammatical_complexity'])) {
            $content .= '<div class="feedback-card box-border">
                    <div>
                        <span class="bold-text">' . get_string('lbl_grammaticalcomplexity', 'qtype_lcspeech') . '</span>
                        <span class="' . $labelstyle . '">' . ucwords(strtolower($result['grammar']['metrics']['grammatical_complexity'])) . '</span>
                    </div>
                </div>
                ';
        }

        $grammar = '';

        if (!empty($content)) {
            $grammar .= '              
                <div id="collapseGrammar' . $question->id . '" class="tab-pane fade" role="tabpanel" aria-labelledby="tabGrammar-' . $question->id . '">
                    <div class="qtype_lcspeech_words">' . $content . '</div>
                </div>
            ';
        }
        return $grammar;
    }

    protected function render_vocabulary($result, $question) {
        $labelstyle = 'comlexity_label_' . $result['vocabulary']['metrics']['vocabulary_complexity'];

        $content = '';
        $content .= '<div class="feedback-card box-border">
                <div>
                    <span class="bold-text">' . get_string('lbl_vocabularycomplexity', 'qtype_lcspeech') . '</span>
                    <span class="' . $labelstyle . '">' . ucwords(strtolower($result['vocabulary']['metrics']['vocabulary_complexity'])) . '</span>
                </div>
            </div>
            ';

        $grammar = '';
        $grammar .= '<div id="collapseVocabulary' . $question->id . '" class="tab-pane fade" role="tabpanel" aria-labelledby="tabVocabulary-' . $question->id . '">
                <div class="qtype_lcspeech_words">' . $content . '</div>
            </div>
        ';
        return $grammar;
    }

    protected function render_speaking_score_scripted($result, $question) {
        $feedback = '';
        $feedback .= '<table style="table-layout: fixed ; width: 100%;" class="generaltable generalbox quizreviewsummary metadata-table">';
        $feedback .= '<tbody>';
        $feedback .= '<tr>
                        <th scope="row">' . get_string('lbl_fluency', 'qtype_lcspeech') . '</th>
                        <th scope="row">' . get_string('lbl_pronunciation', 'qtype_lcspeech') . '</th>
                        <th scope="row">' . get_string('lbl_overall', 'qtype_lcspeech') . '</th>
                    </tr>';
        if ($this->get_config_scoring_option($question) == 'DEFAULT') {
            $feedback .= '<tr>
                <td scope="row">' . $result['fluency']['overall_score'] . '</td>
                <td scope="row">' . $result['pronunciation']['overall_score'] . '</td>
                <td scope="row">' . $result['overall']['overall_score'] . '</td>
            </tr>';
        } else {
            $feedback .= '<tr>
                <td scope="row">' . $result['fluency']['english_proficiency_scores'][$this->get_config_scoring_option($question)]['prediction'] . '</td>
                <td scope="row">' . $result['pronunciation']['english_proficiency_scores'][$this->get_config_scoring_option($question)]['prediction'] . '</td>
                <td scope="row">' . $result['overall']['english_proficiency_scores'][$this->get_config_scoring_option($question)]['prediction'] . '</td>
            </tr>';
        }

        $feedback .= '</tbody>';
        $feedback .= '</table>';

        return $feedback;
    }

    protected function get_config_scoring_option($question) {
        $globalscoringopt = get_config('qtype_lcspeech', 'scoringoptionsetting');
        $scoringoption = !empty($question->scoringoption) ? $question->scoringoption : $globalscoringopt;

        $config = "";
        switch ($scoringoption) {
            case "PTE":
                $config = "mock_pte";
                break;
            case "CEFR":
                $config = "mock_cefr";
                break;
            case "IELTS":
                $config = "mock_ielts";
                break;
            case "DEFAULT":
            default:
                $config = "DEFAULT";
                break;
        }
        return $config;
    }

    protected function get_config_scoring_option_display($question) {
        $globalscoringopt = get_config('qtype_lcspeech', 'scoringoptionsetting');
        $scoringoption = !empty($question->scoringoption) ? $question->scoringoption : $globalscoringopt;

        $config = "";
        switch ($scoringoption) {
            case "PTE":
                $config = get_string('lbl_mockpte', 'qtype_lcspeech');
                break;
            case "CEFR":
                $config = get_string('lbl_mockcefr', 'qtype_lcspeech');
                break;
            case "IELTS":
                $config = get_string('lbl_mockielts', 'qtype_lcspeech');
                break;
            case "LC":
            default:
                $config = "";
                break;
        }
        return $config;
    }

    protected function render_speaking_score_unscripted($result, $question) {
        $feedback = '';
        $feedback .= '<table class="generaltable generalbox quizreviewsummary unscript-speaking-table">';
        $feedback .= '<tbody>';
        $feedback .= '<tr>
                        <th scope="row">' . get_string('lbl_pronunciation', 'qtype_lcspeech') .'</th>
                        <th scope="row">' . get_string('lbl_fluency', 'qtype_lcspeech') .'</th>
                        <th scope="row">' . get_string('lbl_vocabulary', 'qtype_lcspeech') .'</th>
                        <th scope="row">' . get_string('lbl_grammar', 'qtype_lcspeech') .'</th>
                    ';
        if (isset($result['overall']['overall_score'])) {
            $feedback .= '<th scope="row" class="txt-last">' . get_string('lbl_overall', 'qtype_lcspeech') .'</th>';
        }
        $feedback .= '</tr>';

        if ($this->get_config_scoring_option($question) == 'DEFAULT') {
            $feedback .= '<tr>
                <td scope="row">' . $result['pronunciation']['overall_score'] . '</td>
                <td scope="row">' . $result['fluency']['overall_score'] . '</td>
                <td scope="row">' . $result['vocabulary']['overall_score'] . '</td>
                <td scope="row">' . $result['grammar']['overall_score'] . '</td>
            ';

            if (isset($result['overall']['overall_score'])) {
                $feedback .= '<td scope="row" class="txt-last">' . $result['overall']['overall_score'] . '</td>';
            }

            $feedback .= '</tr>';
        } else {
            $feedback .= '<tr>
                <td scope="row">' . $result['pronunciation']['english_proficiency_scores'][$this->get_config_scoring_option($question)]['prediction'] . '</td>
                <td scope="row">' . $result['fluency']['english_proficiency_scores'][$this->get_config_scoring_option($question)]['prediction'] . '</td>
                <td scope="row">' . $result['vocabulary']['english_proficiency_scores'][$this->get_config_scoring_option($question)]['prediction'] . '</td>
                <td scope="row">' . $result['grammar']['english_proficiency_scores'][$this->get_config_scoring_option($question)]['prediction'] . '</td>
            ';

            if (isset($result['overall']['overall_score'])) {
                $feedback .= '<td scope="row">' . $result['overall']['english_proficiency_scores'][$this->get_config_scoring_option($question)]['prediction'] . '</td>';
            }

            $feedback .= '</tr>';
        }

        $feedback .= '</tbody>';
        $feedback .= '</table>';

        return $feedback;
    }

    public function feedback(question_attempt $qa, question_display_options $options) {
        $output = '';
        $hint = null;

        if ($options->feedback) {
            $output .= html_writer::nonempty_tag(
                'div',
                $this->specific_feedback($qa),
                array('class' => 'specificfeedback')
            );
            $hint = $qa->get_applicable_hint();
        }

        if ($options->numpartscorrect) {
            $output .= html_writer::nonempty_tag(
                'div',
                $this->num_parts_correct($qa),
                array('class' => 'numpartscorrect')
            );
        }

        if ($hint) {
            $output .= $this->hint($qa, $hint);
        }

        $output .= '<div class="generalfeedback">';

        if (!empty($this->urls) || ($options->generalfeedback && !empty($this->general_feedback($qa)))) {
            $output .= '<div id="accordion">
                                      <div class="card">
                                        <div class="card-header" id="headingTwo">
                                          <h5 class="mb-0">
                                            <button type="button" class="btn btn-link cbtn" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                              '. get_string('lbl_sampleanswer', 'qtype_lcspeech') .'
                                            </button>
                                            <span><p class="note">'. get_string('lbl_sampleanswer_desc', 'qtype_lcspeech') .'</p></span>
                                          </h5>
                                        </div>

                                        <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
                                          <div class="card-body">';
        }

        if (!empty($this->urls)) {
            $output .= '<div class="qtype_lcspeech_file"><div class="qtype_lcspeech_correct_audio">'. get_string('lbl_correctaudio', 'qtype_lcspeech') .'</div>' . $this->getCorretionAudios($this->urls) . '</div>';
        }


        if ($options->generalfeedback) {
            $output .= html_writer::nonempty_tag(
                'div',
                $this->general_feedback($qa),
                array('class' => 'generalfeedback')
            );
        }

        if ($options->rightanswer) {
            $output .= html_writer::nonempty_tag(
                'div',
                $this->correct_response($qa),
                array('class' => 'rightanswer')
            );
        }

        if (!empty($this->urls) || ($options->generalfeedback && !empty($this->general_feedback($qa)))) {
            $output .= '</div></div></div></div>';
        }

        if (!empty($this->range)) {
            $output .= '<div class="mt-5" id="accordion"><div class="card"><div class="card-header" id="headingThree"><h5 class="mb-0"><button type="button" class="btn btn-link cbtn" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                              '. get_string('lbl_feedback', 'qtype_lcspeech') .'
                                            </button>
                                          </h5>
                                        </div>

                                        <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordion">
                                          <div class="card-body"><div class="qtype_lcspeech_file"><p>' . $this->range . '</p></div></div></div></div></div>';
        }
        $output .= '</div>';

        return $output;
    }

    /**
     * Strings our JS will need.
     *
     * @return string[] lang string names from the qtype_lcspeech lang file.
     */
    public function strings_for_js() {
        return [
            'gumabort',
            'gumabort_title',
            'gumnotallowed',
            'gumnotallowed_title',
            'gumnotfound',
            'gumnotfound_title',
            'gumnotreadable',
            'gumnotreadable_title',
            'gumnotsupported',
            'gumnotsupported_title',
            'gumoverconstrained',
            'gumoverconstrained_title',
            'gumsecurity',
            'gumsecurity_title',
            'gumtype',
            'gumtype_title',
            'nearingmaxsize',
            'nearingmaxsize_title',
            'recordagain',
            'recordingfailed',
            'recordinginprogress',
            'startrecording',
            'uploadaborted',
            'uploadcomplete',
            'uploadfailed',
            'uploadfailed404',
            'uploadpreparing',
            'uploadprogress',
        ];
    }

    private function nBetween($varToCheck, $high, $low) {
        if ($varToCheck < $low) {
            return false;
        }
        if ($varToCheck > $high) {
            return false;
        }
        return true;
    }

    private function getCorretionAudios($urls) {
        if (empty($urls)) {
            return '';
        }
        $single = '';
        foreach ($urls as $url) {
            $single .= '
                        <span class="media-player custom-media">
                            <audio controls>
                                <source src="' . $url . '">
                            </audio>
                        </span>
                    ';
        }
        return $single;
    }
}
