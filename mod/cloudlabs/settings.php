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
 * cloudlabs module admin settings and defaults
 *
 * @package mod_cloudlabs
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
	
	$settings->add(new admin_setting_configtext('cloudlabs/cloudlaburl',
        get_string('cloudlaburl', 'cloudlabs'), get_string('cloudlaburl_desc', 'cloudlabs'), '',PARAM_URL));
		
	$settings->add(new admin_setting_configtext('cloudlabs/cloudlabusername',
        get_string('cloudlabusername', 'cloudlabs'), get_string('cloudlabusername_desc', 'cloudlabs'), ''));
   
    $settings->add(new admin_setting_configtext('cloudlabs/cloudlabpassword',
        get_string('cloudlabpassword', 'cloudlabs'), get_string('cloudlabpassword_desc', 'cloudlabs'), ''));   
}
