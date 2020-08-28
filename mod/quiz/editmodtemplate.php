<?php

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/assessmentengine/modulelib.php');

$slotid = required_param('slotid', PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

// Get the quiz slot.
$slot = $DB->get_record('quiz_slots', array('id' => $slotid));
if (!$slot) {
    print_error('invalidslot', 'mod_quiz');
}
if (!$quiz = $DB->get_record('quiz', array('id' => $slot->quizid))) {
    print_error('invalidquizid', 'quiz');
}

$cm = get_coursemodule_from_instance('quiz', $slot->quizid, $quiz->course);

require_login($cm->course, false, $cm);

if ($returnurl) {
    $returnurl = new moodle_url($returnurl);
} else {
    $returnurl = new moodle_url('/mod/quiz/edit.php', array('cmid' => $cm->id));
}

$url = new moodle_url('/mod/quiz/editrandom.php', array('slotid' => $slotid));
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

if (!$question = $DB->get_record('question', array('id' => $slot->questionid))) {
    print_error('questiondoesnotexist', 'question', $returnurl);
}

// Validate the question category.
if (!$category = $DB->get_record('question_categories', array('id' => $question->category))) {
    print_error('categorydoesnotexist', 'question', $returnurl);
}

// Check permissions.
question_require_capability_on($question, 'edit');

$thiscontext = context_module::instance($cm->id);
$contexts = new question_edit_contexts($thiscontext);

// Create the question editing form.
$mform = new mod_quiz\form\modtemplate_form(new moodle_url('/mod/quiz/editmodtemplate.php'),
        array('contexts' => $contexts));

// Send the question object and a few more parameters to the form.
$toform = fullclone($question);
$toform->slotid = $slotid;
$toform->returnurl = $returnurl;
$toform->topic = $question->topic;

$rolename = '';
$diffname = '';
if(strpos($question->difficulty, ':') !== false) {
    $diffpair = explode(':', $question->difficulty);
    $diffname = $diffpair[1];
    $rolename = $diffpair[0];
}
else {
    $diffname = $question->difficulty;
}

if($rolename) {
    $allroles = $DB->get_records('question_roles');
    $count = 1;
    foreach($allroles as $r) {
        if($r->name == $rolename) {
            $toform->role = $count;
        }
        $count ++;
    }
}
$diff = $DB->get_record('question_difficulties', array('name' => $diffname));
$toform->difficulty = $diff->listindex;

if ($cm !== null) {
    $toform->cmid = $cm->id;
    $toform->courseid = $cm->course;
} else {
    $toform->courseid = $COURSE->id;
}

$toform->slotid = $slotid;

$mform->set_data($toform);

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($fromform = $mform->get_data()) {

    $oldquestion = fullclone($question);

    $diffname = '';
    if($fromform->difficulty) {
        $diff = $DB->get_record('question_difficulties', array('listindex' => $fromform->difficulty));
        $diffname = $diff->name;
    }

    $question->topic = $fromform->topic;
    if($fromform->role) {
        $rolekeys = array_keys($allroles);
        $rolename = $allroles[$rolekeys[$fromform->role - 1]]->name;
        $question->difficulty = $rolename . ':' . $diffname;
    } else {
        $question->difficulty = $diffname;
    }

    $question->name = '';
    if($diffname) {
        $question->name .= $diffname . ' ';
    }
    if ($question->topic) {
        $question->name .= $fromform->topic . ' ';
    }
    if($question->name == '') {
        $question->name .= 'Question template';
    }
    else {
        $question->name .= 'question template';
    }
    if ($rolename) {
        if ($rolename[sizeof($rolename) - 1] == 's') {
            $question->name .= ' for ' . $rolename;
        } else {
            $question->name .= ' for ' . $rolename . 's';
        }
    }

    $DB->update_record('question', $question);
    if(validateTemplates($quiz) !== true) {
        $DB->update_record('question', $oldquestion);
    }

    $returnurl->param('lastchanged', $question->id);
    // redirect($returnurl);
}

$qtypeobj = question_bank::get_qtype('modtemplate');
$streditingquestion = $qtypeobj->get_heading();
$PAGE->set_title($streditingquestion);
$PAGE->set_heading($COURSE->fullname);
$PAGE->navbar->add($streditingquestion);

// Display a heading, question editing form and possibly some extra content needed for
// for this question type.
echo $OUTPUT->header();
$heading = get_string('randomediting', 'mod_quiz');
echo $OUTPUT->heading_with_help($heading, 'randomquestion', 'mod_quiz');

$mform->display();

echo $OUTPUT->footer();
