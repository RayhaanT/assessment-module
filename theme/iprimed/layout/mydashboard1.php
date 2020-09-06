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
 * A two column layout for the iprimed theme.
 *
 * @package   theme_iprimed
 * @copyright 2017 Willian Mano - http://conecti.me
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

user_preference_allow_ajax_update('drawer-open-nav', PARAM_ALPHA);
user_preference_allow_ajax_update('sidepre-open', PARAM_ALPHA);

require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/behat/lib.php');

if (isloggedin()) {
    $navdraweropen = (get_user_preferences('drawer-open-nav', 'true') == 'true');
    $draweropenright = (get_user_preferences('sidepre-open', 'true') == 'true');
} else {
    $navdraweropen = false;
    $draweropenright = false;
}

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = strpos($blockshtml, 'data-block=') !== false;

$extraclasses = [];
if ($navdraweropen) {
    $extraclasses[] = 'drawer-open-left';
}

if ($draweropenright && $hasblocks) {
    $extraclasses[] = 'drawer-open-right';
}

$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$regionmainsettingsmenu = $OUTPUT->region_main_settings_menu();

global $USER, $DB;

$sqlentoll = "SELECT c.id AS id, c.fullname, u.username, u.firstname, u.lastname, u.email
                FROM {role_assignments} ra, {user} u, {course} c, {context} cxt
                WHERE ra.userid = u.id
                AND ra.contextid = cxt.id
                AND cxt.contextlevel = 50
                AND cxt.instanceid = c.id
                AND (roleid = 5) and u.id = ?";

$enrollib = $DB->get_records_sql($sqlentoll, array($USER->id));
$boxdisplay = false;

//print_object($enrollib); exit();

if(!empty($enrollib)){
    $boxdisplay = true;
}


$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'hasdrawertoggle' => true,
    'navdraweropen' => $navdraweropen,
    'draweropenright' => $draweropenright,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'is_siteadmin' => is_siteadmin(),
    'boxdisplay' => $boxdisplay
];

$themesettings = new \theme_iprimed\util\theme_settings();

$templatecontext = array_merge($templatecontext, $themesettings->footer_items());

// if (is_siteadmin()) {
//     global $DB;

//     // Get site total users.
//     $totalactiveusers = $DB->count_records('user', array('deleted' => 0, 'suspended' => 0)) - 1;
//     $totaldeletedusers = $DB->count_records('user', array('deleted' => 1));
//     $totalsuspendedusers = $DB->count_records('user', array('deleted' => 0, 'suspended' => 1));

//     // Get site total courses.
//     $totalcourses = $DB->count_records('course') - 1;

//     // Get the last online users in the past 5 minutes.
//     $onlineusers = new \block_online_users\fetcher(null, time(), 300, null, CONTEXT_SYSTEM, null);
//     $onlineusers = $onlineusers->count_users();

//     // Get the disk usage.
//     $cache = cache::make('theme_iprimed', 'admininfos');
//     $totalusagereadable = $cache->get('totalusagereadable');

//     if (!$totalusagereadable) {
//         $totalusage = get_directory_size($CFG->dataroot);
//         $totalusagereadable = number_format(ceil($totalusage / 1048576));

//         $cache->set('totalusagereadable', $totalusagereadable);
//     }

//     $usageunit = ' MB';
//     if ($totalusagereadable > 1024) {
//         $usageunit = ' GB';
//     }

//     $totalusagereadabletext = $totalusagereadable . $usageunit;

//     $templatecontext['totalusage'] = $totalusagereadabletext;
//     $templatecontext['totalactiveusers'] = $totalactiveusers;
//     $templatecontext['totalsuspendedusers'] = $totalsuspendedusers;
//     $templatecontext['totalcourses'] = $totalcourses;
//     $templatecontext['onlineusers'] = $onlineusers;
// }

// Customization by Nilesh P - Start here
global $USER, $DB;

