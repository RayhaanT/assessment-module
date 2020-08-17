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
        global $DB;

        $mform = $this->_form;

        // Roles
        $mform->addElement('header', 'generalheader', get_string("versionheader", 'question'));

        // Hidden fields
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_LOCALURL);

        $sql = 'SELECT DISTINCT topic FROM moodle.mdl_question ORDER BY topic';
        $alltopics = $DB->get_records_sql($sql);

        $count = 0;
        foreach($alltopics as $t) {
            if(!$t->topic) {continue;} 
            $mform->addElement('float', 'version' . $count, get_string('topicversiongroup', 'question', $t->topic));
            $mform->addHelpButton('version' . $count, 'techversion', 'question');
            $count++;
        }

        $this->add_action_buttons();
    }
}