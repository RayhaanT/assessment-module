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
global $SESSION;
require_once($CFG->dirroot.'/mod/cloudlabs/lib.php');
$id      = optional_param('id', 0, PARAM_INT); // Course Module ID
$errord      = optional_param('errord', 0, PARAM_INT);
$showlaunch=1;
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
if($errord==1){
    $showlaunch=0;
}

$cloudlab_teamid = $cloudlabs->teamid;
$cloudlab_companyid = $cloudlabs->companyid;
$cloudlab_planid = $cloudlabs->planid;
$cloudlab_accesskey = $cloudlabs->accesskey;

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/cloudlabs:view', $context);

$PAGE->set_url('/mod/cloudlabs/view.php', array('id' => $cm->id));
//$DB->set_debug(true);
if($showlaunch==1){
//Login API
    $curl_url = getBaseURL();
	$post_data = new stdClass();
	$username = "iprimedapiadmin@nuvelabs.com";
    $password = "wt4l*CG@@1";	
	$data = generateToken($curl_url.'v1/users/login', $username, $password);
	$_SESSION['token'] = $data;
	
//exit;

$regrefid="";

//Registration
$user = $DB->get_record('user', array('id' => $USER->id));

$reguser = $DB->get_record('cloudlabs_users', array('userid' => $USER->id));


if(!$reguser){
	$post_data = new stdClass();
	$post_data->username = $user->username;
	$post_data->firstname = $user->firstname;
	$post_data->lastname = $user->lastname;
	$post_data->email = $user->email;
	$post_data->password = 'wt4l*CG@@1';
	$post_data->companyId = $cloudlab_companyid;
	$post_data->teamId = $cloudlab_teamid;
	//print_object($post_data);
	$data = addUserToCloudlabs($post_data->email, $post_data->password, $post_data->firstname, $post_data->lastname, $post_data->companyId, $post_data->teamId);
	
		if(isset($data['userid']) && $data['userid']!=""){
			$rudata = new stdClass();
			$rudata->userid = $USER->id;
			$rudata->cuserid = $data['userid'];;
			$rudata->cusername = $data['userName'];
			$rudata->cpassword = $data['password'];
			$rudata->cfirstname = $data['firstName'];
			$rudata->clastname = $data['lastName'];
			$rudata->timecreated = time();
			//print_object($rudata);
			$cregisterid = $DB->insert_record('cloudlabs_users', $rudata);
			$regrefid = $cregisterid;
			//print_object($cregisterid);
			//echo $cregisterid;
			//exit;
		} else if(isset($data['MessageCode']) && $data['MessageCode']!=''){
			$rudata = new stdClass();
			$rudata->userid = $USER->id;
			$rudata->cuserid = $USER->id;
			$rudata->cusername = $user->email;
			$rudata->cpassword = 'wt4l*CG@@1';
			$rudata->cfirstname = $user->firstname;
			$rudata->clastname = $user->lastname;
			$rudata->timecreated = time();
			$cregisterid = $DB->insert_record('cloudlabs_users', $rudata);
			$regrefid = $cregisterid;
			//print_object($cregisterid);
			//echo $cregisterid;
			//exit;
		}
	} else {
		$regrefid = $reguser->id;
	}
	//exit;

	$userintoteam = "";
	$subscriptionid = "";
	$partuser = $DB->get_record('cloudlabs_participants', array('cregisterid' => $regrefid, 'course'=>$course->id, 'moduleid'=>$id ));
	if(!$partuser){
		$pudata = new stdClass();
		$pudata->course = $course->id;
		$pudata->moduleid = $id;
		$pudata->cregisterid = $regrefid;
		$pudata->timecreated = time();
		$participantid = $DB->insert_record('cloudlabs_participants', $pudata);
		$userintoteam = 0;
	} else {
		$userintoteam = $partuser->userintoteam;
		$participantid = $partuser->id;
	}

	if($userintoteam == 0){
		$post_datateam = new stdClass();
		$post_datateam->userName = $user->email;
		$post_datateam->companyId = $cloudlab_companyid;
		$post_datateam->teamIds = $cloudlab_teamid;
		$post_datateam->password = 'wt4l*CG@@1';
		$teamdata = addUserToTeam($post_datateam->userName, $post_datateam->companyId, $post_datateam->teamIds, $post_datateam->password);
		if($teamdata['ResponseStatus'] || $teamdata['MessageCode']==1000){
			$teamup_data = new stdClass();
			$teamup_data->id = $participantid;
			$teamup_data->userintoteam = 1;
			$DB->update_record('cloudlabs_participants', $teamup_data);
			$userintoteam=1;
		}	
	}


	if($partuser->subscriptionid==0 && $userintoteam==1){
		
		$post_datasub = new stdClass();
		$post_datasub->userName = $user->email;
		$post_datasub->password = 'wt4l*CG@@1';
		$post_datasub->teamId   = $cloudlab_teamid;
		$post_datasub->companyId = $cloudlab_companyid;
		$post_datasub->planId  = $cloudlab_planid;
		$datasub = Createsubscriptions($post_datasub->userName, $post_datasub->teamId, $post_datasub->companyId, $post_datasub->planId);
		//print_object($datasub);
		//exit;
		
		if(isset($datasub['subscriptionId'])){
		    $subscriptionid = $datasub['subscriptionId'];
		}
		if(isset($datasub['MessageCode']) && $datasub['MessageCode']==1012){
		    $subscriptionid = $datasub['subscriptionIds'][0];
		}
		if($subscriptionid!=""){
			$subup_data = new stdClass();
			$subup_data->id = $participantid;
			$subup_data->subscriptionid = $subscriptionid;
			$DB->update_record('cloudlabs_participants', $subup_data);
			$showlaunch=1;
			
		}else{
			redirect($CFG->wwwroot.'/mod/cloudlabs/view.php?id='.$id.'&errord=1', \core\notification::error('subscription not created... '));
		}
	} else {
		$showlaunch=1;
	}
}
$PAGE->set_heading('Cloudlabs');
echo $OUTPUT->header();

?>
<style type="text/css">
	.custombtn{
		padding: 20px;
	}
</style>
<div style="padding: 5px; border: 1px solid #ccc; border-radius: 3px; height: 350px; box-shadow: 0 0 20px rgba(0,0,0,.1);">

    <div class="row" style="margin-top:50px;margin-bottom:50px;">
    <br/>
        <div class="col-md-12"><h2><center>Welcome to Cloudlab</center></h2></div>
    </div>

	<div class="row">
		<br/>
		<div class="col-md-12">
			<center> 
				<form method="POST" name="launchform" value="launchform" action="apicall.php">
				    <?php if($showlaunch==1){?>
				    <input type="hidden" name="id" value="<?php echo $id;?>">
					<input type="hidden" name="hidaccesskey" value="<?Php echo $cloudlab_accesskey?>">
					<input type="submit" name="launch" id="launch" value="Launch" class="btn btn-primary custombtn fa fa-lock" />
					<?php } else {?>
					<a href="view.php"><input type="button" name="retry" class="btn btn-primary custombtn fa fa-lock" value="Retry"></a>
					<?php } ?>
				</form>
			</center>
		</div>
	</div>

</div>

<?php
echo $OUTPUT->footer();
