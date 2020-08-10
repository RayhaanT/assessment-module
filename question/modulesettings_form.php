<?php

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

class modulesettings_form extends moodleform
{

    public function __construct($submiturl, $formeditable = true)
    {
        parent::__construct($submiturl, null, 'post', '', null, $formeditable);
    }

    private function getRoleFields($mform, &$repeatedoptions)
    {
        $repeated = array();
        $repeated[] = $mform->createElement('text', 'rolename', get_string('roleno', 'question', '{no}'));

        $repeatedoptions['rolename']['type'] = PARAM_TEXT;

        return $repeated;
    }

    private function getDifficultyLifeCycleFields($mform, &$repeatedoptions, $upperbounds) {
        $repeated = array();
        $repeated[] = $mform->createElement('text', 'difficultyname', get_string('difficultyno', 'question', '{no}'));

        $repeatedoptions['difficultyname']['type'] = PARAM_TEXT;

        $repeated[] = $mform->createElement('float', 'rate1', get_string('retirementpercentage', 'question', $upperbounds[0]));
        $repeated[] = $mform->createElement('float', 'rate2', get_string('retirementpercentage', 'question', $upperbounds[1]));
        $repeated[] = $mform->createElement('float', 'rate3', get_string('retirementpercentage', 'question', $upperbounds[2]));
        $repeated[] = $mform->createElement('float', 'rate4', get_string('retirementpercentagemore', 'question', $upperbounds[2]));        

        // $repeatedoptions['rate1']['default'] = 95;
        // $repeatedoptions['rate2']['default'] = 90;
        // $repeatedoptions['rate3']['default'] = 80;
        // $repeatedoptions['rate4']['default'] = 70;

        return $repeated;
    }

    // Define the body of the form
    protected function definition()
    {
        global $DB;

        $mform = $this->_form;

        // Roles
        $mform->addElement('header', 'generalheader', get_string("roles", 'question'));

        // Hidden fields
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_LOCALURL);

        $repeatedoptions = array();
        $repeated = $this->getRoleFields($mform, $repeatedoptions);
        $allroles = $DB->get_records('question_roles');
        $repeatsatstart = count($allroles);
        $rolesperclick = 3;

        $this->repeat_elements(
            $repeated,
            $repeatsatstart,
            $repeatedoptions,
            'noroles',
            'addrole',
            $rolesperclick,
            get_string('addroles', 'question', $rolesperclick),
            true
        );

        // Difficulty options
        $mform->addElement('header', 'difficultyheader', get_string('difficulties', 'question'));
        $mform->setExpanded('difficultyheader', true);

        $mform->addElement('text', 'difficultylist', get_string('difficultyfield', 'question'));
        $mform->setType('difficultylist', PARAM_TEXT);
        $mform->addHelpButton('difficultylist', 'difficultyfield', 'question');

        $mform->addElement('header', 'difficultylifecycleheader', get_string('difficultylifecycle', 'question'));

        $mform->addElement('static', 'difficultyinfo', get_string('retirementreason', 'question'), get_string('retirementpercentagestatic', 'question'));

        $repeatedoptions = array();
        $ranges = $DB->get_records('question_retirement_ranges');
        $rangebounds = array();
        $count = 0;
        foreach($ranges as $range) {
            $rangebounds[$count] = $range->upperbound;
            $count++;
        }
        $repeated = $this->getDifficultyLifeCycleFields($mform, $repeatedoptions, $rangebounds);
        $alldifficulties = $DB->get_records('question_difficulties');
        $repeatsatstart = count($alldifficulties);
        $rolesperclick = 0;

        $this->repeat_elements(
            $repeated,
            $repeatsatstart,
            $repeatedoptions,
            'nodifficulties',
            'adddifficulty',
            $rolesperclick,
            '',
            true,
            false
        );
        
        // Difficulty range options
        $mform->addElement('header', 'retirementrangeheader', get_string('retirementrangeheader', 'question'));
        
        $mform->addElement('float', 'range1', get_string('upperboundfield', 'question', 1));
        $mform->addElement('float', 'range2', get_string('upperboundfield', 'question', 2));
        $mform->addElement('float', 'range3', get_string('upperboundfield', 'question', 3));

        // Submit buttons
        $buttonarray = array();
        $buttonarray[] = $mform->createElement(
            'submit',
            'updatebutton',
            get_string('savechangesandcontinueediting', 'question')
        );
        $mform->addGroup($buttonarray, 'updatebuttonar', '', array(' '), false);
        $mform->closeHeaderBefore('updatebuttonar');

        $this->add_action_buttons();
    }

    // Validate form inputs
    // Returns errors
    public function validate($fromform, $files)
    {
        global $DB;

        $errors = parent::validation($fromform, $files);

        // Topic
        if (!empty($fromform['topic'])) {
            $condition = 'topic = ' . $fromform['topic'];
            if ($DB->record_exists_select('question', $condition)) {
                $errors['topic'] = get_string('topicinvalid', 'quiz');
            }
        }

        return $errors;
    }
}