<?php

/**
 * Question type class for the Speech Assessment question type.
 *
 * @package   qtype_lcspeech
 * @copyright 2023 Speech Assessment
 */

defined('MOODLE_INTERNAL') || die();

// require_once($CFG->libdir . '/questionlib.php');
// require_once($CFG->dirroot . '/question/engine/lib.php');
// require_once($CFG->dirroot . '/question/type/lcspeech/question.php');


/**
 * The Speech Assessment question type question type.
 *
 * @copyright 2023 Speech Assessment
 */
class lcspeech_admin_setting_configtext extends admin_setting_configtext
{
    public function write_setting($data)
    {
        $newdata = trim($data);
        $lastchar = substr($newdata, -1);
        if ($lastchar == "/") {
            $newdata = substr($newdata, 0, -1);
        }

        return parent::write_setting($newdata);
    }
}
