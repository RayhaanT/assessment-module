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
 * This page lists all the instances of amcat in a particular course
 *
 * @package mod_amcat
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

/** Include required files */
require_once("../../config.php");
require_once($CFG->dirroot.'/mod/amcat/locallib.php');

$id = required_param('id', PARAM_INT);   // course

$PAGE->set_url('/mod/amcat/index.php', array('id'=>$id));

if (!$course = $DB->get_record("course", array("id" => $id))) {
    print_error('invalidcourseid');
}

require_login($course);
$PAGE->set_pagelayout('incourse');

// Trigger instances list viewed event.
$params = array(
    'context' => context_course::instance($course->id)
);
$event = \mod_amcat\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

/// Get all required strings

$stramcats = get_string("modulenameplural", "amcat");
$stramcat  = get_string("modulename", "amcat");


/// Print the header
$PAGE->navbar->add($stramcats);
$PAGE->set_title("$course->shortname: $stramcats");
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($stramcats, 2);

/// Get all the appropriate data

if (! $amcats = get_all_instances_in_course("amcat", $course)) {
    notice(get_string('thereareno', 'moodle', $stramcats), "../../course/view.php?id=$course->id");
    die;
}

$usesections = course_format_uses_sections($course->format);

/// Print the list of instances (your module will probably extend this)

$timenow = time();
$strname  = get_string("name");
$strgrade  = get_string("grade");
$strdeadline  = get_string("deadline", "amcat");
$strnodeadline = get_string("nodeadline", "amcat");
$table = new html_table();

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_'.$course->format);
    $table->head  = array ($strsectionname, $strname, $strgrade, $strdeadline);
    $table->align = array ("center", "left", "center", "center");
} else {
    $table->head  = array ($strname, $strgrade, $strdeadline);
    $table->align = array ("left", "center", "center");
}
// Get all deadlines.
$deadlines = amcat_get_user_deadline($course->id);
foreach ($amcats as $amcat) {
    $cm = get_coursemodule_from_instance('amcat', $amcat->id);
    $context = context_module::instance($cm->id);

    $class = $amcat->visible ? null : array('class' => 'dimmed'); // Hidden modules are dimmed.
    $link = html_writer::link(new moodle_url('view.php', array('id' => $cm->id)), format_string($amcat->name, true), $class);

    $deadline = $deadlines[$amcat->id]->userdeadline;
    if ($deadline == 0) {
        $due = $strnodeadline;
    } else if ($deadline > $timenow) {
        $due = userdate($deadline);
    } else {
        $due = html_writer::tag('span', userdate($deadline), array('class' => 'text-danger'));
    }

    if ($usesections) {
        if (has_capability('mod/amcat:manage', $context)) {
            $grade_value = $amcat->grade;
        } else {
            // it's a student, show their grade
            $grade_value = 0;
            if ($return = amcat_get_user_grades($amcat, $USER->id)) {
                $grade_value = $return[$USER->id]->rawgrade;
            }
        }
        $table->data[] = array (get_section_name($course, $amcat->section), $link, $grade_value, $due);
    } else {
        $table->data[] = array ($link, $amcat->grade, $due);
    }
}
echo html_writer::table($table);
echo $OUTPUT->footer();
