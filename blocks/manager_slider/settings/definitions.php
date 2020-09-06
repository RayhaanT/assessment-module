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
 * @package   block_manager_slider
 * @copyright 2016 Kyriaki Hadjicosta (Coventry University)
 * @copyright
 * @copyright
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

$defaultfilerelativepaths = array(
    'slickcss' => '/blocks/manager_slider/jquery/slick/slick/slick.css',
    'slickthemecss' => '/blocks/manager_slider/jquery/slick/slick/slick-theme.css',
    'fontawesomecss' => '/blocks/manager_slider/style/fontawesome-iconpicker.min.css',
    'courseslidercss' => '/blocks/manager_slider/style/block_manager_slider.css',
    'slickjs' => '/blocks/manager_slider/jquery/slick/slick/slick.js',
    'fontawesomejs' => '/blocks/manager_slider/jquery/fontawesome-iconpicker.min.js',
    'coursesliderjs' => '/blocks/manager_slider/jquery/block_manager_slider.js',
    'picture' => '/blocks/manager_slider/pix/defaultpicture.png',
);

$defaultinstancesettings = array(
    'title' => 'Manager Slider Title',
    'courses' => '',
    'cachetime' => 0,
    'borderwidth' => '1px',
    'borderstyle' => 'solid',
    'borderradius' => '2px',
    'navigationgalleryflag' => 'OFF',
    'navigationoptions' => 'Arrows',
    'numberofslides' => 4,
    'centermodeflag' => 'OFF',
    'autoplayspeed' => 3,
    'coursenameflag' => 'Visible',
    'coursesummaryflag' => 'Visible',
    'instancecsstextarea' => '',
    'navigationarrowprev' => 'fa-angle-double-left',
    'navigationarrownext' => 'fa-angle-double-right',
    'imagedivheight' => 170,
);

$defaultblocksettings = array(
    'customjsfile' => '',
    'customcssfile' => '',
    'backgroundcolor' => '#1177d1',
    'color' => '#FFFFFF',
    'defaultimage' => '/blocks/manager_slider/pix/defaultpicture.png',
);

$fontawesomeiconunicodes = array(
'fa-angle-double-left' => 'f100',
'fa-angle-double-right' => 'f101',
'fa-angle-left' => 'f104',
'fa-angle-right' => 'f105',
'fa-arrow-circle-left' => 'f0a8',
'fa-arrow-circle-right' => 'f0a9',
'fa-arrow-circle-o-left' => 'f190',
'fa-arrow-circle-o-right' => 'f18e',
'fa-arrow-left' => 'f060',
'fa-arrow-right' => 'f061',
'fa-caret-left' => 'f0d9',
'fa-caret-right' => 'f0da',
'fa-caret-square-o-left' => 'f191',
'fa-caret-square-o-right' => 'f152',
'fa-chevron-circle-left' => 'f137',
'fa-chevron-circle-right' => 'f138',
'fa-chevron-left' => 'f053',
'fa-chevron-right' => 'f054',
'fa-long-arrow-left' => 'f177',
'fa-long-arrow-right' => 'f178',
'fa-backward' => 'f04a',
'fa-forward' => 'f04e',
);

$fontawesomematchprev = array(
'fa-angle-double-right' => 'fa-angle-double-left',
'fa-angle-right' => 'fa-angle-left',
'fa-arrow-circle-right' => 'fa-arrow-circle-left',
'fa-arrow-circle-o-right' => 'fa-arrow-circle-o-left',
'fa-arrow-right' => 'fa-arrow-left',
'fa-caret-right' => 'fa-caret-left',
'fa-caret-square-o-right' => 'fa-caret-square-o-left',
'fa-chevron-circle-right' => 'fa-chevron-circle-left',
'fa-chevron-right' => 'fa-chevron-left',
'fa-long-arrow-right' => 'fa-long-arrow-left',
'fa-forward' => 'fa-backward',
);

$hiddenvisible = array(
    'Visible' => 'Visible',
    'Hidden' => 'Hidden'
);

$onoff = array(
    'ON' => 'ON',
    'OFF' => 'OFF'
);

$navigationoptions = array(
    'No navigation' => 'No navigation',
    'Arrows' => 'Arrows',
    'Radio buttons' => 'Radio buttons',
    'Arrows and Radio buttons' => 'Arrows and Radio buttons',
);

$from0to12px = array();
for ($i = 0; $i < 13; $i++) {
    $from0to12px[$i.'px'] = $i.'px';
}

$from0to12 = array();
for ($i = 0; $i < 13; $i++) {
    $from0to12[$i] = $i;
}

$from0to60by5 = array();
for ($i = 0; $i < 61; $i += 5) {
    $from0to60by5[$i] = $i;
}

$borderstyles = array(
    'none' => 'none',
    'solid' => 'solid',
    'dashed' => 'dashed',
    'dotted' => 'dotted',
    'double' => 'double'
);
