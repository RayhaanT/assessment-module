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
 * Settings used by the amcat module, were moved from mod_edit
 *
 * @package mod_amcat
 * @copyright  2009 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 **/

defined('MOODLE_INTERNAL') || die;
/*
if ($ADMIN->fulltree) {
    require_once($CFG->dirroot.'/mod/amcat/locallib.php');
    $yesno = array(0 => get_string('no'), 1 => get_string('yes'));

    // Introductory explanation that all the settings are defaults for the add amcat form.
    $settings->add(new admin_setting_heading('mod_amcat/amcatintro', '', get_string('configintro', 'amcat')));

    // Appearance settings.
    $settings->add(new admin_setting_heading('mod_amcat/appearance', get_string('appearance'), ''));

    // Media file popup settings.
    $setting = new admin_setting_configempty('mod_amcat/mediafile', get_string('mediafile', 'amcat'),
            get_string('mediafile_help', 'amcat'));

    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, true);
    $settings->add($setting);

    $settings->add(new admin_setting_configtext('mod_amcat/mediawidth', get_string('mediawidth', 'amcat'),
            get_string('configmediawidth', 'amcat'), 640, PARAM_INT));

    $settings->add(new admin_setting_configtext('mod_amcat/mediaheight', get_string('mediaheight', 'amcat'),
            get_string('configmediaheight', 'amcat'), 480, PARAM_INT));

    $settings->add(new admin_setting_configcheckbox('mod_amcat/mediaclose', get_string('mediaclose', 'amcat'),
            get_string('configmediaclose', 'amcat'), false, PARAM_TEXT));

    $settings->add(new admin_setting_configselect_with_advanced('mod_amcat/progressbar',
        get_string('progressbar', 'amcat'), get_string('progressbar_help', 'amcat'),
        array('value' => 0, 'adv' => false), $yesno));

    $settings->add(new admin_setting_configselect_with_advanced('mod_amcat/ongoing',
        get_string('ongoing', 'amcat'), get_string('ongoing_help', 'amcat'),
        array('value' => 0, 'adv' => true), $yesno));

    $settings->add(new admin_setting_configselect_with_advanced('mod_amcat/displayleftmenu',
        get_string('displayleftmenu', 'amcat'), get_string('displayleftmenu_help', 'amcat'),
        array('value' => 0, 'adv' => false), $yesno));

    $percentage = array();
    for ($i = 100; $i >= 0; $i--) {
        $percentage[$i] = $i.'%';
    }
    $settings->add(new admin_setting_configselect_with_advanced('mod_amcat/displayleftif',
        get_string('displayleftif', 'amcat'), get_string('displayleftif_help', 'amcat'),
        array('value' => 0, 'adv' => true), $percentage));

    // Slideshow settings.
    $settings->add(new admin_setting_configselect_with_advanced('mod_amcat/slideshow',
        get_string('slideshow', 'amcat'), get_string('slideshow_help', 'amcat'),
        array('value' => 0, 'adv' => true), $yesno));

    $settings->add(new admin_setting_configtext('mod_amcat/slideshowwidth', get_string('slideshowwidth', 'amcat'),
            get_string('configslideshowwidth', 'amcat'), 640, PARAM_INT));

    $settings->add(new admin_setting_configtext('mod_amcat/slideshowheight', get_string('slideshowheight', 'amcat'),
            get_string('configslideshowheight', 'amcat'), 480, PARAM_INT));

    $settings->add(new admin_setting_configtext('mod_amcat/slideshowbgcolor', get_string('slideshowbgcolor', 'amcat'),
            get_string('configslideshowbgcolor', 'amcat'), '#FFFFFF', PARAM_TEXT));

    $numbers = array();
    for ($i = 20; $i > 1; $i--) {
        $numbers[$i] = $i;
    }

    $settings->add(new admin_setting_configselect_with_advanced('mod_amcat/maxanswers',
        get_string('maximumnumberofanswersbranches', 'amcat'), get_string('maximumnumberofanswersbranches_help', 'amcat'),
        array('value' => '5', 'adv' => true), $numbers));

    $settings->add(new admin_setting_configselect_with_advanced('mod_amcat/defaultfeedback',
        get_string('displaydefaultfeedback', 'amcat'), get_string('displaydefaultfeedback_help', 'amcat'),
        array('value' => 0, 'adv' => true), $yesno));

    $setting = new admin_setting_configempty('mod_amcat/activitylink', get_string('activitylink', 'amcat'),
        '');

    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, true);
    $settings->add($setting);

    // Availability settings.
    $settings->add(new admin_setting_heading('mod_amcat/availibility', get_string('availability'), ''));

    $settings->add(new admin_setting_configduration_with_advanced('mod_amcat/timelimit',
        get_string('timelimit', 'amcat'), get_string('configtimelimit_desc', 'amcat'),
            array('value' => '0', 'adv' => false), 60));

    $settings->add(new admin_setting_configcheckbox_with_advanced('mod_amcat/password',
        get_string('password', 'amcat'), get_string('configpassword_desc', 'amcat'),
        array('value' => 0, 'adv' => true)));

    // Flow Control.
    $settings->add(new admin_setting_heading('amcat/flowcontrol', get_string('flowcontrol', 'amcat'), ''));

    $settings->add(new admin_setting_configselect_with_advanced('mod_amcat/modattempts',
        get_string('modattempts', 'amcat'), get_string('modattempts_help', 'amcat'),
        array('value' => 0, 'adv' => false), $yesno));

    $settings->add(new admin_setting_configselect_with_advanced('mod_amcat/displayreview',
        get_string('displayreview', 'amcat'), get_string('displayreview_help', 'amcat'),
        array('value' => 0, 'adv' => false), $yesno));

    $attempts = array();
    for ($i = 10; $i > 0; $i--) {
        $attempts[$i] = $i;
    }

    $settings->add(new admin_setting_configselect_with_advanced('mod_amcat/maximumnumberofattempts',
        get_string('maximumnumberofattempts', 'amcat'), get_string('maximumnumberofattempts_help', 'amcat'),
        array('value' => '1', 'adv' => false), $attempts));

    $defaultnextpages = array();
    $defaultnextpages[0] = get_string("normal", "amcat");
    $defaultnextpages[amcat_UNSEENPAGE] = get_string("showanunseenpage", "amcat");
    $defaultnextpages[amcat_UNANSWEREDPAGE] = get_string("showanunansweredpage", "amcat");

    $settings->add(new admin_setting_configselect_with_advanced('mod_amcat/defaultnextpage',
            get_string('actionaftercorrectanswer', 'amcat'), '',
            array('value' => 0, 'adv' => true), $defaultnextpages));

    $pages = array();
    for ($i = 100; $i >= 0; $i--) {
        $pages[$i] = $i;
    }
    $settings->add(new admin_setting_configselect_with_advanced('mod_amcat/numberofpagestoshow',
        get_string('numberofpagestoshow', 'amcat'), get_string('numberofpagestoshow_help', 'amcat'),
        array('value' => '1', 'adv' => true), $pages));

    // Grade.
    $settings->add(new admin_setting_heading('amcat/grade', get_string('grade'), ''));

    $settings->add(new admin_setting_configselect_with_advanced('mod_amcat/practice',
        get_string('practice', 'amcat'), get_string('practice_help', 'amcat'),
        array('value' => 0, 'adv' => false), $yesno));

    $settings->add(new admin_setting_configselect_with_advanced('mod_amcat/customscoring',
        get_string('customscoring', 'amcat'), get_string('customscoring_help', 'amcat'),
        array('value' => 1, 'adv' => true), $yesno));

    $settings->add(new admin_setting_configselect_with_advanced('mod_amcat/retakesallowed',
        get_string('retakesallowed', 'amcat'), get_string('retakesallowed_help', 'amcat'),
        array('value' => 0, 'adv' => false), $yesno));

    $options = array();
    $options[0] = get_string('usemean', 'amcat');
    $options[1] = get_string('usemaximum', 'amcat');

    $settings->add(new admin_setting_configselect_with_advanced('mod_amcat/handlingofretakes',
        get_string('handlingofretakes', 'amcat'), get_string('handlingofretakes_help', 'amcat'),
        array('value' => 0, 'adv' => true), $options));

    $settings->add(new admin_setting_configselect_with_advanced('mod_amcat/minimumnumberofquestions',
        get_string('minimumnumberofquestions', 'amcat'), get_string('minimumnumberofquestions_help', 'amcat'),
        array('value' => 0, 'adv' => true), $pages));

}
*/