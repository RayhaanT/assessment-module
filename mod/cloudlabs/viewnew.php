<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * cloudlabs module version information
 *
 * @package mod_cloudlabs
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/mod/cloudlabs/lib.php');
$id      = optional_param('id', 0, PARAM_INT); // Course Module ID
global $SESSION, $course;
$showlaunch=0;
if (!empty($id)) {
	if (! $cm = get_coursemodule_from_id('cloudlabs', $id, 0, true)) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        print_error('coursemisconf');
    }
    if (! $cloudlabs = $DB->get_record("cloudlabs", array("id" => $cm->instance))) {
        print_error('invalidcoursemodule');
    }
    
} else {
    print_error('missingparameter');
}

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/cloudlabs:view', $context);

$PAGE->set_url('/mod/cloudlabs/view.php', array('id' => $cm->id));




echo $course->shortname;
echo $courseid = $PAGE->course->id;
exit;



//Login API
    $curl_url = getBaseURL();
	$post_data = new stdClass();
	$username = "iprimedapiadmin@nuvelabs.com";
    $password = "wt4l*CG@@1";
	
	$data = generateToken($curl_url.'v1/users/login', $username, $password);
	$_SESSION['token'] = $data;
	//print_r($data);
	//print_object($data);
//exit;




//Registration

$user = $DB->get_record('user', array('id' => $USER->id));

$post_data = new stdClass();
$post_data->username = $user->username;
$post_data->firstname = $user->firstname;
$post_data->lastname = $user->lastname;
$post_data->email = $user->email;
$post_data->password = 'wt4l*CG@@1';
$post_data->companyId = '458';
$post_data->teamId = '2225';
$data = addUserToCloudlabs($post_data->email, $post_data->password, $post_data->firstname, $post_data->lastname, $post_data->companyId, $post_data->teamId);
//print_object($data);

if(isset($data['userid']) && $data['userid']!=""){
$rudata = new stdClass();
$rudata->userid = $USER->id;
$rudata->course = $course->id;
$rudata->moduleid = $id;
$rudata->clouduserid = $data['userid'];
$rudata->timecreated = time();
$participantid = $DB->insert_record('mdl_cloudlabs_participants', $rudata);
}

if($data['userid'] || $data['MessageCode']==1004){
	$user = $DB->get_record('user', array('id' => $USER->id));
	$post_data = new stdClass();
	$post_data->userName = $user->email;
	$post_data->password = 'wt4l*CG@@1';
	$post_data->teamId   = 2225;
	$post_data->companyId =458;
	$post_data->planId  = 2226;
	$data = Createsubscriptions($post_data->userName, $post_data->teamId, $post_data->companyId, $post_data->planId);
	print_object($data);
	if($data['MessageCode']==1012){
	    //$_SESSION['subscriptionId']= $data['subscriptionIds'][0];
		$SESSION->subscriptionIds = $data['subscriptionIds'][0];
	}
	
	if($data['subscriptionId'] || $data['MessageCode']==1012){
		$_SESSION['subscriptionId'] = $data['subscriptionId'];
		$user = $DB->get_record('user', array('id' => $USER->id));
		$post_data = new stdClass();
		$post_data->userName = $user->email;
		$post_data->companyId = '458';
		$post_data->teamIds = '2225';
		$post_data->password = 'wt4l*CG@@1';
		$data = addUserToTeam($post_data->userName, $post_data->companyId, $post_data->teamIds, $post_data->password);
		if($data['ResponseStatus']){
			$showlaunch=1;
			//redirect($CFG->wwwroot.'/mod/cloudlabs/viewnew.php?id='.$id, \core\notification::success('Lab created successfully... Status  : ' . $data['ResponseStatus']));
		} else {
			redirect($CFG->wwwroot.'/mod/cloudlabs/apierror.php?id='.$id, \core\notification::error('lab not created... '));
		}
		
	} else {
		redirect($CFG->wwwroot.'/mod/cloudlabs/apierror.php?id='.$id, \core\notification::error('subscription not created... '));
	}
} else {
    redirect($CFG->wwwroot.'/mod/cloudlabs/apierror.php?id='.$id, \core\notification::error('user not created... '));
}	

$PAGE->set_url('/mod/cloudlabs/viewnew.php', array('id' => $id));
$PAGE->set_heading('Cloudlabs');
echo $OUTPUT->header();

?>
<style type="text/css">
	.custombtn{
		padding: 20px;
	}
</style>
<div style="padding: 5px; border: 1px solid #ccc; border-radius: 3px; height: 350px; box-shadow: 0 0 20px rgba(0,0,0,.1);">

	<div class="row">
		<br/>
		<div class="col-md-12">
			<center> 
				<form method="POST" name="launchform" value="launchform" action="apicall.php">
					<input type="submit" name="launch" id="launch" value="Launch" class="btn btn-primary custombtn fa fa-lock" />
				</form>
			</center>
		</div>
	</div>

</div>

<?php
echo $OUTPUT->footer();
