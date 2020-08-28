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

  private function getSubModuleFields($mform, &$repeatedoptions, $alldiffs) {
    $repeated = array();
    $repeated[] = $mform->createElement('text', 'topic', get_string('topic', 'quiz'));
    $repeated[] = $mform->createElement('static', 'complexityqlabel', 'Number of questions of different difficulty levels:', '');

    foreach($alldiffs as $diff) {
      $fieldname = str_replace(' ', '', $diff->name) . 'qnum';
      $attributes = array();
      if($diff != end($alldiffs)) {
        $attributes['class'] = 'notlastqnum';
      }
      $repeated[] = $mform->createElement('float', $fieldname, '&nbsp;&nbsp;&nbsp;&nbsp;' . $diff->name, $attributes);
      $repeatedoptions[$fieldname]['default'] = 0;
    }
    
    $repeatedoptions['topic']['type'] = PARAM_TEXT;
    $repeatedoptions['highq']['default'] = 0;
    
    return $repeated;
  }

  // Define the body of the form
  protected function definition() {
    global $DB;

    $mform = $this->_form;

    $mform->addElement('header', 'generalheader', get_string("general", 'form'));

    // Hidden fields
    $mform->addElement('hidden', 'cmid');
    $mform->setType('cmid', PARAM_INT);

    $mform->addElement('hidden', 'addbeforepage');
    $mform->setType('addbeforepage', PARAM_INT);

    $mform->addElement('hidden', 'returnurl');
    $mform->setType('returnurl', PARAM_LOCALURL);

    $mform->addElement('hidden', 'category');
    $mform->setType('category', PARAM_INT);

    // General fields
    $mform->addElement('text', 'name', get_string('modulenamefield', 'quiz'));
    $mform->setType('name', PARAM_TEXT);
    $mform->setDefault('name', 'New Module');

    $roles = array('None');
    $allroles = $DB->get_records('question_roles');
    foreach ($allroles as $role) {
      array_push($roles, $role->name);
    }

    $mform->addElement('select', 'role', get_string('role', 'quiz'), $roles);

    // Submodules
    $mform->addElement('header', 'submodulesheader', 'Submodules', '');
    $mform->setExpanded('submodulesheader', 1);

    $repeatedoptions = array();
    $alldiffs = $DB->get_records('question_difficulties', null, 'listindex');
    $repeated = $this->getSubModuleFields($mform, $repeatedoptions, $alldiffs);
    $repeatsatstart = 1;
    $submodulesperclick = 1;

    $this->repeat_elements($repeated, $repeatsatstart, $repeatedoptions,
      'nosubmods', 'addsubmod', $submodulesperclick,
      get_string('addsubmodule', 'quiz'), true);

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