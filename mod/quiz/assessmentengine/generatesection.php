<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../../../question/editlib.php');  
require_once(__DIR__ . '/generate_section_form.php');

// GET properties
$returnurl = optional_param('returnurl', 0, PARAM_LOCALURL);
$cmid = optional_param('cmid', 0, PARAM_INT);
$addafterpage = optional_param('addafterpage', 0, PARAM_INT);
$categoryid = optional_param('category', 0, PARAM_INT);

// Validate cmid
if ($cmid) {
  list($module, $cm) = get_module_from_cmid($cmid);
  require_login($cm->course, false, $cm);
  $thiscontext = context_module::instance($cmid);
} else {
  print_error('missingcourseorcmid', 'question');
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
if ($cm !== null) {
  $toform->cmid = $cm->id;
  $toform->courseid = $cm->course;
} else {
  throw coding_exception('No course module id provided');
}

$mform = new generate_section_form('generatesection.php', $contexts);
$mform->set_data($toform);

// Process form data
if ($mform->is_cancelled()) {
  if ($inpopup) {
    close_window();
  } else {
    redirect($returnurl);
  }
}
else if($fromform = $mform->get_data()) {
  // Return data from form to quiz for processing
  if ($appendqnumstring) {
    $returnurl->param('sesskey', sesskey());
    $returnurl->param('cmid', $cmid);

    // Required parameters
    $returnurl->param('addqsection', floor($fromform->numberofquestions));

    // Optional parameters
    if(isset($fromform->difficulty)) { $returnurl->param('difficulty', $fromform->difficulty); }
    if(isset($fromform->role)) { $returnurl->param('role', $fromform->role); }
    if(isset($fromform->lifecycle)) { $returnurl->param('lifecycle', $fromform->lifecycle); }
    if(isset($fromform->topic)) { $returnurl->param('topic', $fromform->topic); }
    if(isset($fromform->timelimit)) { $returnurl->param('timelimit', $fromform->timelimit); }
  }
  redirect($returnurl);
}
else {
  // Data failed validation, redisplay form
  $mform->set_data($toform);
  $mform->display();
}

$PAGE->set_title('Add generated section');
$PAGE->set_heading($COURSE->fullname);
$PAGE->navbar->add('Add generated section');

// Display a heading, question editing form and possibly some extra content needed for
// for this question type.
echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
