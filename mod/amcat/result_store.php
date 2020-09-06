<?php

global $CFG, $USER, $DB, $COURSE;
require('../../config.php');

// $id = required_param('id', PARAM_INT);
//$postData = required_param('result', PARAM_RAW);
// $postData =$_POST['data'];
// print_r($_POST);
// $fp = fopen('data.txt', 'a');		//opens file in append mode  
// fwrite($fp, $_POST);  
// fclose($fp);  
// exit();
// Here Post Data comes.
//   $postData = '{
// 	"result": {
// 		"candidateId": "2",
// 		"uniqueID": "10245149",
// 		"amcatID": "257140063990884",
// 		"assessmentName": "MS_Role3_Pre assessment  Language and web development_Practitioner",
// 		"candidateName": "Nilesh",
// 		"candidateEmailID": "keshashankar@gmail.com",
// 		"status": "Not Consider",
// 		"reportURL": [{
// 			"name": "Assessment Report",
// 			"url": "https%3A%2F%2Freport.myamcat.com%2F%3FamcatId%3D257140063990884%26locale%3Den-IN%26outputFormat%3Dhtml%26reportId%3D1%26data%3DSP4T%252BkQuUtmcYe9dgdodH33XZjKLkfoOoJJYi8Fm81rHwGC6bbtoEE5jSrEwX94u3rmm4QkEPRSKIEEhz1f4Mu5LfJ2LOu4OUGNkpMnblEA95apXo98FYgWkxELzEeXFX60BnSZGFC7vkxXCGiic1NcIr%252BQOUqVuNNq6qgmVD0M%253D"
// 		}],
// 		"overallScore": "21.75",
// 		"scores": {
// 			"Automata": "7",
// 			"JavaScript": "17",
// 			"HTML5": "33",
// 			"CSS3": "30"
// 		}
// 	}
// }';


$data = file_get_contents('php://input');
$fp = fopen('data.txt', 'a');		//opens file in append mode  
fwrite($fp, $data);
fwrite($fp, "============================");
fclose($fp);  
$postData  = $data;

// Converted into ARRAY by DECODE
$postData_array = json_decode($postData);

