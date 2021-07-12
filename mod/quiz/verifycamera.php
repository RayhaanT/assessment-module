<?php

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/course/format/lib.php');

$id = required_param('cmid', PARAM_INT); // Course module id
$forcenew = optional_param('forcenew', false, PARAM_BOOL); // Used to force a new preview
$page = optional_param('page', -1, PARAM_INT); // Page to jump to in the attempt.

global $USER, $DB;

require_login();
$PAGE->set_url('/mod/quiz/verifycamera.php', array('id' => $id));
$PAGE->set_pagelayout('incourse');
$quiz = $DB->get_record('quiz', array('id' => $id));
$PAGE->set_title(get_string('enteringquizx', 'quiz', format_string($quiz->name)));
$course = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
$PAGE->set_heading($course->fullname);

$sesskey = $USER->sesskey;

$successURL = new moodle_url('/mod/quiz/startattempt.php');
$failureURL = new moodle_url('/mod/quiz/view.php', array('id' => $id));
$PAGE->requires->js_init_call('M.mod_quiz.get_camera_access', array($id, $page, $forcenew, $successURL->__toString(), $failureURL->__toString(), $sesskey), false, quiz_get_js_module());

echo $OUTPUT->header();

$out1 = get_string('accesscamera1', 'quiz');
$out2 = get_string('accesscamera2', 'quiz');
$out3 = get_string('accesscamera3', 'quiz');
$b = "<br>";
echo "<h1>$out1</h1>$b";
echo "<h3>$out2</h3> <h3>$out3</h3>$b";

echo $OUTPUT->footer();
