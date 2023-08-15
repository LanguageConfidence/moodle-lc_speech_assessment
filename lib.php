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
 * Standard Moodle hooks for the Speech Assessment question type.
 *
 * @package   qtype_lcspeech
 * @copyright 2023 LC Speech Assessment
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Checks file access for the LC Speech Assessment question type.
 *
 * @category files
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function qtype_lcspeech_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $CFG;
    require_once($CFG->libdir . '/questionlib.php');
    question_pluginfile($course, $context, 'qtype_lcspeech', $filearea, $args, $forcedownload, $options);
}


function qtype_lcspeech_ensure_api_config_is_set() {
    global $CFG;

    if (empty($CFG->lcspeech_apikey)) {
        throw new \Exception('qtype_lcspeech plugin requires $CFG->lcspeech_apikey to be set in config.php');
    }
}
