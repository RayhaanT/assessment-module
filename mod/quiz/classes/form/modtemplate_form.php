<?php

namespace mod_quiz\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class modtemplate_form extends \moodleform
{

    /**
     * Form definiton.
     */
    public function definition()
    {
        global $DB;
        $mform = $this->_form;

        $contexts = $this->_customdata['contexts'];
        $usablecontexts = $contexts->having_cap('moodle/question:useall');

        // Standard fields at the start of the form.
        $mform->addElement('header', 'generalheader', get_string("general", 'form'));

        $diffs = array('All');
        $alldiffs = $DB->get_records('question_difficulties', null, 'listindex');
        foreach ($alldiffs as $diff) {
            array_push($diffs, $diff->name);
        }
        $roles = array('All');
        $allroles = $DB->get_records('question_roles');
        foreach ($allroles as $role) {
            array_push($roles, $role->name);
        }

        $mform->addElement('select', 'role', get_string('role', 'quiz'), $roles);
        $mform->addElement('select', 'difficulty', get_string('difficulty', 'quiz'), $diffs);
        $mform->addElement('text', 'topic', get_string('topic', 'quiz'));
        $mform->setType('topic', PARAM_TEXT);

        $mform->addElement('hidden', 'slotid');
        $mform->setType('slotid', PARAM_INT);

        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_LOCALURL);

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    public function set_data($defaultvalues)
    {
        $mform = $this->_form;

        if ($defaultvalues->fromtags) {
            $fromtagselement = $mform->getElement('fromtags');
            foreach ($defaultvalues->fromtags as $fromtag) {
                if (!$fromtagselement->optionExists($fromtag)) {
                    $optionname = get_string('randomfromunavailabletag', 'mod_quiz', explode(',', $fromtag)[1]);
                    $fromtagselement->addOption($optionname, $fromtag);
                }
            }
        }

        parent::set_data($defaultvalues);
    }
}
