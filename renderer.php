<?php

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
class qtype_lcspeech_renderer extends qtype_renderer
{

    public $urls = [];
    public $range = '';

    public function formulation_and_controls(question_attempt $qa, question_display_options $options)
    {
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
    protected function cannot_work_warnings()
    {
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
    protected function playback_ui($recordingurl, string $mediatype, string $filename)
    {
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
    protected function no_recording_message()
    {
        return '
            <span class="playback-widget">
                <span class="no-recording-placeholder">' .
            get_string('norecording', 'qtype_lcspeech') .
            '</span>
            </span>';
    }


    protected function build_pronunciation_feedback($result)
    {
        $feedback = '';

        $words = array_filter(array_map(function ($word) {
            return array(
                'label' => $word['word_text'],
                'mean' => $word['word_score'],
                'phones' => array_filter($word['phonemes'], function ($phoneme) {
                    return $phoneme['ipa_label'] !== 'SIL';
                }),
            );
        }, $result['pronunciation']['words']), function ($word) {
            return count($word['phones']) > 0;
        });
        foreach ($words as $word) {
            $word_feedback = '';
            foreach ($word['phones'] as $phoneme) {
                if ($phoneme['phoneme_score'] >= 60) {
                    $score_colour = 'green';
                } else if ($phoneme['phoneme_score'] >= 30) {
                    $score_colour = 'orange';
                } else {
                    $score_colour = 'red';
                }
                $phone_label = $phoneme['ipa_label'];
                $score_colour_class = 'qtype_lcspeech_phoneme_label_' . $score_colour;
                $word_feedback .= '<div class="qtype_lcspeech_phoneme"><div class="qtype_lcspeech_phoneme_label ' . $score_colour_class . '">' . $phone_label . '</div><div class="qtype_lcspeech_phoneme_score">' . $phoneme['phoneme_score'] . '%</div></div>';
            }
            if ($word['mean'] >= 60) {
                $speechPhrase .= $word['label'] . ' ';
            } else if ($word['mean'] >= 30) {
                $speechPhrase .= '<u style="color:orange">' . $word['label'] . '</u>  ';
            } else {
                $speechPhrase .= '<u style="color:red">' . $word['label'] . '</u>  ';
            }

            $feedback .= '<div class="qtype_lcspeech_word"><div class="qtype_lcspeech_word_label">' . $word['label'] . '</div><div class="qtype_lcspeech_phonemes">' . $word_feedback . '</div></div>';
        }

        return $feedback;
    }

    public function specific_feedback_scripted(question_attempt $qa)
    {
        qtype_lcspeech_ensure_api_config_is_set();

        $question = $qa->get_question();
        $qubaid = $qa->get_usage_id();
        $slot = $qa->get_slot();
        $fs = get_file_storage();
        $componentname = $question->qtype->plugin_name();
        //var_dump($slot);
        // if(isset($question->correctionAudios) && !empty($question->correctionAudios)) {
        //     foreach ($question->correctionAudios as $corr ) {
        //         $draftfiles = $fs->get_area_files($question->contextid, $componentname,'correction_audio', $corr->unique_item_id, 'id', false);
        //         if ($draftfiles) {
        //             foreach ($draftfiles as $file) {
        //                 if ($file->is_directory()) {
        //                     continue;
        //                 }
        //                 $url = moodle_url::make_pluginfile_url($question->contextid, $componentname,'correction_audio', "$qubaid/$slot/{$corr->unique_item_id}", '/',  $file->get_filename());

        //                 $this->urls[] = $url->out();
        //             }
        //         }
        //     }
        // }

        $question = $qa->get_question();
        $response = $qa->get_last_qt_data();
        if (!empty($response) && $question->is_complete_response($response)) {
            $files = $response['recording']->get_files();
            $all_feedback = '';
            foreach ($files as $file) {
                $audio = $file->get_content();
                $result = $question->get_score_for_audio($audio);

                $feedback = $this->build_pronunciation_feedback($result);

                // if(isset($question->feedbackRange) && !empty($question->feedbackRange)) {
                //     foreach ($question->feedbackRange as $r) {
                //         if($this->nBetween($result['overall_score'], (int)$r->to_range, (int)$r->from_range)) {
                //             $this->range = $r->feedback;
                //         }

                //         if ((int)$r->to_range===$result['overall_score']||(int)$r->from_range===$result['overall_score']) {
                //             $this->range = $r->feedback;
                //         }
                //     }
                // }
                // var_dump($result);
                // var_dump(json_decode($result, true));
                // var_dump(json_decode($result['overall_score']));
                // exit;
                $all_feedback .= '<div class=""><div class="qtype_lcspeech_average_score">Average Score IELTS: ' . $result['overall']['english_proficiency_scores']['mock_ielts']['prediction'] . '%</div><div class="qtype_lcspeech_file">' . $speechPhrase . '</div>';
                $all_feedback .= '<div id="accordion">
                                      <div class="card">
                                        <div class="card-header" id="headingOne">
                                          <h5 class="mb-0">
                                            <button type="button" class="btn btn-link cbtn" data-toggle="collapse" data-target="#collapseProFeedback-' . $question->id . '" aria-expanded="false" aria-controls="collapseProFeedback-' . $question->id . '">
                                              Pronunciation feedback
                                            </button>
                                          </h5>
                                        </div>
                                    
                                        <div id="collapseProFeedback-' . $question->id . '" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                                          <div class="card-body">
                                            <div class="qtype_lcspeech_words">' . $feedback . '</div>
                                          </div>
                                        </div>
                                      </div>
                                    
                                    </div></div>';

                // Fluency feedback
                $fluencyFeedback = $this->feedback_fluency($result, $question);
                $all_feedback .= $fluencyFeedback;
            }
            return $question->format_text($all_feedback, FORMAT_HTML, $qa, 'question', 'answerfeedback', null);
        }
        return '';
    }

    public function specific_feedback(question_attempt $qa)
    {
        $question = $qa->get_question();
        // var_dump($question->speechtype);

        if ($question->speechtype == 'scripted') {
            return $this->specific_feedback_scripted($qa);
        }
        return $this->specific_feedback_unscripted($qa);;
    }

    public function specific_feedback_unscripted(question_attempt $qa)
    {
        qtype_lcspeech_ensure_api_config_is_set();

        $question = $qa->get_question();
        $qubaid = $qa->get_usage_id();
        $slot = $qa->get_slot();
        $fs = get_file_storage();
        $componentname = $question->qtype->plugin_name();
        //var_dump($slot);
        // if (isset($question->correctionAudios) && !empty($question->correctionAudios)) {
        //     foreach ($question->correctionAudios as $corr) {
        //         $draftfiles = $fs->get_area_files($question->contextid, $componentname, 'correction_audio', $corr->unique_item_id, 'id', false);
        //         if ($draftfiles) {
        //             foreach ($draftfiles as $file) {
        //                 if ($file->is_directory()) {
        //                     continue;
        //                 }
        //                 $url = moodle_url::make_pluginfile_url($question->contextid, $componentname, 'correction_audio', "$qubaid/$slot/{$corr->unique_item_id}", '/',  $file->get_filename());

        //                 $this->urls[] = $url->out();
        //             }
        //         }
        //     }
        // }

        $question = $qa->get_question();
        $response = $qa->get_last_qt_data();
        if (!empty($response) && $question->is_complete_response($response)) {
            $files = $response['recording']->get_files();
            $all_feedback = '';
            foreach ($files as $file) {
                $audio = $file->get_content();
                $result = $question->get_score_for_audio($audio);

                $feedback = '';

                $feedback .= '<table class="generaltable generalbox quizreviewsummary">';
                $feedback .= '<tbody>';
                $feedback .= '<tr><th class="cell" scope="row">pronunciation</th><td class="cell">' . $result['pronunciation']['english_proficiency_scores']['mock_ielts']['prediction'] . '</td></tr>';
                $feedback .= '<tr><th class="cell" scope="row">fluency</th><td class="cell">' . $result['fluency']['english_proficiency_scores']['mock_ielts']['prediction'] . '</td></tr>';
                $feedback .= '<tr><th class="cell" scope="row">grammar</th><td class="cell">' . $result['grammar']['english_proficiency_scores']['mock_ielts']['prediction'] . '</td></tr>';
                $feedback .= '<tr><th class="cell" scope="row">vocabulary</th><td class="cell">' . $result['vocabulary']['english_proficiency_scores']['mock_ielts']['prediction'] . '</td></tr>';
                $feedback .= '</tbody>';
                $feedback .= '</table>';

                $feedbackPronuncation = $this->build_pronunciation_feedback($result);

                // if (isset($question->feedbackRange) && !empty($question->feedbackRange)) {
                //     foreach ($question->feedbackRange as $r) {
                //         if ($this->nBetween($result['overall'], (int)$r->to_range, (int)$r->from_range)) {
                //             $this->range = $r->feedback;
                //         }

                //         if ((int)$r->to_range === $result['overall'] || (int)$r->from_range === $result['overall']) {
                //             $this->range = $r->feedback;
                //         }
                //     }
                // }

                $all_feedback .= '<div class=""><div class="qtype_lcspeech_average_score">Overall Score: ' . $result['overall']['english_proficiency_scores']['mock_ielts']['prediction'] . '</div><div class="qtype_lcspeech_file">' . $speechPhrase . '</div>';
                $all_feedback .= '<div id="accordion">
                                      <div class="card">
                                        <div class="card-header" id="headingOne">
                                          <h5 class="mb-0">
                                            <button type="button" class="btn btn-link cbtn" data-toggle="collapse" data-target="#collapseProFeedback-' . $question->id . '" aria-expanded="false" aria-controls="collapseProFeedback-' . $question->id . '">
                                              Detailed Result
                                            </button>
                                          </h5>
                                        </div>
                                    
                                        <div id="collapseProFeedback-' . $question->id . '" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                                          <div class="card-body">
                                            <div class="qtype_lcspeech_words">' . $feedback . '</div>
                                          </div>
                                        </div>
                                      </div>
                                    
                                    </div></div>';

                $pronunciation_feedback .= '<div id="accordion">
                        <div class="card">
                            <div class="card-header" id="headingOne">
                                <h5 class="mb-0">
                                <button type="button" class="btn btn-link cbtn" data-toggle="collapse" data-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                    Pronunciation feedback
                                </button>
                                </h5>
                            </div>
                        
                            <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                                <div class="card-body">
                                <div class="qtype_lcspeech_words">' . $feedbackPronuncation . '</div>
                                </div>
                            </div>
                        </div></div>';

                $all_feedback .= $pronunciation_feedback;

                // Fluency feedback
                $fluencyFeedback = $this->feedback_fluency($result, $question);
                $all_feedback .= $fluencyFeedback;
            }

            return $question->format_text($all_feedback, FORMAT_HTML, $qa, 'question', 'answerfeedback', null);
        }
        return '';
    }

    protected function feedback_fluency($response, $question)
    {
        $content = '
            <div style="border-left: 3px #0fa1bfe3 solid;padding-left: 5px;margin-bottom: 15px;">
                <div style="border-bottom: 1px solid #00000040;margin-bottom: 10px;"><span style="font-weight: bold;color: #0fa1bfe3;">Speech Rate</span><span style="float: right;">'  . $response['fluency']['metrics']['speech_rate'] . '</span></div>
                <div><span style="font-weight: bold">Feedback</span></div>
                <div>' . $response['fluency']['feedback']['speech_rate']['feedback_text'] . '</div>
            </div>
            <div style="border-left: 3px #0fa1bfe3 solid;padding-left: 5px;margin-bottom: 15px;">
                <div style="border-bottom: 1px solid #00000040;margin-bottom: 10px;"><span style="font-weight: bold;color: #0fa1bfe3;">Number of pauses</span><span style="float: right;">'  . $response['fluency']['metrics']['number_of_pauses'] . '</span></div>
                <div><span style="font-weight: bold">Feedback</span></div>
                <div>&nbsp;&nbsp;</div>
            </div>
            <div style="border-left: 3px #0fa1bfe3 solid;padding-left: 5px;margin-bottom: 15px;">
                <div style="border-bottom: 1px solid #00000040;margin-bottom: 10px;"><span style="font-weight: bold;color: #0fa1bfe3;">Length of run</span><span style="float: right;">'  . $response['fluency']['metrics']['length_of_run'] . '</span></div>
                <div><span style="font-weight: bold">Feedback</span></div>
                <div>&nbsp;&nbsp;</div>
            </div>
        ';

        $feedback = '';

        $feedback .= '<div id="accordionFeedback">
                        <div class="card">
                            <div class="card-header" id="headingOne">
                                <h5 class="mb-0">
                                    <button type="button" class="btn btn-link cbtn" data-toggle="collapse" data-target="#collapseFluency' . $question->id . '" aria-expanded="false" aria-controls="collapseFluency' . $question->id . '">
                                        Fluency feedback
                                    </button>
                                </h5>
                            </div>
                                            
                            <div id="collapseFluency' . $question->id . '" class="collapse" aria-labelledby="headingOne" data-parent="#accordionFeedback">
                                <div class="card-body">
                                    <div class="qtype_lcspeech_words">' . $content . '</div>
                                </div>
                            </div>
                        </div>
                    </div>';
        return $feedback;
    }

    public function feedback(question_attempt $qa, question_display_options $options)
    {
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
                                              Sample Answer
                                            </button>
                                            <span><p class="note">Note: You should try your best before clicking here</p></span>
                                          </h5>
                                        </div>

                                        <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
                                          <div class="card-body">';
        }

        if (!empty($this->urls)) {
            $output .= '<div class="qtype_lcspeech_file"><div class="qtype_lcspeech_average_score">Correct Audio</div><div>' . $this->getCorretionAudios($this->urls) . '</div>';
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
                                              Feedback
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
    public function strings_for_js()
    {
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

    private function nBetween($varToCheck, $high, $low)
    {
        if ($varToCheck < $low) {
            return false;
        }
        if ($varToCheck > $high) {
            return false;
        }
        return true;
    }

    private function getCorretionAudios($urls)
    {
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
