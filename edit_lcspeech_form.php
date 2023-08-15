<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Defines the editing form for LC Speech Assessment questions.
 *
 * @package   qtype_lcspeech
 * @copyright 2023 LC Speech Assessment
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/edit_question_form.php');

// $this->page->requires->js('qtype_lcspeech/utils.js');
// $this->page->requires->js_init_call('hello');

/**
 * The editing form for LC Speech Assessment questions.
 *
 * @copyright 2021 LC Speech Assessment
 */
class qtype_lcspeech_edit_form extends question_edit_form {

    protected function definition_inner($mform) {
        // var_dump(html_writer::div('Mr', 'toad', array('id' => 'tophat')));

        // Field for speech_assessment_type
        $attributes = array('onchange' => $this->on_change_question_type_js());
        $mform->addElement(
            'select',
            'speechtype',
            get_string('speechtype', 'qtype_lcspeech'),
            $this->build_speech_type_option_select(),
            $attributes
        );
        $mform->setDefault('speechtype', qtype_lcspeech::DEFAULT_SPEECH_ASSESSMENT);

        // Field for timelimitinseconds.
        $mform->addElement(
            'duration',
            'timelimitinseconds',
            get_string('timelimit', 'qtype_lcspeech'),
            ['units' => [1], 'optional' => false]
        );
        $mform->addHelpButton('timelimitinseconds', 'timelimit', 'qtype_lcspeech');
        $mform->setDefault('timelimitinseconds', qtype_lcspeech::DEFAULT_TIMELIMIT);

        // Field for accent
        $mform->addElement('select', 'accent', get_string('accent', 'qtype_lcspeech'), ['us' => 'American (US)', 'uk' => 'British (UK)']);
        $mform->setDefault('accent', qtype_lcspeech::DEFAULT_ACCENT);

        // Field for speechphrase.
        $mform->addElement(
            'text',
            'speechphrase',
            get_string('speechphrase', 'qtype_lcspeech'),
            array('maxlength' => 1000, 'size' => 100)
        );
        $mform->addHelpButton('speechphrase', 'speechphrase', 'qtype_lcspeech');
        // $mform->addRule('speechphrase', null, 'required', null, 'client');
        $mform->setType('speechphrase', PARAM_TEXT);
        $mform->hideIf('speechphrase', 'speechtype', 'eq', 'unscripted');

        // checkbox has content relevance
        // $mform->addElement('advcheckbox', 'hascontext', get_string('has_content_relevance', 'qtype_lcspeech'));
        if (get_config('qtype_lcspeech', 'enablelcbetafeatures')) {
            $mform->addElement('selectyesno', 'hascontext', get_string('has_content_relevance', 'qtype_lcspeech'));

            // context question
            $mform->addElement(
                'text',
                'contextquestion',
                get_string('contextquestion', 'qtype_lcspeech'),
                array('maxlength' => 1000, 'size' => 100)
            );
            $mform->addHelpButton('contextquestion', 'contextquestion', 'qtype_lcspeech');
            $mform->setType('contextquestion', PARAM_TEXT);

            // context description
            $mform->addElement(
                'text',
                'contextdescription',
                get_string('contextdescription', 'qtype_lcspeech'),
                array('maxlength' => 1000, 'size' => 100)
            );
            $mform->addHelpButton('contextdescription', 'contextdescription', 'qtype_lcspeech');
            $mform->setType('contextdescription', PARAM_TEXT);

            // valid_answer_description
            $mform->addElement(
                'text',
                'contextvalidanswerdescription',
                get_string('contextvalidanswerdescription', 'qtype_lcspeech'),
                array('maxlength' => 1000, 'size' => 100)
            );
            $mform->addHelpButton('contextvalidanswerdescription', 'contextvalidanswerdescription', 'qtype_lcspeech');
            $mform->setType('contextvalidanswerdescription', PARAM_TEXT);
        }

        // scoring option
        $mform->addElement(
            'select',
            'scoringoption',
            get_string('scoringoption', 'qtype_lcspeech'),
            array("DEFAULT" => "DEFAULT", "IELTS" => "IELTS", "PTE" => "PTE", "CEFR" => "CEFR")
        );
        $mform->setDefault('scoringoption', qtype_lcspeech::DEFAULT_SCORING_OPTION);

        if (get_config('qtype_lcspeech', 'enablelcbetafeatures')) {
            // Disable context... control when a hascontext dropdown has value '0'.
            $mform->hideIf('hascontext', 'speechtype', 'neq', 'unscripted');

            $mform->hideIf('contextquestion', 'hascontext', '0');
            $mform->hideIf('contextdescription', 'hascontext', '0');
            $mform->hideIf('contextvalidanswerdescription', 'hascontext', '0');
        }

        $this::hide_feedback_and_audio($mform);
        // process case Edit
        // $mform->disabledIf('speechphrase', 'speechtype', 'eq', 'unscripted');
        // $mform->hideIf('speechphrase', 'speechtype', 'eq', 'unscripted');
        // $mform->hideIf('idnumber', 'speechtype', 'eq', 'unscripted');
    }

