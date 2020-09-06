<?php
require('../../config.php');
include_once($CFG->dirroot . '/course/lib.php');
include_once($CFG->libdir . '/coursecatlib.php');
require_once ($CFG->dirroot . '/completion/classes/progress.php');
global $CFG, $DB;


if(isset($_POST['test'])){
	if ($courses = enrol_get_my_courses()) {
		$i=0;
		foreach($courses as $course){
			$col1[$i]["name"]=$course->shortname;
				$courseone = get_course($course->id);
			   if (\core_completion\progress::get_course_progress_percentage($courseone)) {
				$comppc = \core_completion\progress::get_course_progress_percentage($courseone);
				$comppercent = number_format($comppc, 0);
				$hasprogress = true;
				}else {
					$comppercent = 10;
					$hasprogress = false;
				}
			$col1[$i]["compl"]=$comppercent+$i;
			$i++;
		}
	}
	echo json_encode($col1);
}

			