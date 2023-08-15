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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Question type class for the Speech Assessment question type.
 *
 * @package   qtype_lcspeech
 * @copyright 2023 Speech Assessment
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/lcspeech/question.php');


/**
 * The Speech Assessment question type question type.
 *
 * @copyright 2023 Speech Assessment
 */
class qtype_lcspeech extends question_type {

    /** @var int default recording time limit in seconds. */
    const DEFAULT_TIMELIMIT = 30;

    /** @var int max length for media title. */
    const MAX_LENGTH_MEDIA_TITLE = 32;

    /** @var string media type audio */
    const MEDIA_TYPE_AUDIO = 'audio';

    /** @var string default accent. */
    const DEFAULT_ACCENT = 'us';

    /** @var string default accent. */
    const DEFAULT_SPEECH_ASSESSMENT = 'scripted';

    const DEFAULT_SCORING_OPTION = 'DEFAULT';

    const IELTS_SCORING_OPTION = 'IELTS';

    public function response_file_areas() {
        return ['recording'];
    }

    public function extra_question_fields() {
        return array(
            'qtype_lcspeech_options', 'mediatype', 'speechphrase', 'timelimitinseconds',
            'accent', 'speechtype', 'hascontext', 'contextquestion', 'contextdescription',
            'contextvalidanswerdescription', 'scoringoption'
        );
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        $mediatype = $questiondata->options->mediatype;
        parent::initialise_question_instance($question, $questiondata);
        $question->speechphrase = $questiondata->options->speechphrase;
        $question->timelimitinseconds = $questiondata->options->timelimitinseconds;
        $question->mediatype = $questiondata->options->mediatype;
        $question->accent = $questiondata->options->accent;
        $question->speechtype = $questiondata->options->speechtype;
        $question->scoringoption = $questiondata->options->scoringoption;
        $question->hascontext = $questiondata->options->hascontext;
        $question->contextquestion = $questiondata->options->contextquestion;
        $question->contextdescription = $questiondata->options->contextdescription;
        $question->contextvalidanswerdescription = $questiondata->options->contextvalidanswerdescription;
        $question->widgetplaceholders = $this->get_widget_placeholders($questiondata->questiontext);
        $question->feedbackRange = $questiondata->options->range ?? null;
        $question->correctionAudios = $questiondata->options->audios ?? null;
        if (empty($question->widgetplaceholders)) {
            // There was no recorder in the question text. Add one placeholder to the question text with the title 'recording'.
            $question->questiontext .= html_writer::div('[[recording:' . $mediatype . ']]');

            // The widgetplaceholders array's key used as placeholder to be replaced with an audio widget.
            // The value is a array containing title (filename without extension) and the mediatype (audio).
            $question->widgetplaceholders = ['[[recording:' . self::MEDIA_TYPE_AUDIO . ']]' => ['recording', self::MEDIA_TYPE_AUDIO]];
        }
    }

    public function export_to_xml($question, qformat_xml $format, $extra = null) {
        $output = '';
        $output .= '    <mediatype>' . $question->options->mediatype .
            "</mediatype>\n";
        $output .= '    <speechphrase>' . $question->options->speechphrase .
            "</speechphrase>\n";
        $output .= '    <accent>' . $question->options->accent .
            "</accent>\n";
        $output .= '    <timelimitinseconds>' . $question->options->timelimitinseconds .
            "</timelimitinseconds>\n";
        $output .= '    <speechtype>' . $question->options->speechtype .
            "</speechtype>\n";
        $output .= '    <scoringoption>' . $question->options->scoringoption .
            "</scoringoption>\n";
        $output .= '    <hascontext>' . $question->options->hascontext .
            "</hascontext>\n";
        $output .= '    <contextquestion>' . $question->options->contextquestion .
            "</contextquestion>\n";
        $output .= '    <contextdescription>' . $question->options->contextdescription .
            "</contextdescription>\n";
        $output .= '    <contextvalidanswerdescription>' . $question->options->contextvalidanswerdescription .
            "</contextvalidanswerdescription>\n";

        return $output;
    }

