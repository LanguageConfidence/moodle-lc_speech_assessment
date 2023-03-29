<?php

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
function qtype_lcspeech_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = [])
{
    global $CFG;
    require_once($CFG->libdir . '/questionlib.php');
    question_pluginfile($course, $context, 'qtype_lcspeech', $filearea, $args, $forcedownload, $options);
}


function qtype_lcspeech_ensure_api_config_is_set()
{
    global $CFG;

    if (empty($CFG->lcspeech_apikey)) {
        throw new \Exception('qtype_lcspeech plugin requires $CFG->lcspeech_apikey to be set in config.php');
    }
}
