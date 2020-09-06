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
 * This file plays the mediafile set in amcat settings.
 *
 *  If there is a way to use the resource class instead of this code, please change to do so
 *
 *
 * @package mod_amcat
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/amcat/locallib.php');

$id = required_param('id', PARAM_INT);    // Course Module ID
$printclose = optional_param('printclose', 0, PARAM_INT);

$cm = get_coursemodule_from_id('amcat', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$amcat = new amcat($DB->get_record('amcat', array('id' => $cm->instance), '*', MUST_EXIST), $cm);

require_login($course, false, $cm);

// Apply overrides.
$amcat->update_effective_access($USER->id);

$context = $amcat->context;
$canmanage = $amcat->can_manage();

$url = new moodle_url('/mod/amcat/mediafile.php', array('id'=>$id));
if ($printclose !== '') {
    $url->param('printclose', $printclose);
}
$PAGE->set_url($url);
$PAGE->set_pagelayout('popup');
$PAGE->set_title($course->shortname);

$amcatoutput = $PAGE->get_renderer('mod_amcat');

// Get the mimetype
$mimetype = mimeinfo("type", $amcat->mediafile);

if ($printclose) {  // this is for framesets
    if ($amcat->mediaclose) {
        echo $amcatoutput->header($amcat, $cm);
        echo $OUTPUT->box('<form><div><input type="button" onclick="top.close();" value="'.get_string("closewindow").'" /></div></form>', 'amcatmediafilecontrol');
        echo $amcatoutput->footer();
    }
    exit();
}

// Check access restrictions.
if ($timerestriction = $amcat->get_time_restriction_status()) {  // Deadline restrictions.
    echo $amcatoutput->header($amcat, $cm, '', false, null, get_string('notavailable'));
    echo $amcatoutput->amcat_inaccessible(get_string($timerestriction->reason, 'amcat', userdate($timerestriction->time)));
    echo $amcatoutput->footer();
    exit();
} else if ($passwordrestriction = $amcat->get_password_restriction_status(null)) { // Password protected amcat code.
    echo $amcatoutput->header($amcat, $cm, '', false, null, get_string('passwordprotectedamcat', 'amcat', format_string($amcat->name)));
    echo $amcatoutput->login_prompt($amcat, $userpassword !== '');
    echo $amcatoutput->footer();
    exit();
} else if ($dependenciesrestriction = $amcat->get_dependencies_restriction_status()) { // Check for dependencies.
    echo $amcatoutput->header($amcat, $cm, '', false, null, get_string('completethefollowingconditions', 'amcat', format_string($amcat->name)));
    echo $amcatoutput->dependancy_errors($dependenciesrestriction->dependentamcat, $dependenciesrestriction->errors);
    echo $amcatoutput->footer();
    exit();
}

echo $amcatoutput->header($amcat, $cm);

// print the embedded media html code
echo $OUTPUT->box(amcat_get_media_html($amcat, $context));

if ($amcat->mediaclose) {
   echo '<div class="amcatmediafilecontrol">';
   echo $OUTPUT->close_window_button();
   echo '</div>';
}

echo $amcatoutput->footer();
