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
$string['speech_assessment_pronunciation_settings_heading'] = 'Speech assessment pronunciation Settings';
$string['company_id'] = 'Company Id';
$string['api_url'] = 'API URL';
$string['accent'] = 'Accent';
$string['taskremoveoldfiles'] = 'LC remove old recordings';
$string['daysolderaudiofiles'] = 'Max audio storage time (days)';
$string['daysolderaudiofiles_desc'] = 'Specify after how many days older audio files will be deleted to clear up space in the database';
$string['api_scripted_url'] = 'Scripted API URL';
$string['api_unscripted_url'] = 'Unscripted API URL';
$string['api_pronunciation_url'] = 'Pronunciation API URL';
$string['speechtype'] = 'Speech assessment type';
$string['otheroptionsetting'] = 'Other options';
$string['noexpirationaudio'] = 'No expiration';
$string['noexpirationaudio_desc'] = 'No expiration audio';
$string['has_content_relevance'] = 'Display content relevance';
$string['contextquestion'] = 'Context question';
$string['contextquestion_help'] = 'Context question.';
$string['contextdescription'] = 'Context description';
$string['contextdescription_help'] = 'Context description.';
$string['contextvalidanswerdescription'] = 'Context valid answer description';
$string['contextvalidanswerdescription_help'] = 'Context valid answer description.';
$string['scoringoptionsetting'] = 'Scoring option settings';
$string['scoringoption'] = 'Scoring option';
$string['scoringoption_desc'] = 'LC Scoring option';
$string['enablelcbetafeatures'] = 'Enable beta features';
$string['enablelcbetafeatures_desc'] = 'Enable beta features';

$string['lbl_overallscore'] = 'Overall score';
$string['lbl_speakingscore'] = 'Speaking score';
$string['lbl_usedfor'] = 'Used for';
$string['lbl_durationseconds'] = 'Duration seconds';
$string['lbl_connectives'] = 'Connectives';
$string['lbl_fillerword'] = 'Filler word';
$string['lbl_usedforfillerword'] = 'Used for: filler word';
$string['lbl_wordrepetition'] = 'Word repetition';
$string['lbl_usedforwordrepetition'] = 'Used for: Word repetition';
$string['lbl_speechpause'] = 'Speech pause';
$string['lbl_speechrate'] = 'Speech Rate';
$string['lbl_numberofpauses'] = 'Number of pauses';
$string['lbl_numberoffillerwords'] = 'Number of filler words';
$string['lbl_taggedtranscript'] = 'Tagged transcript';
$string['lbl_pauses'] = 'Pauses';
$string['lbl_contentrelevance'] = 'Content relevance';
$string['lbl_pronunciation'] = 'Pronunciation';
$string['lbl_grammarfeedback'] = 'Grammar feedback';
$string['lbl_fluencyfeedback'] = 'Fluency feedback';
$string['lbl_vocabularyfeedback'] = 'Vocabulary feedback';
$string['lbl_thepredictedtext'] = 'The predicted text';
$string['lbl_expectedtext'] = 'Expected text';
$string['lbl_relevancescore'] = 'Relevance score';
$string['lbl_thepredictedtext'] = 'The predicted text';
$string['lbl_unscriptedcontentrelevancescore'] = 'Unscripted content relevance score';
$string['lbl_validanswer'] = 'Valid answer';
$string['lbl_grammaticalcomplexity'] = 'Grammatical complexity';
$string['lbl_grammarmistakeccount'] = 'Grammar mistake count';
$string['lbl_vocabularycomplexity'] = 'Vocabulary complexity';
$string['lbl_fluency'] = 'Fluency';
$string['lbl_overall'] = 'Overall';
$string['lbl_mockpte'] = 'Mock PTE';
$string['lbl_mockcefr'] = 'Mock CEFR';
$string['lbl_mockielts'] = 'Mock IELTS';
$string['lbl_vocabulary'] = 'Vocabulary';
$string['lbl_grammar'] = 'Grammar';
$string['lbl_sampleanswer'] = 'Sample Answer';
$string['lbl_sampleanswer_desc'] = 'Note: You should try your best before clicking here';
$string['lbl_correctaudio'] = 'Correct Audio';
$string['lbl_feedback'] = 'Feedback';