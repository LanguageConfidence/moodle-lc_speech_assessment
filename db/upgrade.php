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
 * Speech Assessment question type db upgrade script
 *
 * @package   qtype_lcspeech
 * @copyright 2023 Speech Assessment
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/question/type/lcspeech/lib.php');

/**
 * Upgrade code for the Speech Assessment question type.
 *
 * @param int $oldversion the version we are upgrading from.
 * @return bool
 */
function xmldb_qtype_lcspeech_upgrade($oldversion) {
    global $DB;

    qtype_lcspeech_ensure_api_config_is_set();

    $dbman = $DB->get_manager();

    $newversion = 2022082600;

    if ($oldversion < $newversion) {
        $table = new xmldb_table('qtype_lcspeech_options');
        $field = new xmldb_field('speechphrase', XMLDB_TYPE_TEXT, null, null, null, null, null, 'mediatype');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $accentfield = new xmldb_field('accent', XMLDB_TYPE_CHAR, 2, null, null, null, 'us', 'timelimitinseconds');

        if (!$dbman->field_exists($table, $accentfield)) {
            $dbman->add_field($table, $accentfield);
        }

        $feedbacktable = new xmldb_table('qtype_lcspeech_feedback');
        $feedbackid = new xmldb_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $feedbackquestionid = new xmldb_field('questionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $feedbackfrom = new xmldb_field('from_range', XMLDB_TYPE_INTEGER, '10', null, true, null, null);
        $feedbackto = new xmldb_field('to_range', XMLDB_TYPE_INTEGER, '10', null, true, null, null);
        $feedback = new xmldb_field('feedback', XMLDB_TYPE_TEXT, 'long', null, null, null, null);

        $feedbacktable->add_key('primary', XMLDB_KEY_PRIMARY, array('id'), null, null);
        $feedbacktable->add_key('foreignkey1', XMLDB_KEY_FOREIGN, array('questionid'), 'question', array('id'));

        if (!$dbman->table_exists($feedbacktable)) {
            $feedbacktable->addField($feedbackid);
            $feedbacktable->addField($feedbackquestionid);
            $feedbacktable->addField($feedbackfrom);
            $feedbacktable->addField($feedbackto);
            $feedbacktable->addField($feedback);
            $dbman->create_table($feedbacktable);
        }

        $correctionaudiotable = new xmldb_table('qtype_lcspeech_audios');
        $correctionaudioid = new xmldb_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $correctionaudioquestionid = new xmldb_field('questionid', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $correctionaudiolanguage = new xmldb_field('language', XMLDB_TYPE_TEXT, '20', null, true, null, null);
        $correctionaudiofile = new xmldb_field('audio_file', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);

        $correctionaudiotable->add_key('primary', XMLDB_KEY_PRIMARY, array('id'), null, null);
        $correctionaudiotable->add_key('foreignkey1', XMLDB_KEY_FOREIGN, array('questionid'), 'question', array('id'));

        if (!$dbman->table_exists($correctionaudiotable)) {
            $correctionaudiotable->addField($correctionaudioid);
            $correctionaudiotable->addField($correctionaudioquestionid);
            $correctionaudiotable->addField($correctionaudiolanguage);
            $correctionaudiotable->addField($correctionaudiofile);
            $dbman->create_table($correctionaudiotable);
        }

        $correctionaudiouniqueid = new xmldb_field('unique_item_id', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        if (!$dbman->field_exists($correctionaudiotable, $correctionaudiouniqueid)) {
            $dbman->add_field($correctionaudiotable, $correctionaudiouniqueid);
        }

        upgrade_plugin_savepoint(true, $newversion, 'qtype', 'lcspeech');
    }

    return true;
}
