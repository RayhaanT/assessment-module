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
 * Handles amcat actions
 *
 * ACTIONS handled are:
 *    confirmdelete
 *    delete
 *    move
 *    moveit
 *    duplicate
 * @package mod_amcat
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/amcat/locallib.php');

$id     = required_param('id', PARAM_INT);         // Course Module ID
$action = required_param('action', PARAM_ALPHA);   // Action
$pageid = required_param('pageid', PARAM_INT);

$cm = get_coursemodule_from_id('amcat', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$amcat = new amcat($DB->get_record('amcat', array('id' => $cm->instance), '*', MUST_EXIST));

require_login($course, false, $cm);

$url = new moodle_url('/mod/amcat/amcat.php', array('id'=>$id,'action'=>$action));
$PAGE->set_url($url);

$context = context_module::instance($cm->id);
require_capability('mod/amcat:edit', $context);
require_sesskey();

$amcatoutput = $PAGE->get_renderer('mod_amcat');

/// Process the action
switch ($action) {
    case 'confirmdelete':
        $PAGE->navbar->add(get_string($action, 'amcat'));

        $thispage = $amcat->load_page($pageid);

        echo $amcatoutput->header($amcat, $cm, '', false, null, get_string('deletingpage', 'amcat', format_string($thispage->title)));
        echo $OUTPUT->heading(get_string("deletingpage", "amcat", format_string($thispage->title)));
        // print the jumps to this page
        $params = array("amcatid" => $amcat->id, "pageid" => $pageid);
        if ($answers = $DB->get_records_select("amcat_answers", "amcatid = :amcatid AND jumpto = :pageid + 1", $params)) {
            echo $OUTPUT->heading(get_string("thefollowingpagesjumptothispage", "amcat"));
            echo "<p align=\"center\">\n";
            foreach ($answers as $answer) {
                if (!$title = $DB->get_field("amcat_pages", "title", array("id" => $answer->pageid))) {
                    print_error('cannotfindpagetitle', 'amcat');
                }
                echo $title."<br />\n";
            }
        }
        echo $OUTPUT->confirm(get_string("confirmdeletionofthispage","amcat"),"amcat.php?action=delete&id=$cm->id&pageid=$pageid","view.php?id=$cm->id");

        break;
    case 'move':
        $PAGE->navbar->add(get_string($action, 'amcat'));

        $title = $DB->get_field("amcat_pages", "title", array("id" => $pageid));

        echo $amcatoutput->header($amcat, $cm, '', false, null, get_string('moving', 'amcat', format_String($title)));
        echo $OUTPUT->heading(get_string("moving", "amcat", format_string($title)), 3);

        $params = array ("amcatid" => $amcat->id, "prevpageid" => 0);
        if (!$page = $DB->get_record_select("amcat_pages", "amcatid = :amcatid AND prevpageid = :prevpageid", $params)) {
            print_error('cannotfindfirstpage', 'amcat');
        }

        echo html_writer::start_tag('div', array('class' => 'move-page'));

        echo html_writer::start_tag('div', array('class' => 'available-position'));
        $moveurl = "amcat.php?id=$cm->id&sesskey=".sesskey()."&action=moveit&pageid=$pageid&after=0";
        echo html_writer::link($moveurl, get_string("movepagehere", "amcat"));
        echo html_writer::end_tag('div');

        while (true) {
            if ($page->id != $pageid) {
                if (!$title = trim(format_string($page->title))) {
                    $title = "<< ".get_string("notitle", "amcat")."  >>";
                }
                echo html_writer::tag('div', $title, array('class' => 'page'));

                echo html_writer::start_tag('div', array('class' => 'available-position'));
                $moveurl = "amcat.php?id=$cm->id&sesskey=".sesskey()."&action=moveit&pageid=$pageid&after={$page->id}";
                echo html_writer::link($moveurl, get_string("movepagehere", "amcat"));
                echo html_writer::end_tag('div');
            }
            if ($page->nextpageid) {
                if (!$page = $DB->get_record("amcat_pages", array("id" => $page->nextpageid))) {
                    print_error('cannotfindnextpage', 'amcat');
                }
            } else {
                // last page reached
                break;
            }
        }
        echo html_writer::end_tag('div');

        break;
    case 'delete':
        $thispage = $amcat->load_page($pageid);
        $thispage->delete();
        redirect("$CFG->wwwroot/mod/amcat/edit.php?id=$cm->id");
        break;
    case 'moveit':
        $after = (int)required_param('after', PARAM_INT); // target page

        $amcat->resort_pages($pageid, $after);
        redirect("$CFG->wwwroot/mod/amcat/edit.php?id=$cm->id");
        break;
    case 'duplicate':
            $amcat->duplicate_page($pageid);
            redirect(new moodle_url('/mod/amcat/edit.php', array('id' => $cm->id)));
        break;
    default:
        print_error('unknowaction');
        break;
}

echo $amcatoutput->footer();