    public function import_from_xml($data, $question, qformat_xml $format, $extra = null) {
        $questiontype = $data['@']['type'];
        if ($questiontype != $this->name()) {
            return false;
        }

        $qo = $format->import_headers($data);
        $qo->qtype = $questiontype;

        $qo->mediatype = $format->getpath($data, array('#', 'mediatype', 0, '#'), self::MEDIA_TYPE_AUDIO);
        $qo->speechphrase = $format->getpath($data, array('#', 'speechphrase', 0, '#'), false, true, 'speechphrase is a required field for Speech Assessment question definitions');
        $qo->timelimitinseconds = $format->getpath(
            $data,
            array('#', 'timelimitinseconds', 0, '#'),
            get_config('timelimit', 'qtype_lcspeech')
        );
        $qo->accent = $format->getpath($data, array('#', 'accent', 0, '#'), self::DEFAULT_ACCENT);

        return $qo;
    }

    /**
     * When there are placeholders in the question text, validate them and
     * return validation error and display the placeholders format to the question author.
     *
     * @param string $qtext
     * @param string $mediatype
     * @return string|null
     * @throws coding_exception
     */
    public function validate_widget_placeholders($qtext, $mediatype) {

        // The placeholder format.
        $a = new \stdClass();
        $a->text = null;
        $a->format = get_string('err_placeholderformat', 'qtype_lcspeech');

        // Check correctness of open and close square brackets within the question text.
        $openingbrackets = 0;
        $closingbrackets = 0;
        if (preg_match_all("/\[\[/", $qtext, $matches, PREG_SPLIT_NO_EMPTY, 0)) {
            $openingbrackets = count($matches[0]);
        }
        if (preg_match_all("/\]\]/", $qtext, $matches, PREG_SPLIT_NO_EMPTY, 0)) {
            $closingbrackets = count($matches[0]);
        }
        if ($openingbrackets || $closingbrackets) {
            if ($openingbrackets < $closingbrackets) {
                return get_string('err_opensquarebrackets', 'qtype_lcspeech', $a);
            }
            if ($openingbrackets > $closingbrackets) {
                return get_string('err_closesquarebrackets', 'qtype_lcspeech', $a);
            }
        }
        $pattern = "/(\[\[)([A-Za-z0-9_-]+)(:)([a-z]+)(]])/";
        preg_match_all($pattern, $qtext, $matches, PREG_PATTERN_ORDER, 0);

        // If medatype is audio, custom placeholer is not allowed.
        if ($mediatype === self::MEDIA_TYPE_AUDIO && $matches[2]) {
            return get_string(
                'err_placeholdernotallowed',
                'qtype_lcspeech',
                get_string($mediatype, 'qtype_lcspeech')
            );
        }

        if ($matches) {
            // Validate titles.
            $titles = $matches[2];
            $titlesused = [];
            foreach ($titles as $key => $title) {
                if ($title === '' || $title === '-' || $title === '_') {
                    $a->text = $title;
                    return get_string('err_placeholdertitle', 'qtype_lcspeech', $a);
                }
                // The title string exeeds the max length.
                if (strlen($title) > self::MAX_LENGTH_MEDIA_TITLE) {
                    $a->text = $title;
                    $a->maxlength = self::MAX_LENGTH_MEDIA_TITLE;
                    return get_string('err_placeholdertitlelength', 'qtype_lcspeech', $a);
                }
                if (preg_match('/[A-Z]/', $title)) {
                    $a->text = $title;
                    return get_string('err_placeholdertitlecase', 'qtype_lcspeech', $a);
                }
                if (isset($titlesused[$title])) {
                    $a->text = $title;
                    return get_string('err_placeholdertitleduplicate', 'qtype_lcspeech', $a);
                }
                $titlesused[$title] = 1;
            }
            // Validate media types.
            $mediatypes = $matches[4];
            foreach ($mediatypes as $key => $mt) {
                if ($mt !== self::MEDIA_TYPE_AUDIO) {
                    $a->text = $mt;
                    return get_string('err_placeholdermediatype', 'qtype_lcspeech', $a);
                }
            }
            // A media placeholder is not in a correct format.
            if (count($matches[0]) < $openingbrackets) {
                return get_string('err_placeholderincorrectformat', 'qtype_lcspeech', $a);
            }
        }
        return null;
    }

    /**
     * Returns an array of widget placeholders when there are placeholders in question text
     * and when there is no placeholder in the question text, add one as default.
     *
     * @param $questiontext
     * @return array placeholder => filename
     */
    public function get_widget_placeholders($questiontext) {
        preg_match_all('/\[\[([a-z0-9_-]+):(audio)]]/i', $questiontext, $matches, PREG_SET_ORDER);

        $widgetplaceholders = [];
        foreach ($matches as $match) {
            $widgetplaceholders[$match[0]] = [$match[1], $match[2]];
        }
        return $widgetplaceholders;
    }

