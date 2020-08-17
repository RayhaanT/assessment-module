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

function filterDuplicates($questions, $existing) {
	foreach($existing as $e) {
		if (($key = array_search($e, $questions)) !== false) {
			unset($questions[$key]);
		}
	}
	return $questions;
}

function getAverageDifficulty($diffstring) {
	global $DB;
	$alldiffs = $DB->get_records('question_difficulties', null, 'listindex');
	if(strpos($diffstring, ':') !== false) {
		$diffpairs = explode(',', $diffstring);
		$diffs = array();
		foreach($diffpairs as $d) {
			array_push($diffs, explode(':', $d)[1]);
		}
		$diffnumbers = array();
		foreach($diffs as $d) {
			foreach($alldiffs as $a) {
				if($d == $a->name) {
					array_push($diffnumbers, $a->listindex);
					break;
				}
			}
		}
		$total = 0;
		foreach($diffnumbers as $n) {
			$total += $n;
		}
		$averageindex = ceil($total/count($diffnumbers));
		foreach($alldiffs as $d) {
			if($d->listindex == $averageindex) {
				return $d->name;
			}
		}
	}
	else {
		return $diffstring;
	}
	return null;
}

function filterAndEvaluateRetirement($questions) {
	global $DB;
	$filteredQuestions = array();
	$ranges = $DB->get_records('question_retirement_ranges', null, 'upperbound');

	foreach($questions as $q) {
		$flags = '00000';
		// If permanently removed for too many correct answers
		if ($q->retirementflags > 15) {
			continue;
		}

		// Question manually suspended
		if ($q->suspensionend > time()) {
			$flags[4] = '1';
		}

		// Question retired based on version
		if ($version = $DB->get_record('question_versions', array('topic' => $q->topic))) {
			if ($q->techversion != -1 && $q->techversion < $version->version) {
				$flags[3] = '1';
			}
		}

		// Question expired
		if($q->lifecycleexpiry > 0 && $q->lifecycleexpiry < time()) {
			$flags[2] = '1';
		}

		// Question retired/suspended because of too many correct answers
		if($q->attempts > 40) {
			$thisrange = 1;
			foreach($ranges as $r) {
				if($r->upperbound >= $q->attempts) {
					break;
				}
				$thisrange++;
			}
			
			if($q->overalldifficulty > 0) {
				$thisdiff = $DB->get_record('question_difficulties', array('listindex' => $q->overalldifficulty));
			}
			else {
				$diffname = getAverageDifficulty($q->difficulty);
				$thisdiff = $DB->get_record('question_difficulties', array('name' => $diffname));
			}

			$rangename = 'range' . $thisrange;
			if($q->attempts != 0) {
				if($thisdiff->$rangename / 100 < $q->attemptaccuracy / $q->attempts) {
					$flags[1] = '1';

					// If its already been suspended
					if($q->retirementflags > 7) {
						$flags[0] = '1';
					}
					else {
						// Suspend for a week
						$q->suspensionend = time() + 604800;
					}
				}
			}
		}

		$flagsdec = bindec($flags);
		// Update permanent flags in db
		if ($flagsdec != $q->retirementflags) {
			$baseflags = 0;
			if($q->retirementflags > 15 || $flagsdec > 15) {
				$baseflags = 16;
			}
			else if($q->retirementflags > 7 || $flagsdec > 7) {
				$baseflags = 8;
			}
			$variableflags = bindec(substr($flags, 2));
			$q->retirementflags = $baseflags + $variableflags;
		}
		$DB->update_record('question', $q);
		// If the current flags are all down, let it through
		if ($flagsdec == 0) {
			array_push($filteredQuestions, $q);
		}
	}

	return $filteredQuestions;
}

