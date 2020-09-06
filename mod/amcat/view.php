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
 * This page prints a particular instance of amcat
 *
 * @package mod_amcat
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 **/

define('NO_OUTPUT_BUFFERING', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/mod/amcat/locallib.php');
require_once($CFG->libdir . '/grade/constants.php');

$id      = required_param('id', PARAM_INT);             // Course Module ID
$pageid  = optional_param('pageid', null, PARAM_INT);   // amcat Page ID
$edit    = optional_param('edit', -1, PARAM_BOOL);
$userpassword = optional_param('userpassword','',PARAM_RAW);
$backtocourse = optional_param('backtocourse', false, PARAM_RAW);

$cm = get_coursemodule_from_id('amcat', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$amcat = new amcat($DB->get_record('amcat', array('id' => $cm->instance), '*', MUST_EXIST), $cm, $course);

require_login($course, false, $cm);
$modulecontext = context_module::instance($cm->id);


$PAGE->set_url('/mod/amcat/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();
?>

<div style="padding: 5px; border: 1px solid #ccc; border-radius: 3px; height: 350px; box-shadow: 0 0 20px rgba(0,0,0,.1);">

    <div class="row" style="vertical-align: center;">
        <div class="col-md-12">
            <center>
                <br><br><br>
                <h3> Welcome into Aspiring Minds !!! </h3>
                <h4> Enter button to start test </h4> 
                <br>
                <form method="POST" name="loginform" value="loginform" action="amapicall.php" target="_blank">
                    <input type="hidden" name="courseid" id="courseid" value="<?php echo $course->id; ?>" />
                    <input type="hidden" name="activityid" id="activityid" value="<?php echo $id; ?>" />
                    <input type="submit" name="amresult" id="amresult" value="Start Test" class="btn btn-primary custombtn fa fa-lock" />
                </form>
                <br><br>
                <h4 style="color: blue"> All the Best !!! </h4>                 
            </center>
        </div>


    </div>
</div>

<?php

echo $OUTPUT->footer();


// if ($backtocourse) {
//     redirect(new moodle_url('/course/view.php', array('id'=>$course->id)));
// }

// // Apply overrides.
// $amcat->update_effective_access($USER->id);

// $url = new moodle_url('/mod/amcat/view.php', array('id'=>$id));
// if ($pageid !== null) {
//     $url->param('pageid', $pageid);
// }
// $PAGE->set_url($url);
// $PAGE->force_settings_menu();

// $context = $amcat->context;
// $canmanage = $amcat->can_manage();

// $amcatoutput = $PAGE->get_renderer('mod_amcat');

// $reviewmode = $amcat->is_in_review_mode();

// if ($amcat->usepassword && !empty($userpassword)) {
//     require_sesskey();
// }

// // Check these for students only TODO: Find a better method for doing this!
// if ($timerestriction = $amcat->get_time_restriction_status()) {  // Deadline restrictions.
//     echo $amcatoutput->header($amcat, $cm, '', false, null, get_string('notavailable'));
//     echo $amcatoutput->amcat_inaccessible(get_string($timerestriction->reason, 'amcat', userdate($timerestriction->time)));
//     echo $amcatoutput->footer();
//     exit();
// } else if ($passwordrestriction = $amcat->get_password_restriction_status($userpassword)) { // Password protected amcat code.
//     echo $amcatoutput->header($amcat, $cm, '', false, null, get_string('passwordprotectedamcat', 'amcat', format_string($amcat->name)));
//     echo $amcatoutput->login_prompt($amcat, $userpassword !== '');
//     echo $amcatoutput->footer();
//     exit();
// } else if ($dependenciesrestriction = $amcat->get_dependencies_restriction_status()) { // Check for dependencies.
//     echo $amcatoutput->header($amcat, $cm, '', false, null, get_string('completethefollowingconditions', 'amcat', format_string($amcat->name)));
//     echo $amcatoutput->dependancy_errors($dependenciesrestriction->dependentamcat, $dependenciesrestriction->errors);
//     echo $amcatoutput->footer();
//     exit();
// }

// // This is called if a student leaves during a amcat.
// if ($pageid == amcat_UNSEENBRANCHPAGE) {
//     $pageid = amcat_unseen_question_jump($amcat, $USER->id, $pageid);
// }

// // To avoid multiple calls, store the magic property firstpage.
// $amcatfirstpage = $amcat->firstpage;
// $amcatfirstpageid = $amcatfirstpage ? $amcatfirstpage->id : false;

// // display individual pages and their sets of answers
// // if pageid is EOL then the end of the amcat has been reached
// // for flow, changed to simple echo for flow styles, michaelp, moved amcat name and page title down
// $attemptflag = false;
// if (empty($pageid)) {
//     // make sure there are pages to view
//     if (!$amcatfirstpageid) {
//         if (!$canmanage) {
//             $amcat->add_message(get_string('amcatnotready2', 'amcat')); // a nice message to the student
//         } else {
//             if (!$DB->count_records('amcat_pages', array('amcatid'=>$amcat->id))) {
//                 redirect("$CFG->wwwroot/mod/amcat/edit.php?id=$cm->id"); // no pages - redirect to add pages
//             } else {
//                 $amcat->add_message(get_string('amcatpagelinkingbroken', 'amcat'));  // ok, bad mojo
//             }
//         }
//     }

//     // if no pageid given see if the amcat has been started
//     $retries = $amcat->count_user_retries($USER->id);
//     if ($retries > 0) {
//         $attemptflag = true;
//     }

//     if (isset($USER->modattempts[$amcat->id])) {
//         unset($USER->modattempts[$amcat->id]);  // if no pageid, then student is NOT reviewing
//     }

//     $lastpageseen = $amcat->get_last_page_seen($retries);

//     // Check if the amcat was attempted in an external device like the mobile app.
//     // This check makes sense only when the amcat allows offline attempts.
//     if ($amcat->allowofflineattempts && $timers = $amcat->get_user_timers($USER->id, 'starttime DESC', '*', 0, 1)) {
//         $timer = current($timers);
//         if (!empty($timer->timemodifiedoffline)) {
//             $lasttime = format_time(time() - $timer->timemodifiedoffline);
//             $amcat->add_message(get_string('offlinedatamessage', 'amcat', $lasttime), 'warning');
//         }
//     }

//     // Check to see if end of amcat was reached.
//     if (($lastpageseen !== false && ($lastpageseen != amcat_EOL))) {
//         // End not reached. Check if the user left.
//         if ($amcat->left_during_timed_session($retries)) {

//             echo $amcatoutput->header($amcat, $cm, '', false, null, get_string('leftduringtimedsession', 'amcat'));
//             if ($amcat->timelimit) {
//                 if ($amcat->retake) {
//                     $continuelink = new single_button(new moodle_url('/mod/amcat/view.php',
//                             array('id' => $cm->id, 'pageid' => $amcat->firstpageid, 'startlastseen' => 'no')),
//                             get_string('continue', 'amcat'), 'get');

//                     echo html_writer::div($amcatoutput->message(get_string('leftduringtimed', 'amcat'), $continuelink),
//                             'center leftduring');

//                 } else {
//                     $courselink = new single_button(new moodle_url('/course/view.php',
//                             array('id' => $PAGE->course->id)), get_string('returntocourse', 'amcat'), 'get');

//                     echo html_writer::div($amcatoutput->message(get_string('leftduringtimednoretake', 'amcat'), $courselink),
//                             'center leftduring');
//                 }
//             } else {
//                 echo $amcatoutput->continue_links($amcat, $lastpageseen);
//             }
//             echo $amcatoutput->footer();
//             exit();
//         }
//     }

//     if ($attemptflag) {
//         if (!$amcat->retake) {
//             echo $amcatoutput->header($amcat, $cm, 'view', '', null, get_string("noretake", "amcat"));
//             $courselink = new single_button(new moodle_url('/course/view.php', array('id'=>$PAGE->course->id)), get_string('returntocourse', 'amcat'), 'get');
//             echo $amcatoutput->message(get_string("noretake", "amcat"), $courselink);
//             echo $amcatoutput->footer();
//             exit();
//         }
//     }
//     // start at the first page
//     if (!$pageid = $amcatfirstpageid) {
//         echo $amcatoutput->header($amcat, $cm, 'view', '', null);
//         // amcat currently has no content. A message for display has been prepared and will be displayed by the header method
//         // of the amcat renderer.
//         echo $amcatoutput->footer();
//         exit();
//     }
//     /// This is the code for starting a timed test
//     if(!isset($USER->startamcat[$amcat->id]) && !$canmanage) {
//         $amcat->start_timer();
//     }
// }

// $currenttab = 'view';
// $extraeditbuttons = false;
// $amcatpageid = null;
// $timer = null;

// if ($pageid != amcat_EOL) {

//     $amcat->set_module_viewed();

//     $timer = null;
//     // This is the code updates the amcattime for a timed test.
//     $startlastseen = optional_param('startlastseen', '', PARAM_ALPHA);

//     // Check to see if the user can see the left menu.
//     if (!$canmanage) {
//         $amcat->displayleft = amcat_displayleftif($amcat);

//         $continue = ($startlastseen !== '');
//         $restart  = ($continue && $startlastseen == 'yes');
//         $timer = $amcat->update_timer($continue, $restart);

//         // Check time limit.
//         if (!$amcat->check_time($timer)) {
//             redirect(new moodle_url('/mod/amcat/view.php', array('id' => $cm->id, 'pageid' => amcat_EOL, 'outoftime' => 'normal')));
//             die; // Shouldn't be reached, but make sure.
//         }
//     }

//     list($newpageid, $page, $amcatcontent) = $amcat->prepare_page_and_contents($pageid, $amcatoutput, $reviewmode);

//     if (($edit != -1) && $PAGE->user_allowed_editing()) {
//         $USER->editing = $edit;
//     }

//     $PAGE->set_subpage($page->id);
//     $currenttab = 'view';
//     $extraeditbuttons = true;
//     $amcatpageid = $page->id;
//     $extrapagetitle = $page->title;

//     amcat_add_fake_blocks($PAGE, $cm, $amcat, $timer);
//     echo $amcatoutput->header($amcat, $cm, $currenttab, $extraeditbuttons, $amcatpageid, $extrapagetitle);
//     if ($attemptflag) {
//         // We are using level 3 header because attempt heading is a sub-heading of amcat title (MDL-30911).
//         echo $OUTPUT->heading(get_string('attempt', 'amcat', $retries), 3);
//     }
//     // This calculates and prints the ongoing score.
//     if ($amcat->ongoing && !empty($pageid) && !$reviewmode) {
//         echo $amcatoutput->ongoing_score($amcat);
//     }
//     if ($amcat->displayleft) {
//         echo '<a name="maincontent" id="maincontent" title="' . get_string('anchortitle', 'amcat') . '"></a>';
//     }
//     echo $amcatcontent;
//     echo $amcatoutput->progress_bar($amcat);
//     echo $amcatoutput->footer();

// } else {

//     // End of amcat reached work out grade.
//     // Used to check to see if the student ran out of time.
//     $outoftime = optional_param('outoftime', '', PARAM_ALPHA);

//     $data = $amcat->process_eol_page($outoftime);
//     $amcatcontent = $amcatoutput->display_eol_page($amcat, $data);

//     amcat_add_fake_blocks($PAGE, $cm, $amcat, $timer);
//     echo $amcatoutput->header($amcat, $cm, $currenttab, $extraeditbuttons, $amcatpageid, get_string("congratulations", "amcat"));
//     echo $amcatcontent;
//     echo $amcatoutput->footer();
// }
