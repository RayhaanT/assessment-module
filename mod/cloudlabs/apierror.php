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
$PAGE->set_url('/mod/cloudlabs/apierror.php', array('id' => $id));
$PAGE->set_heading('Cloudlabs');
echo $OUTPUT->header();

?>
<style type="text/css">
	.custombtn{
		padding: 20px;
	}
</style>
<div style="padding: 5px; border: 1px solid #ccc; border-radius: 3px; height: 350px; box-shadow: 0 0 20px rgba(0,0,0,.1);">
<div class="row" style="margin-top:50px;margin-bottom:80px;">
<br/>
<div class="col-md-12"><h2><center>Welcome to Cloudlab</center></h2></div>
</div>
<div class="row">
        
		<br/>
		<div class="col-md-12">
			<center> 
			    <a href="viewsteps.php?id=2">Try with Stepwise</a>

			</center>
		</div>
	</div>
</div>
<?php
echo $OUTPUT->footer();
