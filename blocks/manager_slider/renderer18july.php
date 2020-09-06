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
 * Renderer definitions for block course slider.
 *
 * @package block_manager_slider
 * @copyright 2016 Kyriaki Hadjicosta (Coventry University)
 * @copyright 2017 Manoj Solanki (Coventry University)
 * @copyright
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
defined ( 'MOODLE_INTERNAL' ) || die ();

/**
 * Course Slider renderer implementation.
 *
 * @package block_course_slider
 * @copyright 2016 Kyriaki Hadjicosta (Coventry University)
 * @copyright 2017 Manoj Solanki (Coventry University)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_manager_slider_renderer extends plugin_renderer_base {

    /**
     * Returns the css file in link tag.
     *
     * @param string $filerelativepath
     * @return string
     */
    public function block_manager_slider_css_file_as_html($filerelativepath) {
        global $CFG;
        return '<link rel="stylesheet" type="text/css" href="' . new moodle_url ( $CFG->wwwroot . $filerelativepath ) . '" />';
    }

    /**
     * Returns the js file in script tag.
     *
     * @param string $filerelativepath
     * @return string
     */
    public function block_manager_slider_js_file_as_html($filerelativepath) {
        global $CFG;
        return '<script type="text/javascript" src="' . new moodle_url ( $CFG->wwwroot . $filerelativepath ) . '"></script>';
    }

    /**
     * Returns the course slider and the navigation gallery css in style tag.
     *
     * @param string $instancecss
     * @return string
     */
    public function block_manager_slider_instance_css_as_html($instancecss) {
        return html_writer::tag ( 'style', $instancecss );
    }

    /**
     * Returns the course slider and the navigation gallery css.
     *
     * @param string   $instancecssid
     * @param stdClass $displayoptionscss
     * @return string
     */
    public function block_manager_slider_add_instance_css($instancecssid, $displayoptionscss) {
        $instancecss = '';

        $backgroundcolorcss = 'background-color:' . $displayoptionscss->backgroundcolor . ';';

        $colorcss = 'color:' . $displayoptionscss->color . ';';

        $bordercolorcsscourse = 'border-color:' . $displayoptionscss->backgroundcolor . ';';
        $bordercolorcssname = 'border-color:' . $displayoptionscss->color . ';';

        $borderradiuscss = 'border-radius:' . $displayoptionscss->borderradius . ';';
        $borderstylecss = 'border-style:' . $displayoptionscss->borderstyle . ';';
        $borderwidthcss = 'border-width:' . $displayoptionscss->borderwidth . ';';

        $imagedivheightcss = 'height:' . $displayoptionscss->imagedivheight . 'px;';
        $imagedivheightgallerycss = 'height:' . ($displayoptionscss->imagedivheight / 2) . 'px;';

        $darkerbackgroundcolor = $this->block_manager_slider_alter_brightness ( $displayoptionscss->backgroundcolor, - 50 );

        $backgroundcolorcssbutton = 'background-color:' . $darkerbackgroundcolor . ';';

        $boxshadowcss = 'box-shadow: 0.06em 0.06em 0.06em 0.06em ' . $darkerbackgroundcolor . ';';

        // Add css image height.
        $instancecss .= "\n" . $instancecssid . ' .courseslider-course-image-div{' . $imagedivheightcss . '}';
        $instancecss .= "\n" . $instancecssid . '-nav' . ' .courseslider-course-image-div{' . $imagedivheightgallerycss . '}';

        // Add css for slick-arrow.
        $instancecss .= "\n" . $instancecssid . ' .slick-arrow{' . $backgroundcolorcssbutton . '}';
        $instancecss .= "\n" . $instancecssid . '-nav' . ' .slick-arrow{' . $backgroundcolorcssbutton . '}';

        // Add css for .slick-prev:before, .slick-next:before.
        $fontfamily = 'font-family: FontAwesome;';
        $navigationarrowprevcss = 'content:\'\\' . $displayoptionscss->navigationarrowprev . '\';';
        $navigationarrownextcss = 'content:\'\\' . $displayoptionscss->navigationarrownext . '\';';
        $instancecss .= "\n" . $instancecssid . ' .slick-prev:before{' . $fontfamily . $navigationarrowprevcss . ';}';
        $instancecss .= "\n" . $instancecssid . ' .slick-next:before{' . $fontfamily . $navigationarrownextcss . ';}';
        $instancecss .= "\n" . $instancecssid . '-nav' . ' .slick-prev:before{' . $fontfamily . $navigationarrowprevcss . ';}';
        $instancecss .= "\n" . $instancecssid . '-nav' . ' .slick-next:before{' . $fontfamily . $navigationarrownextcss . ';}';

        // Add css for courseslider and courseslider-nav border color. Leave it to user to make it visible
        // by editing the custom css of the instance.
        $instancecss .= "\n" . $instancecssid . '{' . $bordercolorcsscourse . '}';
        $instancecss .= "\n" . $instancecssid . '-nav' . '{' . $bordercolorcsscourse . '}';

        // Add css for courseslider-course,courseslider-course:hover,courseslider-course-nav.
        $instancecoursecss = $bordercolorcsscourse . $borderradiuscss . $borderstylecss . $borderwidthcss . $boxshadowcss;
        $instancecss .= "\n" . $instancecssid . ' .courseslider-course{' . $instancecoursecss . '}';
        $instancecss .= "\n" . $instancecssid . ' .courseslider-course:hover{' . $backgroundcolorcss . ' color: red !important;}';
        $instancecss .= "\n" . $instancecssid . '-nav' . ' .courseslider-course-nav{' . $instancecoursecss . '}';

        $instancecss .= "\n" . $instancecssid . ' .enrolllink{ background-color: white;  color: #ff5200;  border: 2px solid #ff5200; }}';
        $instancecss .= "\n" . $instancecssid . ' .enrolllink:hover{ color: #ff5200 !important; }';
        
        
        


        // Add css for courseslider-course-name.
        $instancenamecss = $backgroundcolorcss . $bordercolorcssname . $colorcss . $borderradiuscss .
                $borderstylecss . $borderwidthcss;
        $instancecss .= "\n" . $instancecssid . ' .courseslider-course-name{' . $instancenamecss . '}';

        // Add css for courseslider-course-summary.
        $instancesummarycss = $colorcss;
        $instancecss .= "\n" . $instancecssid . ' .courseslider-course-summary{' . $instancesummarycss . '}';

        return $instancecss;
    }

    /**
     * Returns the course slider and the navigation gallery one after the other in division tag.
     *
     * @param array        $mappedcourses
     * @param array        $coursesorder
     * @param string       $instancecssid
     * @param stdClass     $displayoptionshtml
     * @return string
     */
    public function block_manager_slider_courseslider_as_html($mappedcourses, $coursesorder, $instancecssid, $displayoptionshtml) {
        global $CFG;
        $courseslider = '';
        $courseslidernav = '';

        $coursehtml = '';
        $coursenavhtml = '';

        $coursepictures = '';
        $coursesummary = '';
        $coursename = '';
        $courseurl = '';
        $coursecount = 0;

        foreach ($coursesorder as $id) {
            if (array_key_exists ( $id, $mappedcourses )) {
                $course = $mappedcourses [$id];

                $coursehtml = '';
                $coursenavhtml = '';

                $courseid = 'courseslidercourse' . $instancecssid . $coursecount;
                $courseurl = new moodle_url ( '/course/view.php', array (
                        'id' => $course->id
                ) );

                $course = new course_in_list ( $course );

                // Add Course overview images in content.
                $coursepictures = $this->block_manager_slider_pictures_as_html ( $course );
                $coursehtml .= $coursepictures;
                if ($displayoptionshtml->navigationgalleryflag) {
                    $coursenavhtml .= $coursepictures;
                }

                // Add course summary in content.
                if ($displayoptionshtml->coursesummaryflag && $course->has_summary ()) {
                    $coursesummary = strip_tags ( $course->summary );

                    $coursehtml .= $this->block_manager_slider_summary_as_html ( $coursesummary );
                }

                // Add course name in content.
                if ($displayoptionshtml->coursenameflag) {
                    $coursename = strip_tags ( $course->fullname );

                    $coursehtml .= $this->block_manager_slider_name_as_html ( $coursename,  $course->id);
                    if ($displayoptionshtml->navigationgalleryflag) {
                        $coursenavhtml .= $this->block_manager_slider_name_nav_as_html ( $coursename, $courseurl );
                    }
                }

                $coursehtml = html_writer::tag ( 'div', $coursehtml, array (
                        'class' => 'courseslider-course',
                        'id' => $courseid
                ) );
                if ($displayoptionshtml->navigationgalleryflag) {
                    $coursenavhtml = html_writer::tag ( 'div', $coursenavhtml, array (
                            'class' => 'courseslider-course-nav'
                    ) );
                }

                // Enclose the course in anchor.
                $coursehtml = html_writer::link ( $courseurl, $coursehtml, array (
                        'class' => 'courseslider-course-anchor'
                ) );

                $coursecount ++;

                $courseslider .= $coursehtml;
                if ($displayoptionshtml->navigationgalleryflag) {
                    $courseslidernav .= $coursenavhtml;
                }
            }
        }

        $courseslider = html_writer::tag ( 'div', $courseslider, array (
                'class' => 'courseslider',
                'id' => 'courseslider' . $instancecssid,
                'data-navigationgallery' => $displayoptionshtml->navigationgalleryflag,
                'data-numberofslides' => $displayoptionshtml->numberofslides,
                'data-centermode' => $displayoptionshtml->centermodeflag,
                'data-navigationoption' => $displayoptionshtml->navigationoptions,
                'data-autoplayspeed' => $displayoptionshtml->autoplayspeed
        ) );
        if ($displayoptionshtml->navigationgalleryflag) {
            $courseslidernav = html_writer::tag ( 'div', $courseslidernav, array (
                    'class' => 'courseslider-nav',
                    'id' => 'courseslider' . $instancecssid . '-nav',
                    'data-navigationgallery' => $displayoptionshtml->navigationgalleryflag,
                    'data-numberofslides' => $displayoptionshtml->numberofslides,
                    'data-centermode' => $displayoptionshtml->centermodeflag,
                    'data-navigationoption' => $displayoptionshtml->navigationoptions,
                    'data-autoplayspeed' => $displayoptionshtml->autoplayspeed
            ) );
        }

        return $courseslider . $courseslidernav;
    }

    /**
     * Returns name of course in paragraph tag for the course slider.
     *
     * @param string $coursename
     * @return string
     */
    public function block_manager_slider_name_as_html($coursename , $courseid = 1) {
        $coursenamehtml = '';
        global $CFG;
        $enrolllink = $CFG->wwwroot.'/user/index.php?id='.$courseid;
        
        $coursenamehtml .= html_writer::link ( $enrolllink, 'Enroll', array (
                        'class' => 'btn btn-p enrolllink',
                ) );

        $coursenamehtml .= html_writer::tag ( 'p', $coursename, array (
                'class' => 'courseslider-course-name courseslider-truncate'
        ) );

        return $coursenamehtml;
    }

    /**
     * Returns name of course in anchor tag for the navigation gallery.
     *
     * @param string     $coursename
     * @param moodle_url $courseurl
     * @return string
     */
    public function block_manager_slider_name_nav_as_html($coursename, $courseurl) {
        $coursenamehtmlnav = '';

        $coursenamehtmlnav .= html_writer::link ( $courseurl, $coursename, array (
                'class' => 'courseslider-course-name courseslider-truncate'
        ) );

        return $coursenamehtmlnav;
    }

    /**
     * Returns summary of course in paragraph tag.
     *
     * @param string $coursesummary
     * @return string
     */
    public function block_manager_slider_summary_as_html($coursesummary) {
        $coursesummaryhtml = '';
        $summarydisplay = '';

        $coursesummaryhtml .= html_writer::tag ( 'p', $coursesummary, array (
                'class' => 'courseslider-course-summary courseslider-truncate'
        ) );

        return $coursesummaryhtml;
    }

    /**
     * Returns all pictures in manager one after the other, each in image tag.
     *
     * @param manager $course
     * @return string
     */
    public function block_manager_slider_pictures_as_html($course) {
        include(dirname ( __FILE__ ) . BLOCK_MANAGER_SLIDER_DEFINITIONS);
        global $CFG;
        $picturesrc = '';
        $coursepictures = '';
        foreach ($course->get_course_overviewfiles () as $file) {
            $isimage = $file->is_valid_image ();
            $picturesrc = file_encode_url ( "$CFG->wwwroot/pluginfile.php", '/' .
                    $file->get_contextid () . '/' . $file->get_component () . '/' . $file->get_filearea () .
                    $file->get_filepath () . $file->get_filename (), ! $isimage );
            if ($isimage) {
                $coursepictures .= html_writer::empty_tag ( 'img', array (
                        'src' => $picturesrc,
                        'class' => 'courseslider-course-image'
                ) );
            }
        }

        if (empty ( $coursepictures )) {

            $context = context_system::instance ();
            $fs = get_file_storage ();
            $files = $fs->get_area_files ( $context->id, 'block_manager_slider', 'defaultimage', false, '', false );
            $file = reset ( $files );

            // If a default picture has been uploaded in settings, retrieve it.
            if ($file) {
                $defaultimageurl = moodle_url::make_pluginfile_url ( $file->get_contextid (),
                        $file->get_component (), $file->get_filearea (), $file->get_itemid (), $file->get_filepath (),
                        $file->get_filename () );
            } else {
                // If no default image is set in config, add manually from one in plugin directory.
                $defaultimageurl = $CFG->wwwroot . '/blocks/manager_slider/pix/course-slider-default-picture.png';
            }

            $coursepictures .= html_writer::empty_tag ( 'img', array (
                    'src' => $defaultimageurl,
                    'class' => 'courseslider-course-image'
            ) );
        }

       return $hoverhtml =  html_writer::tag ( 'div', $coursepictures, array (
                'class' => 'courseslider-course-image-div'
        ) );
    }

    /**
     * Returns a colour in HEX format.
     *
     * @param string $colourstr
     * @param string $steps
     * @return string
     */
    public function block_manager_slider_alter_brightness($colourstr, $steps) {
        $colourstr = str_replace ( '#', '', $colourstr );

        $rhex = substr ( $colourstr, 0, 2 );
        $ghex = substr ( $colourstr, 2, 2 );
        $bhex = substr ( $colourstr, 4, 2 );

        $r = hexdec ( $rhex );
        $g = hexdec ( $ghex );
        $b = hexdec ( $bhex );

        $r = dechex ( max ( 0, min ( 255, $r + $steps ) ) );
        $g = dechex ( max ( 0, min ( 255, $g + $steps ) ) );
        $b = dechex ( max ( 0, min ( 255, $b + $steps ) ) );

        $r = str_pad ( $r, 2, "0" );
        $g = str_pad ( $g, 2, "0" );
        $b = str_pad ( $b, 2, "0" );

        $rgbhex = '#' . $r . $g . $b;

        return $rgbhex;
    }
}