<?php
require('../../config.php');
include_once($CFG->dirroot . '/course/lib.php');
include_once($CFG->libdir . '/coursecatlib.php');
require_once ($CFG->dirroot . '/completion/classes/progress.php');
global $CFG, $DB, $USER;
$sqlenrolledcourses = "SELECT c.id as courseid, c.fullname
            FROM {course} c
            JOIN {context} ct ON c.id = ct.instanceid
            JOIN {role_assignments} ra ON ra.contextid = ct.id
            JOIN {user} u ON u.id = ra.userid
            JOIN {role} r ON r.id = ra.roleid
            where u.id = ?";
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
		$widgets ='';
        foreach ($groups as $key => $group) {
             $groups_array[$group->id] = $group->name;
        }
		$widgets .= html_writer::start_tag('div', array('class' => 'col-md-4'));
                $widgets .= html_writer::start_tag('form', array('id'=>'form_managegroup'));
                $widgets .= html_writer::select($groups_array, 'group', '', '',array('onchange' => 'managegroup(this.value, '.$groupcourseid->courseid  .' )'));
                $widgets .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'courseid', 'value'=> $groupcourseid->courseid ));

                $widgets .= html_writer::end_tag('form');
            $widgets .= html_writer::end_tag('div');
        $widgets .= html_writer::end_tag('div');
		
		echo $widgets;

?>