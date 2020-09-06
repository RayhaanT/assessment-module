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
 * Library functions for overview.
 *
 * @package   block_managerprogress
 * @copyright 2018 Peter Dias
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Constants for the user preferences view options
 */
define('BLOCK_MANAGERPROGRESS_VIEW_ACTIVITIES', get_string('s1b', 'block_managerprogress'));
define('BLOCK_MANAGERPROGRESS_VIEW_ASSESSMENT', get_string('s2b', 'block_managerprogress'));
define('BLOCK_MANAGERPROGRESS_VIEW_COURSE', get_string('s3b', 'block_managerprogress'));
define('BLOCK_MANAGERPROGRESS_VIEW_ASSIGNMENTS', get_string('s4b', 'block_managerprogress'));

/**
 * Get the current user preferences that are available
 *
 * @return mixed Array representing current options along with defaults
 */
function block_managerprogress_user_preferences() {

    $preferences['block_managerprogress_user_view_preference'] = array(
        'null' => NULL_NOT_ALLOWED,
        'default' => array(
            BLOCK_MANAGERPROGRESS_VIEW_ACTIVITIES,
            BLOCK_MANAGERPROGRESS_VIEW_ASSESSMENT,
            BLOCK_MANAGERPROGRESS_VIEW_COURSE,
            BLOCK_MANAGERPROGRESS_VIEW_ASSIGNMENTS
        ),
        'type' => PARAM_ALPHA,
        'choices' => array(
            BLOCK_MANAGERPROGRESS_VIEW_ACTIVITIES,
            BLOCK_MANAGERPROGRESS_VIEW_ASSESSMENT,
            BLOCK_MANAGERPROGRESS_VIEW_COURSE,
            BLOCK_MANAGERPROGRESS_VIEW_ASSIGNMENTS
        )
    );
    return $preferences;
}