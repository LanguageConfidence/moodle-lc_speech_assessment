<?php

/**
 * Strings for component 'qtype_lcspeech', language 'en', branch 'MOODLE_38_STABLE'
 *
 * @package   qtype_lcspeech
 * @copyright 2023 LC Speech Assessment
 */

$string['audio'] = 'Single audio';
$string['audiobitrate'] = 'Audio bitrate';
$string['audiobitrate_desc'] = 'Quality of audio recording (larger number means higher quality)';
$string['downloadrecording'] = 'Download {$a}';
$string['err_closesquarebrackets'] = 'Missing close square bracket(s). {$a->format}';
$string['err_opensquarebrackets'] = 'Missing open square bracket(s). {$a->format}';
$string['err_placeholderformat'] = 'The placeholder format is [[name:audio]], where name can only contain lower-case letters, numbers, hyphens and underscores, and must be no more than 32 characters long.';
$string['err_placeholderincorrectformat'] = 'The placeholders in the question text are not in the correct format. {$a->format}';
$string['err_placeholdermediatype'] = 'Widget type "{$a->text}" is not valid. {$a->format}';
$string['err_placeholdernotallowed'] = 'You cannot use placeholders with Recording type {$a}.';
$string['err_placeholdertitle'] = '"{$a->text}" is not a valid name. {$a->format}';
$string['err_placeholdertitlecase'] = '"{$a->text}" is not a valid name. Names may only contain lower-case letters. {$a->format}';
$string['err_placeholdertitleduplicate'] = '"{$a->text}" has been used more than once. Each name must be different.';
$string['err_placeholdertitlelength'] = '"{$a->text}" is longer than {$a->maxlength} characters. {$a->format}';
$string['err_timelimit'] = 'Maximum recording duration cannot be greater than {$a}.';
$string['err_timelimitpositive'] = 'Maximum recording duration must be greater than 0.';
$string['filex'] = 'File {$a}';
$string['gumabort'] = 'Something strange happened which prevented the webcam/microphone from being used';
$string['gumabort_title'] = 'Something happened';
$string['gumnotallowed'] = 'The user must allow the browser access to the webcam/microphone';
$string['gumnotallowed_title'] = 'Wrong permissions';
$string['gumnotfound'] = 'There is no input device connected or enabled';
$string['gumnotfound_title'] = 'Device missing';
$string['gumnotreadable'] = 'Something is preventing the browser from accessing the webcam/microphone';
$string['gumnotreadable_title'] = 'Hardware error';
$string['gumnotsupported'] = 'Your browser does not support recording over an insecure connection and must close the plugin';
$string['gumnotsupported_title'] = 'No support for insecure connection';
$string['gumoverconstrained'] = 'The current webcam/microphone can not produce a stream with the required constraints';
$string['gumoverconstrained_title'] = 'Problem with constraints';
$string['gumsecurity'] = 'Your browser does not support recording over an insecure connection and must close the plugin';
$string['gumsecurity_title'] = 'No support for insecure connection';
$string['gumtype'] = 'Tried to get stream from the webcam/microphone, but no constraints were specified';
$string['gumtype_title'] = 'No constraints specified';
$string['mediatype'] = 'Type of recording';
$string['insecurewarning'] = 'Your browser will not allow this plugin to work unless it is used over HTTPS.';
$string['insecurewarningtitle'] = 'Insecure connection';
$string['nearingmaxsize'] = 'You have attained the maximum size limit for file uploads';
$string['nearingmaxsize_title'] = 'Recording stopped';
$string['norecording'] = 'No recording';
$string['nowebrtc'] = 'Your browser offers limited or no support for WebRTC technologies yet, and cannot be used with this type of question. Please switch or upgrade your browser.';
$string['nowebrtctitle'] = 'WebRTC not supported';
$string['optionsforaudio'] = 'Audio options';
$string['optionsforreport'] = 'Report options';
$string['pleaserecordsomethingineachpart'] = 'Please complete your answer.';
$string['pluginname'] = 'LC Speech Assessment';
$string['pluginname_help'] = 'Students respond to the question text by recording audio directly into their browser. This is then graded using the Speech Assessment Web Service.';
$string['pluginname_link'] = 'question/type/lcspeech';
$string['pluginnameadding'] = 'Adding a LC Speech Assessment question';
$string['pluginnameediting'] = 'Editing a LC Speech Assessment question';
$string['pluginnamesummary'] = 'Students respond to the question text by recording audio directly into their browser. This is then graded using the Speech Assessment Web Service.';
$string['privacy:metadata'] = 'The LC Speech Assessment question type plugin does not store any personal data.';
$string['recordagain'] = 'Re-record';
$string['recordingfailed'] = 'Recording failed';
$string['recordinginprogress'] = 'Stop recording ({$a})';
$string['startrecording'] = 'Record your answer';
$string['timelimit'] = 'Maximum recording duration';
$string['timelimit_desc'] = 'Maximum time that a question author can set for the recording length.';
$string['timelimit_help'] = 'This is the longest duration of a recording that the student is allowed to make. If they reach this time, the recording will automatically stop. There is an upper limit to the value that can be set here. If you need a longer time, ask an administrator.';
$string['uploadaborted'] = 'Saving aborted';
$string['uploadcomplete'] = 'Recording uploaded';
$string['uploadfailed'] = 'Upload failed';
$string['uploadfailed404'] = 'Upload failed (file too big?)';
$string['uploadpreparing'] = 'Preparing upload ...';
$string['uploadprogress'] = 'Uploading ({$a})';
$string['speechphrase'] = 'Speech phrase';
$string['speechphrase_help'] = 'This is the phrase that you expect the student to say out loud and will be assessed against.';
$string['err_speechphraseempty'] = 'Speech phrase is required';
$string['note'] = "Click the 'Record your answer' button above to start recording, pause for a moment and then start speaking. Once you have finished your attempt, click the 'Submit and finish' button below.";
$string['other_settings_heading'] = 'Other Settings';
$string['speech_assessment_scripted_settings_heading'] = 'Speech assessment scripted Settings';
$string['speech_assessment_unscripted_settings_heading'] = 'Speech assessment unscripted Settings';
$string['company_id'] = 'Company Id';
$string['api_url'] = 'API URL';
$string['accent'] = 'Accent';
$string['taskremoveoldfiles'] = 'Remove Old recordings';
$string['daysolderaudiofiles'] = 'Number Of Days';
$string['daysolderaudiofiles_desc'] = 'Specify how many days older files need to be deleted';
$string['api_scripted_url'] = 'API URL';
$string['api_unscripted_url'] = 'API URL';
$string['speechtype'] = 'Speech assessment type';
