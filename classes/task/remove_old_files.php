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

namespace qtype_lcspeech\task;

use core\task\scheduled_task;

class remove_old_files extends scheduled_task {

    /**
     * @inheritDoc
     */
    public function get_name() {
        return get_string('taskremoveoldfiles', 'qtype_lcspeech');
    }

    /**
     * @inheritDoc
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->libdir . '/questionlib.php');

        // check no expire
        $isnoexpiration = get_config('qtype_lcspeech', 'noexpirationaudio');
        if ($isnoexpiration) {
            mtrace('Setting no expiration audio, value: ' . $isnoexpiration);
            return;
        }

        mtrace('START execute remove old files');
        try {
            $fs = get_file_storage();
            $days = get_config('qtype_lcspeech', 'daysolderaudiofiles');
            mtrace('config daysolderaudiofiles: ' . $days . ' days');

            $this->updateexistingrecords();

            // Delete old files.
            $dbtype = $CFG->dbtype;
            // oldFiles.
            mtrace('DB Type : ' . $dbtype);
            if ($dbtype == 'pgsql') {
                $oldfiles = $DB->get_records_sql(
                    "
                        SELECT *
                        FROM {files} f
                        WHERE 1 = 1
                        AND (to_timestamp(f.timecreated)::date <= (now()::date - :days::integer))
                        AND f.filearea = :filearea
                    ",
                    array(
                        'days' => $days,
                        'filearea' => 'response_recording'
                    )
                );
            } else {
                $oldfiles = $DB->get_records_sql("
                    SELECT *
                    FROM {files} f
                    WHERE DATE(FROM_UNIXTIME(f.timecreated)) <= DATE_SUB(CURDATE(), INTERVAL " . $days . " DAY) AND f.filearea=?", ['response_recording']);
            }
            $count = 0;
            foreach ($oldfiles as $f) {
                $file = $fs->get_file($f->contextid, $f->component, $f->filearea, $f->itemid, $f->filepath, $f->filename);
                if ($file) {
                    mtrace('begin delete file : ' . $f->filename . ', path: ' . $f->filepath);
                    $file->delete();
                }
                ++$count;
            }
            mtrace('Cleaned up ' . $count . ' old files.');
        } catch (\Exception $e) {
            mtrace('Something went wrong ' . $e);
        }
        mtrace('END execute remove old files');
    }

    private function updateexistingrecords() {
        mtrace('START updateexistingrecords');
        global $DB, $CFG;
        $fs = get_file_storage();
        // Get al files where file area is response_recording.

        $allfilesrecord;
        $dbtype = $CFG->dbtype;
        $days = get_config('qtype_lcspeech', 'daysolderaudiofiles');
        if ($dbtype == 'pgsql') {
            $allfilesrecord = $DB->get_records_sql(
                "
                    SELECT *
                    FROM {files} f
                    WHERE 1 = 1
                    AND (to_timestamp(f.timecreated)::date <= (now()::date - :days::integer))
                    AND f.filearea = :filearea
                ",
                array(
                    'days' => $days,
                    'filearea' => 'response_recording'
                )
            );
        } else {
            $allfilesrecord = $DB->get_records_sql("
                SELECT *
                FROM {files} f
                WHERE DATE(FROM_UNIXTIME(f.timecreated)) <= DATE_SUB(CURDATE(), INTERVAL " . $days . " DAY) AND f.filearea=?", ['response_recording']);
        }

        if (!isset($allfilesrecord)) {
            return null;
        }

        mtrace('begin check existing records, number: ' . count($allfilesrecord));
        foreach ($allfilesrecord as $afiles) {
            mtrace('start process itemid: ' . $afiles->itemid . ', filename: ' . $afiles->filename);
            $file = $fs->get_file($afiles->contextid, $afiles->component, $afiles->filearea, $afiles->itemid, $afiles->filepath, $afiles->filename);
            $attemptstepid = $afiles->itemid;
            $lastattemptid = $this->get_attempt_id_using_step_id($attemptstepid);
            $questionid = $this->get_question_id($lastattemptid);
            $sentence = $this->getsentence($questionid);

            if (!isset($sentence)) {
                continue;
            }

            // Calculate payload hash.
            $payload = array(
                "audio_format" => 'wav',
                "expected_text" => $sentence,
                "audio_base64" => base64_encode($file->get_content())
            );

            $payloadkeys = array_keys($payload);
            sort($payloadkeys);
            $payloadhash = hash('sha256', json_encode(
                array_map(function ($key) use ($payload) {
                    return array($key => $payload[$key]);
                }, $payloadkeys)
            ));
            $apiresults = $DB->get_record_sql(
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
            if (!isset($apiresults)) {
                mtrace('not found : ' . $attemptstepid);
                continue;
            }

            mtrace('end process itemid: ' . $afiles->itemid . ', filename: ' . $afiles->filename);
        }

        mtrace('END updateexistingrecords');
    }

    private function get_hash_identifier($attemptstepid, $lastattemptid, $questionid) {

        if (!$attemptstepid || !$lastattemptid || !$questionid) {
            return null;
        }

        $payloadunique = array(
            "question_id" => (string)$questionid,
            "attempt_id" => (string)$lastattemptid,
            "step_id" => (string)$attemptstepid
        );

        return  $this->get_payload_hash($payloadunique);
    }

    private function getsentence($questionid) {
        mtrace('start getsentence: ' . $questionid);
        global $DB;
        $dbrecord = $DB->get_record_sql(
            "
                SELECT speechphrase
                FROM {qtype_lcspeech_options}
                WHERE questionid = :id
                LIMIT 1
            ",
            array(
                'id' => $questionid,
            )
        );
        if ($dbrecord) {
            mtrace('end getsentence: ' . $questionid . ', result: ' . $dbrecord->speechphrase);
            return  $dbrecord->speechphrase;
        }
        return null;
    }

    private function get_question_id($attemptid) {
        mtrace('start get_question_id: ' . $attemptid);
        global $DB;
        $dbrecord = $DB->get_record_sql(
            "
                SELECT questionid
                FROM {question_attempts}
                WHERE id = :id
                LIMIT 1
            ",
            array(
                'id' => $attemptid,
            )
        );

        if ($dbrecord) {
            mtrace('end get_question_id: ' . $attemptid);
            return  json_decode($dbrecord->questionid, true);
        }
        return null;
    }

    private function get_attempt_id_using_step_id($stepid) {
        mtrace('start get_attempt_id_using_step_id: ' . $stepid);
        global $DB;
        $dbrecord = $DB->get_record_sql(
            "
                SELECT questionattemptid
                FROM {question_attempt_steps}
                WHERE id = :id
                LIMIT 1
            ",
            array(
                'id' => $stepid,
            )
        );
        if ($dbrecord) {
            mtrace('end get_attempt_id_using_step_id: ' . $stepid . ', result :' . json_decode($dbrecord->questionattemptid, true));
            return json_decode($dbrecord->questionattemptid, true);
        }
        return null;
    }

    private function get_payload_hash($payload) {
        $payloadkeys = array_keys($payload);
        sort($payloadkeys);
        return hash('sha256', json_encode(array_map(static function ($key) use ($payload) {
            return array($key => $payload[$key]);
        }, $payloadkeys), JSON_THROW_ON_ERROR));
    }
}
