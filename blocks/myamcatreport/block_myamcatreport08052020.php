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
 * Starred courses block.
 *
 * @package   block_myamcatreport
 * @copyright 2018 Simey Lameze <simey@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Starred courses block definition class.
 *
 * @package   block_myamcatreport
 * @copyright 2018 Simey Lameze <simey@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_myamcatreport extends block_base {

    /**
     * Initialises the block.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_myamcatreport');
    }

    /**
     * Gets the block contents.
     *
     * @return string The block HTML.
     */
    public function get_content() {
         global $DB, $USER;
        if ($this->content !== null) {
            return $this->content;
        }

        $sqlenrolledcourses = "SELECT c.id as courseid, c.fullname, u.id
            FROM {course} c
            JOIN {context} ct ON c.id = ct.instanceid
            JOIN {role_assignments} ra ON ra.contextid = ct.id
            JOIN {user} u ON u.id = ra.userid
            JOIN {role} r ON r.id = ra.roleid
            where u.id = ?";
        $mycoures = $DB->get_records_sql($sqlenrolledcourses, array($USER->id));
        $defaultcourses = 0;
        if(!empty($mycoures)){
            $defaultcourses = current($mycoures)->courseid;
        }

        $courseid = isset($_GET['course'])? $_GET['course'] : $defaultcourses;
       // echo  $courseid; exit();

        // $renderable = new \block_myamcatreport\output\main();
        $renderer = $this->page->get_renderer('block_myamcatreport');
        
       // $courseid = 2;
       
        //$sql = "select cm.id, m.name, cm.instance from {modules} m, {course_modules} cm where cm.module=m.id and cm.course = ? ";
        
        $sql = "SELECT am.name, gg.finalgrade FROM {grade_items} as gi 
            LEFT JOIN  {amcat} as am on gi.iteminstance = am.id 
            LEFT JOIN {grade_grades} as gg ON gi.id = gg.itemid
            where  itemmodule='amcat' and courseid = ? and gg.userid = ?";

        $res_mycat = $DB->get_records_sql($sql, array($courseid, $USER->id));

        $sqlquiz = "SELECT am.name,  gg.finalgrade FROM {grade_items} as gi 
                LEFT JOIN {quiz} as am on gi.iteminstance = am.id 
                LEFT JOIN {grade_grades} as gg ON gi.id = gg.itemid 
                where itemmodule='quiz' and courseid = ? and gg.userid = ?";

        $res_quiz = $DB->get_records_sql($sqlquiz, array($courseid, $USER->id));

        $res = array_merge($res_mycat,$res_quiz);

        $coursemoduel = array();
        $finalgrade = array();
        foreach ($res as $key => $value) {
            array_push($coursemoduel, $value->name); 
            array_push($finalgrade, $value->finalgrade); 
        }
        //print_object($coursemoduel); exit();
        $chart = new \core\chart_bar(); // Create a bar chart instance.
        $series1 = new \core\chart_series('Score', $finalgrade);
        $chart->add_series($series1);
        $chart->set_labels($coursemoduel);
        


        $this->content->text = '<button type="button" class="btn btn-info btn-lg" data-toggle="modal" style="background-color:#875692; color: #fff; border: 1px solid #875692;float: right;" data-target="#chart_model">Show Data</button>';
        $chtml = $this->chart_model($coursemoduel, $finalgrade);
        
        $this->content->text .= $renderer->render($chart);
        $this->content->text .= $chtml;
        return $this->content;
    }


    public function chart_model($coursemoduel, $finalgrade){

        $model = '<div id="chart_model" class="modal fade" role="dialog" >
          <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content">
              <div class="modal-header">  <h4 class="modal-title float-left">My Assessments Data</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
              </div>
              <div class="modal-body">
                    
                ';

                if(!empty($coursemoduel)){

                    $model .= '<table style="width:100%; padding:10px;"> <tr> <td> <b> Activity Name </b> </td> <td> <b> Score </b>  </td>  </tr>';
                    
                    foreach ($coursemoduel as $key => $value) {
                        $model .= '<tr>';
                        
                        $model .= '<td>';
                        $model .= $value;
                        $model .= '</td>';

                        $model .= '<td>';
                        $model .= $finalgrade[$key];
                        $model .= '</td>';
                        
                        $model .= '</tr>';
                    }

                    $model .= '</table>';
                 } else {
                    $model .= '<p> There is no records... </p>';
                 }
                    

             $model .=  ' </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
              </div>
            </div>

          </div>
        </div>';


        return $model;
    }
    /**
     * Locations where block can be displayed.
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my' => true);
    }

    /**
     * Allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Return the plugin config settings for external functions.
     *
     * @return stdClass the configs for both the block instance and plugin
     * @since Moodle 3.8
     */
    public function get_config_for_external() {
        // Return all settings for all users since it is safe (no private keys, etc..).
        $configs = get_config('block_myamcatreport');

        return (object) [
            'instance' => new stdClass(),
            'plugin' => $configs,
        ];
    }
}
