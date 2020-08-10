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

$alldifficulties = $DB->get_records('question_difficulties', null, 'listindex');
$count = 0;
$concatenatednames = '';
foreach($alldifficulties as $diff) {
    if($concatenatednames != '') {
        $concatenatednames .= ', ';
    }
    $concatenatednames .= $diff->name;

    $toform->difficultyname[$count] = $diff->name;
    $toform->rate1[$count] = $diff->range1;
    $toform->rate2[$count] = $diff->range2;
    $toform->rate3[$count] = $diff->range3;
    $toform->rate4[$count] = $diff->range4;

    $count++;
}
$toform->difficultylist = $concatenatednames;

$allbounds = $DB->get_records('question_retirement_ranges');
if(count($allbounds) < 3) {
    throw new moodle_exception('There are too few upper bounds for ranges in the database (should be 3).');
}
$toform->range1 = $allbounds[1]->upperbound;
$toform->range2 = $allbounds[2]->upperbound;
$toform->range3 = $allbounds[3]->upperbound;

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

    $difficultylevelsraw = explode(',', $fromform->difficultylist);
    $difficultylevels = [];
    foreach($difficultylevelsraw as $diff) {
        array_push($difficultylevels, trim($diff));
    }
    $alldiffs = $DB->get_records('question_difficulties', null, 'listindex');
    $originaldiffs = $DB->get_records('question_difficulties', null, 'listindex');
    $newdiffs = [];
    for($x = 0; $x < count($difficultylevels); $x++) {
        $exists = false;
        foreach($alldiffs as $d) {
            if($d->name == $difficultylevels[$x]) {
                $d->listindex = $x + 1;
                $d->updated = true;
                array_push($newdiffs, $d);
                $exists = true;
                break;
            }
        }
        if(!$exists) {
            $newdiff = new stdClass();
            $newdiff->name = $difficultylevels[$x];
            $newdiff->listindex = $x + 1;
            $newdiff->updated = false;
            array_push($newdiffs, $newdiff);
        }
    }
    foreach($alldiffs as $d) {
        $removed = true;
        foreach($newdiffs as $l) {
            if($d->name == $l->name) {
                $removed = false;
                break;
            }
        }
        if($removed) {
            $DB->delete_records('question_difficulties', array('id' => $d->id));
        }
    }
    foreach($newdiffs as $nd) {
        if($nd->updated == true) {
            $DB->update_record('question_difficulties', $nd);
        }
        else {
            $DB->insert_record('question_difficulties', $nd);
        }
    }

    for($x = 0; $x < count($fromform->difficultyname); $x++) {
        foreach($originaldiffs as $d) {
            print_r($d);
            if($d->listindex == $x + 1) {
                $diff = clone($d);
                break;
            }
        }
        unset($diff->listindex);

        $diff->range1 = $fromform->rate1[$x];
        $diff->range2 = $fromform->rate2[$x];
        $diff->range3 = $fromform->rate3[$x];
        $diff->range4 = $fromform->rate4[$x];

        if ($fromform->difficultyname[$x]) {
            $diff->name = $fromform->difficultyname[$x];
        }
        $DB->update_record('question_difficulties', $diff);
    }

    $range = new stdClass();
    $range->upperbound = $fromform->range1; $range->id = 1;
    $DB->update_record('question_retirement_ranges', $range);
    $range->upperbound = $fromform->range2; $range->id = 2;
    $DB->update_record('question_retirement_ranges', $range);
    $range->upperbound = $fromform->range3; $range->id = 3;
    $DB->update_record('question_retirement_ranges', $range);

    // If we are saving and continuing to edit the question.
    if (!empty($fromform->updatebutton)) {
        redirect($url);
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