function getQuestionsInQuiz($quiz) {
	global $DB;

	$questionslotsinquiz = $DB->get_records('quiz_slots', array('quizid' => $quiz->id));
	$selectquestions = '';
	foreach ($questionslotsinquiz as $q) {
		if ($selectquestions != '') {
			$selectquestions .= ' OR ';
		}
		$selectquestions .= "id = '" . $q->questionid . "'";
	}
	if ($selectquestions != '') {
		return $DB->get_records_select('question', $selectquestions);
	}
	return array();
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
$PAGE->requires->css('/question/groupstyles.css');

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
	$returnurl = new moodle_url('/mod/quiz/edit.php');
	// Return data from form to quiz for processing
	$returnurl->param('sesskey', sesskey());
	$returnurl->param('cmid', $cmid);
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
	$structure->check_can_be_edited();

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
	$roleindex = $fromform->role;
	if($roleindex != 0) {
		$allroles = $DB->get_records('question_roles');
		$rolekeys = array_keys($allroles);
		$role = $allroles[$rolekeys[$roleindex - 1]]->name;
	}
	else {
		$role = '';
	}
	$timelimit = $fromform->timelimit;
	$addqsection = 0;

	// Get current quiz questions to remove duplicates
	$questionsinquiz = getQuestionsInQuiz($quiz);

	for($m = 0; $m < $fromform->nosubmods; $m++) {
		$lowertopic = strtolower($fromform->topic[$m]);
		$topic = trim($lowertopic);
		$topic = $fromform->topic[$m];

		$condition = '';
		if($topic) {
			$condition = addSelectCondition($condition, 'topic', $topic);
		}
		if ($condition != '') {
			$condition .= ' AND ';
		}
		$condition .= 'parent = 0';

		$alldiffs = $DB->get_records('question_difficulties', null, 'listindex');
		$difficultyfields = array();
		foreach($alldiffs as $d) {
			$difficultyfields[$d->name] = str_replace(' ', '', $d->name) . 'qnum';
		}
		foreach($difficultyfields as $diffname => $field) {
			$questionsinquiz = getQuestionsInQuiz($quiz);

			$qnum = $fromform->$field[$m];
			if($role) {
				$rolecondition = $condition . " AND difficulty REGEXP '" . $role . ':' . $diffname . "'";
			} else {
				$rolecondition = $condition . " AND difficulty REGEXP '" . $diffname . "'";
			}
			$rawcondition = addSelectCondition($condition, 'difficulty', $diffname);
			$qpool = $DB->get_records_select('question', $rolecondition);
			$qpool = array_merge($qpool, $DB->get_records_select('question', $rawcondition));
			$qpool = filterDuplicates($qpool, $questionsinquiz);
			$qpool = filterAndEvaluateRetirement($qpool);
			$maxindex = sizeof($qpool) - 1;
			if($maxindex + 1 < $qnum) {
				$qnum = $maxindex + 1;
			}
			$indexedpool = [];
			foreach ($qpool as $q) {
				array_push($indexedpool, $q);
			}
			for ($y = 0; $y < $qnum; $y++) {
				$newq = rand(0, $maxindex);
				quiz_add_quiz_question($indexedpool[$newq]->id, $quiz, $addbeforepage, null, true);
				array_splice($indexedpool, $newq, 1);
				$maxindex--;
			}
			$addqsection += $qnum;
		}
	}

	// Repaginate to place all questions from section on same page
	$repage = new \mod_quiz\repaginate($quiz->id);
	$firstnewslot = 1;
	if ($addbeforepage == 0) {
		$firstnewslot = $lastslot + 1;
		// Close page gaps between added questions
		for ($x = 0; $x < $addqsection - 1; $x++) {
			$repage->repaginate_slots($lastslot + $x + 2, 1);
		}
	} 
	else {
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
		$reversedpageslots = [];
		for($p = sizeof($indexedpageslots) - 1; $p > -1; $p--) {
			array_push($reversedpageslots, $indexedpageslots[$p]);
		}
		// Move all questions on current page to next page
		foreach ($reversedpageslots as $s) {
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
			if($x == 0) {
				$firstnewslot = $oldslotnumbers[$x] - sizeof($pageslots);
			}
			$indexedslots[$x]->slot = $oldslotnumbers[$x] - sizeof($pageslots);
			$DB->update_record('quiz_slots', $indexedslots[$x]);
		}
	}


	if($addqsection > 0) {
		// Create section for module
		// If a section already exists where the module was added (otherwise results in database conflict)
		if ($modulesection = $DB->get_record('quiz_sections', array('quizid' => $quiz->id, 'firstslot' => $firstnewslot))) {
			// Only triggers if there are no other questions in the quiz. Edits the default section
			if($addbeforepage == 0) {
				$modulesection->heading = $fromform->name;
				$modulesection->module = 1;
				$modulesection->timelimit = $timelimit;
				$DB->update_record('quiz_sections', $modulesection);
			}
			// Move existing section to match with repagination and create a new heading for the module
			else {
				$reversed = array_reverse($pageslots);
				$firstoldslot = array_pop($reversed);
				$modulesection->firstslot = $firstoldslot->slot;
				$DB->update_record('quiz_sections', $modulesection);

				$structure->add_section_heading($addbeforepage, $fromform->name);
				$newmodsection = $DB->get_record('quiz_sections', array('quizid' => $quiz->id, 'firstslot' => $firstnewslot));
				$newmodsection->module = 1;
				$newmodsection->timelimit = $timelimit;
				$DB->update_record('quiz_sections', $newmodsection);
			}
		} 
		// If a new section needs to be created
		else {
			if($addbeforepage == 0) {
				$sectionpage = end($allslots)->page + 1;
			}
			else {
				$sectionpage = $addbeforepage;
			}
			$structure->add_section_heading($sectionpage, $fromform->name);
			$modulesection = $DB->get_record('quiz_sections', array('quizid' => $quiz->id, 'firstslot' => $firstnewslot));
			$modulesection->module = 1;
			$modulesection->timelimit = $timelimit;
			$DB->update_record('quiz_sections', $modulesection);
		}
	}

	update_section_time_limits($quiz);

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
