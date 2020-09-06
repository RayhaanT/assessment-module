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
 * locallib tests.
 *
 * @package    mod_amcat
 * @category   test
 * @copyright  2016 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/mod/amcat/locallib.php');

/**
 * locallib testcase.
 *
 * @package    mod_amcat
 * @category   test
 * @copyright  2016 Adrian Greeve <adrian@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_amcat_locallib_testcase extends advanced_testcase {

    /**
     * Test duplicating a amcat page element.
     */
    public function test_duplicate_page() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $amcatmodule = $this->getDataGenerator()->create_module('amcat', array('course' => $course->id));
        // Convert to a amcat object.
        $amcat = new amcat($amcatmodule);

        // Set up a generator to create content.
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_amcat');
        $tfrecord = $generator->create_question_truefalse($amcat);
        $amcat->duplicate_page($tfrecord->id);

        // amcat pages.
        $records = $DB->get_records('amcat_pages', array('qtype' => 2));
        $sameelements = array('amcatid', 'qtype', 'qoption', 'layout', 'display', 'title', 'contents', 'contentsformat');
        $baserecord = array_shift($records);
        $secondrecord = array_shift($records);
        foreach ($sameelements as $element) {
            $this->assertEquals($baserecord->$element, $secondrecord->$element);
        }
        // Need amcat answers as well.
        $baserecordanswers = array_values($DB->get_records('amcat_answers', array('pageid' => $baserecord->id)));
        $secondrecordanswers = array_values($DB->get_records('amcat_answers', array('pageid' => $secondrecord->id)));
        $sameanswerelements = array('amcatid', 'jumpto', 'grade', 'score', 'flags', 'answer', 'answerformat', 'response',
                'responseformat');
        foreach ($baserecordanswers as $key => $baseanswer) {
            foreach ($sameanswerelements as $element) {
                $this->assertEquals($baseanswer->$element, $secondrecordanswers[$key]->$element);
            }
        }
    }

    /**
     * Test test_amcat_get_user_deadline().
     */
    public function test_amcat_get_user_deadline() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $basetimestamp = time(); // The timestamp we will base the enddates on.

        // Create generator, course and amcats.
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $student3 = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $amcatgenerator = $this->getDataGenerator()->get_plugin_generator('mod_amcat');

        // Both amcats close in two hours.
        $amcat1 = $amcatgenerator->create_instance(array('course' => $course->id, 'deadline' => $basetimestamp + 7200));
        $amcat2 = $amcatgenerator->create_instance(array('course' => $course->id, 'deadline' => $basetimestamp + 7200));
        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));

        $student1id = $student1->id;
        $student2id = $student2->id;
        $student3id = $student3->id;
        $teacherid = $teacher->id;

        // Users enrolments.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($student1id, $course->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($student2id, $course->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($student3id, $course->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($teacherid, $course->id, $teacherrole->id, 'manual');

        // Create groups.
        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $group1id = $group1->id;
        $group2id = $group2->id;
        $this->getDataGenerator()->create_group_member(array('userid' => $student1id, 'groupid' => $group1id));
        $this->getDataGenerator()->create_group_member(array('userid' => $student2id, 'groupid' => $group2id));

        // Group 1 gets an group override for amcat 1 to close in three hours.
        $record1 = (object) [
            'amcatid' => $amcat1->id,
            'groupid' => $group1id,
            'deadline' => $basetimestamp + 10800 // In three hours.
        ];
        $DB->insert_record('amcat_overrides', $record1);

        // Let's test amcat 1 closes in three hours for user student 1 since member of group 1.
        // amcat 2 closes in two hours.
        $this->setUser($student1id);
        $params = new stdClass();

        $comparearray = array();
        $object = new stdClass();
        $object->id = $amcat1->id;
        $object->userdeadline = $basetimestamp + 10800; // The overriden deadline for amcat 1.

        $comparearray[$amcat1->id] = $object;

        $object = new stdClass();
        $object->id = $amcat2->id;
        $object->userdeadline = $basetimestamp + 7200; // The unchanged deadline for amcat 2.

        $comparearray[$amcat2->id] = $object;

        $this->assertEquals($comparearray, amcat_get_user_deadline($course->id));

        // Let's test amcat 1 closes in two hours (the original value) for user student 3 since member of no group.
        $this->setUser($student3id);
        $params = new stdClass();

        $comparearray = array();
        $object = new stdClass();
        $object->id = $amcat1->id;
        $object->userdeadline = $basetimestamp + 7200; // The original deadline for amcat 1.

        $comparearray[$amcat1->id] = $object;

        $object = new stdClass();
        $object->id = $amcat2->id;
        $object->userdeadline = $basetimestamp + 7200; // The original deadline for amcat 2.

        $comparearray[$amcat2->id] = $object;

        $this->assertEquals($comparearray, amcat_get_user_deadline($course->id));

        // User 2 gets an user override for amcat 1 to close in four hours.
        $record2 = (object) [
            'amcatid' => $amcat1->id,
            'userid' => $student2id,
            'deadline' => $basetimestamp + 14400 // In four hours.
        ];
        $DB->insert_record('amcat_overrides', $record2);

        // Let's test amcat 1 closes in four hours for user student 2 since personally overriden.
        // amcat 2 closes in two hours.
        $this->setUser($student2id);

        $comparearray = array();
        $object = new stdClass();
        $object->id = $amcat1->id;
        $object->userdeadline = $basetimestamp + 14400; // The overriden deadline for amcat 1.

        $comparearray[$amcat1->id] = $object;

        $object = new stdClass();
        $object->id = $amcat2->id;
        $object->userdeadline = $basetimestamp + 7200; // The unchanged deadline for amcat 2.

        $comparearray[$amcat2->id] = $object;

        $this->assertEquals($comparearray, amcat_get_user_deadline($course->id));

        // Let's test a teacher sees the original times.
        // amcat 1 and amcat 2 close in two hours.
        $this->setUser($teacherid);

        $comparearray = array();
        $object = new stdClass();
        $object->id = $amcat1->id;
        $object->userdeadline = $basetimestamp + 7200; // The unchanged deadline for amcat 1.

        $comparearray[$amcat1->id] = $object;

        $object = new stdClass();
        $object->id = $amcat2->id;
        $object->userdeadline = $basetimestamp + 7200; // The unchanged deadline for amcat 2.

        $comparearray[$amcat2->id] = $object;

        $this->assertEquals($comparearray, amcat_get_user_deadline($course->id));
    }

    public function test_is_participant() {
        global $USER, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student2 = $this->getDataGenerator()->create_and_enrol($course, 'student', [], 'manual', 0, 0, ENROL_USER_SUSPENDED);
        $amcatmodule = $this->getDataGenerator()->create_module('amcat', array('course' => $course->id));

        // Login as student.
        $this->setUser($student);
        // Convert to a amcat object.
        $amcat = new amcat($amcatmodule);
        $this->assertEquals(true, $amcat->is_participant($student->id),
            'Student is enrolled, active and can participate');

        // Login as student2.
        $this->setUser($student2);
        $this->assertEquals(false, $amcat->is_participant($student2->id),
            'Student is enrolled, suspended and can NOT participate');

        // Login as an admin.
        $this->setAdminUser();
        $this->assertEquals(false, $amcat->is_participant($USER->id),
            'Admin is not enrolled and can NOT participate');

        $this->getDataGenerator()->enrol_user(2, $course->id);
        $this->assertEquals(true, $amcat->is_participant($USER->id),
            'Admin is enrolled and can participate');

        $this->getDataGenerator()->enrol_user(2, $course->id, [], 'manual', 0, 0, ENROL_USER_SUSPENDED);
        $this->assertEquals(true, $amcat->is_participant($USER->id),
            'Admin is enrolled, suspended and can participate');
    }
}
