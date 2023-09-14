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
 * Question class for the Speech Assessment question type.
 *
 * @package   qtype_lcspeech
 * @copyright 2023 Speech Assessment
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/question/type/lcspeech/lib.php');

/**
 * A Speech Assessment question that is being attempted.
 *
 * @copyright 2023 Speech Assessment
 */
class qtype_lcspeech_question extends question_graded_automatically {

    /**
     * @var int the phrase that the student is to say out loud and to be assessed against.
     */
    public $speechphrase;

    /**
     * @var int the maximum length recording, in seconds, the student is allowed to make.
     */
    public $timelimitinseconds;

    /**
     * @var string media type, 'audio'
     */
    public $mediatype;

    /**
     * @var string accent
     */
    public $accent;

    /**
     * @var string[] placeholder => filename
     */
    public $widgetplaceholders;

    public function get_expected_data() {
        return ['recording' => question_attempt::PARAM_FILES];
    }

    /**
     * Get the upload file size limit that applies here.
     *
     * @param context $context the context we are in.
     * @return int max size in bytes.
     */
    public function get_upload_size_limit(context $context) {
        global $CFG;

        $coursebytes = $maxbytes = 0;
        list($context, $course, $cm) = get_context_info_array($context->id);
        if (is_object($course)) {
            $coursebytes = $course->maxbytes;
        }

        return get_user_max_upload_file_size($context, $CFG->maxbytes, $coursebytes);
    }

    public function summarise_response(array $response) {
        if (!isset($response['recording']) || $response['recording'] === '') {
            return get_string('norecording', 'qtype_lcspeech');
        }

        $files = $response['recording']->get_files();
        $file = reset($files);

        if (!$file) {
            return get_string('norecording', 'qtype_lcspeech');
        }

        return get_string('filex', 'qtype_lcspeech', $file->get_filename());
    }

    public function is_complete_response(array $response) {
        if (!isset($response['recording']) || $response['recording'] === '') {
            return false;
        }

        $files = $response['recording']->get_files();
        foreach ($this->widgetplaceholders as $unused => [$title, $mediatype]) {
            $filename = \qtype_lcspeech::get_media_filename($title, $mediatype);
            if (!$this->get_file_from_response($filename, $files)) {
                return false;
            }
        }
        return true;
    }

    public function is_gradable_response(array $response) {
        if (!isset($response['recording']) || $response['recording'] === '') {
            return false;
        }

        $files = $response['recording']->get_files();
        return !empty($files);
    }

    /**
     * Get a specific file from the array of files in a resonse (or null).
     *
     * @param string $filename the file we want.
     * @param stored_file[] $files all the files from a response (e.g. $response['recording']->get_files();)
     * @return stored_file|null the file, if it exists, or null if not.
     */
    public function get_file_from_response(string $filename, array $files): ?stored_file {
        foreach ($files as $file) {
            if ($file->get_filename() === $filename) {
                return $file;
            }
        }

        return null;
    }

    public function get_validation_error(array $response) {
        if ($this->is_complete_response($response)) {
            return '';
        }
        return get_string('pleaserecordsomethingineachpart', 'qtype_lcspeech');
    }

    public function get_score_for_audio($audio) {
        global $DB;

        $format = 'wav';
        $payload;
        // check speedtype
        if ($this->speechtype == 'scripted' || $this->speechtype == 'pronunciation') {
            $payload = array(
                "audio_format" => $format,
                "expected_text" => $this->speechphrase,
                "audio_base64" => base64_encode($audio)
            );
        } else {
            // unscripted
            $payload = array(
                "audio_format" => $format,
                "audio_base64" => base64_encode($audio)
            );

            $context = array();
            if (!empty($this->contextquestion)) {
                $context["question"] = $this->contextquestion;
            }
            if (!empty($this->contextdescription)) {
                $context["context_description"] = $this->contextdescription;
            }
            if (!empty($this->contextvalidanswerdescription)) {
                $context["valid_answer_description"] = $this->contextvalidanswerdescription;
            }

            if (count($context) > 0) {
                $payload["context"] = $context;
            }
        }

        $payloadkeys = array_keys($payload);
        sort($payloadkeys);
        $payloadhash = hash('sha256', json_encode(
            array_map(function ($key) use ($payload) {
                return array($key => $payload[$key]);
            }, $payloadkeys)
        ));

        $dbrecord = $DB->get_record_sql(
            "
                SELECT response_json
                FROM {qtype_lcspeech_api_results}
                WHERE payload_hash = :payload_hash
                ORDER BY createdat DESC
                LIMIT 1
            ",
            array(
                'payload_hash' => $payloadhash
            )
        );

        if ($dbrecord) {
            return json_decode($dbrecord->response_json, true);
        }

        $processedresult = $this->call_api_and_process_response($payload);

        if (!$processedresult['success']) {
            throw new \Exception($processedresult['error_message']);
        }

        $newrecord = new stdClass();
        $newrecord->sentence = '';
        $newrecord->format = $format;
        $newrecord->scoring = 'none';
        $newrecord->payload_hash = $payloadhash;
        $newrecord->response_json = $processedresult['raw_response'];
        $newrecord->createdat = time();
        $DB->insert_record('qtype_lcspeech_api_results', $newrecord);

        return $processedresult['parsed_response'];
    }

