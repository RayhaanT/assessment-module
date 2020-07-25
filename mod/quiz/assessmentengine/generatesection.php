<?php

function addSelectCondition($currentCondition, $column, $value) {
    if (!$value) {
        return $currentCondition;
    }
    if ($currentCondition != '') {
        $currentCondition .= ' AND ';
    }
    $currentCondition .= $column . " = '" . $value . "'";
    return $currentCondition;
}

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../../../question/editlib.php');
require_once(__DIR__ . '/generate_section_form.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
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
if ($addbeforepage !== 0) {
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
} else if ($fromform = $mform->get_data()) {
	$returnurl = new moodle_url('/mod/quiz/edit.php');
	// Return data from form to quiz for processing
	$returnurl->param('sesskey', sesskey());
	$returnurl->param('cmid', $cmid);

	// Get quiz data objects
	list($thispageurl, $contexts, $cmid, $cm, $quiz, $pagevars) =
		question_edit_setup('editq', '/mod/quiz/assessmentengine/generatesection.php', true);
	$course = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
	$quizobj = new quiz($quiz, $cm, $course);
	$structure = $quizobj->get_structure();

	// Add a group of questions to the quiz based on provided parameters
	// $structure->check_can_be_edited();

	// $addbeforepage = optional_param('addbeforepage', 0, PARAM_INT);
	// $difficulty = optional_param('difficulty', 0, PARAM_INT);
	// $role = optional_param('role', 0, PARAM_INT);
	// $topic = optional_param('topic', '', PARAM_ALPHA);
	// $timelimit = optional_param('timelimit', 0, PARAM_INT);
	// $lifecycle = optional_param('lifecycle', 0, PARAM_INT);
	// $addqsection = optional_param('addqsection', 0, PARAM_INT);

	// Get slot data for repagination
	if ($addbeforepage == 0) {
		$allslots = $DB->get_records('quiz_slots', array('quizid' => $quiz->id), 'slot');
		if ($allslots) {
			$lastslot = end($allslots)->slot;
			$newpage = end($allslots)->page + 1;
		} else {
			$lastslot = 0;
			$newpage = 1;
		}
	} else {
		$pageslots = $DB->get_records('quiz_slots', array('quizid' => $quiz->id, 'page' => $addbeforepage), 'slot');
		$lastslot = end($pageslots)->slot;
		$newpage = $addbeforepage;
		$numberofslots = sizeof($pageslots);
	}

	// Add questions to quiz via database and form data
	$role = $fromform->role;
	$addqsection = 0;

	for($m = 0; $m < $fromform->nosubmods; $m++) {
		$topic = $fromform->topic[$m];
		if(isset($fromform->lifecycle)) {
			$lifecycle = $fromform->lifecycle[$m];
		} else {
			$lifecycle = 0;
		}
		$highq = $fromform->highq[$m];
		$midq = $fromform->mediumq[$m];
		$lowq = $fromform->lowq[$m];

		$condition = '';
		$condition = addSelectCondition($condition, 'role', $role);
		$condition = addSelectCondition($condition, 'topic', $topic);
		if ($condition != '' && $lifecycle) {
			$condition .= ' AND ';
		}
		if ($lifecycle) {
			$condition .= '(lifecycleexpiry > ' . time() . ' OR lifecycleexpiry = 0)';
		}
		if ($condition != '') {
			$condition .= ' AND ';
		}
		$condition .= 'parent = 0';

		if($highq) {
			$highcondition= addSelectCondition($condition, 'difficulty', 3);
			$qpool = $DB->get_records_select('question', $highcondition);
			$maxindex = sizeof($qpool) - 1;
			if ($maxindex + 1 < $highq) {
				$highq = $maxindex + 1;
			}
			$indexedpool = [];
			foreach ($qpool as $q) {
				array_push($indexedpool, $q);
			}
			for ($y = 0; $y < $highq; $y++) {
				$newq = rand(0, $maxindex);
				quiz_add_quiz_question($indexedpool[$newq]->id, $quiz, $addbeforepage);
				array_splice($indexedpool, $newq, 1);
				$maxindex--;
			}
		} else {$highq = 0;}

		if ($midq) {
			$midcondition = addSelectCondition($condition, 'difficulty', 2);
			$qpool = $DB->get_records_select('question', $midcondition);
			$maxindex = sizeof($qpool) - 1;
			if ($maxindex + 1 < $midq) {
				$midq = $maxindex + 1;
			}
			$indexedpool = [];
			foreach ($qpool as $q) {
				array_push($indexedpool, $q);
			}
			for ($y = 0; $y < $midq; $y++) {
				$newq = rand(0, $maxindex);
				quiz_add_quiz_question($indexedpool[$newq]->id, $quiz, $addbeforepage);
				array_splice($indexedpool, $newq, 1);
				$maxindex--;
			}
		} else {$midq = 0;}

		if ($lowq) {
			$lowcondition = addSelectCondition($condition, 'difficulty', 1);
			$qpool = $DB->get_records_select('question', $lowcondition);
			$maxindex = sizeof($qpool) - 1;
			if ($maxindex + 1 < $lowq) {
				$lowq = $maxindex + 1;
			}
			$indexedpool = [];
			foreach ($qpool as $q) {
				array_push($indexedpool, $q);
			}
			for ($y = 0; $y < $lowq; $y++) {
				$newq = rand(0, $maxindex);
				quiz_add_quiz_question($indexedpool[$newq]->id, $quiz, $addbeforepage);
				array_splice($indexedpool, $newq, 1);
				$maxindex--;
			}
		} else {
			$lowq = 0;
		}

		$addqsection += $lowq + $midq + $highq;
	}

	// Repaginate to place all questions from section on same page
	$repage = new \mod_quiz\repaginate($quiz->id);
	if ($addbeforepage == 0) {
		// Close page gaps between added questions
		for ($x = 0; $x < $addqsection - 1; $x++) {
			$repage->repaginate_slots($lastslot + $x + 2, 1);
		}
	} else {
		// Move all questions from later pages down a page
		$condition = "page > '" . $addbeforepage . "'" . " AND " . "quizid = '" . $quiz->id . "'";
		$allslots = $DB->get_records_select('quiz_slots', $condition);
		foreach ($allslots as $s) {
			$s->page = $s->page + 1;
			$DB->update_record('quiz_slots', $s);
		}

		// Move new slots out of the way to prevent id conflicts
		$allpageslots = $DB->get_records('quiz_slots', array('quizid' => $quiz->id, 'page' => $addbeforepage), 'slot');
		$placeholderslot = -1;
		$oldslotnumbers = [];
		foreach ($allpageslots as $s) {
			if ($s->slot > $lastslot) {
				array_push($oldslotnumbers, $s->slot);
				$s->slot = $placeholderslot;
				$placeholderslot--;
				$DB->update_record('quiz_slots', $s);
			}
		}

		$indexedpageslots = [];
		foreach ($pageslots as $s) {
			array_push($indexedpageslots, $s);
		}
		array_reverse($indexedpageslots);
		// Move all questions on current page to next page
		foreach ($pageslots as $s) {
			$s->page = $s->page + 1;
			$s->slot = $s->slot + $addqsection;
			$DB->update_record('quiz_slots', $s);
		}

		$allpageslots = $DB->get_records('quiz_slots', array('quizid' => $quiz->id, 'page' => $addbeforepage), 'slot');
		$indexedslots = [];
		foreach ($allpageslots as $x) {
			array_push($indexedslots, $x);
		}
		// Move new slots back into place
		for ($x = 0; $x < sizeof($indexedslots); $x++) {
			$indexedslots[$x]->slot = $oldslotnumbers[$x] - sizeof($pageslots);
			$DB->update_record('quiz_slots', $indexedslots[$x]);
		}
	}

	// Wrap up
	quiz_delete_previews($quiz);
	quiz_update_sumgrades($quiz);

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