    /**
     * Return the filename for a particular recorder.
     *
     * @param string $filename file base name without extension, E.g. 'recording-one'.
     * @param string $mediatype 'audio'
     * @return string the file name that should be used.
     */
    public static function get_media_filename(string $filename, string $mediatype) {
        return $filename . '.wav';
    }


    public function save_question_options($question) {
        global $DB;
        $extraquestionfields = $this->extra_question_fields();

        if (is_array($extraquestionfields)) {
            $questionextensiontable = array_shift($extraquestionfields);

            $function = 'update_record';
            $questionidcolname = $this->questionid_column_name();
            $options = $DB->get_record(
                $questionextensiontable,
                array($questionidcolname => $question->id)
            );
            if (!$options) {
                $function = 'insert_record';
                $options = new stdClass();
                $options->$questionidcolname = $question->id;
            }
            foreach ($extraquestionfields as $field) {
                if (property_exists($question, $field)) {
                    $options->$field = $question->$field;
                }
            }

            $DB->{$function}($questionextensiontable, $options);
        }

        $this->save_other_table_details($DB, $question);
    }



    public function save_other_table_details($db, $question) {
        // $itemId = file_get_submitted_draft_itemid('correction_audio');
        // $context = $this->get_context_by_category_id($question->category);
        $function = 'update_record';
        $questionidcolname = $this->questionid_column_name();
        for ($i = 0; $i < 4; $i++) {
            $options = $db->get_record('qtype_lcspeech_feedback',
            array($questionidcolname => $question->id,
            'from_range' => $question->from_range[$i],
            'to_range' => $question->to_range[$i]));
            if (!$options) {
                $function = 'insert_record';
                $options = new stdClass();
                $options->$questionidcolname = $question->id;
            }
            $options->from_range = $question->from_range[$i];
            $options->to_range = $question->to_range[$i];
            $options->feedback = isset($question->feedback[$i]) ? $question->feedback[$i] : '';
            $db->{$function}('qtype_lcspeech_feedback', $options);
        }
        $componentname = 'qtype_lcspeech';
        $optionsaudios = $db->get_records('qtype_lcspeech_audios', array($questionidcolname => $question->id));
        if (!empty($optionsaudios)) {
            $db->delete_records('qtype_lcspeech_audios', array($questionidcolname => $question->id));
        }

        if (isset($question->correction_audio) && !empty($question->correction_audio)) {
            $i = 0;
            foreach ($question->correction_audio as $audio) {
                file_save_draft_area_files($audio, $question->context->id, $componentname,
                'correction_audio', (int)$audio, array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1));
                $fs = get_file_storage();
                $files = $fs->get_area_files($question->context->id, 'qtype_lcspeech', 'correction_audio', (int)$audio, '', false);
                if (empty($files)) {
                    continue;
                }
                $file = reset($files);
                $filename = $file->get_filename();
                $optionsaudios = new stdClass();
                $optionsaudios->$questionidcolname = $question->id;
                $optionsaudios->language = $question->language[$i];
                $optionsaudios->audio_file = $filename;
                $optionsaudios->unique_item_id = $audio;
                $db->insert_record('qtype_lcspeech_audios', $optionsaudios);
            }
        }
    }

    public function get_question_options($question) {
        global $CFG, $DB, $OUTPUT;

        if (!isset($question->options)) {
            $question->options = new stdClass();
        }
        $extraquestionfields = $this->extra_question_fields();
        if (is_array($extraquestionfields)) {
            $questionextensiontable = array_shift($extraquestionfields);
            $extradata = $DB->get_record(
                $questionextensiontable,
                array($this->questionid_column_name() => $question->id),
                implode(', ', $extraquestionfields)
            );
            if ($extradata) {
                foreach ($extraquestionfields as $field) {
                    $question->options->$field = $extradata->$field;
                }
            } else {
                echo $OUTPUT->notification('Failed to load question options from the table ' .
                    $questionextensiontable . ' for questionid ' . $question->id);
                return false;
            }
        }
        $question->options->range = $DB->get_records('qtype_lcspeech_feedback', array('questionid' => $question->id), 'id ASC');
        $question->options->audios = $DB->get_records('qtype_lcspeech_audios', array('questionid' => $question->id), 'id ASC');
        return true;
    }
}
