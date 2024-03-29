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
 * Admin settings for the Speech Assessment question type.
 *
 * @package   qtype_lcspeech
 * @copyright 2023 Speech Assessment
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/lcspeech/lcspeech_admin_setting_configduration.php');
require_once($CFG->dirroot . '/question/type/lcspeech/lcspeech_admin_setting_configtext.php');

if ($ADMIN->fulltree) {
    global $CFG;

    // Default settings for audio.
    $settings->add(new admin_setting_heading(
        'audiooptionsheading',
        get_string('optionsforaudio', 'qtype_lcspeech'),
        ''
    ));

    // Recording time limit.
    $settings->add(new lcspeech_admin_setting_configduration(
        'qtype_lcspeech/timelimit',
        get_string('timelimit', 'qtype_lcspeech'),
        get_string('timelimit_desc', 'qtype_lcspeech'),
        ['units' => [1], 'optional' => false],
        60
    ));

    // Audio bitrate.
    $settings->add(new admin_setting_configtext(
        'qtype_lcspeech/audiobitrate',
        get_string('audiobitrate', 'qtype_lcspeech'),
        get_string('audiobitrate_desc', 'qtype_lcspeech'),
        128000,
        PARAM_INT,
        8
    ));

    // Other settings.
    $settings->add(new admin_setting_heading('speech_assessment_scripted_settings_heading', get_string('speech_assessment_scripted_settings_heading', 'qtype_lcspeech'), ''));
    $settings->add(new lcspeech_admin_setting_configtext('qtype_lcspeech/api_scripted_url', get_string('api_scripted_url', 'qtype_lcspeech'), '', ''));


    // Unscripted settings.
    $settings->add(new admin_setting_heading('speech_assessment_unscripted_settings_heading', get_string('speech_assessment_unscripted_settings_heading', 'qtype_lcspeech'), ''));
    $settings->add(new lcspeech_admin_setting_configtext('qtype_lcspeech/api_unscripted_url', get_string('api_unscripted_url', 'qtype_lcspeech'), '', ''));

    // Pronunciation settings.
    $settings->add(new admin_setting_heading('speech_assessment_pronunciation_settings_heading'
            , get_string('speech_assessment_pronunciation_settings_heading', 'qtype_lcspeech'), ''));
    $settings->add(new lcspeech_admin_setting_configtext(
        'qtype_lcspeech/api_pronunciation_url',
        get_string('api_pronunciation_url', 'qtype_lcspeech'),
        '',
        ''
    ));

    // Other settings.
    $settings->add(new admin_setting_heading('otheroptionsetting', get_string('otheroptionsetting', 'qtype_lcspeech'), ''));
    $settings->add(new admin_setting_configtext(
        'qtype_lcspeech/daysolderaudiofiles',
        get_string('daysolderaudiofiles', 'qtype_lcspeech'),
        get_string('daysolderaudiofiles_desc', 'qtype_lcspeech'),
        15,
        PARAM_INT,
        4
    ));

    $settings->add(new admin_setting_configcheckbox(
        'qtype_lcspeech/noexpirationaudio',
        get_string('noexpirationaudio', 'qtype_lcspeech'),
        get_string('noexpirationaudio_desc', 'qtype_lcspeech'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'qtype_lcspeech/enablelcbetafeatures',
        get_string('enablelcbetafeatures', 'qtype_lcspeech'),
        get_string('enablelcbetafeatures_desc', 'qtype_lcspeech'),
        0
    ));
    // Scoring option settings (IELTS, PTE, CEFR, LC).
    $settings->add(new admin_setting_heading('scoringoptionsetting', get_string('scoringoptionsetting', 'qtype_lcspeech'), ''));
    $settings->add(new admin_setting_configselect(
        'qtype_lcspeech/scoringoptionsetting',
        get_string('scoringoption', 'qtype_lcspeech'),
        get_string('scoringoption_desc', 'qtype_lcspeech'),
        'DEFAULT',
        array("DEFAULT" => "DEFAULT", "IELTS" => "IELTS", "PTE" => "PTE", "CEFR" => "CEFR")
    ));
}
