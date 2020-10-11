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
        $rolefields = array();

        $rolefields[] = $mform->createElement('text', 'rolename', '');
        $mform->registerNoSubmitButton('deleterole');
        $rolefields[] = $mform->createElement('submit', 'deleterole', get_string('delete'));
        $repeated[] = $mform->createElement('group', 'rolebuttonpair', get_string('roleno', 'question', '{no}'), $rolefields, null, false);

        $repeatedoptions['rolename']['type'] = PARAM_TEXT;

        return $repeated;
    }

    private function getSubjectFields($mform, &$repeatedoptions) {
        $repeated = array();
        $subjectFields = array();

        $subjectFields[] = $mform->createElement('text', 'subjectname', '');
        $mform->registerNoSubmitButton('deletesubject');
        $subjectFields[] = $mform->createElement('submit', 'deletesubject', get_string('delete'));
        $repeated[] = $mform->createElement('group', 'subjectbuttonpair', get_string('subjectno', 'question', '{no}'), $subjectFields, null, false);

        $repeatedoptions['subjectname']['type'] = PARAM_TEXT;

        return $repeated;
    }

    private function getDifficultyLifeCycleFields($mform, &$repeatedoptions, $upperbounds) {
        $repeated = array();
        $repeated[] = $mform->createElement('text', 'difficultyname', get_string('difficultyno', 'question', '{no}'));

        $repeatedoptions['difficultyname']['type'] = PARAM_TEXT;
        $repeatedoptions['difficultyname']['helpbutton'] = array('difficultyname', 'question');

        $repeated[] = $mform->createElement('static', 'retirementsubheading', get_string('retirementsubheading', 'question'));
        $repeatedoptions['retirementsubheading']['helpbutton'] = array('retirementreason', 'question');

        $repeated[] = $mform->createElement('float', 'rate1', get_string('retirementpercentage', 'question', $upperbounds[0]));
        $repeated[] = $mform->createElement('float', 'rate2', get_string('retirementpercentage', 'question', $upperbounds[1]));
        $repeated[] = $mform->createElement('float', 'rate3', get_string('retirementpercentage', 'question', $upperbounds[2]));
        $repeated[] = $mform->createElement('float', 'rate4', get_string('retirementpercentagemore', 'question', $upperbounds[2]));

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
        $rolesperclick = 2;

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

        $mform->addElement('header', 'generalheader', get_string("subjects", "question"));

        $repeatedoptions = array();
        $repeated = $this->getSubjectFields($mform, $repeatedoptions);
        $allSubjects = $DB->get_records('question_subjects');
        $repeatsatstart = count($allSubjects);
        $subjectsperclick = 1;

        $this->repeat_elements(
            $repeated,
            $repeatsatstart,
            $repeatedoptions,
            'nosubjects',
            'addsubject',
            $subjectsperclick,
            get_string('addsubjects', 'question', $subjectsperclick),
            true
        );

        // Difficulty options
        $mform->addElement('header', 'difficultyheader', get_string('difficulties', 'question'));
        $mform->setExpanded('difficultyheader', true);

        $mform->addElement('text', 'difficultylist', get_string('difficultyfield', 'question'));
        $mform->setType('difficultylist', PARAM_TEXT);
        $mform->addHelpButton('difficultylist', 'difficultyfield', 'question');

        $mform->addElement('header', 'difficultylifecycleheader', get_string('difficultylifecycle', 'question'));

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