<?php

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

class suspend_form extends moodleform
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

        // Header
        $mform->addElement('header', 'generalheader', get_string('onetimesuspension', 'question'));

        // Hidden fields
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_LOCALURL);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('date_selector', 'suspensionenddate', get_string('suspensionend', 'question'));

        $mform->addElement('header', 'periodicheader', get_string('periodicsuspension', 'question'));
        $mform->setExpanded('periodicheader', 'true');

        $mform->addElement('duration', 'enabledperiod', get_string('enabledperiod', 'question'));
        $mform->addHelpButton('enabledperiod', 'periodicsuspension', 'question');

        $mform->addElement('duration', 'disabledperiod', get_string('disabledperiod', 'question'));
        $mform->addHelpButton('disabledperiod', 'periodicsuspension', 'question');

        $mform->addElement('date_time_selector', 'cyclestarttime', get_string('suspensioncyclestarttime', 'question'));

        $this->add_action_buttons();
    }
}