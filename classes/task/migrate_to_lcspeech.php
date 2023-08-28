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

class migrate_to_lcspeech extends scheduled_task {

    /**
     * @inheritDoc
     */
    public function get_name() {
        return get_string('taskmigratetolcspeech', 'qtype_lcspeech');
    }

    /**
     * @inheritDoc
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->libdir . '/questionlib.php');

        mtrace('START migrate questions to LC Speech plugin');
        try {
            // Languageconfidence: pronunciation.
            // Speechassessment: unscripted.
            $this->migrate_qtype_languageconfidence();

            $this->migrate_qtype_speechassessment();
        } catch (\Exception $e) {
            mtrace('Something went wrong ' . $e);
        }
        mtrace('END migrate questions to LC Speech plugin');
    }

    private function migrate_qtype_speechassessment() {
        $qtype = 'speechassessment';
        mtrace('start migrate plugin: ' . $qtype);

        // Get all question by qtype.
        $questions = $this->get_question_by_qtype($qtype);
        // Insert mdl_qtype_lcspeech_feedback.
        $this->handle_qtype_speechassessment($questions);
    }


    private function migrate_qtype_languageconfidence() {
        $qtype = 'languageconfidence';
        mtrace('start migrate plugin: ' . $qtype);

        // Get all question by qtype.
        $questions = $this->get_question_by_qtype($qtype);
        // Insert mdl_qtype_lcspeech_feedback.
        $this->handle_qtype_languageconfidence($questions);
    }

    private function get_question_by_qtype($qtype) {
        global $DB, $CFG;
        // Delete old files.
        $questions = $DB->get_records_sql(
            "
                SELECT *
                FROM {question} q
                WHERE 1 = 1
                AND q.qtype = :qtype
            ",
            array(
                'qtype' => $qtype
            )
        );

        mtrace($qtype .' Found size: ' . count($questions));
        return $questions;
    }

    private function handle_qtype_speechassessment($questions) {
        global $DB, $CFG;
        // Loop all questions speechassessment.
        foreach ($questions as $q) {
            mtrace('process question: ' . $q->id);
            $q->qtype = 'lcspeech';
            $apiresults = $DB->update_record("question", $q);

            // Insert record mdl_qtype_lcspeech_feedback.
            $this->insert_lcspeech_feedback($q->id);

            // Insert mdl_qtype_lcspeech_options.
            $this->insert_lcspeech_feedback_speechassessment($q->id);
        }
    }


    private function handle_qtype_languageconfidence($questions) {
        global $DB, $CFG;
        // Loop all questions languageconfidence.
        foreach ($questions as $q) {
            mtrace('process question: ' . $q->id);
            $q->qtype = 'lcspeech';
            $apiresults = $DB->update_record("question", $q);

            // Insert record mdl_qtype_lcspeech_feedback.
            $this->insert_lcspeech_feedback($q->id);

            // Insert mdl_qtype_lcspeech_options.
            $this->insert_lcspeech_feedback_languageconfidence($q->id);
        }
    }

    private function insert_lcspeech_feedback_languageconfidence($questionid) {
        global $DB, $CFG;
        // Delete mdl_qtype_lcspeech_options by question id.
        $DB->delete_records('qtype_lcspeech_options', array('questionid' => $questionid));

        // Get mdl_qtype_langconfid_options
        $dbrecord = $DB->get_record_sql(
            "
                SELECT *
                FROM {qtype_langconfid_options}
                WHERE questionid = :id
                LIMIT 1
            ",
            array(
                'id' => $questionid,
            )
        );

        // Build record and save.
        $opt = new \stdClass();
        $opt->questionid = $questionid;
        $opt->mediatype = $dbrecord->mediatype;
        $opt->speechphrase = $dbrecord->speechphrase;
        $opt->timelimitinseconds = $dbrecord->timelimitinseconds;
        $opt->accent = $dbrecord->accent;
        $opt->speechtype = 'pronunciation';
        $opt->hascontext = 0;
        $opt->scoringoption = 'DEFAULT';
        $DB->insert_record('qtype_lcspeech_options', $opt);
    }

    private function insert_lcspeech_feedback_speechassessment($questionid) {
        global $DB, $CFG;
        // Delete mdl_qtype_lcspeech_options by question id.
        $DB->delete_records('qtype_lcspeech_options', array('questionid' => $questionid));

        // Get qtype_speechass_options.
        $dbrecord = $DB->get_record_sql(
            "
                SELECT *
                FROM {qtype_speechass_options}
                WHERE questionid = :id
                LIMIT 1
            ",
            array(
                'id' => $questionid,
            )
        );

        // Build record and save.
        $opt = new \stdClass();
        $opt->questionid = $questionid;
        $opt->mediatype = $dbrecord->mediatype;
        $opt->speechphrase = $dbrecord->speechphrase;
        $opt->timelimitinseconds = $dbrecord->timelimitinseconds;
        $opt->accent = $dbrecord->accent;
        $opt->speechtype = 'unscripted';
        $opt->hascontext = 0;
        $opt->scoringoption = 'DEFAULT';
        $DB->insert_record('qtype_lcspeech_options', $opt);
    }

    private function insert_lcspeech_feedback($questionid) {
        global $DB, $CFG;
        // Delete mdl_qtype_lcspeech_feedback by question id.
        $DB->delete_records('qtype_lcspeech_feedback', array('questionid' => $questionid));

        $f1 = new \stdClass();
        $f1->questionid = $questionid;
        $f1->from_range = 0;
        $f1->to_range = 30;
        $f1->feedback = '';
        $DB->insert_record('qtype_lcspeech_feedback', $f1);

        $f2 = new \stdClass();
        $f2->questionid = $questionid;
        $f2->from_range = 31;
        $f2->to_range = 50;
        $f2->feedback = '';
        $DB->insert_record('qtype_lcspeech_feedback', $f2);

        $f3 = new \stdClass();
        $f3->questionid = $questionid;
        $f3->from_range = 51;
        $f3->to_range = 80;
        $f3->feedback = '';
        $DB->insert_record('qtype_lcspeech_feedback', $f3);

        $f4 = new \stdClass();
        $f4->questionid = $questionid;
        $f4->from_range = 81;
        $f4->to_range = 100;
        $f4->feedback = '';
        $DB->insert_record('qtype_lcspeech_feedback', $f4);
    }

    private function get_sentence($questionid) {
        mtrace('start get_sentence: ' . $questionid);
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
            mtrace('end get_sentence: ' . $questionid . ', result: ' . $dbrecord->speechphrase);
            return  $dbrecord->speechphrase;
        }
        return null;
    }

}
