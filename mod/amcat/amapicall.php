<?php
global $CFG, $USER, $DB, $COURSE;
require('../../config.php');
require_once($CFG->dirroot.'/mod/amcat/lib.php');

$courseid      	= optional_param('courseid', 0, PARAM_INT); // Course Module ID
$login 			= optional_param('amresult', '', PARAM_RAW);
$activityid     = optional_param('activityid', 0, PARAM_INT); // Course Module ID

if($login == 'Start Test'){
	$curl_url = getBaseURL_myamcat();
	$new_date = date("Y-m-d H:m:s");

	if($activityid != 0){
		$sql = 'SELECT * FROM {course_modules} as cm INNER JOIN 
		{amcat} as a  ON cm.instance = a.id and cm.course = a.course 
		WHERE cm.id = ?';

		$amcat = $DB->get_record_sql($sql , array($activityid));

		$new_date = $amcat->startdatetime;
		$testid =  $amcat->testid;
	}
	
	$new_date = date("Y-m-d H:m:s", $new_date);		// 2020-05-17 14:05:0			
	
	$post_data = array();
	$post_data['testID'] = $testid;
	$post_data['autoLoginURL'] = '1';
	$post_data['startTestTime'] = $new_date;		// 2020-05-14 23:06:00	
	$post_data['hrsToLive'] = '72';
	
	$returURL = $CFG->wwwroot.'/course/view.php?id='.$courseid;
	$post_data['candidateData'] =  array(
		'emailID' => $USER->email,
		'firstName' => $USER->firstname,
		'lastName' => $USER->lastname,
		'testEndReturnURL' => $returURL,
		'customInfo1' => $USER->id,
		'customInfo2' => $courseid,
		'customInfo3' => $USER->id,
		'customInfo4' => $activityid,
	);
	$post_data['actionFlag'] = array(
		'sendEmail' => 'Y'
	);
	$data = testschuldeAPI($curl_url.'api/schedule/schedule', json_encode($post_data));
	if($data['status'] != 'error'){
		redirect($data['data']['autoLoginURL']);
	} else {
		redirect($CFG->wwwroot.'/mod/amcat/view.php?id='.$activityid, \core\notification::error($data['message']));
	}
}