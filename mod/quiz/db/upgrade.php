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
 * Upgrade script for the quiz module.
 *
 * @package    mod_quiz
 * @copyright  2006 Eloy Lafuente (stronk7)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Quiz module upgrade function.
 * @param string $oldversion the version we are upgrading from.
 */
function xmldb_quiz_upgrade($oldversion) {
    // global $CFG;

    // // Automatically generated Moodle v3.5.0 release upgrade line.
    // // Put any upgrade step following this.

    // // Automatically generated Moodle v3.6.0 release upgrade line.
    // // Put any upgrade step following this.

    // // Automatically generated Moodle v3.7.0 release upgrade line.
    // // Put any upgrade step following this.

    // // Automatically generated Moodle v3.8.0 release upgrade line.
    // // Put any upgrade step following this.

    // // Automatically generated Moodle v3.9.0 release upgrade line.
    // // Put any upgrade step following this.

    // global $DB;

    // $dbman = $DB->get_manager();

    // // Define fields to be added to question database
    // $table = new xmldb_table('question');
    // $topicField = new xmldb_field('topic', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'idnumber');
    // $difficultyField = new xmldb_field('difficulty', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'topic');
    // $roleField = new xmldb_field('role', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'difficulty');
    // $lifecycleexpiryField = new xmldb_field('lifecycleexpiry', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'role');

    // // Conditionally add new fields
    // if (!$dbman->field_exists($table, $topicField)) {
    //     $dbman->add_field($table, $topicField);
    // }
    // if (!$dbman->field_exists($table, $difficultyField)) {
    //     $dbman->add_field($table, $difficultyField);
    // }
    // if (!$dbman->field_exists($table, $roleField)) {
    //     $dbman->add_field($table, $roleField);
    // }
    // if (!$dbman->field_exists($table, $lifecycleexpiryField)) {
    //     $dbman->add_field($table, $lifecycleexpiryField);
    // }

    // return true;
}
