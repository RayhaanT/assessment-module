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
 *
 * Settings for Block course slider.
 *
 * @package   block_myteamprogramer_slider
 * @copyright 2016 Kyriaki Hadjicosta (Coventry University)
 * @copyright 2017 Manoj Solanki (Coventry University)
 * @copyright
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/blocks/myteamprogramer_slider/lib.php');
require(dirname(__FILE__).BLOCK_MYTEAMPROGRAMER_SLIDER_DEFINITIONS);

if ($ADMIN->fulltree) {

    // Course slider customjsfile.
    $name = 'block_myteamprogramer_slider/customjsfile';
    $title = get_string('customjsfile', BLOCK_MYTEAMPROGRAMER_SLIDER_CLASSNAME);
    $description = get_string('customjsfiledesc', BLOCK_MYTEAMPROGRAMER_SLIDER_CLASSNAME);
    $defaultvalue = $defaultblocksettings['customjsfile'];
    $settings->add(new admin_setting_configtext($name, $title, $description, $defaultvalue));

    // Course slider customcssfile.
    $name = 'block_myteamprogramer_slider/customcssfile';
    $title = get_string('customcssfile', BLOCK_MYTEAMPROGRAMER_SLIDER_CLASSNAME);
    $description = get_string('customcssfiledesc', BLOCK_MYTEAMPROGRAMER_SLIDER_CLASSNAME);
    $defaultvalue = $defaultblocksettings['customcssfile'];
    $settings->add(new admin_setting_configtext($name, $title, $description, $defaultvalue));

    // Course slider background color.
    $name = 'block_myteamprogramer_slider/backgroundcolor';
    $title = get_string('backgroundcolor', BLOCK_MYTEAMPROGRAMER_SLIDER_CLASSNAME);
    $description = get_string('backgroundcolordesc', BLOCK_MYTEAMPROGRAMER_SLIDER_CLASSNAME);
    $defaultvalue = $defaultblocksettings['backgroundcolor'];
    $previewconfig = null;
    $settings->add(new admin_setting_configcolourpicker($name, $title, $description, $defaultvalue, $previewconfig));

    // Course slider color.
    $name = 'block_myteamprogramer_slider/color';
    $title = get_string('color', BLOCK_MYTEAMPROGRAMER_SLIDER_CLASSNAME);
    $description = get_string('colordesc', BLOCK_MYTEAMPROGRAMER_SLIDER_CLASSNAME);
    $defaultvalue = $defaultblocksettings['color'];
    $previewconfig = null;
    $settings->add(new admin_setting_configcolourpicker($name, $title, $description, $defaultvalue, $previewconfig));

    // Default course image.
    $name = 'block_myteamprogramer_slider/defaultimage';
    $title = get_string('defaultimage', BLOCK_MYTEAMPROGRAMER_SLIDER_CLASSNAME);
    $description = get_string('defaultimagedesc', BLOCK_MYTEAMPROGRAMER_SLIDER_CLASSNAME);
    $settings->add(new admin_setting_configstoredfile($name, $title, $description, 'defaultimage'));

}
