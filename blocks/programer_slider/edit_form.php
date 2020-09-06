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
 * Version details
 *
 * @package   block_programer_slider
 * @copyright 2016 Kyriaki Hadjicosta (Coventry University)
 * @copyright
 * @copyright
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/programer_slider/lib.php');
require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Course Slider edit form implementation class.
 *
 * @package block_programer_slider
 * @copyright 2016 Kyriaki Hadjicosta (Coventry University)
 * @copyright 2017 Manoj Solanki (Coventry University)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_programer_slider_edit_form extends block_edit_form {

    /**
     * Override specific definition to provide course slider instance settings.
     *
     * @param stdClass $mform
     *
     */
    protected function specific_definition($mform) {
        global $CFG;

        include(dirname(__FILE__).BLOCK_PROGRAMER_SLIDER_DEFINITIONS);

        // Section header title according to language file.
        $mform->addElement('header', 'general', get_string('generalconfiguration', BLOCK_PROGRAMER_SLIDER_LANG));

        // Title.
        $mform->addElement('text', 'config_title', get_string('title', BLOCK_PROGRAMER_SLIDER_LANG));
        $mform->setDefault('config_title', $defaultinstancesettings['title']);
        $mform->setType('config_title', PARAM_TEXT);

        // Cache time.
        $mform->addElement('duration', 'config_cachetime', get_string('cachetime', BLOCK_PROGRAMER_SLIDER_LANG));
        $mform->setDefault('config_cachetime', $defaultinstancesettings['cachetime']);
        $mform->setType('config_cachetime', PARAM_INT);

        // Courses.
        $mform->addElement('text', 'config_courses', get_string('courses', BLOCK_PROGRAMER_SLIDER_LANG));
        $mform->setDefault('config_courses', $defaultinstancesettings['courses']);
        $mform->setType('config_courses', PARAM_TEXT);
        $mform->addHelpButton('config_courses', 'courses', BLOCK_PROGRAMER_SLIDER_LANG);

        // Style Configuration.
        $mform->addElement('header', 'optionssection', get_string('styleconfiguration', BLOCK_PROGRAMER_SLIDER_LANG));

        // Border width.
        $mform->addElement('select', 'config_borderwidth', get_string('borderwidth', BLOCK_PROGRAMER_SLIDER_LANG), $from0to12px);
        $mform->setDefault('config_borderwidth', $defaultinstancesettings['borderwidth']);
        $mform->setType('config_borderwidth', PARAM_TEXT);

        // Border style.
        $mform->addElement('select', 'config_borderstyle', get_string('borderstyle', BLOCK_PROGRAMER_SLIDER_LANG), $borderstyles);
        $mform->setDefault('config_borderstyle', $defaultinstancesettings['borderstyle']);
        $mform->setType('config_borderstyle', PARAM_TEXT);

        // Border radius.
        $mform->addElement('select', 'config_borderradius', get_string('borderradius', BLOCK_PROGRAMER_SLIDER_LANG), $from0to12px);
        $mform->setDefault('config_borderradius', $defaultinstancesettings['borderradius']);
        $mform->setType('config_borderradius', PARAM_TEXT);

        // Image height.
        $mform->addElement('text', 'config_imagedivheight', get_string('imagedivheight', BLOCK_PROGRAMER_SLIDER_LANG));
        $mform->setDefault('config_imagedivheight', $defaultinstancesettings['imagedivheight']);
        $mform->setType('config_imagedivheight', PARAM_TEXT);

        // Navigation Configuration.
        $mform->addElement('header', 'optionssection', get_string('navigationconfiguration', BLOCK_PROGRAMER_SLIDER_LANG));

        // Navigation Gallery.
        $mform->addElement('select', 'config_navigationgalleryflag',
                get_string('navigationgalleryflag', BLOCK_PROGRAMER_SLIDER_LANG), $onoff);
        $mform->setDefault('config_navigationgalleryflag', $defaultinstancesettings['navigationgalleryflag']);
        $mform->setType('config_navigationgalleryflag', PARAM_TEXT);

        // Navigation Options.
        $mform->addElement('select', 'config_navigationoptions',
                get_string('navigationoptions', BLOCK_PROGRAMER_SLIDER_LANG), $navigationoptions);
        $mform->setDefault('config_navigationoptions', $defaultinstancesettings['navigationoptions']);
        $mform->setType('config_navigationoptions', PARAM_TEXT);

        // Navigation Arrow Icons.

        $mform->addElement('text', 'config_navigationarrownext', get_string('navigationarrownext', BLOCK_PROGRAMER_SLIDER_LANG));
        $formhtmlnext = '<link href="'.$CFG->wwwroot.'/blocks/programer_slider/style/fontawesome-iconpicker.min.css"' .
                'rel="stylesheet" type="text/css">';
        $formhtmlnext .= '<script type="text/javascript" src="' .
                $CFG->wwwroot.'/blocks/programer_slider/jquery/fontawesome-iconpicker.min.js"></script>';
        $formhtmlnext .= '<script type="text/javascript">$(function(){ $("#id_config_navigationarrownext").' .
                         'iconpicker({placement: "right", selectedCustomClass: "label label-success"}); });</script>';
        $mform->addElement('html', $formhtmlnext);
        $mform->setType('config_navigationarrownext', PARAM_TEXT);

        // Number of slides.
        $mform->addElement('select', 'config_numberofslides', get_string('numberofslides', BLOCK_PROGRAMER_SLIDER_LANG), $from0to12);
        $mform->setDefault('config_numberofslides', $defaultinstancesettings['numberofslides']);
        $mform->setType('config_numberofslides', PARAM_INT);

        // Center mode.
        $mform->addElement('select', 'config_centermodeflag', get_string('centermodeflag', BLOCK_PROGRAMER_SLIDER_LANG), $onoff);
        $mform->setDefault('config_centermodeflag', $defaultinstancesettings['centermodeflag']);
        $mform->setType('config_centermodeflag', PARAM_TEXT);

        // Autoplay speed.
        $mform->addElement('duration', 'config_autoplayspeed', get_string('autoplayspeed', 'block_course_slider'));
        $mform->setDefault('config_autoplayspeed', $defaultinstancesettings['autoplayspeed']);
        $mform->setType('config_autoplayspeed', PARAM_INT);

        // Course configuration.
        $mform->addElement('header', 'optionssection', get_string('courseconfiguration', BLOCK_PROGRAMER_SLIDER_LANG));

        // Course Name.
        $mform->addElement('select', 'config_coursenameflag',
                get_string('coursenameflag', BLOCK_PROGRAMER_SLIDER_LANG), $hiddenvisible);
        $mform->setDefault('config_coursenameflag', $defaultinstancesettings['coursenameflag']);
        $mform->setType('config_coursenameflag', PARAM_TEXT);

        // Course Summary.
        $mform->addElement('select', 'config_coursesummaryflag', get_string('coursesummaryflag', BLOCK_PROGRAMER_SLIDER_LANG),
                $hiddenvisible);
        $mform->setDefault('config_coursesummaryflag', $defaultinstancesettings['coursesummaryflag']);
        $mform->setType('config_coursesummaryflag', PARAM_TEXT);

        // Instance CSS customisation.
        $mform->addElement('header', 'optionssection', get_string('instancecsscustomisation', BLOCK_PROGRAMER_SLIDER_LANG));

        // Instance CSS ID.
        $mform->addElement('static', 'config_instancecssid', get_string('instancecssid', BLOCK_PROGRAMER_SLIDER_LANG));

        // Instance custom CSS.
        $mform->addElement('textarea', 'config_instancecustomcsstextarea',
                get_string('instancecustomcsstextarea', BLOCK_PROGRAMER_SLIDER_LANG));
        $mform->setDefault('config_instancecustomcsstextarea', $defaultinstancesettings['instancecsstextarea']);
        $mform->setType('config_instancecustomcsstextarea', PARAM_TEXT);

    }

}
