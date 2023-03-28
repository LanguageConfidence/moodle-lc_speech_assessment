<?php
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

    $newVersion = 2022082600;

    if ($oldversion < $newVersion) {
        $table = new xmldb_table('qtype_lcspeech_options');
        $field = new xmldb_field('speechphrase', XMLDB_TYPE_TEXT, null, null, null, null, null, 'mediatype');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $accentField = new xmldb_field('accent', XMLDB_TYPE_CHAR, 2, null, null, null, 'us', 'timelimitinseconds');

        if (!$dbman->field_exists($table, $accentField)) {
            $dbman->add_field($table, $accentField);
        }

        $feedBackTable =  new xmldb_table('qtype_lcspeech_feedback');
        $feedBackId = new xmldb_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $feedBackQuestionId = new xmldb_field('questionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $feedBackFrom = new xmldb_field('from_range', XMLDB_TYPE_INTEGER, '10', null, true, null, null);
        $feedBackTo = new xmldb_field('to_range', XMLDB_TYPE_INTEGER, '10', null, true, null, null);
        $feedBack = new xmldb_field('feedback', XMLDB_TYPE_TEXT, 'long', null, null, null, null);

        $feedBackTable->add_key('primary', XMLDB_KEY_PRIMARY, array('id'), null, null);
        $feedBackTable->add_key('foreignkey1', XMLDB_KEY_FOREIGN, array('questionid'), 'question', array('id'));


        if(!$dbman->table_exists($feedBackTable)) {
            $feedBackTable->addField($feedBackId);
            $feedBackTable->addField($feedBackQuestionId);
            $feedBackTable->addField($feedBackFrom);
            $feedBackTable->addField($feedBackTo);
            $feedBackTable->addField($feedBack);
            $dbman->create_table($feedBackTable);
        }


        $correctionAudioTable =  new xmldb_table('qtype_lcspeech_audios');
        $correctionAudioId = new xmldb_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $correctionAudioQuestionId = new xmldb_field('questionid', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $correctionAudioLanguage = new xmldb_field('language', XMLDB_TYPE_TEXT, '20', null, true, null, null );
        $correctionAudioFile = new xmldb_field('audio_file', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);

        $correctionAudioTable->add_key('primary', XMLDB_KEY_PRIMARY, array('id'), null, null);
        $correctionAudioTable->add_key('foreignkey1', XMLDB_KEY_FOREIGN, array('questionid'), 'question', array('id'));

        if(!$dbman->table_exists($correctionAudioTable)) {
            $correctionAudioTable->addField($correctionAudioId);
            $correctionAudioTable->addField($correctionAudioQuestionId);
            $correctionAudioTable->addField($correctionAudioLanguage);
            $correctionAudioTable->addField($correctionAudioFile);
            $dbman->create_table($correctionAudioTable);
        }

        $correctionAudioUniqueId = new xmldb_field('unique_item_id', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        if (!$dbman->field_exists($correctionAudioTable, $correctionAudioUniqueId)) {
            $dbman->add_field($correctionAudioTable, $correctionAudioUniqueId);
        }


        upgrade_plugin_savepoint(true, $newVersion, 'qtype', 'lcspeech');
    }



    return true;
}
