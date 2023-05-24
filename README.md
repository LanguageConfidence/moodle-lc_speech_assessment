# Speech Assessment - Moodle Question Type Plugin

## Configuration

1. Sign up to our Blobr developer portal [here](https://developers.languageconfidence.ai) and get your API key + API URL
2. Edit your Moodle `config.php` file and add the following config variable:
    - `$CFG->lcspeech_apikey`, using you Blobr API key
## Installation

1. Ensure you have Moodle 3.9 (2020061500) or newer installed
2. Clone the plugin from the project directory:
https://github.com/LanguageConfidence/lc_saapi_moodle_qtype
3. On the plugin directory, remove the `.git/` folder, and compress the file as a `.zip` file.
4. Naviagte to Site Administrator -> Plugins -> Install plugins on Moodle site.
5. Upload or drag and drop the `.zip` file to the installation upload dropbox, and follow the steps for updating the database till finish.
6. After the installation above, the settings page will be shown. Input the required settings for the plugin and save changes.
7. Navigate to Site Administrator -> Plugins -> Plugins overview -> Additional plugins. You should see a new question type name LC Speech Assessment.

## Usage

1. Create a new Quiz activity or edit an existing Quiz activity's settings
2. Select `Immediate Feedback` for question behaviour so that students immediately see the score from the Speech Assessment API before submitting their response
3. Add a new question
4. Select the `LC Speech Assessment` question type
1. In the question setting page, type in necessary settings for the questions:
    - Question name: Name of the question.
    - Question text: The sentence/question that user will be asked to speak.
    - Default mark: Default maximum mark that user will get.
    - ID number: Question ID.
    - Speech assessment type: Type of speech assessment question. There are 3 options:
        - `Scripted`
        - `Unscripted`
        - `Pronunciation`
    - Maximum record duration: The maximum time that will be recorded for 1 user audio. It must not surpass the limit that is set in the plugin.
    - Accent: Choosing speaking accent `(US/UK)`
    - Scoring option: Choose the scoring option for the report (`IELTS`/`PTE`/`CEFR`/`LC Score`)
    - Scripted only:
        - Speech phrase: This will be used to calculate the accuracy of the user speech based in the scripted question. This need to be exactly the same as `Question text`.
    - Unscripted only:
        - Display content relevance: Enable this feature will check if user's answer is relevant to the question or not
            - Context question: The question asked
            - Context description: Short description of what context is expected
            - Valid answer: Short description of a valid answer
2. Save changes
