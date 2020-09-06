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
 * Action for processing page answers by users
 *
 * @package mod_amcat
 * @copyright  2009 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

/** Require the specific libraries */
require_once("../../config.php");
require_once($CFG->dirroot.'/mod/amcat/locallib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('amcat', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$amcat = new amcat($DB->get_record('amcat', array('id' => $cm->instance), '*', MUST_EXIST), $cm, $course);

require_login($course, false, $cm);
require_sesskey();

// Apply overrides.
$amcat->update_effective_access($USER->id);

$context = $amcat->context;
$canmanage = $amcat->can_manage();
$amcatoutput = $PAGE->get_renderer('mod_amcat');

$url = new moodle_url('/mod/amcat/continue.php', array('id'=>$cm->id));
$PAGE->set_url($url);
$PAGE->set_pagetype('mod-amcat-view');
$PAGE->navbar->add(get_string('continue', 'amcat'));

// This is the code updates the amcat time for a timed test
// get time information for this user
if (!$canmanage) {
    $amcat->displayleft = amcat_displayleftif($amcat);
    $timer = $amcat->update_timer();
    if (!$amcat->check_time($timer)) {
        redirect(new moodle_url('/mod/amcat/view.php', array('id' => $cm->id, 'pageid' => amcat_EOL, 'outoftime' => 'normal')));
        die; // Shouldn't be reached, but make sure.
    }
} else {
    $timer = new stdClass;
}

// record answer (if necessary) and show response (if none say if answer is correct or not)
$page = $amcat->load_page(required_param('pageid', PARAM_INT));

$reviewmode = $amcat->is_in_review_mode();

// Process the page responses.
$result = $amcat->process_page_responses($page);

if ($result->nodefaultresponse || $result->inmediatejump) {
    // Don't display feedback or force a redirecto to newpageid.
    redirect(new moodle_url('/mod/amcat/view.php', array('id'=>$cm->id,'pageid'=>$result->newpageid)));
}

// Set Messages.
$amcat->add_messages_on_page_process($page, $result, $reviewmode);

$PAGE->set_url('/mod/amcat/view.php', array('id' => $cm->id, 'pageid' => $page->id));
$PAGE->set_subpage($page->id);

/// Print the header, heading and tabs
amcat_add_fake_blocks($PAGE, $cm, $amcat, $timer);
echo $amcatoutput->header($amcat, $cm, 'view', true, $page->id, get_string('continue', 'amcat'));

if ($amcat->displayleft) {
    echo '<a name="maincontent" id="maincontent" title="'.get_string('anchortitle', 'amcat').'"></a>';
}
// This calculates and prints the ongoing score message
if ($amcat->ongoing && !$reviewmode) {
    echo $amcatoutput->ongoing_score($amcat);
}
if (!$reviewmode) {
    echo format_text($result->feedback, FORMAT_MOODLE, array('context' => $context, 'noclean' => true));
}

// User is modifying attempts - save button and some instructions
if (isset($USER->modattempts[$amcat->id])) {
    $content = $OUTPUT->box(get_string("gotoendofamcat", "amcat"), 'center');
    $content .= $OUTPUT->box(get_string("or", "amcat"), 'center');
    $content .= $OUTPUT->box(get_string("continuetonextpage", "amcat"), 'center');
    $url = new moodle_url('/mod/amcat/view.php', array('id' => $cm->id, 'pageid' => amcat_EOL));
    echo $content . $OUTPUT->single_button($url, get_string('finish', 'amcat'));
}

// Review button back
if (!$result->correctanswer && !$result->noanswer && !$result->isessayquestion && !$reviewmode && $amcat->review && !$result->maxattemptsreached) {
    $url = new moodle_url('/mod/amcat/view.php', array('id' => $cm->id, 'pageid' => $page->id));
    echo $OUTPUT->single_button($url, get_string('reviewquestionback', 'amcat'));
}

$url = new moodle_url('/mod/amcat/view.php', array('id'=>$cm->id, 'pageid'=>$result->newpageid));

if ($amcat->review && !$result->correctanswer && !$result->noanswer && !$result->isessayquestion && !$result->maxattemptsreached) {
    // If both the "Yes, I'd like to try again" and "No, I just want to go on  to the next question" point to the same
    // page then don't show the "No, I just want to go on to the next question" button. It's confusing.
    if ($page->id != $result->newpageid) {
        // Button to continue the amcat (the page to go is configured by the teacher).
        echo $OUTPUT->single_button($url, get_string('reviewquestioncontinue', 'amcat'));
    }
} else {
    // Normal continue button
    echo $OUTPUT->single_button($url, get_string('continue', 'amcat'));
}

echo $amcatoutput->footer();
