<?php

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
class qtype_lcspeech_edit_form extends question_edit_form
{

    protected function definition_inner($mform)
    {
        // var_dump(html_writer::div('Mr', 'toad', array('id' => 'tophat')));

        // Field for speech_assessment_type
        $attributes =  array('onchange' => $this->onChangeQuestionTypeJS());
        $mform->addElement(
            'select',
            'speechtype',
            get_string('speechtype', 'qtype_lcspeech'),
            $this->buildSpeechTypeOptionSelect(),
            $attributes
        );
        $mform->setDefault('speechtype', qtype_lcspeech::DEFAULT_SPEECH_ASSESSMENT);

        // Field for speechphrase.
        $mform->addElement(
            'text',
            'speechphrase',
            get_string('speechphrase', 'qtype_lcspeech'),
            array('maxlength' => 1000, 'size' => 100)
        );
        $mform->addHelpButton('speechphrase', 'speechphrase', 'qtype_lcspeech');
        $mform->addRule('speechphrase', null, 'required', null, 'client');
        $mform->setType('speechphrase', PARAM_TEXT);

        // Field for timelimitinseconds.
        $mform->addElement(
            'duration',
            'timelimitinseconds',
            get_string('timelimit', 'qtype_lcspeech'),
            ['units' => [60, 1], 'optional' => false]
        );
        $mform->addHelpButton('timelimitinseconds', 'timelimit', 'qtype_lcspeech');
        $mform->setDefault('timelimitinseconds', qtype_lcspeech::DEFAULT_TIMELIMIT);

        // Field for accent
        $mform->addElement('select', 'accent', get_string('accent', 'qtype_lcspeech'), ['us' => 'American (US)', 'uk' => 'British (UK)']);
        $mform->setDefault('accent', qtype_lcspeech::DEFAULT_ACCENT);


        $this::hideFeedbackAndAudio($mform);
        // process case Edit
        // $mform->disabledIf('speechphrase', 'speechtype', 'eq', 'unscripted');
        // $mform->hideIf('speechphrase', 'speechtype', 'eq', 'unscripted');
        // $mform->hideIf('idnumber', 'speechtype', 'eq', 'unscripted');
    }

    private function buildSpeechTypeOptionSelect()
    {
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

        // $url_scripted = get_config('qtype_lcspeech', 'api_scripted_url');
        // $url_unscripted = get_config('qtype_lcspeech', '');
        // $url_pronunciation = get_config('qtype_lcspeech', '');
        // var_dump($url);
        // exit;
        // } else if ($speechtype == 'unscripted') {
        //     $url = get_config('qtype_lcspeech', '');
        // } else if ($speechtype == 'pronunciation') {
        //     $url = get_config('qtype_lcspeech', '');
        // }

        // ['scripted'=>'Scripted', 'unscripted'=>'Unscripted', 'pronunciation'=>'Pronunciation']
        return $option;
    }

    private function onChangeQuestionTypeJS()
    {
        $content = 'var value = document.getElementById("id_speechtype").value; 
        console.log("fitem_id_speechphrase value: " + value); 
        if (value == "unscripted") { 
            document.getElementById("id_speechphrase").value = "empty"; 
            document.getElementById("fitem_id_speechphrase").style.display = "none"; 
        } else { 
            document.getElementById("id_speechphrase").value = ""; 
            document.getElementById("fitem_id_speechphrase").style.display = ""; 
        }';
        return $content;
    }

    protected function hideFeedbackAndAudio($mform)
    {
        // add Feedback
        $mform->addElement('header', 'score_feedback', 'Feedback');
        $fromRange = array(0, 31, 51, 81);
        $toRange = array(30, 50, 80, 100);
        for ($i = 0; $i < 4; $i++) {
            $availableFirstGroup = array();
            $availableFirstGroup[] = &$mform->createElement('text', "from_range[{$i}]", 'From', 'placeholder="set from range" disabled="true" class="custom-feedback-range"');
            $availableFirstGroup[] = &$mform->createElement('text', "to_range[{$i}]", 'To', 'placeholder="set to range" disabled="true" class="custom-feedback-range"');
            $availableFirstGroup[] = &$mform->createElement('textarea', "feedback[{$i}]", 'Feedback', 'wrap="virtual" rows="5" cols="50" class="custom-feedback-textarea"');
            $mform->setType("from_range[{$i}]", PARAM_INT);
            $mform->setType("to_range[{$i}]", PARAM_INT);
            $mform->setDefault("from_range[{$i}]", $fromRange[$i]);
            $mform->setDefault("to_range[{$i}]", $toRange[$i]);
            $mform->addGroup($availableFirstGroup, 'available_group_{$i}', $i === 0 ? 'Grade Range' : '', '', false);
        }

        $mform->setExpanded('score_feedback');

        $mform->addElement('header', 'audio_upload', 'Correction Audio');
        $repeatarray = array();
        $repeatno = 0;
        if (!empty($this->question->options->audios)) {
            $repeatno = count($this->question->options->audios);
        }

        $uploadGroup = [];
        $uploadGroup[] = &$mform->createElement('select', 'language', 'Language', ['en-US' => 'American', 'en-AU' => 'Australian', 'en-UK' => 'British'], 'class="correction-audio-select"');
        $uploadGroup[] = &$mform->createElement('filepicker', 'correction_audio', 'Audio', null, array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1, 'accepted_types' => array('.mp3', '.wav', '.ogg')));
        $uploadGroup[] = &$mform->createElement('submit', 'removeaudio', 'Remove');
        $mform->registerNoSubmitButton('removeaudio');
        $repeatarray[] = $mform->createElement('group', 'audio_upload_group', '', $uploadGroup, null, false);

        $repeateloptions = array();

        $this->repeat_elements($repeatarray, $repeatno, $repeateloptions, 'audio_correction_repeats', 'audio_correction_fields', 1, null, true, "removeaudio");
        $mform->setExpanded('audio_upload');
    }

    public function validation($data, $files)
    {
        $errors = parent::validation($data, $files);

        // Validate placeholders in the question text.
        $placeholdererrors = (new qtype_lcspeech)->validate_widget_placeholders($data['questiontext']['text'], qtype_lcspeech::MEDIA_TYPE_AUDIO);
        if ($placeholdererrors) {
            $errors['questiontext'] = $placeholdererrors;
        }

        // Validate the speech phrase.
        // if (
        //     !array_key_exists('speechphrase', $data) ||
        //     !is_string($data['speechphrase']) ||
        //     strlen($data['speechphrase']) < 1
        // ) {
        //     $errors['speechphrase'] = get_string('err_speechphraseempty', 'qtype_lcspeech');
        // }

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

    public function qtype()
    {
        return 'lcspeech';
    }


    protected function data_preprocessing($question)
    {
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
