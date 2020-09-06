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
 * @package    block_managerrank
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//include_once($CFG->dirroot . '/course/lib.php');
//include_once($CFG->libdir . '/coursecatlib.php');
//require_once ($CFG->dirroot . '/completion/classes/progress.php');
class block_managerrank extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_managerrank');
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
		WHERE r.id = 1 and u.id=' . $USER->id . '');
		$courseslast = $DB->get_record_sql('SELECT MAX(c.id) as id FROM {user} u 
		INNER JOIN {role_assignments} ra ON ra.userid = u.id 
		INNER JOIN {context} ct ON ct.id = ra.contextid 
		INNER JOIN {course} c ON c.id = ct.instanceid 
		INNER JOIN {role} r ON r.id = ra.roleid 
		WHERE r.id = 1 and u.id=' . $USER->id . '');
		
		@$o='';
		$o.='<div class="row">
			<div class="col-md-1"></div>
			<div class="col-md-10">
					</div>
			<div class="col-md-1"></div>
			</div>
			<div class="row">
			<div class="col-md-1"></div>
			<div class="col-md-4">
					<select name="coursmid" id="ccmrid" class="form-control" onchange="showHintGroup(this.value)">';
		$o.='<option value="" selected>'.get_string('selectcours', 'block_managerrank').'</option>';		
		foreach ($courses  as $cid){
			if($courseslast->id==$cid->id){
			$o.='<option value="'.$cid->id.'">'.$cid->fullname.'</option>';
			}else{
			
			$o.='<option value="'.$cid->id.'">'.$cid->fullname.'</option>';		
			}
								
		}
		
		
		$o.='</select> </div><div class="col-md-4"><span id="txtHint"><select name="coursgid" id="gid" class="form-control" "><option>Select Group</option></select></span> </div>
		<div class="col-md-1"></div>
		</div><div class="row"></div><div class="row" ></div><div class="row"></div><input type="hidden" id="lastmid" name="lastmid" value="'.$courseslast->id.'">';
		$o.='<div class="row" id="rrpm" style="margin-top: 5%;"></div>';
		$o.='<button class="btn btn-primary" style="float:right;margin-bottom:5px;" onclick="redir_mrank_All();" id="viewAllId">View All</button><button class="btn btn-primary" style="float:right;margin-bottom:5px;display:none;" onclick="redir_mrank();" id="viewlessId" >View Less</button><script>
		function showHintGroup(str) {
			  if (str.length == 0) {
				document.getElementById("txtHint").innerHTML = "";
				return;
			  } else {
				var xmlhttp = new XMLHttpRequest();
				xmlhttp.onreadystatechange = function() {
				  if (this.readyState == 4 && this.status == 200) {
					document.getElementById("txtHint").innerHTML = this.responseText;
				  }
				};
				xmlhttp.open("POST", "/lmsnew/blocks/managerrank/getGroup.php?coid=" + str, true);
				xmlhttp.send();
			  }
			}
		
			function redir_mrank(){
			var mid=document.getElementById("ccmrid").value;
			var gid=document.getElementById("gid").value;
			document.getElementById("viewAllId").style.display = "block";
			document.getElementById("viewlessId").style.display = "none";
			if(mid.length >0){
			var mid=document.getElementById("ccmrid").value;
			
			}else{
			var mid=document.getElementById("lastmid").value;
			
			}
			$.post("'.$CFG->wwwroot.'/blocks/ranking/reportm_glob.php", {"courseid": mid,"groupid": gid,"perpage":"5"}, function (jsondata) {
			$("#rrpm").html(jsondata);
			}, "json");
						}
			function redir_mrank_All(){
			var mid=document.getElementById("ccmrid").value;
			var gid=document.getElementById("gid").value;
			document.getElementById("viewAllId").style.display = "none";
			document.getElementById("viewlessId").style.display = "block";
			if(mid.length >0){
			var mid=document.getElementById("ccmrid").value;
			
			}else{
			var mid=document.getElementById("lastmid").value;

			}
			$.post("'.$CFG->wwwroot.'/blocks/ranking/reportm_glob.php", {"courseid": mid,"groupid": gid,"perpage":"All"}, function (jsondata) {
			$("#rrpm").html(jsondata);
			}, "json");
						}
			</script>';
			
					$this->title = 'Manager Dashboard';
					$this->content = new stdClass();
					$this->content->text = $o;
					$this->content->footer = '';
					return $this->content;
		
	}
    
}