if(isset($USER->id)){
    // Showing completion percentage of the complete program
    $countenroll = 0;
    $sqlentoll_ass1 = "SELECT c.id AS id, c.fullname, u.username, u.firstname, u.lastname, u.email
                FROM {role_assignments} ra, {user} u, {course} c, {context} cxt
                WHERE ra.userid = u.id
                AND ra.contextid = cxt.id
                AND cxt.contextlevel = 50
                AND cxt.instanceid = c.id
                AND (roleid = 5) and u.id = ? and category != 0";

        $courses = $DB->get_records_sql($sqlentoll_ass1, array($USER->id));

   // $courses = $DB->get_records_sql('SELECT * FROM {course} WHERE category != ?', array(0));
    foreach ($courses as $key => $course) {
        $context= context_course::instance( $course->id );
        $enrolled = is_enrolled($context,$USER->id, '', true);
        if($enrolled){
            $countenroll++;
        }
    }
    $completion_course = $DB->get_records_sql("select * from {course_completions} where userid = ? and timecompleted != 'NULL'", array($USER->id));
    $templatecontext['countenroll'] =  $countenroll;
    $templatecontext['course_completions'] =  0;
    if(!empty($completion_course)){
        $templatecontext['course_completions'] = count($completion_course);
    }

    // Showing number of assessment completed/total number of assessment
    $templatecontext['myamcatassessmentcount'] = 0;
    $templatecontext['myamcatassessmentcount_completed'] = 0;
    if ($DB->get_manager()->table_exists('amcat') && $DB->get_manager()->table_exists('quiz')) {
        $completed_assessment = 0;
        $completedcountmod = 0;


        $sqlentoll_ass1 = "SELECT c.id AS id, c.fullname, u.username, u.firstname, u.lastname, u.email
                FROM {role_assignments} ra, {user} u, {course} c, {context} cxt
                WHERE ra.userid = u.id
                AND ra.contextid = cxt.id
                AND cxt.contextlevel = 50
                AND cxt.instanceid = c.id
                AND (roleid = 5) and u.id = ?";

        $enrollib_ass1 = $DB->get_records_sql($sqlentoll_ass1, array($USER->id));
        $asscount = 0; $asscouncc = array();$c=0; $i=0;

        $incoursemod  = array();
        $c = 0;
        $p = 0;
        foreach ($enrollib_ass1 as $key => $value) {
            
             $sql_getcompletionmod = "SELECT cm.id FROM {course_modules} as cm INNER JOIN {modules} as m ON m.id = cm.module 
                WHERE (m.name='quiz' OR m.name='amcat' )  AND cm.deletioninprogress = 0 AND cm.course = ?";
                $completionmod = $DB->get_records_sql($sql_getcompletionmod, array($value->id));
                 foreach ($completionmod as $key => $value) {
                    $incoursemod[]  = $value->id;
                    $c++;
                }
        } 
        $coursemod = 0;
        if(!empty($incoursemod))
            $coursemod = implode(',', $incoursemod);
        
      
		$sql_coursemodcompletion = 'SELECT count(*) as count FROM {course_modules_completion} AS cmc 
			INNER JOIN {course_modules} as cm 
			ON cmc.coursemoduleid = cm.id 
			INNER JOIN {modules} as m ON m.id=cm.module
			WHERE cmc.coursemoduleid IN (?) and userid = ? and completionstate = 1 and (m.name="amcat" OR m.name="quiz")';
		
				
        $mod_completion = $DB->count_records_sql($sql_coursemodcompletion, array($coursemod, $USER->id));
        $templatecontext['myamcatassessmentcount_completed'] =  $mod_completion;
        $templatecontext['myamcatassessmentcount'] = $c;       
    }

    // Showing number of assignments completed/ total number of assignments
    if ($DB->get_manager()->table_exists('assign')) {
        
        $sqlentoll_ass = "SELECT c.id AS id, c.fullname, u.username, u.firstname, u.lastname, u.email
                FROM {role_assignments} ra, {user} u, {course} c, {context} cxt
                WHERE ra.userid = u.id
                AND ra.contextid = cxt.id
                AND cxt.contextlevel = 50
                AND cxt.instanceid = c.id
                AND (roleid = 5) and u.id = ?";

        $enrollib_ass = $DB->get_records_sql($sqlentoll_ass, array($USER->id));
        $asscount = 0; $asscouncc = array();$c=0; $i=0;
        foreach ($enrollib_ass as $key => $value) {
            $assignmentcount = $DB->count_records('assign', array('course' => $value->id));
            $modulecc = $DB->get_records('course_modules', array('course' => $value->id));
            $asscount += $assignmentcount;
            foreach ($modulecc as $key1 => $moduleccvalue) {
                $asscouncc[$i] = $moduleccvalue->id;
                $i++;
            }
        }
        $mcount = 0;
        if(!empty($asscouncc))
            $mcount = implode($asscouncc, ',');
        $assignmentcount1 = $asscount;
        $templatecontext['assignmentcount'] =  $assignmentcount1;
        $sql_comp = 'SELECT count(*) as count FROM {course_modules_completion} AS cmc 
				INNER JOIN {course_modules} as cm 
				ON cmc.coursemoduleid = cm.id 
				INNER JOIN {modules} as m ON m.id=cm.module
				WHERE cmc.coursemoduleid IN (?) and userid = ? and completionstate = 1 and m.name="assign"';
				
        $completion_assignment = $DB->count_records_sql($sql_comp, array( $mcount, $USER->id));
        $templatecontext['assignment_completed'] =  $completion_assignment;
        
    }
}



// Improve boost navigation.
theme_iprimed_extend_flat_navigation($PAGE->flatnav);

$templatecontext['flatnavigation'] = $PAGE->flatnav;
$templatecontext = array_merge($templatecontext, $themesettings->footericon(), $themesettings->footerlefticon());
echo $OUTPUT->render_from_template('theme_iprimed/mydashboard', $templatecontext);
