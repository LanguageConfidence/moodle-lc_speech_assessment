<?php

/**
 * Admin settings for the Speech Assessment question type.
 *
 * @package   qtype_lcspeech
 * @copyright 2023 Speech Assessment
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    global $CFG;

    // Default settings for audio.
    $settings->add(new admin_setting_heading(
        'audiooptionsheading',
        get_string('optionsforaudio', 'qtype_lcspeech'),
        ''
    ));

    // Recording time limit.
    $settings->add(new admin_setting_configduration(
        'qtype_lcspeech/timelimit',
        get_string('timelimit', 'qtype_lcspeech'),
        get_string('timelimit_desc', 'qtype_lcspeech'),
        60,
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

    //other settings
    $settings->add(new admin_setting_heading('speech_assessment_scripted_settings_heading', get_string('speech_assessment_scripted_settings_heading', 'qtype_lcspeech'), ''));
    $settings->add(new admin_setting_configtext('qtype_lcspeech/api_scripted_url', get_string('api_scripted_url', 'qtype_lcspeech'), '', ''));
    $settings->add(new admin_setting_configtext(
        'qtype_lcspeech/daysolderaudiofiles',
        get_string('daysolderaudiofiles', 'qtype_lcspeech'),
        get_string('daysolderaudiofiles_desc', 'qtype_lcspeech'),
        15,
        PARAM_INT,
        4
    ));

    $settings->add(new admin_setting_heading('speech_assessment_unscripted_settings_heading', get_string('speech_assessment_unscripted_settings_heading', 'qtype_lcspeech'), ''));
    $settings->add(new admin_setting_configtext('qtype_lcspeech/api_unscripted_url', get_string('api_unscripted_url', 'qtype_lcspeech'), '', ''));
}
