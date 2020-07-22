<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../../../question/editlib.php');  
require_once(__DIR__ . '/generate_section_form.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/formslib.php');

// GET properties
$returnurl = optional_param('returnurl', 0, PARAM_LOCALURL);
$cmid = optional_param('cmid', 0, PARAM_INT);
$addbeforepage = optional_param('addbeforepage', 0, PARAM_INT);

$url = new moodle_url('/mod/quiz/generatesection/generatesection.php');
if ($returnurl !== 0) {
  $url->param('returnurl', $returnurl);
}
if ($cmid !== 0) {
  $url->param('cmid', $cmid);
}
if($addbeforepage !== 0) {
  $url->param('addbeforepage', $addbeforepage);
}
$PAGE->set_url($url);

// Validate cmid
if ($cmid) {
  list($module, $cm) = get_module_from_cmid($cmid);
  require_login($cm->course, false, $cm);
  $thiscontext = context_module::instance($cmid);
} else {
  // print_error('missingcourseorcmid', 'question');
}
$contexts = new question_edit_contexts($thiscontext);
$PAGE->set_pagelayout('admin');

// Check permissions
// If the user has permission to create a new question, they can generate a section
// Assessment engine has no assosciated capabilities
require_capability('moodle/question:add', $thiscontext);

// Set the form toup with some sort of renderer
$mform = NULL;

// Pass data to the form
$toform = new stdClass();
$toform->returnurl = $url;
$toform->addbeforepage = $addbeforepage;
if ($cm !== null) {
  $toform->cmid = $cm->id;
  $toform->courseid = $cm->course;
}

$mform = new generate_section_form('generatesection.php', $contexts);
$mform->set_data($toform);

// Process form data
if ($mform->is_cancelled()) {
  $returnurl = new moodle_url($returnurl);
  redirect($returnurl);
}
else if($fromform = $mform->get_data()) {
  $returnurl = new moodle_url('/mod/quiz/edit.php');
  // Return data from form to quiz for processing
  $returnurl->param('sesskey', sesskey());
  $returnurl->param('cmid', $cmid);

  // Required parameters
  $returnurl->param('addqsection', floor($fromform->numberofquestions));
  $returnurl->param('addbeforepage', $addbeforepage);

  // Optional parameters
  if(isset($fromform->difficulty)) { $returnurl->param('difficulty', $fromform->difficulty); }
  if(isset($fromform->role)) { $returnurl->param('role', $fromform->role); }
  if(isset($fromform->lifecycle)) { $returnurl->param('lifecycle', $fromform->lifecycle); }
  if(isset($fromform->topic)) { $returnurl->param('topic', $fromform->topic); }
  if(isset($fromform->timelimit)) { $returnurl->param('timelimit', $fromform->timelimit); }
  redirect($returnurl);
}

$PAGE->set_title('Section generation');
$PAGE->set_heading($COURSE->fullname);
$PAGE->navbar->add('Add generated section');

// Display a heading, question editing form and possibly some extra content needed for
// for this question type.
echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
