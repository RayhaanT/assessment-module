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
 * Course list block.
 *
 * @package    block_userrank
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//include_once($CFG->dirroot . '/course/lib.php');
//include_once($CFG->libdir . '/coursecatlib.php');
//require_once ($CFG->dirroot . '/completion/classes/progress.php');
class block_userrank extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_userrank');
    }

    function has_config() {
        return true;
    }

    function get_content() {
        global $CFG, $USER, $DB, $OUTPUT;

        if($this->content !== NULL) {
            return $this->content;
        }
		$courses = $DB->get_records_sql('SELECT c.* FROM {user} u 
		INNER JOIN {role_assignments} ra ON ra.userid = u.id 
		INNER JOIN {context} ct ON ct.id = ra.contextid 
		INNER JOIN {course} c ON c.id = ct.instanceid 
		INNER JOIN {role} r ON r.id = ra.roleid 
		WHERE r.id = 5 and u.id=' . $USER->id . '');
		$courseslast = $DB->get_record_sql('SELECT MAX(c.id) as id FROM {user} u 
		INNER JOIN {role_assignments} ra ON ra.userid = u.id 
		INNER JOIN {context} ct ON ct.id = ra.contextid 
		INNER JOIN {course} c ON c.id = ct.instanceid 
		INNER JOIN {role} r ON r.id = ra.roleid 
		WHERE r.id = 5 and u.id=' . $USER->id . '');
		
		@$o='';
		$o.='<div class="row">
			<div class="col-md-1"></div>
			<div class="col-md-10">
					</div>
			<div class="col-md-1"></div>
			</div>
			<div class="row">
			<div class="col-md-1"></div>
			<div class="col-md-10">
					<select name="coursid" id="ccrid" class="form-control" onchange="redir_rank()">';
		$o.='<option value="">'.get_string('selectcours', 'block_userrank').'</option>';		
		foreach ($courses  as $cid){
			if($courseslast->id==$cid->id){
			$o.='<option value="'.$cid->id.'" selected>'.$cid->fullname.'</option>';
			}else{
			
			$o.='<option value="'.$cid->id.'">'.$cid->fullname.'</option>';		
			}
								
		}
		$o.='</select> </div>
		<div class="col-md-1"></div>
		</div><div class="row"></div><div class="row" ></div><div class="row"></div><input type="hidden" id="lastid" name="lastid" value="'.$courseslast->id.'">';
		$o.='<div class="row" id="rrp" style="margin-top: 5%;"></div>';
		$o.='<script>
		window.onload = function() {
			  redir_rank();
			};
			function redir_rank(){
			var id=document.getElementById("ccrid").value;
			if(id.length >0){
			var id=document.getElementById("ccrid").value;
			}else{
			var id=document.getElementById("lastid").value;
			}
			$.post("'.$CFG->wwwroot.'/blocks/ranking/report_glob.php", {"courseid": id}, function (jsondata) {
			$("#rrp").html(jsondata);
			}, "json");
						}
			</script>';
			
					$this->title = 'Ranking details';
					$this->content = new stdClass();
					$this->content->text = $o;
					$this->content->footer = '';
					return $this->content;
		
	}
    
}




