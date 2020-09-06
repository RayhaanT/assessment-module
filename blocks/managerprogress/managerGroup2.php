<?php
require('../../config.php');
include_once($CFG->dirroot . '/course/lib.php');
include_once($CFG->libdir . '/coursecatlib.php');
require_once ($CFG->dirroot . '/completion/classes/progress.php');
global $CFG, $DB, $USER;
$course_id = $_GET['courseid'];
$groupid = $_GET['group'];

		   
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
            $widgets .= html_writer::tag('h5', get_string('s1', 'block_managerprogress'));
            
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
                ";
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
                $widgets .= html_writer::tag('h5', get_string('s2', 'block_managerprogress'));

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
                $widgets .= html_writer::tag('h5', get_string('s3', 'block_managerprogress'));

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
                $widgets .= html_writer::tag('h5', get_string('s4', 'block_managerprogress'));
                  
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
                $widgets .= html_writer::tag('h5', get_string('nolayout', 'block_managerprogress'));
                $widgets .= html_writer::end_tag('div');
            }  
            $widgets .= html_writer::end_tag('div');
            $widgets .= html_writer::end_tag('div');

        //$this->content->text = $widgets;
        echo $widgets;
   
		
		?>