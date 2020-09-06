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
 * cloudlabs configuration form
 *
 * @package mod_cloudlabs
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/cloudlabs/locallib.php');
require_once($CFG->libdir.'/filelib.php');

class mod_cloudlabs_mod_form extends moodleform_mod {
    function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        $config = get_config('cloudlabs');

        //-------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements();
		
		
		//--------------
		$mform->addElement('header', 'labdetailssecion', get_string('labdetails', 'cloudlabs'));
		$mform->addElement('text', 'companyid', get_string('companyid', 'cloudlabs'), array('size'=>'48'));
		$mform->addRule('companyid', null, 'required', null, 'client');
		
		//team array fetch from table
		$teamSql = "SELECT * FROM {cloudlabs_detail} where isDeleted = ? and labid=? order by name ASC";
        $teamResult = $DB->get_records_sql($teamSql, array('N','team'));
		$teamarray = array();
        foreach ($teamResult as $key => $value) {
                $teamarray[$value->cid] = $value->name; 
         }		
		//$teamarray = array('2225'=>'2225','461'=>'Mean Stack lab', '462'=>'AWS EC2 instance', '2432'=>'Java eclips', '2434'=>'GCP');
		$mform->addElement('select', 'teamid', get_string('teamid', 'cloudlabs'), $teamarray);
		
		//plan array fetch from table
		$planSql = "SELECT * FROM {cloudlabs_detail} where isDeleted = ? and labid=? order by name ASC";
        $planResult = $DB->get_records_sql($planSql, array('N','plan'));
		$planarray = array();
        foreach ($planResult as $key => $value) {
                $planarray[$value->cid] = $value->name; 
         }	
		//$planarray = array('2226'=>'2226','464'=>'Mean Stack lab', '459'=>'AWS EC2 instance', '2431'=>'Java eclips plan','2436'=>'GCP plan' );
		//$mform->addElement('text', 'planid', get_string('planid', 'cloudlabs'), array('size'=>'48'));
		$mform->addElement('select', 'planid', get_string('planid', 'cloudlabs'),$planarray);
		$mform->addRule('planid', null, 'required', null, 'client');
		
		$mform->addElement('text', 'accesskey', get_string('accesskey', 'cloudlabs'), array('size'=>'48'));
		$mform->addRule('accesskey', null, 'required', null, 'client');

        //-------------------------------------------------------
        $mform->addElement('header', 'contentsection', get_string('contentheader', 'cloudlabs'));
        $mform->addElement('editor', 'cloudlabs', get_string('content', 'cloudlabs'), null, cloudlabs_get_editor_options($this->context));
        $mform->addRule('cloudlabs', get_string('required'), 'required', null, 'client');
		
        //-------------------------------------------------------
        $mform->addElement('header', 'appearancehdr', get_string('appearance'));

        if ($this->current->instance) {
            $options = resourcelib_get_displayoptions(explode(',', $config->displayoptions), $this->current->display);
        } else {
            $options = resourcelib_get_displayoptions(explode(',', $config->displayoptions));
        }
        if (count($options) == 1) {
            $mform->addElement('hidden', 'display');
            $mform->setType('display', PARAM_INT);
            reset($options);
            $mform->setDefault('display', key($options));
        } else {
            $mform->addElement('select', 'display', get_string('displayselect', 'cloudlabs'), $options);
            $mform->setDefault('display', $config->display);
        }

        if (array_key_exists(RESOURCELIB_DISPLAY_POPUP, $options)) {
            $mform->addElement('text', 'popupwidth', get_string('popupwidth', 'cloudlabs'), array('size'=>3));
            if (count($options) > 1) {
                $mform->hideIf('popupwidth', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupwidth', PARAM_INT);
            $mform->setDefault('popupwidth', $config->popupwidth);

            $mform->addElement('text', 'popupheight', get_string('popupheight', 'cloudlabs'), array('size'=>3));
            if (count($options) > 1) {
                $mform->hideIf('popupheight', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupheight', PARAM_INT);
            $mform->setDefault('popupheight', $config->popupheight);
        }

        $mform->addElement('advcheckbox', 'printheading', get_string('printheading', 'cloudlabs'));
        $mform->setDefault('printheading', $config->printheading);
        $mform->addElement('advcheckbox', 'printintro', get_string('printintro', 'cloudlabs'));
        $mform->setDefault('printintro', $config->printintro);
        $mform->addElement('advcheckbox', 'printlastmodified', get_string('printlastmodified', 'cloudlabs'));
        $mform->setDefault('printlastmodified', $config->printlastmodified);

        // add legacy files flag only if used
        if (isset($this->current->legacyfiles) and $this->current->legacyfiles != RESOURCELIB_LEGACYFILES_NO) {
            $options = array(RESOURCELIB_LEGACYFILES_DONE   => get_string('legacyfilesdone', 'cloudlabs'),
                             RESOURCELIB_LEGACYFILES_ACTIVE => get_string('legacyfilesactive', 'cloudlabs'));
            $mform->addElement('select', 'legacyfiles', get_string('legacyfiles', 'cloudlabs'), $options);
            $mform->setAdvanced('legacyfiles', 1);
        }

        //-------------------------------------------------------
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------
        $this->add_action_buttons();

        //-------------------------------------------------------
        $mform->addElement('hidden', 'revision');
        $mform->setType('revision', PARAM_INT);
        $mform->setDefault('revision', 1);
    }

    /**
     * Enforce defaults here.
     *
     * @param array $defaultvalues Form defaults
     * @return void
     **/
    public function data_preprocessing(&$defaultvalues) {
        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('cloudlabs');
            $defaultvalues['cloudlabs']['format'] = $defaultvalues['contentformat'];
            $defaultvalues['cloudlabs']['text']   = file_prepare_draft_area($draftitemid, $this->context->id, 'mod_cloudlabs',
                    'content', 0, cloudlabs_get_editor_options($this->context), $defaultvalues['content']);
            $defaultvalues['cloudlabs']['itemid'] = $draftitemid;
        }
        if (!empty($defaultvalues['displayoptions'])) {
            $displayoptions = unserialize($defaultvalues['displayoptions']);
            if (isset($displayoptions['printintro'])) {
                $defaultvalues['printintro'] = $displayoptions['printintro'];
            }
            if (isset($displayoptions['printheading'])) {
                $defaultvalues['printheading'] = $displayoptions['printheading'];
            }
            if (isset($displayoptions['printlastmodified'])) {
                $defaultvalues['printlastmodified'] = $displayoptions['printlastmodified'];
            }
            if (!empty($displayoptions['popupwidth'])) {
                $defaultvalues['popupwidth'] = $displayoptions['popupwidth'];
            }
            if (!empty($displayoptions['popupheight'])) {
                $defaultvalues['popupheight'] = $displayoptions['popupheight'];
            }
        }
    }
}

