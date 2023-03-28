<?php
/**
 * Speech Assessment question type backup handler
 *
 * @package   qtype_lcspeech
 * @copyright 2023 Speech Assessment
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Provides the information to backup Speech Assessment questions
 *
 * @copyright 2023 Speech Assessment
 */
class backup_qtype_lcspeech_plugin extends backup_qtype_plugin {

    /**
     * Returns the qtype information to attach to question element
     */
    protected function define_question_plugin_structure() {

        // Define the virtual plugin element with the condition to fulfill.
        $plugin = $this->get_plugin_element(null, '../../qtype', 'lcspeech');

        // Create one standard named plugin element (the visible container).
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        // Connect the visible container ASAP.
        $plugin->add_child($pluginwrapper);

        // Now create the qtype own structures.
        $lcspeech = new backup_nested_element('lcspeech', array('id'), array(
            'mediatype', 'speechphrase', 'timelimitinseconds', 'accent'));

        // Now the own qtype tree.
        $pluginwrapper->add_child($lcspeech);

        // Set source to populate the data.
        $lcspeech->set_source_table('qtype_lcspeech_options',
            array('questionid' => backup::VAR_PARENTID));

        // Don't need to annotate ids nor files.

        return $plugin;
    }
}
