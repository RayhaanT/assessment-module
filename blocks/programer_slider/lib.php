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
 * Course slider block helper functions and callbacks
 *
 * @package   block_programer_slider
 * @copyright 2016 Kyriaki Hadjicosta (Coventry University)
 * @copyright 2017 Manoj Solanki (Coventry University)
 * @copyright
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

define('BLOCK_PROGRAMER_SLIDER_CLASSNAME', 'block_programer_slider');
define('BLOCK_PROGRAMER_SLIDER_LANG', 'block_programer_slider');
define('BLOCK_PROGRAMER_SLIDER_BLOCKNAME', 'programer_slider');
define('BLOCK_PROGRAMER_SLIDER_DEFINITIONS', '/settings/definitions.php');
define('BLOCK_PROGRAMER_SLIDER_MILLISECONDS', 1000);

/**
 * This is a callback method with a special naming convention that is searched for
 * by the Moodle File API.
 *
 * See link below for further information.  At the time of writing, note that the example in the link
 * isn't used exactly as is, as it wouldn't work and it is an old example.
 * Main things that are different are the use of send_stored_file instead (correct one to use in later versions of Moodle)
 * and not using the File API get_file() method.  It justs uses get_file_by_hash() (file_storage class in core) instead.
 *
 * Also refer to other plugins to get an idea how these kind of methods work (a good one is the mod forum plugin).
 *
 * @link  https://docs.moodle.org/dev/File_API#Serving_files_to_users
 * @param stdClass $course          Course object
 * @param stdClass $cm              Course module object
 * @param stdClass $context         Context object
 * @param string   $filearea        File area
 * @param array    $args            Extra arguments
 * @param bool     $forcedownload   Whether or not force download
 * @param array    $options         Additional options affecting the file serving
 * @return none
 */
function block_programer_slider_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {

    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    // Make sure the filearea is one of those used by the plugin.  This may
    // need to be expanded if this method gets called for other file areas.
    if ($filearea !== 'defaultimage') {
        return false;
    }

    $fullpath = "/{$context->id}/block_programer_slider/$filearea/{$args[0]}/{$args[1]}";

    // Actually retrieve the file.
    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // Finally send the file.
    send_stored_file($file, 0, 0, true, $options); // download MUST be forced - security!
}