    private function build_speech_type_option_select() {
        $option = array();

        if (get_config('qtype_lcspeech', 'api_scripted_url')) {
            $option['scripted'] = 'Scripted';
        }
        if (get_config('qtype_lcspeech', 'api_unscripted_url')) {
            $option['unscripted'] = 'Unscripted';
        }
        if (get_config('qtype_lcspeech', 'api_pronunciation_url')) {
            $option['pronunciation'] = 'Pronunciation';
        }

        /* $url_scripted = get_config('qtype_lcspeech', 'api_scripted_url');
        $url_unscripted = get_config('qtype_lcspeech', '');
        $url_pronunciation = get_config('qtype_lcspeech', '');
        var_dump($url);
        exit;
        } else if ($speechtype == 'unscripted') {
            $url = get_config('qtype_lcspeech', '');
        } else if ($speechtype == 'pronunciation') {
            $url = get_config('qtype_lcspeech', '');
        }

        ['scripted'=>'Scripted', 'unscripted'=>'Unscripted', 'pronunciation'=>'Pronunciation'] */
        return $option;
    }

    private function on_change_question_type_js() {
        $content = 'var value = document.getElementById("id_speechtype").value;
        console.log("fitem_id_speechphrase value: " + value);
        if (value == "unscripted") {
            document.getElementById("id_speechphrase").value = "empty";
            document.getElementById("fitem_id_speechphrase").style.display = "none";
            document.getElementById("fitem_id_hascontext").style.display = "";
            document.getElementById("fitem_id_contextquestion").style.display = "";
            document.getElementById("fitem_id_contextdescription").style.display = "";
            document.getElementById("fitem_id_contextvalidanswerdescription").style.display = "";
        } else {
            document.getElementById("id_speechphrase").value = "";
            document.getElementById("fitem_id_speechphrase").style.display = "";
            document.getElementById("fitem_id_hascontext").style.display = "none";
            document.getElementById("fitem_id_contextquestion").style.display = "none";
            document.getElementById("fitem_id_contextdescription").style.display = "none";
            document.getElementById("fitem_id_contextvalidanswerdescription").style.display = "none";
        }';
        return $content;
    }

