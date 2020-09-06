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
 * This file contains the backup task for the amcat module
 *
 * @package     mod_amcat
 * @category    backup
 * @copyright   2010 Sam Hemelryk
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/amcat/backup/moodle2/backup_amcat_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the amcat instance
 *
 * @copyright  2010 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_amcat_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the amcat.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_amcat_activity_structure_step('amcat structure', 'amcat.xml'));
    }

    /**
     * Encodes URLs to various amcat scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot.'/mod/amcat','#');

        // Provides the interface for overall authoring of amcats
        $pattern = '#'.$base.'/edit\.php\?id=([0-9]+)#';
        $replacement = '$@amcatEDIT*$1@$';
        $content = preg_replace($pattern, $replacement, $content);

        // Action for adding a question page.  Prints an HTML form.
        $pattern = '#'.$base.'/editpage\.php\?id=([0-9]+)&(amp;)?pageid=([0-9]+)#';
        $replacement = '$@amcatEDITPAGE*$1*$3@$';
        $content = preg_replace($pattern, $replacement, $content);

        // Provides the interface for grading essay questions
        $pattern = '#'.$base.'/essay\.php\?id=([0-9]+)#';
        $replacement = '$@amcatESSAY*$1@$';
        $content = preg_replace($pattern, $replacement, $content);

        // Provides the interface for viewing the report
        $pattern = '#'.$base.'/report\.php\?id=([0-9]+)#';
        $replacement = '$@amcatREPORT*$1@$';
        $content = preg_replace($pattern, $replacement, $content);

        // This file plays the mediafile set in amcat settings.
        $pattern = '#'.$base.'/mediafile\.php\?id=([0-9]+)#';
        $replacement = '$@amcatMEDIAFILE*$1@$';
        $content = preg_replace($pattern, $replacement, $content);

        // This page lists all the instances of amcat in a particular course
        $pattern = '#'.$base.'/index\.php\?id=([0-9]+)#';
        $replacement = '$@amcatINDEX*$1@$';
        $content = preg_replace($pattern, $replacement, $content);

        // This page prints a particular page of amcat
        $pattern = '#'.$base.'/view\.php\?id=([0-9]+)&(amp;)?pageid=([0-9]+)#';
        $replacement = '$@amcatVIEWPAGE*$1*$3@$';
        $content = preg_replace($pattern, $replacement, $content);

        // Link to one amcat by cmid
        $pattern = '#'.$base.'/view\.php\?id=([0-9]+)#';
        $replacement = '$@amcatVIEWBYID*$1@$';
        $content = preg_replace($pattern, $replacement, $content);

        // Return the now encoded content
        return $content;
    }
}
