# Speech Assessment - Moodle Question Type Plugin

## Installation

1. Ensure you have Moodle 3.9 (2020061500) or newer installed
2. On your Moodle server copy this folder to `/path/to/moodle/question/type/lcspeech/`
3. Sign up to our Blobr developer portal [here](https://msexjbfqpxdz5jku.developer.blobr.app) and get your API key + API URL
3. Edit your Moodle `config.php` file and add the following config variable:
    - `$CFG->lcspeech_apikey`, using you Blobr API key
4. Visit your Moodle admin page
5. Run the database upgrade to install the plugin
6. Set your API url (given in the Blobr developer portal) in the plugin settings for Speech Assessment. The URL should end in `/pronunciation` without a trailing slash

## Usage

1. Create a new Quiz activity or edit an existing Quiz activity's settings
2. Select `Immediate Feedback` for question behaviour so that students immediately see the score from the Speech Assessment API before submitting their response
3. Add a new question
4. Select the `Speech Assessment` question type
5. Enter the desired English phrase in the `Speech phrase` text box
6. Fill out the `Question` text box with either text or an audio recording that the student should say out loud
7. You can set the desired `accent` to score pronunciation against. The options are US and UK, the default is US.
8. Save the question