    protected function hide_feedback_and_audio($mform) {
        // add Feedback
        $mform->addElement('header', 'score_feedback', 'Feedback');
        $fromrange = array(0, 31, 51, 81);
        $torange = array(30, 50, 80, 100);
        for ($i = 0; $i < 4; $i++) {
            $availablefirstgroup = array();
            $availablefirstgroup[] = &$mform->createElement('text', "from_range[{$i}]", 'From', 'placeholder="set from range" disabled="true" class="custom-feedback-range"');
            $availablefirstgroup[] = &$mform->createElement('text', "to_range[{$i}]", 'To', 'placeholder="set to range" disabled="true" class="custom-feedback-range"');
            $availablefirstgroup[] = &$mform->createElement('textarea', "feedback[{$i}]", 'Feedback', 'wrap="virtual" rows="5" cols="50" class="custom-feedback-textarea"');
            $mform->setType("from_range[{$i}]", PARAM_INT);
            $mform->setType("to_range[{$i}]", PARAM_INT);
            $mform->setDefault("from_range[{$i}]", $fromrange[$i]);
            $mform->setDefault("to_range[{$i}]", $torange[$i]);
            $mform->addGroup($availablefirstgroup, 'available_group_{$i}', $i === 0 ? 'Grade Range' : '', '', false);
        }

        $mform->setExpanded('score_feedback');

        $mform->addElement('header', 'audio_upload', 'Correction Audio');
        $repeatarray = array();
        $repeatno = 0;
        if (!empty($this->question->options->audios)) {
            $repeatno = count($this->question->options->audios);
        }

        $uploadgroup = [];
        $uploadgroup[] = &$mform->createElement('select', 'language', 'Language', ['en-US' => 'American', 'en-AU' => 'Australian', 'en-UK' => 'British'],
        'class="correction-audio-select"');
        $uploadgroup[] = &$mform->createElement('filepicker', 'correction_audio', 'Audio', null, array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1,
        'accepted_types' => array('.mp3', '.wav', '.ogg')));
        $uploadgroup[] = &$mform->createElement('submit', 'removeaudio', 'Remove');
        $mform->registerNoSubmitButton('removeaudio');
        $repeatarray[] = $mform->createElement('group', 'audio_upload_group', '', $uploadgroup, null, false);

        $repeateloptions = array();

        $this->repeat_elements($repeatarray, $repeatno, $repeateloptions, 'audio_correction_repeats', 'audio_correction_fields', 1, null, true, "removeaudio");
        $mform->setExpanded('audio_upload');
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate placeholders in the question text.
        $placeholdererrors = (new qtype_lcspeech)->validate_widget_placeholders($data['questiontext']['text'], qtype_lcspeech::MEDIA_TYPE_AUDIO);
        if ($placeholdererrors) {
            $errors['questiontext'] = $placeholdererrors;
        }

        /* Validate the speech phrase.
        if (
            !array_key_exists('speechphrase', $data) ||
            !is_string($data['speechphrase']) ||
            strlen($data['speechphrase']) < 1
        ) {
            $errors['speechphrase'] = get_string('err_speechphraseempty', 'qtype_lcspeech');
        } */

        // Validate the time.
        $maxtimelimit = get_config('qtype_lcspeech', 'timelimit');
        if ($data['timelimitinseconds'] <= 0) {
            $errors['timelimitinseconds'] = get_string('err_timelimitpositive', 'qtype_lcspeech');
        } else if ($data['timelimitinseconds'] > $maxtimelimit) {
            $errors['timelimitinseconds'] = get_string(
                'err_timelimit',
                'qtype_lcspeech',
                format_time($maxtimelimit)
            );
        }

        return $errors;
    }

    public function qtype() {
        return 'lcspeech';
    }


    protected function data_preprocessing($question) {
        if (isset($question->options->range)) {
            $feedback = [];
            foreach ($question->options->range as $range) {
                $feedback[] = $range->feedback;
            }
            $question->feedback = $feedback;
        }

        if (isset($question->options->audios)) {
            $i = 0;
            foreach ($question->options->audios as $audio) {
                $draftitemid = file_get_submitted_draft_itemid('correction_audio{$i}');
                file_prepare_draft_area(
                    $draftitemid,
                    $this->context->id,
                    'qtype_lcspeech',
                    'correction_audio',
                    $audio->unique_item_id ? (int)$audio->unique_item_id : null,
                    array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => -1)
                );

                $question->correction_audio[$i] = $draftitemid;
                $question->language[$i] = $audio->language;
                $i++;
            }
        }

        return $question;
    }
}
