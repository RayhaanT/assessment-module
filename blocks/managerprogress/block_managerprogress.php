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
 * Starred courses block.
 *
 * @package   block_managerprogress
 * @copyright 2018 Simey Lameze <simey@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Starred courses block definition class.
 *
 * @package   block_managerprogress
 * @copyright 2018 Simey Lameze <simey@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_managerprogress extends block_base {

    /**
     * Initialises the block.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_managerprogress');
    }

    /**
     * Gets the block contents.
     *
     * @return string The block HTML.
     */
    public function get_content() {
         global $DB, $USER;
        if ($this->content !== null) {
            return $this->content;
        }
        $this->page->requires->css('/blocks/managerprogress/style.css');

        // Default Coures id get on all enrol coures's first/current element.
        $sqlenrolledcourses = "SELECT c.id as courseid, c.fullname
            FROM {course} c
            JOIN {context} ct ON c.id = ct.instanceid
            JOIN {role_assignments} ra ON ra.contextid = ct.id
            JOIN {user} u ON u.id = ra.userid
            JOIN {role} r ON r.id = ra.roleid
            where u.id = ? and ra.roleid = 1 and c.category != 0";
        $mycoures = $DB->get_records_sql($sqlenrolledcourses, array($USER->id));
        $defaultcourses = 0;
        if(!empty($mycoures)){
            $defaultcourses = current($mycoures)->courseid;
        }
        // Get coures id from URL or if empty then use default course id.
        $course_id = isset($_GET['courseid'])? $_GET['courseid'] : $defaultcourses;

        $sql_group = "select g.id, g.name from {groups} g INNER JOIN 
                        {groups_members} gm ON gm.groupid = g.id
                        INNER JOIN {user} u ON u.id = gm.userid
                        where g.courseid = ? and u.id= ?";
        $groups = $DB->get_records_sql($sql_group, array($course_id, $USER->id));

        if(!empty($mycoures)){
            $defaultgroups = current($groups)->id;
        }

        $groupid = isset($_GET['group'])? $_GET['group'] : $defaultgroups;

        $mycourselist = array(0 => 'Select Coures');
        foreach ($mycoures as $key => $coures) {
            $mycourselist[$coures->courseid] = $coures->fullname; 
        }

        $groupcourseid = $DB->get_record('groups', array('id' => $groupid));

        // SQL for get Group of selected coures..
        $sql_group = "select g.id, g.name from {groups} g INNER JOIN 
                        {groups_members} gm ON gm.groupid = g.id
                        INNER JOIN {user} u ON u.id = gm.userid
                        where g.courseid = ? and u.id= ?";
        $groups = $DB->get_records_sql($sql_group, array($course_id, $USER->id));
        $groups_array = array(0 => 'Select Group');
        foreach ($groups as $key => $group) {
             $groups_array[$group->id] = $group->name;
        }
        $widgets = '';
        $onchange = "
                function managegroup(groupid, gcourseid){
                      document.getElementById('form_managegroup').submit();
                }
        ";

        $widgets .= html_writer::script($onchange);

        $widgets .= html_writer::start_tag('div', array('class' => 'row' ));

            // DROP DOWN OF COURSE
            $widgets .= html_writer::start_tag('div', array('class' => 'col-md-4'));
                $widgets .= html_writer::start_tag('form', array('id'=>'form_managerprogress'));
                $widgets .= html_writer::select($mycourselist, 'courseid', $course_id, '',array('onchange' => 'showdropcourse(this.value)'));
                $widgets .= html_writer::end_tag('form');
            $widgets .= html_writer::end_tag('div');

            // DROP DOWN OF GROUP
			$widgets .= '<span id="txtgrp">';
            $widgets .= html_writer::start_tag('div', array('class' => 'col-md-4'));
                $widgets .= html_writer::start_tag('form', array('id'=>'form_managegroup'));
                $widgets .= html_writer::select($groups_array, 'group', $group, '',array('onchange' => 'managegroup(this.value, '.$groupcourseid->courseid  .' )'));
                $widgets .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'courseid', 'value'=> $groupcourseid->courseid ));

                $widgets .= html_writer::end_tag('form');
            $widgets .= html_writer::end_tag('div');
        $widgets .= html_writer::end_tag('div');
		$widgets .= '</span>';
		$widgets .='<script> function showdropcourse(str) {
			  if (str.length == 0) {
				document.getElementById("txtgrp").innerHTML = "";
				return;
			  } else {
				var xmlhttp = new XMLHttpRequest();
				xmlhttp.onreadystatechange = function() {
				  if (this.readyState == 4 && this.status == 200) {
					document.getElementById("txtgrp").innerHTML = this.responseText;
				  }
				};
				xmlhttp.open("POST", "/lmsnew/blocks/managerprogress/managerGroup.php?courseid=" + str, true);
				xmlhttp.send();
			  }
			} </script>';
		
		

        
		
		$widgets .='<script> function showdrop2course(str,str1) {
			
			  if (str.length == 0) {
				document.getElementById("txtgrp2").innerHTML = "";
				return;
			  } else {
				var xmlhttp = new XMLHttpRequest();
				xmlhttp.onreadystatechange = function() {
				  if (this.readyState == 4 && this.status == 200) {
					  
					document.getElementById("txtgrp2").innerHTML = this.responseText;
				  }
				};
				xmlhttp.open("POST", "/blocks/managerprogress/managerGroup2.php?courseid=" + str +"&group="+str1, true);
				xmlhttp.send();
			  }
			} </script>';
			
       
		$widgets .= '<div id="txtgrp2">';
		$layouts = array();

        $settings =  get_config("block_managerprogress");
        if(!empty($settings->layouts))
          $layouts = explode(",", $settings->layouts);

        $countlayout = count($layouts);

        if($countlayout == 4){
            $class = 'col-md-3';
        } else if($countlayout == 3){
            $class = 'col-md-4';
        } else if($countlayout == 2){
            $class = 'col-md-6';
        } else if($countlayout == 1){
            $class = 'col-md-12';
        } else if($countlayout == 0){
            $class = 'col-md-3';    // This should be zero, if zero then show all.
        }
 // BOX1 
        global $CFG;
        $widgets .= html_writer::start_tag('div', array('class' => 'row outerbox' ));    

        $widgets .= html_writer::div(' <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>');
		
