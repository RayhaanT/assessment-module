<?php

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

class techversion_form extends moodleform
{

    public function __construct($submiturl, $formeditable = true)
    {
        parent::__construct($submiturl, null, 'post', '', null, $formeditable);
    }

    // Define the body of the form
    protected function definition()
    {
        global $DB, $CFG;

        $mform = $this->_form;

        // Roles
        $mform->addElement('header', 'generalheader', get_string("versionheader", 'question'));

        // Hidden fields
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_LOCALURL);

        $sql = "SELECT DISTINCT topic FROM " . $CFG->dbname . "." . $CFG->prefix . "question ORDER BY topic";
        $alltopics = $DB->get_records_sql($sql);

        $difficulties = array('None');
        $alldiffs = $DB->get_records('question_difficulties', null, 'listindex');
        foreach ($alldiffs as $diff) {
            array_push($difficulties, $diff->name);
        }

        $count = 0;
        foreach($alltopics as $t) {
            if(!$t->topic) {continue;} 
            $mform->addElement('float', 'version' . $count, get_string('topicversiongroup', 'question', $t->topic));
            $mform->addElement('select', 'difficulty' . $count, get_string('difficulty', 'quiz'), $difficulties);
            $mform->addHelpButton('version' . $count, 'techversion', 'question');
            $count++;
        }

        $this->add_action_buttons();
    }
}