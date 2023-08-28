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
 * Language Confidence question type scheduled task information.
 *
 * @package   qtype_languageconfidence
 * @copyright 2021 Language Confidence
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'qtype_lcspeech\task\remove_old_files',
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ),
    array(
        'classname' => 'qtype_lcspeech\task\migrate_to_lcspeech',
        'blocking' => 0,
        'minute' => 0,
        'hour' => 0,
        'day' => 0,
        'month' => 0,
        'dayofweek' => '*'
    )
);
