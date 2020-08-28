<?php
defined('MOODLE_INTERNAL') || die();


/**
 * modtemplate editing form definition.
 */
class qtype_modtemplate_edit_form extends question_edit_form {
    /**
     * Build the form definition.
     *
     * This adds all the form files that the default question type supports.
     * If your question type does not support all these fields, then you can
     * override this method and remove the ones you don't want with $mform->removeElement().
     */
    protected function definition() {
        $mform = $this->_form;

        // Standard fields at the start of the form.
        $mform->addElement('header', 'generalheader', get_string("general", 'form'));

        $mform->addElement('questioncategory', 'category', get_string('category', 'question'),
                array('contexts' => $this->contexts->having_cap('moodle/question:useall'), 'top' => true));

        $tops = question_get_top_categories_for_contexts(array_column($this->contexts->all(), 'id'));
        $mform->hideIf('questiontext[text]', 'category', 'in', $tops);

        $mform->addElement('hidden', 'qtype');
        $mform->setType('qtype', PARAM_ALPHA);

        $this->add_hidden_fields();

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    public function set_data($question) {
        $question->questiontext = array('text' => $question->questiontext);
        // We don't want the complex stuff in the base class to run.
        moodleform::set_data($question);
    }

    public function validation($fromform, $files) {
        // Validation of category is not relevant for this question type.

        return array();
    }

    public function qtype() {
        return 'modtemplate';
    }
}
