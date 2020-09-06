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
 * @package block_myteamprogramer_slider
 * @copyright 2016 Kyriaki Hadjicosta (Coventry University)
 * @copyright 2017 Manoj Solanki (Coventry University)
 * @copyright
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/myteamprogramer_slider/lib.php');
require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/pagelib.php');


/**
 * Course Slider block implementation class.
 *
 * @package block_myteamprogramer_slider
 * @copyright 2016 Kyriaki Hadjicosta (Coventry University)
 * @copyright 2017 Manoj Solanki (Coventry University)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_myteamprogramer_slider extends block_base {

    /**
     * Adds title to block instance.
     */
    public function init() {
        $this->title = get_string ( BLOCK_MYTEAMPROGRAMER_SLIDER_BLOCKNAME, BLOCK_MYTEAMPROGRAMER_SLIDER_CLASSNAME );
    }

    /**
     * Calls functions to load js and css and returns block instance content.
     */
    public function get_content() {
        return $this->content;
    }

    /**
     * Generates block instance content.
     */
    public function specialization() {
        global $DB, $CFG;
        include(dirname ( __FILE__ ) . BLOCK_MYTEAMPROGRAMER_SLIDER_DEFINITIONS);

        $content = '';
        $this->content = new stdClass ();
        $this->content->text = '';

        if (isset ( $this->config->title )) {
            $this->title = $this->config->title;
        }

        // Initiate caching.
        $cache = cache::make ( BLOCK_MYTEAMPROGRAMER_SLIDER_CLASSNAME, 'blockdata' );

        // Get system time.
        $timenow = time ();

        // Get instance ID.
        $instanceid = $this->instance->id;

        // Add coursesliderp ID in configuration page.
        $instancecssid = '#courseslider' . $instanceid;

        if (isset ( $this->config->instancecssid )) {
            $this->config->instancecssid = $instancecssid;
        }

        $timetolive = $timenow;

        // This if statement allows users to reset cache by setting it to 0.
        if (isset ( $this->config->cachetime )) {
            $cachetime = $this->config->cachetime;
            // If timetolive has not passed yet, return the cached block content.
            if (intval ( $cachetime ) != 0) {
                if ($timenow <= $cache->get ( 'timetolive' . $instanceid )) {
                    $this->content = $cache->get ( 'blockcontent' . $instanceid );
                    return;
                }
            }

            // Prepare new timetolive.
            $timetolive = $timenow + intval ( $cachetime );
        }

        // Prepare new content.

        $renderer = $this->page->get_renderer ( BLOCK_MYTEAMPROGRAMER_SLIDER_CLASSNAME );
        $displayoptionshtml = new stdClass ();
        $displayoptionscss = new stdClass ();

        if (isset ( $this->config->courses ) && ! empty ( $this->config->courses )) {
            // $courses = $this->config->courses;

            // $courses = trim ( $courses );
            // $courses = explode ( ',', $courses );
            // $coursesorder = $courses;

            // $courses = $DB->get_records_list ( 'course', 'id', $courses );
            global $USER;
            $sqlenrolledcourses = "SELECT c.*
            FROM {course} c
            JOIN {context} ct ON c.id = ct.instanceid
            JOIN {role_assignments} ra ON ra.contextid = ct.id
            JOIN {user} u ON u.id = ra.userid
            JOIN {role} r ON r.id = ra.roleid
            where u.id = ? and  ra.roleid = 1 and c.category != 0";
            $courses = $DB->get_records_sql($sqlenrolledcourses, array($USER->id));
            
            $coursesorder = array();
            
            foreach ($courses as $key => $value) {
                $coursesorder[] = $value->id; 
            }
            // print_object($coursesorder);
            // print_object($courses);
            // exit();



            // Add courses to the content in html format.

            // Prepare CSS display options.
            $displayoptionscss->backgroundcolor = get_config( BLOCK_MYTEAMPROGRAMER_SLIDER_CLASSNAME, 'backgroundcolor' );

            if (empty ( $displayoptionscss->backgroundcolor )) {
                $displayoptionscss->backgroundcolor = $defaultblocksettings ['backgroundcolor'];
            }

            $displayoptionscss->color = get_config ( BLOCK_MYTEAMPROGRAMER_SLIDER_CLASSNAME, 'color' );

            if ((empty ( $displayoptionscss->color ))) {
                $displayoptionscss->color = $defaultblocksettings ['color'];
            }

            if (isset ( $this->config->borderradius )) {
                $displayoptionscss->borderradius = $this->config->borderradius;
            } else {
                $displayoptionscss->borderradius = $defaultblocksettings ['borderradius'];
            }

            if (isset ( $this->config->borderstyle )) {
                $displayoptionscss->borderstyle = $this->config->borderstyle;
            } else {
                $displayoptionscss->borderstyle = $defaultblocksettings ['borderstyle'];
            }

            if (isset ( $this->config->borderwidth )) {
                $displayoptionscss->borderwidth = $this->config->borderwidth;
            } else {
                $displayoptionscss->borderwidth = $defaultblocksettings ['borderwidth'];
            }

            if (isset ( $this->config->imagedivheight )) {
                $displayoptionscss->imagedivheight = intval ( $this->config->imagedivheight );
            } else {
                $displayoptionscss->imagedivheight = $defaultinstancesettings ['imagedivheight'];
            }

            if (empty ( $displayoptionscss->imagedivheight )) {
                $displayoptionscss->imagedivheight = $defaultinstancesettings ['imagedivheight'];
            } else {
                $displayoptionscss->imagedivheight = $displayoptionscss->imagedivheight;
            }

            if (empty ( $displayoptionscss->navigationarrownext )) {
                $displayoptionscss->navigationarrownext = $defaultinstancesettings ['navigationarrownext'];
            }

            $displayoptionscss->navigationarrowprev = $fontawesomeiconunicodes
                    [$fontawesomematchprev[$displayoptionscss->navigationarrownext]];
            $displayoptionscss->navigationarrownext = $fontawesomeiconunicodes[$displayoptionscss->navigationarrownext];

            // Prepare HTML display options.
            if (isset ( $this->config->coursenameflag )) {
                $displayoptionshtml->coursenameflag = (($this->config->coursenameflag == $hiddenvisible['Visible']) ? 1 : 0);
            } else {
                $displayoptionshtml->coursenameflag = ($defaultinstancesettings
                        ['coursenameflag'] == $hiddenvisible['Visible'] ? 1 : 0);
            }

            if (isset ( $this->config->coursesummaryflag )) {
                $displayoptionshtml->coursesummaryflag = (($this->config->coursesummaryflag == $hiddenvisible['Visible']) ? 1 : 0);
            } else {
                $displayoptionshtml->coursesummaryflag = ($defaultinstancesettings
                        ['coursesummaryflag'] == $hiddenvisible['Visible'] ? 1 : 0);
            }

            if (isset ( $this->config->numberofslides )) {
                $displayoptionshtml->numberofslides = $this->config->numberofslides;
            } else {
                $displayoptionshtml->numberofslides = $defaultinstancesettings ['numberofslides'];
            }

            if (isset ( $this->config->navigationgalleryflag )) {
                $displayoptionshtml->navigationgalleryflag = (($this->config->navigationgalleryflag == $onoff ['ON']) ? 1 : 0);
            } else {
                $displayoptionshtml->navigationgalleryflag = ($defaultinstancesettings
                        ['navigationgalleryflag'] == $onoff['ON'] ? 1 : 0);
            }

            if (isset ( $this->config->navigationoptions )) {
                $displayoptionshtml->navigationoptions = $this->config->navigationoptions;
            } else {
                $displayoptionshtml->navigationoptions = $defaultinstancesettings ['navigationoptions'];
            }

            if (isset ( $this->config->centermodeflag )) {
                $displayoptionshtml->centermodeflag = (($this->config->centermodeflag == 'ON') ? 1 : 0);
            } else {
                $displayoptionshtml->centermodeflag = ($defaultinstancesettings['centermodeflag'] == 'ON' ? 1 : 0);
            }

            if (isset ( $this->config->autoplayspeed )) {
                $displayoptionshtml->autoplayspeed = intval ( $this->config->autoplayspeed );
            } else {
                $displayoptionshtml->autoplayspeed = $defaultinstancesettings ['autoplayspeed'];
            }

            if (empty ( $displayoptionshtml->autoplayspeed )) {
                $displayoptionshtml->autoplayspeed = $defaultinstancesettings ['autoplayspeed'] * BLOCK_MYTEAMPROGRAMER_SLIDER_MILLISECONDS;
            } else {
                $displayoptionshtml->autoplayspeed = $this->config->autoplayspeed * BLOCK_MYTEAMPROGRAMER_SLIDER_MILLISECONDS;
            }

            // Generate coursesliders based on configuration settings.
            $content .= $renderer->block_myteamprogramer_slider_courseslider_as_html($courses, $coursesorder,
                        $instanceid, $displayoptionshtml);

            // Add instance css.
            if (isset ( $this->config->instancecssid )) {
                $this->config->instancecssid = $instancecssid;
            }

            $instancecss = $renderer->block_myteamprogramer_slider_add_instance_css ( $instancecssid, $displayoptionscss );
            // Add content found in instance custom css textarea.
            if (isset ( $this->config->instancecustomcsstextarea )) {
                $instancecss .= $this->config->instancecustomcsstextarea;
            }

            $content .= $renderer->block_myteamprogramer_slider_instance_css_as_html ( $instancecss );

            // Notice: js and css files do not load for all instances if are not added in the content of the block as done below.

            // Load css files.
            $content .= $this->block_myteamprogramer_slider_load_css ();

            // Load js files.
            $content .= $this->block_myteamprogramer_slider_load_js ();
        }

        $this->content->text .= $content;

        $cache->set ( 'timetolive' . $instanceid, $timetolive );
        $cache->set ( 'blockcontent' . $instanceid, $this->content );
    }

    /**
     * Allows multiple instances of the block.
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Enables block instnace configuration.
     */
    public function has_config() {
        return true;
    }

    /**
     * Makes block instnace header visible.
     */
    public function hide_header() {
        return false;
    }

    /**
     * Calls functions to load slick slider and course slider css.
     */
    private function block_myteamprogramer_slider_load_css() {
        global $CFG;
        include(dirname ( __FILE__ ) . BLOCK_MYTEAMPROGRAMER_SLIDER_DEFINITIONS);

        $renderer = $this->page->get_renderer ( BLOCK_MYTEAMPROGRAMER_SLIDER_CLASSNAME );

        $css = '';

        // Load slick slider css.
        $css .= $renderer->block_myteamprogramer_slider_css_file_as_html ( $defaultfilerelativepaths ['slickcss'] );
        $css .= $renderer->block_myteamprogramer_slider_css_file_as_html ( $defaultfilerelativepaths ['slickthemecss'] );

        // Load course slider css.
        $css .= $renderer->block_myteamprogramer_slider_css_file_as_html ( $defaultfilerelativepaths ['courseslidercss'] );
        $css .= $renderer->block_myteamprogramer_slider_css_file_as_html ( $defaultfilerelativepaths ['fontawesomecss'] );
        $css .= $renderer->block_myteamprogramer_slider_css_file_as_html ( get_config ( BLOCK_MYTEAMPROGRAMER_SLIDER_BLOCKNAME, 'customcssfile' ) );
        return $css;
    }

    /**
     * Calls functions to load slick slider and course slider js.
     */
    private function block_myteamprogramer_slider_load_js() {
        global $CFG;
        include(dirname ( __FILE__ ) . BLOCK_MYTEAMPROGRAMER_SLIDER_DEFINITIONS);

        $renderer = $this->page->get_renderer ( BLOCK_MYTEAMPROGRAMER_SLIDER_CLASSNAME );

        $js = '';

        // Load slick slider js.
        $js .= $renderer->block_myteamprogramer_slider_js_file_as_html ( $defaultfilerelativepaths ['slickjs'] );

        // Load course slider css.
        $js .= $renderer->block_myteamprogramer_slider_js_file_as_html ( $defaultfilerelativepaths ['fontawesomejs'] );
        $js .= $renderer->block_myteamprogramer_slider_js_file_as_html ( $defaultfilerelativepaths ['coursesliderjs'] );
        $customjs = get_config ( BLOCK_MYTEAMPROGRAMER_SLIDER_BLOCKNAME, 'customjsfile' );

        if ($customjs) {
            $js .= $renderer->block_myteamprogramer_slider_js_file_as_html ( $customjs );
        }

        return $js;
    }
}
