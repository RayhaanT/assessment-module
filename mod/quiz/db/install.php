<?php

defined('MOODLE_INTERNAL') || die;

function xmldb_quiz_install() {
    global $DB;

    $role = new stdClass();
    $role->name = 'Fresher';
    $DB->insert_record('question_roles', $role);
    $role->name = 'Mid-level';
    $DB->insert_record('question_roles', $role);
    $role->name = 'Senior';
    $DB->insert_record('question_roles', $role);

    $difficulty = new stdClass();
    $difficulty->name = 'Easy'; $difficulty->listindex = 1;
    $DB->insert_record('question_difficulties', $difficulty);
    $difficulty->name = 'Medium'; $difficulty->listindex = 2;
    $DB->insert_record('question_difficulties', $difficulty);
    $difficulty->name = 'Hard'; $difficulty->listindex = 3;
    $DB->insert_record('question_difficulties', $difficulty);

    $range = new stdClass();
    $range->upperbound = 500;
    $DB->insert_record('question_retirement_ranges', $range);
    $range->upperbound = 1000;
    $DB->insert_record('question_retirement_ranges', $range);
    $range->upperbound = 2000;
    $DB->insert_record('question_retirement_ranges', $range);

    $subject = new stdClass();
    $subject->name = 'Math';
    $DB->insert_record('question_subjects', $subject);
    $subject->name = 'English';
    $DB->insert_record('question_subjects', $subject);
    $subject->name = 'Verbal reasoning';
    $DB->insert_record('question_subjects', $subject);
    $subject->name = 'Non-verbal reasoning';
    $DB->insert_record('question_subjects', $subject);

    $region = new stdClass();
    $region->name = 'Algebra';
    $DB->insert_record('question_regions', $region);
    $region->name = 'Arithmetic';
    $DB->insert_record('question_regions', $region);
    $region->name = 'Grammar';
    $DB->insert_record('question_regions', $region);
}