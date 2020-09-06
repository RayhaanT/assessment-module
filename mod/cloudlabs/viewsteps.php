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
$id      = optional_param('id', 0, PARAM_INT); // Course Module ID
$PAGE->set_url('/mod/cloudlabs/viewsteps.php', array('id' => $id));
$PAGE->set_heading('Cloudlabs');
echo $OUTPUT->header();

?>
<style type="text/css">
	.custombtn{
		padding: 20px;
	}
</style>
<div style="padding: 5px; border: 1px solid #ccc; border-radius: 3px; height: 400px; box-shadow: 0 0 20px rgba(0,0,0,.1);">
    <div class="row" style="margin-top:10px;margin-bottom:20px;">
        <div class="col-md-12"><h2><center>Welcome to Cloudlab</center></h2></div>
		<div class="col-md-12"><h4>Sorry for inconvenience!</h3></div>
		<div class="col-md-12"><h5>We could not completed your request so please retry with step wise.</h4></div>
    </div>

	<div class="row" style="vertical-align: center;">
		<div class="col-md-3">
			<center> 
				<form method="POST" name="loginform" value="loginform" action="apicall.php">
					<input type="submit" name="login" id="login" value="Login" class="btn btn-primary custombtn fa fa-lock" />
				</form>
			</center>
		</div>
		
		<div class="col-md-3">
			<center> 
				<form method="POST" name="registrationform"  value="registrationform" action="apicall.php">
				<input type="submit" name="registration" id="registration" class="btn btn-primary custombtn fa fa-user-circle-o" value="Registration" />
				</form>
			</center>
		</div>


		<div class="col-md-3">
			<center> 
				<form method="POST" name="subscriptionsform" value="subscriptionsform" action="apicall.php">
					<input type="submit" name="subscriptions" id="subscriptions" value="Create Lab" class="btn btn-primary custombtn fa fa-lock" />
				</form>
			</center>
			
		</div>

		<div class="col-md-3">
			<center> 
				<form method="POST" name="createlabform" value="createlabform" action="apicall.php">
					<input type="submit" name="createlab" id="createlab" value="Assign Lab" class="btn btn-primary custombtn fa fa-lock" />
				</form>
			</center>
		</div>
	</div>

	<br><br>
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
