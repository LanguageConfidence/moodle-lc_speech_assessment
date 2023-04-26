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
class lcspeech_admin_setting_configduration extends admin_setting_configduration
{
    public function validate($data)
    {
        return false;
        // your custom validation logic here
        var_dump($data);
        exit;
    }

    public function write_setting($data)
    {
        $total_sec = $data["v"] * $data["u"];
        if ($total_sec > 60) {
            return "Maximum recording duration cannot be greater than 60 secs";
        }

        return parent::write_setting($data);
    }
}
