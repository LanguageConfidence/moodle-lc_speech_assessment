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
            'mediatype', 'speechphrase', 'timelimitinseconds', 'accent', 'speechtype'
        ));

        // Now the own qtype tree.
        $pluginwrapper->add_child($lcspeech);

        // Set source to populate the data.
        $lcspeech->set_source_table(
            'qtype_lcspeech_options',
            array('questionid' => backup::VAR_PARENTID)
        );

        // Don't need to annotate ids nor files.

        return $plugin;
    }
}