if(isset($postData_array) && !empty($postData_array)){
	foreach ($postData_array as $key => $result) {
		$amresult = new stdClass();
		$amresult->course				=  $result->courseid; 
		$amresult->uniqueid				=  $result->uniqueID; 
		$amresult->candidateid			=  $result->candidateId; 
		$amresult->candidatename 		=  $result->candidateName; 
		$amresult->candidateemailid 	=  $result->candidateEmailID; 
		$amresult->amcatid 				=  $result->activityid; 
		$amresult->assessmentname 		=  $result->assessmentName; 
		$amresult->status 				=  $result->status; 
		$amresult->overallscore 		=  $result->overallScore; 
		$amresult->reporturl 			=  json_encode($result->reportURL); 
		$amresult->timecreated 			=  time(); 
		$amresult->timemodified 		=  time();
		$insertedid = $DB->insert_record('amcat_result', $amresult);				// amresult Table where result is storing.
		
		foreach ($result->scores as $key => $scores) {
			$scores_obj = new stdClass();
			$scores_obj->resultid 	= $insertedid;
			$scores_obj->subjects	= $key;
			$scores_obj->scores     = $scores;
			$resultscoreid = $DB->insert_record('amcat_score', $scores_obj);		// resultscore Table where we store score of candidate as per candidate.
		}

		$grades = new stdClass();
		$grades->amcatid 	= $result->activityid;
		$grades->userid	= $result->moodleuserid;
		$grades->grade     = $result->overallScore;
		$grades->completed = '1';
		$amcat_gradesid = $DB->insert_record('amcat_grades', $grades);

		$sql = "SELECT am.id as amid, gi.id as itemid, am.name, am.course, gi.grademax, gi.gradepass FROM {grade_items} as gi LEFT JOIN  {amcat} as am on gi.iteminstance = am.id 
				LEFT JOIN {course_modules} as cm ON am.course = cm.course and cm.instance = am.id 
				where cm.id = ? and itemmodule='amcat' and courseid = ?";

		$activitydetails = $DB->get_record_sql($sql, array($result->activityid, $result->courseid));

		if(!empty($activitydetails)){

			$sql_checkrecoreds = "SELECT * FROM {grade_grades} where itemid = ? and userid = ?";
			$checkrecord = $DB->get_record_sql($sql_checkrecoreds, array($activitydetails->itemid, $result->moodleuserid));

			if(empty($checkrecord)){
					$grade_grades = new stdClass();
					$grade_grades->itemid					=	$activitydetails->itemid;
					$grade_grades->userid					=	$result->moodleuserid;
					$grade_grades->rawgrade					=	Null;
					$grade_grades->rawgrademax				=	$activitydetails->grademax;
					$grade_grades->rawgrademin				=	'0.000000';
					$grade_grades->rawscaleid				=	Null;
					$grade_grades->usermodified				=	Null;
					$grade_grades->finalgrade				=	$result->overallScore;
					$grade_grades->hidden					=	0;
					$grade_grades->locked					=	0;
					$grade_grades->locktime					=	0;
					$grade_grades->exported					=	0;
					$grade_grades->overridden				=	0;
					$grade_grades->excluded					=	0;
					$grade_grades->feedback					=	Null;
					$grade_grades->feedbackformat			=	0;
					$grade_grades->information				=	Null;
					$grade_grades->informationformat		=	0;
					$grade_grades->timecreated				=	Null;
					$grade_grades->timemodified				=	Null;
					$grade_grades->aggregationstatus		=	'novalue';
					$grade_grades->aggregationweight		=	'0.00000';
					$grade_gradesid = $DB->insert_record('grade_grades', $grade_grades);
					


					$completionstate = 1;
					if ($result->overallScore >= $activitydetails->gradepass){
						$completionstate = 2;	
					} else if ($result->overallScore < $activitydetails->gradepass){
						$completionstate = 3;
					}

					$course_modules_completion = new stdClass();
					$course_modules_completion->coursemoduleid		=	$result->activityid;
					$course_modules_completion->userid 				=	$result->moodleuserid;
					$course_modules_completion->completionstate		=	$completionstate;
					$course_modules_completion->viewed				=	1;
					$course_modules_completion->overrideby			=	Null;
					$course_modules_completion->timemodified		=	time();
					$completionid = $DB->insert_record('course_modules_completion', $course_modules_completion);

					if($completionid){
						$returnarr = array('status' => 'success', 'code' => 200, 'message' => 'Data Saved');
						$fp = fopen('data.txt', 'a');		//opens file in append mode  
						//fwrite($fp, $completionid);

						fwrite($fp, "<br> Insert : ============== ". date("Y/m/d H:m:s")." => ". $completionid ." ============== <br> ");
						fclose($fp);  
						return json_encode($returnarr);
					}

			} else {
				// Update records here...
				$grade_grades = new stdClass();
				$grade_grades->itemid					=	$activitydetails->itemid;
				$grade_grades->userid					=	$result->moodleuserid;
				$grade_grades->rawgrade					=	Null;
				$grade_grades->rawgrademax				=	$activitydetails->grademax;
				$grade_grades->rawgrademin				=	'0.000000';
				$grade_grades->rawscaleid				=	Null;
				$grade_grades->usermodified				=	Null;
				$grade_grades->finalgrade				=	$result->overallScore;
				$grade_grades->hidden					=	0;
				$grade_grades->locked					=	0;
				$grade_grades->locktime					=	0;
				$grade_grades->exported					=	0;
				$grade_grades->overridden				=	0;
				$grade_grades->excluded					=	0;
				$grade_grades->feedback					=	Null;
				$grade_grades->feedbackformat			=	0;
				$grade_grades->information				=	Null;
				$grade_grades->informationformat		=	0;
				$grade_grades->timecreated				=	Null;
				$grade_grades->timemodified				=	Null;
				$grade_grades->aggregationstatus		=	'novalue';
				$grade_grades->aggregationweight		=	'0.00000';
				$grade_grades->id						=	$checkrecord->id;
				
				$grade_gradesid = $DB->update_record('grade_grades', $grade_grades);

				$sql_completion = "SELECT * FROM {course_modules_completion} where coursemoduleid = ? and userid = ?";
				$records_sql_completion = $DB->get_record_sql($sql_completion, array($result->activityid, $result->moodleuserid));

					$completionstate = 1;
				if ($result->overallScore >= $activitydetails->gradepass){
					$completionstate = 2;	
				} else if ($result->overallScore < $activitydetails->gradepass){
					$completionstate = 3;
				}

				$course_modules_completion = new stdClass();
				$course_modules_completion->coursemoduleid		=	$result->activityid;
				$course_modules_completion->userid 				=	$result->moodleuserid;
				$course_modules_completion->completionstate		=	$completionstate;
				$course_modules_completion->viewed				=	1;
				$course_modules_completion->overrideby			=	Null;
				$course_modules_completion->timemodified		=	time();
				$course_modules_completion->id					=	$records_sql_completion->id;
				$completionid = $DB->update_record('course_modules_completion', $course_modules_completion);


				if($completionid){
					$returnarr = array('status' => 'success', 'code' => 200, 'message' => 'Data Saved');
					$fp = fopen('data.txt', 'a');		//opens file in append mode  
					//fwrite($fp, $completionid);
					fwrite($fp, "<br> Update : ============== ". date("Y/m/d H:m:s")." => ". $completionid ." ============== <br> ");
					fclose($fp);  
					return json_encode($returnarr);
				}
			}


		} 
	}
} else {
	redirect($CFG->wwwroot.'/my/');
}