if (in_array("Activities", $layouts)){
			
            $widgets .= html_writer::start_tag('div', array('class' => 'box4 '. $class));
			
            $widgets .= html_writer::tag('h4', get_string('s1', 'block_managerprogress'));
            
           /********************** Activities Completed / Overall Activities ***********************/
                $sql_completed_overall = "SELECT g.userid 
                 FROM {groups_members} AS g
                 LEFT JOIN {user} AS u ON g.userid=u.id
                 WHERE g.groupid = ? AND u.deleted = 0 AND u.suspended = 0 ";
                
                  $complteionrecords = $DB->get_records_sql($sql_completed_overall, array($groupid));
                  $completed_activities = 0;
                  $completedcountmod = 0;


                  $sql_getcompletionmod = "SELECT id FROM {course_modules} WHERE  course = ? AND deletioninprogress = 0 ";
                  $completionmod = $DB->get_records_sql($sql_getcompletionmod, array($course_id));
                  
                  $incoursemod  = array();
                  $q=0;$p=0;
                  foreach ($completionmod as $key => $value) {
                     $incoursemod[]  = $value->id;
                     $q++;
                  }
                  $coursemod = 0;
                  if(!empty($incoursemod))
                    $coursemod = implode(',', $incoursemod);
                  
                  foreach ($complteionrecords as $key => $complteionrecord) {

                   $sql_coursemodcompletion = "SELECT count(*) as cmc FROM {course_modules_completion} WHERE userid = $complteionrecord->userid AND coursemoduleid IN ($coursemod) AND completionstate = 1";

                    $mod_completion = $DB->get_record_sql($sql_coursemodcompletion, array());
                    if($mod_completion->cmc == $q){
                      $completedcountmod++;
                    }
                    $p++;
                  }       
                  $completed_activities =  $completedcountmod / $p * 100 ;
                $incompleted_activities = 100 - $completed_activities;
                /*********************************************/
				

            $script = "
                google.charts.load('current', {'packages':['corechart']});
                google.charts.setOnLoadCallback(drawChart1);
                function drawChart1() {
                  var data = google.visualization.arrayToDataTable([
                    ['Task', 'Hours per Day'],
                    ['Completed',     ".round($completed_activities, 2)."],
                    ['Incompleted',    ".round($incompleted_activities, 2)."]
                  ]);
                  var options = {'title':'Activities Completed / Overall activities', 
                                 'width':'auto', 
                                 'height':'auto',
                                  pieHole: 0.8,
                                  };

                 
                  var chart = new google.visualization.PieChart(document.getElementById('donut_single1'));
                  chart.draw(data, options);
                }";
                $widgets .= html_writer::script($script);
                $widgets .= html_writer::div('<div id="donut_single1" style="width: 100%; height: 130px;"></div>');
                
                $widgets .= html_writer::start_tag('div', array('class' => 'status'));
                $widgets .= html_writer::div("Completed: ".round($completed_activities, 2));
                $widgets .= html_writer::div("Incompleted: ".round($incompleted_activities, 2));
                $widgets .= html_writer::end_tag('div');


                $widgets .= html_writer::end_tag('div');
				
				
            }
			
            if (in_array("Assessment", $layouts)){
                // BOX 2
				
                $widgets .= html_writer::start_tag('div', array('class' => 'box4 '.$class));
                $widgets .= html_writer::tag('h4', get_string('s2', 'block_managerprogress'));

                /********************** Assessment Completed / Overall assessment ***********************/
                $sql_completed_overall = "SELECT g.userid 
                 FROM {groups_members} AS g
                 LEFT JOIN {user} AS u ON g.userid=u.id
                 WHERE g.groupid = ? AND u.deleted = 0 AND u.suspended = 0 ";
                
                  $complteionrecords = $DB->get_records_sql($sql_completed_overall, array($groupid));
                  $completed_assessment = 0;
                  $completedcountmod = 0;

                  $sql_getcompletionmod = "SELECT cm.id FROM {course_modules} as cm INNER JOIN {modules} as m ON m.id = cm.module WHERE (m.name='quiz' OR m.name='amcat' ) AND cm.course = ? AND cm.deletioninprogress = 0 ";
                  $completionmod = $DB->get_records_sql($sql_getcompletionmod, array($course_id));

                  $incoursemod  = array();
                  $c=0;$p=0;
                  foreach ($completionmod as $key => $value) {
                     $incoursemod[]  = $value->id;
                     $c++;
                  }

                  $coursemod = 0;
                  if(!empty($incoursemod))
                    $coursemod = implode(',', $incoursemod);
                  foreach ($complteionrecords as $key => $complteionrecord) {
                    $sql_coursemodcompletion = "SELECT count(*) as cmc FROM {course_modules_completion} WHERE userid = $complteionrecord->userid AND coursemoduleid IN ($coursemod) AND completionstate = 1";
                    $mod_completion = $DB->get_record_sql($sql_coursemodcompletion, array());
                    if($mod_completion->cmc == $c){
                      $completedcountmod++;
                    }
                    $p++;
                  }           
                  $completed_assessment =  $completedcountmod / $p * 100 ;
                  $incompleted_assessment = 100 - $completed_assessment;
                
                /*********************************************/
                $script = "
                    google.charts.load('current', {'packages':['corechart']});
                    google.charts.setOnLoadCallback(drawChart2);
                    function drawChart2() {
                      var data = google.visualization.arrayToDataTable([
                        ['Task', 'Hours per Day'],
                        ['Completed',     ".$completed_assessment."],
                        ['Incompleted',    ".$incompleted_assessment."]
                      ]);
                      
                      var options = {'title':'Assessment Completed / Overall assessment', 
                                     'width':'auto', 
                                     'height':'auto',
                                      pieHole: 0.8,
                                      };
                      var chart = new google.visualization.PieChart(document.getElementById('donut_single2'));
                      chart.draw(data, options);
                    }";
                $widgets .= html_writer::script($script);
                $widgets .= html_writer::div('<div id="donut_single2" style="width: 100%; height: 130px;"></div>');
                $widgets .= html_writer::start_tag('div', array('class' => 'status'));
                $widgets .= html_writer::div("Completed: ".round($completed_assessment, 2));
                $widgets .= html_writer::div("Incompleted: ".round($incompleted_assessment, 2));
                $widgets .= html_writer::end_tag('div');
                $widgets .= html_writer::end_tag('div');
            }

            if (in_array("Coures", $layouts)){
                /******************************* Completion Percentage / Overall Students  *************************/
                $widgets .= html_writer::start_tag('div', array('class' => 'box4 '.$class));
                $widgets .= html_writer::tag('h4', get_string('s3', 'block_managerprogress'));

                $sql_completed_overall = "SELECT g.userid 
                 FROM {groups_members} AS g
                 LEFT JOIN {user} AS u ON g.userid=u.id
                 WHERE g.groupid = ? AND u.deleted = 0 AND u.suspended = 0 ";
                
                $complteionrecords = $DB->get_records_sql($sql_completed_overall, array($groupid));
                $completion_percentage = 0;
                $completedcount = 0;
                foreach ($complteionrecords as $key => $complteionrecord) {

                  $sql_getcompletion = "SELECT * FROM {course_completions} WHERE userid = ? AND course = ? AND timecompleted IS NOT NULL";
                  $completion = $DB->get_records_sql($sql_getcompletion, array($complteionrecord->userid, $course_id));
                  if(!empty($completion)){
                    $completedcount++;
                  }
                  $completion_percentage++;
                }
                $completion_percentage =  $completedcount / $completion_percentage * 100 ;
                $incompleted_percentage = 100 - $completion_percentage;

                $script = "google.charts.setOnLoadCallback(drawChart3);
                    function drawChart3() {
                      var data = google.visualization.arrayToDataTable([
                        ['Task', 'Hours per Day'],
                        ['Completion Percentage',     ".$completion_percentage."],
                        ['Incompleted Percentage',    ".$incompleted_percentage."]
                      ]);

                      var options = {'title':'Completion Percentage / Overall Students', 
                                     'width':'auto', 
                                     'height':'auto',
                                      pieHole: 0.8,
                                    };
                      var chart = new google.visualization.PieChart(document.getElementById('donut_single3'));
                      chart.draw(data, options);
                    }";
                $widgets .= html_writer::script($script);
                $widgets .= html_writer::div('<div id="donut_single3" style="width: 100%; height: 130px;"></div>');
                $widgets .= html_writer::start_tag('div', array('class' => 'status'));
                $widgets .= html_writer::div("Completed: ".round($completion_percentage, 2));
                $widgets .= html_writer::div("Incompleted: ".round($incompleted_percentage, 2));
                $widgets .= html_writer::end_tag('div');
                $widgets .= html_writer::end_tag('div');
            }

            if (in_array("Assignments", $layouts)){
                /******************************** Assignments Completed / Overall Assignments *********************/
                $widgets .= html_writer::start_tag('div', array('class' => 'box4 '.$class));
                $widgets .= html_writer::tag('h4', get_string('s4', 'block_managerprogress'));
                  
                 $sql_completed_overall = "SELECT g.userid 
                   FROM {groups_members} AS g
                   LEFT JOIN {user} AS u ON g.userid=u.id
                   WHERE g.groupid = ? AND u.deleted = 0 AND u.suspended = 0 ";
                
                  $complteionrecords = $DB->get_records_sql($sql_completed_overall, array($groupid));
                  $completion_assignments = 0;
                  $completedcountmod = 0;


                  $sql_getcompletionmod = "SELECT cm.id FROM {course_modules} as cm INNER JOIN {modules} as m ON m.id = cm.module WHERE m.name='assign' AND course = ? AND deletioninprogress = 0";
                  $completionmod = $DB->get_records_sql($sql_getcompletionmod, array($course_id));
                  $incoursemod  = array();
                  $j = 0; $k = 0;
                  foreach ($completionmod as $key => $value) {
                     $incoursemod[]  = $value->id;
                     $j++;
                  }

                  $coursemod = 0;
                  if(!empty($incoursemod))
                    $coursemod = implode(',', $incoursemod);
                  foreach ($complteionrecords as $key => $complteionrecord) {
                    $sql_coursemodcompletion = "SELECT count(*) as cmc FROM {course_modules_completion} WHERE userid = $complteionrecord->userid AND coursemoduleid IN ($coursemod) AND completionstate = 1";
                    $mod_completion = $DB->get_record_sql($sql_coursemodcompletion, array());
                    if($mod_completion->cmc == $j){
                      $completedcountmod++;
                    }
                    $k++;
                  }           
                  $completion_assignments =  $completedcountmod / $k * 100 ;
                  $incompleted_assignments = 100 - $completion_assignments;
                 
                 $script = "
                  google.charts.setOnLoadCallback(drawChart4);
                    function drawChart4() {
                      var data = google.visualization.arrayToDataTable([
                        ['Task', 'Hours per Day'],
                        ['Completed',     ".round($completion_assignments, 2)."],
                        ['Incompleted',    ".round($incompleted_assignments, 2)."]
                      ]);
                      
                      var options = {'title':'Assignments Completed / Overall Assignment', 
                                     'width':'auto', 
                                     'height':'auto',
                                      pieHole: 0.8,
                                      };

                     
                      var chart = new google.visualization.PieChart(document.getElementById('donut_single4'));
                      chart.draw(data, options);
                    }";  

                $widgets .= html_writer::script($script);
                $widgets .= html_writer::div('<div id="donut_single4" style="width: 100%; height: 130px;"></div>');
                $widgets .= html_writer::start_tag('div', array('class' => 'status'));
                $widgets .= html_writer::div("Completed: ".round($completion_assignments, 2));
                $widgets .= html_writer::div("Incompleted: ".round($incompleted_assignments, 2));
                $widgets .= html_writer::end_tag('div');
            }


            if (empty($layouts)){
                $widgets .= html_writer::start_tag('div', array('class' => 'col-md-12'));
                $widgets .= html_writer::tag('h4', get_string('nolayout', 'block_managerprogress'));
                $widgets .= html_writer::end_tag('div');
            }  
            $widgets .= html_writer::end_tag('div');
            $widgets .= html_writer::end_tag('div');		
			$widgets .= html_writer::end_tag('div');
			$widgets .= '</div>';
        $this->content->text = $widgets;
		
    }


    /**
     * Locations where block can be displayed.
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my' => true);
    }

    /**
     * Allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Return the plugin config settings for external functions.
     *
     * @return stdClass the configs for both the block instance and plugin
     * @since Moodle 3.8
     */
    public function get_config_for_external() {
        // Return all settings for all users since it is safe (no private keys, etc..).
        $configs = get_config('block_managerprogress');

        return (object) [
            'instance' => new stdClass(),
            'plugin' => $configs,
        ];
    }
}
