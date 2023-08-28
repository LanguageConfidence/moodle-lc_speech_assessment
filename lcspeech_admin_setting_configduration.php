<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Question type class for the Speech Assessment question type.
 *
 * @package   qtype_lcspeech
 * @copyright 2023 Speech Assessment
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The Speech Assessment question type question type.
 *
 * @copyright 2023 Speech Assessment
 */
class lcspeech_admin_setting_configduration extends admin_setting_configduration {
    public function write_setting($data) {
        $totalsec = $data["v"] * $data["u"];
        if ($totalsec > 60) {
            return "Maximum recording duration cannot be greater than 60 secs";
        }

        return parent::write_setting($data);
    }
}
