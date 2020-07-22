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

  // Define the body of the form
  protected function definition() {
    $mform = $this->_form;

    $mform->addElement('header', 'generalheader', get_string("general", 'form'));

    // Hidden fields?
    $mform->addElement('hidden', 'cmid');
    $mform->setType('cmid', PARAM_INT);

    $mform->addElement('hidden', 'addbeforepage');
    $mform->setType('addbeforepage', PARAM_INT);

    $mform->addElement('hidden', 'returnurl');
    $mform->setType('returnurl', PARAM_LOCALURL);

    // General fields
    $mform->addElement('float', 'numberofquestions', get_string('numberofquestions', 'quiz'));
    $mform->setDefault('numberofquestions', 1);
    $mform->addRule('numberofquestions', null, 'required', null, 'client');

    // Fields used by the assessment engine to poll the question bank
    $difficulties = array(
      'None',
      'EASY',
      'MEDIUM',
      'HARD'
    );
    $roles = array(
      'None',
      'Fresher',
      'Mid-level',
      'Senior'
    );
    $mform->addElement('checkbox', 'lifecycle', get_string('lifecycleenable', 'quiz'));
    $mform->setDefault('lifecycle', 1);

    $mform->addElement('select', 'difficulty', get_string('difficulty', 'quiz'), $difficulties);

    $mform->addElement('text', 'topic', get_string('topic', 'quiz'));
    $mform->setType('topic', PARAM_TEXT);

    $mform->addElement('select', 'role', get_string('role', 'quiz'), $roles);

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