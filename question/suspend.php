<?php

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/suspend_form.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/question/editlib.php');

question_edit_setup('questions', '/question/edit.php');

// GET properties
$courseid = optional_param('courseid', 0, PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);

$url = new moodle_url('/question/suspend.php');
$url->param('courseid', $courseid);
$url->param('id', $id);
$PAGE->set_url($url);

// Pass data to the form
$toform = new stdClass();
$toform->courseid = $courseid;
$toform->id = $id;
$toform->returnurl = $url;
$mform = new suspend_form('suspend.php');
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

    $question = $DB->get_record('question', array('id' => $id));
    $question->suspensionend = $fromform->suspensionenddate;
    $DB->update_record('question', $question);

    redirect($returnurl);
}

$PAGE->set_title('Suspend a question');
$PAGE->set_heading($COURSE->fullname);
$PAGE->navbar->add('Suspend a question');

// Display a heading, question editing form and possibly some extra content needed for
// for this question type.
echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
