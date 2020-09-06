<?php
global $CFG, $USER, $DB, $SESSION;
require('../../config.php');
require_once($CFG->dirroot.'/mod/cloudlabs/lib.php');

$id      = optional_param('id', 0, PARAM_INT); // Course Module ID

$login = optional_param('login', '', PARAM_RAW);
$registration = optional_param('registration', '', PARAM_RAW);
$createlab = optional_param('createlab', '', PARAM_RAW);
$launch = optional_param('launch', '', PARAM_RAW);
$subscriptions= optional_param('subscriptions', '', PARAM_RAW);
$hidaccesskey= optional_param('hidaccesskey', '', PARAM_RAW);
$viewdetails = 1;

if (!empty($id)) {
	if (! $cm = get_coursemodule_from_id('cloudlabs', $id, 0, true)) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        print_error('coursemisconf');
    }
} else {
    print_error('missingparameter');
}
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/cloudlabs:view', $context);
$PAGE->set_url('/mod/cloudlabs/apicall.php');

 if($launch == 'Launch') {

	if($USER->id) {
		$curl_url = getBaseURL();
		$user = $DB->get_record('user', array('id' => $USER->id));
		$post_data = new stdClass();
		$post_data->userName = $user->email;
		$post_data->password = 'wt4l*CG@@1';
		$lcArray= array('1002'=>'Missing required arguments','1003'=>'Invalid argument(s)', '1005'=>'Access denied for user', '1024'=>'Cannot launch. Subscription already deleted.', '1025'=>'Cannot launch. Subscription already deleting.', '1026'=>'Cannot launch. Subscription is creating.', '1027'=>'Cannot launch. Subscription is creation failed.', '1028'=>'Launch in progress.', '1029'=>'Cannot launch. Subscription creation is pending.', '1030'=>'Cannot launch. Subscription creation is suspended.', '1031'=>'Cannot launch. Subscription assigned duration completed.', '1032'=>'Cannot launch. Unknown error.');		
		//$DB->set_debug(true);
		$reguser = $DB->get_record('cloudlabs_users', array('userid' => $USER->id));
		$regrefid = $reguser->id;

		$record = $DB->get_record('cloudlabs_participants', array('cregisterid' => $regrefid, 'moduleid'=>$id ));
		//print_object($record);
		
		$subscriptionId = $record->subscriptionid;
	    //exit;
		$data = labLaunch($post_data->userName, $post_data->password, $subscriptionId);
		//echo "112345";
		//print_object($data);
		//exit;
		if(isset($data['MessageCode']) &&  $data['MessageCode']!=''){
			//$viewdetails = 0;
			$mcode = $data['MessageCode'];
			$mdetails = $data['MessageDetail'];

			$mess_info = 0;
			if($mcode == 1003){
				$message = '1003 : Oops! Looks like there\'s an issue.  Contact the Admin for support!';
			} else if($mcode == 1005){
				$message = '1005 : Oops! Looks like there\'s an issue.  Contact the Admin for support!';
			} else if($mcode == 1002){
				$message = '1002 : Oops! Looks like there\'s an issue.  Contact the Admin for support!';
			} else if($mcode == 1024){
				$message = '1024 : Oops! Looks like there\'s an issue.  Contact the Admin for support!';
			} else if($mcode == 1025){
				$message = '1025 : Oops! Looks like there\'s an issue.  Contact the Admin for support!';
			} else if($mcode == 1026){
				$message = '1026 : Please wait! Your lab will launch shortly!';
				$mess_info = 1;
			} else if($mcode == 1027){
				$message = '1027 : Oops! Looks like there\'s an issue.  Contact the Admin for support!';
			} else if($mcode == 1028){
				$message = '1028 : Please wait! Your lab will launch shortly! Please try after five minutes!';
			} else if($mcode == 1029){
				$message = '1029 : Oops! Looks like there\'s an issue.  Contact the Admin for support!';
			} else if($mcode == 1030){
				$message = '1030 : Oops! Looks like there\'s an issue.  Contact the Admin for support!';
			} else if($mcode == 1031){
				$message = '1031 : Oops! Looks like there\'s an issue.  Contact the Admin for support!';
			} else if($mcode == 1032){
				$message = '1032 : Oops! Looks like there\'s an issue.  Contact the Admin for support!';
			} else {
				$message = $mdetails;
			}
			if($mess_info == 1)
				redirect($CFG->wwwroot.'/mod/cloudlabs/view.php?id='.$id, \core\notification::info($message));
			else 
				redirect($CFG->wwwroot.'/mod/cloudlabs/view.php?id='.$id, \core\notification::error($message));

		}else {
			$viewdetails = 1;
			//print_object($data['userAccess']);
			//echo $hidaccesskey;
			$urldata = json_decode($data['userAccess']);
			foreach($urldata as $uk=>$uval){
				//print_object($uval);
				if($uval->key == $hidaccesskey)
				{
				    $url = $uval->value;
				}
				if($uval->key == 'loginuser')
				{
				    $loginuser_f = $uval->value;
				}
				if($uval->key == 'password')
				{
				    $loginpassword_f = $uval->value;
				}
			
			}
			//echo $url."ABCD";
		//	print_object($urldata);
		//echo $url = $urldata[1]->value;
			
			//print_object($data);
		//exit;
			//redirect($url);
		}
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

    <div class="row" style="margin-top:30px;margin-bottom:20px;">
    <br/>
        <div class="col-md-12"><h2><center>Welcome to Cloudlab</center></h2></div>
    </div>
	<?php if($viewdetails==1){?>
	<div class="row">
	    <div class="col-md-12">
			<center> 		    
			<b>Your Lab is ready, please click the URL to launch the lab </b> :  <a href="<?php echo $url;?>" target="_blank"><?php echo $url;?></a>
			</center>
		
		</div>
	</div>
	<?php if(empty($loginuser_f) || empty($loginpassword_f) )
		{ ?>
		<div class="row">
		<br><br>
		</div>
		
		<?php }
		else {
		?>
		<div class="row">
			<div class="col-md-12">
				<center>
					<b>Lab User Login ID</b> : <?php echo $loginuser_f;?>
				</center> 
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<center> 
					<b>Lab User Password</b> : <?php echo $loginpassword_f;?>
				</center> 
			</div>
		</div>
		<?php
		}

		?>
	
	
	<?php } ?>	
</div>

<?php
echo $OUTPUT->footer();	
