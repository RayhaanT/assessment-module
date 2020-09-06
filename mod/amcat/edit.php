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
 * Provides the interface for overall authoring of amcats
 *
 * @package mod_amcat
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/amcat/locallib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('amcat', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$amcat = new amcat($DB->get_record('amcat', array('id' => $cm->instance), '*', MUST_EXIST));

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/amcat:manage', $context);

$mode    = optional_param('mode', get_user_preferences('amcat_view', 'collapsed'), PARAM_ALPHA);
// Ensure a valid mode is set.
if (!in_array($mode, array('single', 'full', 'collapsed'))) {
    $mode = 'collapsed';
}
$PAGE->set_url('/mod/amcat/edit.php', array('id'=>$cm->id,'mode'=>$mode));
$PAGE->force_settings_menu();

if ($mode != get_user_preferences('amcat_view', 'collapsed') && $mode !== 'single') {
    set_user_preference('amcat_view', $mode);
}

$amcatoutput = $PAGE->get_renderer('mod_amcat');
$PAGE->navbar->add(get_string('edit'));
echo $amcatoutput->header($amcat, $cm, $mode, false, null, get_string('edit', 'amcat'));

if (!$amcat->has_pages()) {
    // There are no pages; give teacher some options
    require_capability('mod/amcat:edit', $context);
    echo $amcatoutput->add_first_page_links($amcat);
} else {
    switch ($mode) {
        case 'collapsed':
            echo $amcatoutput->display_edit_collapsed($amcat, $amcat->firstpageid);
            break;
        case 'single':
            $pageid =  required_param('pageid', PARAM_INT);
            $PAGE->url->param('pageid', $pageid);
            $singlepage = $amcat->load_page($pageid);
            echo $amcatoutput->display_edit_full($amcat, $singlepage->id, $singlepage->prevpageid, true);
            break;
        case 'full':
            echo $amcatoutput->display_edit_full($amcat, $amcat->firstpageid, 0);
            break;
    }
}

echo $amcatoutput->footer();
