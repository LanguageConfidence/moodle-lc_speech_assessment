<?php
/**
 * Privacy provider for the Speech Assessment question type.
 *
 * @package   qtype_lcspeech
 * @copyright 2023 Speech Assessment
 */

namespace qtype_lcspeech\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;


/**
 * Privacy provider for the Speech Assessment question type.
 */
class provider implements \core_privacy\local\metadata\null_provider {

    public static function get_reason() : string {
        return 'privacy:metadata';
    }
}
