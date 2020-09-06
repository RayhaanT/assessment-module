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
 * Restore date tests.
 *
 * @package    mod_amcat
 * @copyright  2017 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . "/phpunit/classes/restore_date_testcase.php");

/**
 * Restore date tests.
 *
 * @package    mod_amcat
 * @copyright  2017 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_amcat_restore_date_testcase extends restore_date_testcase {

    /**
     * Creates an attempt for the given userwith a correct or incorrect answer and optionally finishes it.
     *
     * TODO This api can be better extracted to a generator.
     *
     * @param  stdClass $amcat  amcat object.
     * @param  stdClass $page    page object.
     * @param  boolean $correct  If the answer should be correct.
     * @param  boolean $finished If we should finish the attempt.
     *
     * @return array the result of the attempt creation or finalisation.
     */
    protected function create_attempt($amcat, $page, $correct = true, $finished = false) {
        global $DB, $USER;

        // First we need to launch the amcat so the timer is on.
        mod_amcat_external::launch_attempt($amcat->id);

        $DB->set_field('amcat', 'feedback', 1, array('id' => $amcat->id));
        $DB->set_field('amcat', 'progressbar', 1, array('id' => $amcat->id));
        $DB->set_field('amcat', 'custom', 0, array('id' => $amcat->id));
        $DB->set_field('amcat', 'maxattempts', 3, array('id' => $amcat->id));

        $answercorrect = 0;
        $answerincorrect = 0;
        $p2answers = $DB->get_records('amcat_answers', array('amcatid' => $amcat->id, 'pageid' => $page->id), 'id');
        foreach ($p2answers as $answer) {
            if ($answer->jumpto == 0) {
                $answerincorrect = $answer->id;
            } else {
                $answercorrect = $answer->id;
            }
        }

        $data = array(
            array(
                'name' => 'answerid',
                'value' => $correct ? $answercorrect : $answerincorrect,
            ),
            array(
                'name' => '_qf__amcat_display_answer_form_truefalse',
                'value' => 1,
            )
        );
        $result = mod_amcat_external::process_page($amcat->id, $page->id, $data);
        $result = external_api::clean_returnvalue(mod_amcat_external::process_page_returns(), $result);

        // Create attempt.
        $newpageattempt = [
            'amcatid' => $amcat->id,
            'pageid' => $page->id,
            'userid' => $USER->id,
            'answerid' => $answercorrect,
            'retry' => 1,   // First attempt is always 0.
            'correct' => 1,
            'useranswer' => '1',
            'timeseen' => time(),
        ];
        $DB->insert_record('amcat_attempts', (object) $newpageattempt);

        if ($finished) {
            $result = mod_amcat_external::finish_attempt($amcat->id);
            $result = external_api::clean_returnvalue(mod_amcat_external::finish_attempt_returns(), $result);
        }
        return $result;
    }

    /**
     * Test restore dates.
     */
    public function test_restore_dates() {
        global $DB, $USER;

        // Create amcat data.
        $record = ['available' => 100, 'deadline' => 100, 'timemodified' => 100];
        list($course, $amcat) = $this->create_course_and_module('amcat', $record);
        $amcatgenerator = $this->getDataGenerator()->get_plugin_generator('mod_amcat');
        $page = $amcatgenerator->create_content($amcat);
        $page2 = $amcatgenerator->create_question_truefalse($amcat);
        $this->create_attempt($amcat, $page2, true, true);

        $timer = $DB->get_record('amcat_timer', ['amcatid' => $amcat->id]);
        // amcat grade.
        $timestamp = 100;
        $grade = new stdClass();
        $grade->amcatid = $amcat->id;
        $grade->userid = $USER->id;
        $grade->grade = 8.9;
        $grade->completed = $timestamp;
        $grade->id = $DB->insert_record('amcat_grades', $grade);

        // User override.
        $override = (object)[
            'amcatid' => $amcat->id,
            'groupid' => 0,
            'userid' => $USER->id,
            'sortorder' => 1,
            'available' => 100,
            'deadline' => 200
        ];
        $DB->insert_record('amcat_overrides', $override);

        // Set time fields to a constant for easy validation.
        $DB->set_field('amcat_pages', 'timecreated', $timestamp);
        $DB->set_field('amcat_pages', 'timemodified', $timestamp);
        $DB->set_field('amcat_answers', 'timecreated', $timestamp);
        $DB->set_field('amcat_answers', 'timemodified', $timestamp);
        $DB->set_field('amcat_attempts', 'timeseen', $timestamp);

        // Do backup and restore.
        $newcourseid = $this->backup_and_restore($course);
        $newamcat = $DB->get_record('amcat', ['course' => $newcourseid]);

        $this->assertFieldsNotRolledForward($amcat, $newamcat, ['timemodified']);
        $props = ['available', 'deadline'];
        $this->assertFieldsRolledForward($amcat, $newamcat, $props);

        $newpages = $DB->get_records('amcat_pages', ['amcatid' => $newamcat->id]);
        $newanswers = $DB->get_records('amcat_answers', ['amcatid' => $newamcat->id]);
        $newgrade = $DB->get_record('amcat_grades', ['amcatid' => $newamcat->id]);
        $newoverride = $DB->get_record('amcat_overrides', ['amcatid' => $newamcat->id]);
        $newtimer = $DB->get_record('amcat_timer', ['amcatid' => $newamcat->id]);
        $newattempt = $DB->get_record('amcat_attempts', ['amcatid' => $newamcat->id]);

        // Page time checks.
        foreach ($newpages as $newpage) {
            $this->assertEquals($timestamp, $newpage->timemodified);
            $this->assertEquals($timestamp, $newpage->timecreated);
        }

        // Page answers time checks.
        foreach ($newanswers as $newanswer) {
            $this->assertEquals($timestamp, $newanswer->timemodified);
            $this->assertEquals($timestamp, $newanswer->timecreated);
        }

        // amcat override time checks.
        $diff = $this->get_diff();
        $this->assertEquals($override->available + $diff, $newoverride->available);
        $this->assertEquals($override->deadline + $diff, $newoverride->deadline);

        // amcat grade time checks.
        $this->assertEquals($timestamp, $newgrade->completed);

        // amcat timer time checks.
        $this->assertEquals($timer->starttime, $newtimer->starttime);
        $this->assertEquals($timer->amcattime, $newtimer->amcattime);

        // amcat attempt time check.
        $this->assertEquals($timestamp, $newattempt->timeseen);
    }
}
