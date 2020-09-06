<?php
require('../../config.php');
include_once($CFG->dirroot . '/course/lib.php');
include_once($CFG->libdir . '/coursecatlib.php');
require_once ($CFG->dirroot . '/completion/classes/progress.php');
global $CFG, $DB, $USER;
$coid = $_GET['coid'];
$sql_group = "select g.id, g.name from {groups} g INNER JOIN 
                        {groups_members} gm ON gm.groupid = g.id
                        INNER JOIN {user} u ON u.id = gm.userid
                        where g.courseid =".$coid." and u.id=".$USER->id;
$groups = $DB->get_records_sql($sql_group, array());
$list='';
$list .='<select name="coursgid" id="gid" class="form-control" onchange="redir_mrank()">';
		$list .='<option>Select Group</option>';		
		foreach ($groups  as $cid){
			$list.='<option value="'.$cid->id.'">'.$cid->name.'</option>';
								
		}
		
		
		$list .='</select>';
		echo $list;

?>