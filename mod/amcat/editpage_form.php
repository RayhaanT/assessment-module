<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Generic forms used for page selection
 *
 * @package mod_amcat
 * @copyright  2009 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();

/**
 * Question selection form
 *
 * @copyright  2009 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class amcat_add_page_form_selection extends amcat_add_page_form_base {

    public $qtype = 'questiontype';
    public $qtypestring = 'selectaqtype';
    protected $standard = false;
    protected $manager = null;

    public function __construct($arg1, $arg2) {
        $this->manager = amcat_page_type_manager::get($arg2['amcat']);
        parent::__construct($arg1, $arg2);
    }

    public function custom_definition() {
        $mform = $this->_form;
        $types = $this->manager->get_page_type_strings(amcat_page::TYPE_QUESTION);
        asort($types);
        $mform->addElement('select', 'qtype', get_string('selectaqtype', 'amcat'), $types);
        $mform->setDefault('qtype', amcat_PAGE_MULTICHOICE); // preselect the most common type
    }
}

/**
 * Dummy class to represent an unknown question type and direct to the selection
 * form.
 */
final class amcat_add_page_form_unknown extends amcat_add_page_form_base {}
