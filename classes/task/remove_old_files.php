<?php

/** @noinspection SqlDialectInspection */

/** @noinspection SqlNoDataSourceInspection */

namespace qtype_languageconfidence\task;

use core\task\scheduled_task;

class remove_old_files extends scheduled_task
{

    /**
     * @inheritDoc
     */
    public function get_name()
    {
        return get_string('taskremoveoldfiles', 'qtype_lcspeech');
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        mtrace('START execute remove old files');
        try {
            global $DB, $CFG;
            require_once($CFG->libdir . '/questionlib.php');
            $fs = get_file_storage();
            $days = get_config('qtype_lcspeech', 'daysolderaudiofiles');
            mtrace('config daysolderaudiofiles: ' . $days . ' days');

            $this->updateExistingRecords();

            /**delete old files **/
            $dbtype = $CFG->dbtype;
            // $oldFiles;
            mtrace('DB Type : ' . $dbtype);
            if ($dbtype == 'pgsql') {
                $oldFiles = $DB->get_records_sql(
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
                $oldFiles = $DB->get_records_sql("
                    SELECT *
                    FROM {files} f
                    WHERE DATE(FROM_UNIXTIME(f.timecreated)) <= DATE_SUB(CURDATE(), INTERVAL " . $days . " DAY) AND f.filearea=?", ['response_recording']);
            }
            $count = 0;
            foreach ($oldFiles as $f) {
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

    private function updateExistingRecords()
    {
        mtrace('START updateExistingRecords');
        global $DB, $CFG;
        $fs = get_file_storage();
        /** get al files where file area is response_recording */

        $allFilesRecord;
        $dbtype = $CFG->dbtype;
        $days = get_config('qtype_languageconfidence', 'daysolderaudiofiles');
        if ($dbtype == 'pgsql') {
            $allFilesRecord = $DB->get_records_sql(
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
            $allFilesRecord = $DB->get_records_sql("
                SELECT *
                FROM {files} f
                WHERE DATE(FROM_UNIXTIME(f.timecreated)) <= DATE_SUB(CURDATE(), INTERVAL " . $days . " DAY) AND f.filearea=?", ['response_recording']);
        }

        if (!isset($allFilesRecord)) {
            return null;
        }

        mtrace('begin check existing records, number: ' . count($allFilesRecord));
        foreach ($allFilesRecord as $aFiles) {
            mtrace('start process itemid: ' . $aFiles->itemid . ', filename: ' . $aFiles->filename);
            $file = $fs->get_file($aFiles->contextid, $aFiles->component, $aFiles->filearea, $aFiles->itemid, $aFiles->filepath, $aFiles->filename);
            $attemptStepId = $aFiles->itemid;
            $lastAttemptId = $this->getAttemptIdUsingStepId($attemptStepId);
            $questionId = $this->getQuestionId($lastAttemptId);
            $sentence = $this->getSentence($questionId);

            if (!isset($sentence)) {
                continue;
            }

            // calculate payload hash
            $payload = array(
                "audio_format" => 'wav',
                "expected_text" => $sentence,
                "audio_base64" => base64_encode($file->get_content())
            );

            $payload_keys = array_keys($payload);
            sort($payload_keys);
            $payload_hash = hash('sha256', json_encode(
                array_map(function ($key) use ($payload) {
                    return array($key => $payload[$key]);
                }, $payload_keys)
            ));
            $apiResults = $DB->get_record_sql(
                "
                    SELECT response_json
                    FROM {qtype_lcspeech_api_results}
                    WHERE payload_hash = :payload_hash
                    ORDER BY createdat DESC
                    LIMIT 1
                ",
                array(
                    'payload_hash' => $payload_hash
                )
            );
            if (!isset($apiResults)) {
                mtrace('not found : ' . $attemptStepId);
                continue;
            }

            mtrace('end process itemid: ' . $aFiles->itemid . ', filename: ' . $aFiles->filename);
            // $payloadUnique = $this->getHashIdentifier($attemptStepId, $lastAttemptId, $questionId);
            // if($payloadUnique) {
            //     $compare_scale_clause = $DB->sql_compare_text('payload_hash')  . ' = ' . $DB->sql_compare_text(':payload_hash');
            //     $DB->set_field_select("qtype_lcspeech_api_results", 'unique_hash_identifier', $payloadUnique, $compare_scale_clause, array('payload_hash'=>$payload_hash));
            // }
        }

        mtrace('END updateExistingRecords');
    }

    private function getHashIdentifier($attemptStepId, $lastAttemptId, $questionId)
    {

        if (!$attemptStepId || !$lastAttemptId || !$questionId) {
            return null;
        }

        $payloadUnique = array(
            "question_id" => (string)$questionId,
            "attempt_id" => (string)$lastAttemptId,
            "step_id" => (string)$attemptStepId
        );

        return  $this->getPayloadHash($payloadUnique);
    }

    private function getSentence($questionId)
    {
        mtrace('start getSentence: ' . $questionId);
        global $DB;
        $db_record = $DB->get_record_sql(
            "
                SELECT speechphrase
                FROM {qtype_lcspeech_options}
                WHERE questionid = :id
                LIMIT 1
            ",
            array(
                'id' => $questionId,
            )
        );
        if ($db_record) {
            mtrace('end getSentence: ' . $questionId . ', result: ' . $db_record->speechphrase);
            return  $db_record->speechphrase;
        }
        return null;
    }

    private function getQuestionId($attemptId)
    {
        mtrace('start getQuestionId: ' . $attemptId);
        global $DB;
        $db_record = $DB->get_record_sql(
            "
                SELECT questionid
                FROM {question_attempts}
                WHERE id = :id
                LIMIT 1
            ",
            array(
                'id' => $attemptId,
            )
        );

        if ($db_record) {
            mtrace('end getQuestionId: ' . $attemptId);
            return  json_decode($db_record->questionid, true);
        }
        return null;
    }

    private function getAttemptIdUsingStepId($stepId)
    {
        mtrace('start getAttemptIdUsingStepId: ' . $stepId);
        global $DB;
        $db_record = $DB->get_record_sql(
            "
                SELECT questionattemptid
                FROM {question_attempt_steps}
                WHERE id = :id
                LIMIT 1
            ",
            array(
                'id' => $stepId,
            )
        );
        if ($db_record) {
            mtrace('end getAttemptIdUsingStepId: ' . $stepId . ', result :' . json_decode($db_record->questionattemptid, true));
            return json_decode($db_record->questionattemptid, true);
        }
        return null;
    }

    private function getPayloadHash($payload)
    {
        $payload_keys = array_keys($payload);
        sort($payload_keys);
        return hash('sha256', json_encode(array_map(static function ($key) use ($payload) {
            return array($key => $payload[$key]);
        }, $payload_keys), JSON_THROW_ON_ERROR));
    }
}
