<?php

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/techversion_form.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/question/editlib.php');

question_edit_setup('questions', '/question/edit.php');

// GET properties
$courseid = optional_param('courseid', 0, PARAM_INT);

$url = new moodle_url('/question/techversion.php');
$url->param('courseid', $courseid);
$PAGE->set_url($url);

// Pass data to the form
$toform = new stdClass();
$toform->returnurl = $url;
$toform->courseid = $courseid;

$sql = "SELECT DISTINCT topic FROM " .  $CFG->dbname . "." . $CFG->prefix . "question ORDER BY topic";
$alltopics = $DB->get_records_sql($sql);
$allDifficulties = $DB->get_records('question_difficulties', null, 'id');
$difficultyKeys = array_keys($allDifficulties);

$count = 0;
foreach($alltopics as $t) {
    if(!$t->topic) {
        continue;
    }
    $fieldname = 'version' . $count;
    $difficultyFieldName = 'difficulty' . $count;
    if($thisversion = $DB->get_record('question_versions', array('topic' => $t->topic))) {
        // Equivalent to float cast, removes trailing zeroes
        $toform->$fieldname = $thisversion->version + 0;
    } else {
        $toform->$fieldname = 0;
        $newversion = new stdClass();
        $newversion->topic = $t->topic;
        $newversion->version = 0;
        $DB->insert_record('question_versions', $newversion);
        $thisversion = $newversion;
    }

    if(isset($thisversion->difficulty)) {
        $diffName = $thisversion->difficulty;
        $diffCount = 0;
        foreach($allDifficulties as $d) {
            if($d->name == $diffName) {
                $toform->$difficultyFieldName = $diffCount + 1;
            }
            $diffCount++;
        }
    }

    $count++;
}

$mform = new techversion_form('techversion.php');
$mform->set_data($toform);

// Process form data
if ($mform->is_cancelled()) {
    $returnurl = new moodle_url('/question/edit.php');
    // Return data from form to quiz for processing
    $returnurl->param('sesskey', sesskey());
    $returnurl->param('courseid', $courseid);
    redirect($returnurl);
} else if ($fromform = $mform->get_data()) {
    $returnurl = new moodle_url('/question/edit.php');
    // Return data from form to quiz for processing
    $returnurl->param('sesskey', sesskey());
    $returnurl->param('courseid', $courseid);

    $count = 0;
    foreach($alltopics as $t) {
        if (!$t->topic) {
            continue;
        }
        if($versionentry = $DB->get_record('question_versions', array('topic' => $t->topic))) {
            $fieldname = 'version' . $count;
            $difficultyFieldName = 'difficulty' . $count;
            $versionentry->version = $fromform->$fieldname;
            if($fromform->$difficultyFieldName) {
                $versionentry->difficulty = $allDifficulties[$difficultyKeys[$fromform->$difficultyFieldName - 1]]->name;
            }
            $DB->update_record('question_versions', $versionentry);
        }
        $count++;
    }

    redirect($returnurl);
}

$PAGE->requires->css('/question/groupstyles.css');

$PAGE->set_title('Version settings');
$PAGE->set_heading($COURSE->fullname);
$PAGE->navbar->add('Configure version numbers');

// Display a heading, question editing form and possibly some extra content needed for
// for this question type.
echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
