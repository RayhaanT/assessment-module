<?php

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

class generate_section_form extends moodleform {

  protected $contexts;

  public function __construct($submiturl, $contexts, $formeditable = true) {
    $this->contexts = $contexts;
    parent::__construct($submiturl, null, 'post', '', null, $formeditable);
  }

  private function getSubModuleFields($mform, &$repeatedoptions) {
    $repeated = array();
    $repeated[] = $mform->createElement('text', 'topic', get_string('topic', 'quiz'));
    $repeated[] = $mform->createElement('checkbox', 'lifecycle', get_string('lifecycleenable', 'quiz'));
    $repeated[] = $mform->createElement('static', 'complexityqlabel', 'Number of questions of different complexities:', '');
    $repeated[] = $mform->createElement('float', 'highq', 'High complexity');
    $repeated[] = $mform->createElement('float', 'mediumq', 'Medium complexity');
    $repeated[] = $mform->createElement('float', 'lowq', 'Low complexity');

    $repeatedoptions['topic']['type'] = PARAM_TEXT;
    $repeatedoptions['highq']['default'] = 0;
    $repeatedoptions['mediumq']['default'] = 0;
    $repeatedoptions['lowq']['default'] = 0;

    return $repeated;
  }

  // Define the body of the form
  protected function definition() {
    $mform = $this->_form;

    $mform->addElement('header', 'generalheader', get_string("general", 'form'));

    // Hidden fields
    $mform->addElement('hidden', 'cmid');
    $mform->setType('cmid', PARAM_INT);

    $mform->addElement('hidden', 'addbeforepage');
    $mform->setType('addbeforepage', PARAM_INT);

    $mform->addElement('hidden', 'returnurl');
    $mform->setType('returnurl', PARAM_LOCALURL);

    // General fields
    $mform->addElement('text', 'name', get_string('modulenamefield', 'quiz'));
    $mform->setType('name', PARAM_TEXT);
    $mform->setDefault('name', 'New Module');

    $roles = array(
      'None',
      'Fresher',
      'Mid-level',
      'Senior'
    );

    $mform->addElement('select', 'role', get_string('role', 'quiz'), $roles);

    // Submodules
    $mform->addElement('header', 'submodulesheader', 'Submodules', '');
    $mform->setExpanded('submodulesheader', 1);

    $answersoption = '';
    $repeatedoptions = array();
    $repeated = $this->getSubModuleFields($mform, $repeatedoptions, $answersoption);
    $repeatsatstart = 1;
    $submodulesperclick = 1;

    $this->repeat_elements($repeated, $repeatsatstart, $repeatedoptions,
      'nosubmods', 'addsubmod', $submodulesperclick,
      get_string('addsubmodule', 'quiz'), true);

    // $difficulties = array(
    //   'None',
    //   'Low',
    //   'Medium',
    //   'High'
    // );

    // $mform->addElement('checkbox', 'lifecycle', get_string('lifecycleenable', 'quiz'));
    // $mform->setDefault('lifecycle', 1);

    // $mform->addElement('select', 'difficulty', get_string('difficulty', 'quiz'), $difficulties);

    // $mform->addElement('text', 'topic', get_string('topic', 'quiz'));
    // $mform->setType('topic', PARAM_TEXT);

    // Timing options
    $mform->addElement('header', 'timingheader', get_string('timing', 'quiz'));

    $mform->addElement('duration', 'timelimit', get_string('timelimit', 'quiz'));
    $mform->setDefault('duration', 0);

    $this->add_action_buttons();
  }

  // Validate form inputs
  // Returns errors
  public function validate($fromform, $files) {
    global $DB;

    $errors = parent::validation($fromform, $files);

    // Topic
    if(!empty($fromform['topic'])) {
      $condition = 'topic = ' . $fromform['topic'];
      if($DB->record_exists_select('question', $condition)) {
        $errors['topic'] = get_string('topicinvalid', 'quiz');
      }
    }

    return $errors;
  }
}