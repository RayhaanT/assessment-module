<?php

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/modulesettings_form.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/question/editlib.php');

question_edit_setup('questions', '/question/edit.php');

// GET properties
$courseid = optional_param('courseid', 0, PARAM_INT);

$url = new moodle_url('/question/modulesettings.php');
$url->param('courseid', $courseid);
$PAGE->set_url($url);

// Check permissions
// If the user has permission to create a new question, they can generate a section
// Assessment engine has no assosciated capabilities

// Set the form toup with some sort of renderer
$mform = NULL;

// Pass data to the form
$toform = new stdClass();
$toform->returnurl = $url;
$toform->courseid = $courseid;

$allroles = $DB->get_records('question_roles');
$count = 0;
foreach($allroles as $role) {
    $toform->rolename[$count] = $role->name;
    $count++;
}

$mform = new modulesettings_form('modulesettings.php');
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

    foreach($fromform->rolename as $rolename) {
        if(!$rolename) {
            continue;
        }
        $exists = false;
        foreach($allroles as $role) {
            if($role->name == $rolename) {
                $exists = true;
                break;
            }
        }
        if(!$exists) {
            $newrole = new stdClass();
            $newrole->name = $rolename;
            $DB->insert_record('question_roles', $newrole);
        }
    }

    redirect($returnurl);
}

$PAGE->set_title('Module settings');
$PAGE->set_heading($COURSE->fullname);
$PAGE->navbar->add('Configure module settings');

// Display a heading, question editing form and possibly some extra content needed for
// for this question type.
echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
