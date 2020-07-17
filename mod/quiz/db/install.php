<?php

defined('MOODLE_INTERNAL') || die;

function xmldb_quiz_install() {
  global $DB;

  $dbman = $DB->get_manager();

  // Define fields to be added to question database
  $table = new xmldb_table('question');
  $topicField = new xmldb_field('topic', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'idnumber');
  $difficultyField = new xmldb_field('difficulty', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'topic');
  $roleField = new xmldb_field('role', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'difficulty');
  $lifecycleexpiryField = new xmldb_field('lifecycleexpiry', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'role');

  // Conditionally add new fields
  if (!$dbman->field_exists($table, $topicField)) {
    $dbman->add_field($table, $topicField);
  }
  if (!$dbman->field_exists($table, $difficultyField)) {
    $dbman->add_field($table, $difficultyField);
  }
  if (!$dbman->field_exists($table, $roleField)) {
    $dbman->add_field($table, $roleField);
  }
  if (!$dbman->field_exists($table, $lifecycleexpiryField)) {
    $dbman->add_field($table, $lifecycleexpiryField);
  }

}