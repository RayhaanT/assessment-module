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
 * Settings for the managerprogress block
 *
 * @package    block_managerprogress
 * @copyright  2019 Mihail Geshoski <mihail@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
	require_once($CFG->dirroot . '/blocks/managerprogress/lib.php');

    // Display Course Categories on the starred courses block items.
    // Presentation options heading.
    $settings->add(new admin_setting_heading('block_managerprogress/appearance',
            get_string('appearance', 'admin'), ''));

    $choices = array(BLOCK_MANAGERPROGRESS_VIEW_ACTIVITIES =>get_string('s1b', 'block_managerprogress'),
            BLOCK_MANAGERPROGRESS_VIEW_ASSESSMENT =>get_string('s2b', 'block_managerprogress'),
            BLOCK_MANAGERPROGRESS_VIEW_COURSE =>get_string('s3b', 'block_managerprogress'),
            BLOCK_MANAGERPROGRESS_VIEW_ASSIGNMENTS =>get_string('s4b', 'block_managerprogress'));

    $settings->add(new admin_setting_configmulticheckbox(
            'block_managerprogress/layouts',
            get_string('layouts', 'block_managerprogress'),
            get_string('layouts_help', 'block_managerprogress'),
            $choices,
            $choices));

   unset ($choices);

}
