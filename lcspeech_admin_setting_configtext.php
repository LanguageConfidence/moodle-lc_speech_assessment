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
class lcspeech_admin_setting_configtext extends admin_setting_configtext {
    public function write_setting($data) {
        $newdata = trim($data);
        $lastchar = substr($newdata, -1);
        if ($lastchar == "/") {
            $newdata = substr($newdata, 0, -1);
        }

        return parent::write_setting($newdata);
    }
}