    protected function call_api_and_process_response($payload) {
        global $CFG, $USER;
        $apikey = $CFG->lcspeech_apikey;

        $speechtype = $this->speechtype;
        $url;
        if ($speechtype == 'scripted') {
            $url = get_config('qtype_lcspeech', 'api_scripted_url');
        } else if ($speechtype == 'unscripted') {
            $url = get_config('qtype_lcspeech', 'api_unscripted_url');
        } else if ($speechtype == 'pronunciation') {
            $url = get_config('qtype_lcspeech', 'api_pronunciation_url');
        }

        $endpoint = "{$url}/{$this->accent}";
        // var_dump($endpoint);
        $header = array(
            'Content-Type: application/json',
            'x-blobr-key: ' . $apikey,
            'x-user-id:' . $this->get_current_hostname() . '-' . $USER->id,
            'lc-custom-moodle-instance-hostname:' . $this->get_current_hostname()
        );
        // check setting lc-beta-features
        if (get_config('qtype_lcspeech', 'enablelcbetafeatures')) {
            array_push($header, 'lc-beta-features: true');
        }

        $postdata = json_encode($payload);
        // $ch = curl_init($endpoint);
        // curl_setopt($ch, CURLOPT_POST, 1);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        // $resultraw = curl_exec($ch);
        // curl_close($ch);
        $options = array(
            'RETURNTRANSFER' => 1,
            'HEADER' => 0,
            'FAILONERROR' => 1,
            'FOLLOWLOCATION' => 1
        );

        $curl = new \curl();
        $curl->setHeader($header);
        $resultraw = $curl->post($endpoint, $postdata, $options);

        $result = json_decode($resultraw, true);
        if ($result == null) {
            return array(
                'success' => false,
                'raw_response' => $resultraw,
                'parsed_response' => $result,
                'error_message' => 'Failed to get the score - Can not get response from API'
            );
        }

        if (array_key_exists('overall', $result) || array_key_exists('overall_score', $result)) {
            return array(
                'success' => true,
                'raw_response' => $resultraw,
                'parsed_response' => $result,
                'error_message' => null
            );
        } else {
            if (array_key_exists('detail', $result)) {
                $errormessage = 'Error: ' . $result['detail'];
            } else {
                $errormessage = 'Failed to get the score';
            }
            return array(
                'success' => false,
                'raw_response' => $resultraw,
                'parsed_response' => $result,
                'error_message' => $errormessage
            );
        }
    }

    public function grade_response(array $response) {
        qtype_lcspeech_ensure_api_config_is_set();
        $scores = array();
        if ($this->is_complete_response($response)) {
            $files = $response['recording']->get_files();
            foreach ($files as $file) {
                $audio = $file->get_content();
                $result = $this->get_score_for_audio($audio);
                // array_push($scores, $result['overall_score'] / 100);
                if ($this->speechtype == 'pronunciation') {
                    array_push($scores, $result['overall_score'] / 100);
                } else {
                    array_push($scores, $result['overall']['english_proficiency_scores']['mock_ielts']['prediction'] / 10);
                }
            }
        }
        $total = array_sum($scores);
        $count = count($scores);
        $fraction = $total / $count;

        return array(
            $fraction,
            question_state::graded_state_for_fraction($fraction)
        );
    }

    public function get_hint($hintnumber, question_attempt $qa) {
        return null;
    }

    public function get_right_answer_summary() {
        return null;
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        return question_utils::arrays_same_at_key_missing_is_blank(
            $prevresponse,
            $newresponse,
            'recording'
        );
    }

    public function get_answers() {
        return $this->answers;
    }

    public function get_correct_response() {
        return null;
    }

    public function check_file_access(
        $qa,
        $options,
        $component,
        $filearea,
        $args,
        $forcedownload
    ) {
        if ($component == 'question' && $filearea == 'response_recording') {
            return true;
        }
        if ($filearea == 'correction_audio') {
            return true;
        }
        return parent::check_file_access(
            $qa,
            $options,
            $component,
            $filearea,
            $args,
            $forcedownload
        );
    }

    public function get_current_hostname() {
        $servername = $_SERVER['SERVER_NAME'];

        if (!in_array($_SERVER['SERVER_PORT'], [80, 443])) {
            $port = ":$_SERVER[SERVER_PORT]";
        } else {
            $port = '';
        }

        return $servername . $port;
    }
}
