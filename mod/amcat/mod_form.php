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
 * Form to define a new instance of amcat or edit an instance.
 * It is used from /course/modedit.php.
 *
 * @package mod_amcat
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 **/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/amcat/locallib.php');

class mod_amcat_mod_form extends moodleform_mod {

    protected $course = null;

    public function __construct($current, $section, $cm, $course) {
        $this->course = $course;
        parent::__construct($current, $section, $cm, $course);
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * @deprecated since Moodle 3.1
     */
    public function mod_amcat_mod_form($current, $section, $cm, $course) {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct($current, $section, $cm, $course);
    }

    function definition() {
        global $CFG, $COURSE, $DB, $OUTPUT;

        $mform    = $this->_form;

        $amcatconfig = get_config('mod_amcat');

        $mform->addElement('header', 'general', get_string('general', 'form'));

//         /** Legacy slideshow width element to maintain backwards compatibility */
//         $mform->addElement('hidden', 'width');
//         $mform->setType('width', PARAM_INT);
//         $mform->setDefault('width', $amcatconfig->slideshowwidth);

//         /** Legacy slideshow height element to maintain backwards compatibility */
//         $mform->addElement('hidden', 'height');
//         $mform->setType('height', PARAM_INT);
//         $mform->setDefault('height', $amcatconfig->slideshowheight);

//         /** Legacy slideshow background color element to maintain backwards compatibility */
//         $mform->addElement('hidden', 'bgcolor');
//         $mform->setType('bgcolor', PARAM_TEXT);
//         $mform->setDefault('bgcolor', $amcatconfig->slideshowbgcolor);

//         /** Legacy media popup width element to maintain backwards compatibility */
//         $mform->addElement('hidden', 'mediawidth');
//         $mform->setType('mediawidth', PARAM_INT);
//         $mform->setDefault('mediawidth', $amcatconfig->mediawidth);

//         /** Legacy media popup height element to maintain backwards compatibility */
//         $mform->addElement('hidden', 'mediaheight');
//         $mform->setType('mediaheight', PARAM_INT);
//         $mform->setDefault('mediaheight', $amcatconfig->mediaheight);

//         /** Legacy media popup close button element to maintain backwards compatibility */
//         $mform->addElement('hidden', 'mediaclose');
//         $mform->setType('mediaclose', PARAM_BOOL);
//         $mform->setDefault('mediaclose', $amcatconfig->mediaclose);
// */
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements();
		//amcat assessment name fetch from table
		$optionSql = "SELECT * FROM {amcat_assessment_name} where isDeleted = ? order by name ASC";
        $optionResult = $DB->get_records_sql($optionSql, array('N'));
		$option = array();
        foreach ($optionResult as $key => $value) {
                $option[$value->aid] = $value->name; 
         }
		 
        //$option = array(181952 => 'Java Test', 181838 => 'FS_Role1_Pre Language and web development_Practitioners', 181839   => 'FS_Role1_Language and web development_Practitioners');
        $mform->addElement('select', 'testid', get_string('testid', 'mod_amcat'), $option);
        $mform->setType('testid', PARAM_INT);
        $mform->addRule('testid', get_string('required'), 'required', null, 'client');


        $mform->addElement('date_time_selector', 'startdatetime', get_string('startdatetime', 'mod_amcat'));
        $mform->setType('startdatetime', PARAM_INT);
        $mform->addRule('startdatetime', get_string('required'), 'required', null, 'client');
        

        // Appearance.
        // $mform->addElement('header', 'appearancehdr', get_string('appearance'));

        // $filemanageroptions = array();
        // $filemanageroptions['filetypes'] = '*';
        // $filemanageroptions['maxbytes'] = $this->course->maxbytes;
        // $filemanageroptions['subdirs'] = 0;
        // $filemanageroptions['maxfiles'] = 1;
/*
        $mform->addElement('filemanager', 'mediafile', get_string('mediafile', 'amcat'), null, $filemanageroptions);
        $mform->addHelpButton('mediafile', 'mediafile', 'amcat');
        $mform->setAdvanced('mediafile', $amcatconfig->mediafile_adv);

        $mform->addElement('selectyesno', 'progressbar', get_string('progressbar', 'amcat'));
        $mform->addHelpButton('progressbar', 'progressbar', 'amcat');
        $mform->setDefault('progressbar', $amcatconfig->progressbar);
        $mform->setAdvanced('progressbar', $amcatconfig->progressbar_adv);

        $mform->addElement('selectyesno', 'ongoing', get_string('ongoing', 'amcat'));
        $mform->addHelpButton('ongoing', 'ongoing', 'amcat');
        $mform->setDefault('ongoing', $amcatconfig->ongoing);
        $mform->setAdvanced('ongoing', $amcatconfig->ongoing_adv);

        $mform->addElement('selectyesno', 'displayleft', get_string('displayleftmenu', 'amcat'));
        $mform->addHelpButton('displayleft', 'displayleftmenu', 'amcat');
        $mform->setDefault('displayleft', $amcatconfig->displayleftmenu);
        $mform->setAdvanced('displayleft', $amcatconfig->displayleftmenu_adv);

        $options = array();
        for($i = 100; $i >= 0; $i--) {
            $options[$i] = $i.'%';
        }
        $mform->addElement('select', 'displayleftif', get_string('displayleftif', 'amcat'), $options);
        $mform->addHelpButton('displayleftif', 'displayleftif', 'amcat');
        $mform->setDefault('displayleftif', $amcatconfig->displayleftif);
        $mform->setAdvanced('displayleftif', $amcatconfig->displayleftif_adv);

        $mform->addElement('selectyesno', 'slideshow', get_string('slideshow', 'amcat'));
        $mform->addHelpButton('slideshow', 'slideshow', 'amcat');
        $mform->setDefault('slideshow', $amcatconfig->slideshow);
        $mform->setAdvanced('slideshow', $amcatconfig->slideshow_adv);

        $numbers = array();
        for ($i = 20; $i > 1; $i--) {
            $numbers[$i] = $i;
        }

        $mform->addElement('select', 'maxanswers', get_string('maximumnumberofanswersbranches', 'amcat'), $numbers);
        $mform->setDefault('maxanswers', $amcatconfig->maxanswers);
        $mform->setAdvanced('maxanswers', $amcatconfig->maxanswers_adv);
        $mform->setType('maxanswers', PARAM_INT);
        $mform->addHelpButton('maxanswers', 'maximumnumberofanswersbranches', 'amcat');

        $mform->addElement('selectyesno', 'feedback', get_string('displaydefaultfeedback', 'amcat'));
        $mform->addHelpButton('feedback', 'displaydefaultfeedback', 'amcat');
        $mform->setDefault('feedback', $amcatconfig->defaultfeedback);
        $mform->setAdvanced('feedback', $amcatconfig->defaultfeedback_adv);

        // Get the modules.
        if ($mods = get_course_mods($COURSE->id)) {
            $modinstances = array();
            foreach ($mods as $mod) {
                // Get the module name and then store it in a new array.
                if ($module = get_coursemodule_from_instance($mod->modname, $mod->instance, $COURSE->id)) {
                    // Exclude this amcat, if it's already been saved.
                    if (!isset($this->_cm->id) || $this->_cm->id != $mod->id) {
                        $modinstances[$mod->id] = $mod->modname.' - '.$module->name;
                    }
                }
            }
            asort($modinstances); // Sort by module name.
            $modinstances=array(0=>get_string('none'))+$modinstances;

            $mform->addElement('select', 'activitylink', get_string('activitylink', 'amcat'), $modinstances);
            $mform->addHelpButton('activitylink', 'activitylink', 'amcat');
            $mform->setDefault('activitylink', 0);
            $mform->setAdvanced('activitylink', $amcatconfig->activitylink_adv);
        }

        // Availability.
        $mform->addElement('header', 'availabilityhdr', get_string('availability'));

        $mform->addElement('date_time_selector', 'available', get_string('available', 'amcat'), array('optional'=>true));
        $mform->setDefault('available', 0);

        $mform->addElement('date_time_selector', 'deadline', get_string('deadline', 'amcat'), array('optional'=>true));
        $mform->setDefault('deadline', 0);

        // Time limit.
        $mform->addElement('duration', 'timelimit', get_string('timelimit', 'amcat'),
                array('optional' => true));
        $mform->addHelpButton('timelimit', 'timelimit', 'amcat');
        $mform->setAdvanced('timelimit', $amcatconfig->timelimit_adv);
        $mform->setDefault('timelimit', $amcatconfig->timelimit);

        $mform->addElement('selectyesno', 'usepassword', get_string('usepassword', 'amcat'));
        $mform->addHelpButton('usepassword', 'usepassword', 'amcat');
        $mform->setDefault('usepassword', $amcatconfig->password);
        $mform->setAdvanced('usepassword', $amcatconfig->password_adv);

        $mform->addElement('passwordunmask', 'password', get_string('password', 'amcat'));
        $mform->setDefault('password', '');
        $mform->setAdvanced('password', $amcatconfig->password_adv);
        $mform->setType('password', PARAM_RAW);
        $mform->hideIf('password', 'usepassword', 'eq', 0);
        $mform->hideIf('passwordunmask', 'usepassword', 'eq', 0);

        // Dependent on.
        if ($this->current && isset($this->current->dependency) && $this->current->dependency) {
            $mform->addElement('header', 'dependencyon', get_string('prerequisiteamcat', 'amcat'));
            $mform->addElement('static', 'warningobsolete',
                get_string('warning', 'amcat'),
                get_string('prerequisiteisobsolete', 'amcat'));
            $options = array(0 => get_string('none'));
            if ($amcats = get_all_instances_in_course('amcat', $COURSE)) {
                foreach ($amcats as $amcat) {
                    if ($amcat->id != $this->_instance) {
                        $options[$amcat->id] = format_string($amcat->name, true);
                    }

                }
            }
            $mform->addElement('select', 'dependency', get_string('dependencyon', 'amcat'), $options);
            $mform->addHelpButton('dependency', 'dependencyon', 'amcat');
            $mform->setDefault('dependency', 0);

            $mform->addElement('text', 'timespent', get_string('timespentminutes', 'amcat'));
            $mform->setDefault('timespent', 0);
            $mform->setType('timespent', PARAM_INT);
            $mform->disabledIf('timespent', 'dependency', 'eq', 0);

            $mform->addElement('checkbox', 'completed', get_string('completed', 'amcat'));
            $mform->setDefault('completed', 0);
            $mform->disabledIf('completed', 'dependency', 'eq', 0);

            $mform->addElement('text', 'gradebetterthan', get_string('gradebetterthan', 'amcat'));
            $mform->setDefault('gradebetterthan', 0);
            $mform->setType('gradebetterthan', PARAM_INT);
            $mform->disabledIf('gradebetterthan', 'dependency', 'eq', 0);
        } else {
            $mform->addElement('hidden', 'dependency', 0);
            $mform->setType('dependency', PARAM_INT);
            $mform->addElement('hidden', 'timespent', 0);
            $mform->setType('timespent', PARAM_INT);
            $mform->addElement('hidden', 'completed', 0);
            $mform->setType('completed', PARAM_INT);
            $mform->addElement('hidden', 'gradebetterthan', 0);
            $mform->setType('gradebetterthan', PARAM_INT);
            $mform->setConstants(array('dependency' => 0, 'timespent' => 0,
                    'completed' => 0, 'gradebetterthan' => 0));
        }

        // Allow to enable offline amcats only if the Mobile services are enabled.
        if ($CFG->enablemobilewebservice) {
            $mform->addElement('selectyesno', 'allowofflineattempts', get_string('allowofflineattempts', 'amcat'));
            $mform->addHelpButton('allowofflineattempts', 'allowofflineattempts', 'amcat');
            $mform->setDefault('allowofflineattempts', 0);
            $mform->setAdvanced('allowofflineattempts');
            $mform->disabledIf('allowofflineattempts', 'timelimit[number]', 'neq', 0);

            $mform->addElement('static', 'allowofflineattemptswarning', '',
                    $OUTPUT->notification(get_string('allowofflineattempts_help', 'amcat'), 'warning'));
            $mform->setAdvanced('allowofflineattemptswarning');
        } else {
            $mform->addElement('hidden', 'allowofflineattempts', 0);
            $mform->setType('allowofflineattempts', PARAM_INT);
        }

        // Flow control.
        $mform->addElement('header', 'flowcontrol', get_string('flowcontrol', 'amcat'));

        $mform->addElement('selectyesno', 'modattempts', get_string('modattempts', 'amcat'));
        $mform->addHelpButton('modattempts', 'modattempts', 'amcat');
        $mform->setDefault('modattempts', $amcatconfig->modattempts);
        $mform->setAdvanced('modattempts', $amcatconfig->modattempts_adv);

        $mform->addElement('selectyesno', 'review', get_string('displayreview', 'amcat'));
        $mform->addHelpButton('review', 'displayreview', 'amcat');
        $mform->setDefault('review', $amcatconfig->displayreview);
        $mform->setAdvanced('review', $amcatconfig->displayreview_adv);

        $numbers = array();
        for ($i = 10; $i > 0; $i--) {
            $numbers[$i] = $i;
        }
        $mform->addElement('select', 'maxattempts', get_string('maximumnumberofattempts', 'amcat'), $numbers);
        $mform->addHelpButton('maxattempts', 'maximumnumberofattempts', 'amcat');
        $mform->setDefault('maxattempts', $amcatconfig->maximumnumberofattempts);
        $mform->setAdvanced('maxattempts', $amcatconfig->maximumnumberofattempts_adv);

        $defaultnextpages = array();
        $defaultnextpages[0] = get_string('normal', 'amcat');
        $defaultnextpages[amcat_UNSEENPAGE] = get_string('showanunseenpage', 'amcat');
        $defaultnextpages[amcat_UNANSWEREDPAGE] = get_string('showanunansweredpage', 'amcat');
        $mform->addElement('select', 'nextpagedefault', get_string('actionaftercorrectanswer', 'amcat'), $defaultnextpages);
        $mform->addHelpButton('nextpagedefault', 'actionaftercorrectanswer', 'amcat');
        $mform->setDefault('nextpagedefault', $amcatconfig->defaultnextpage);
        $mform->setAdvanced('nextpagedefault', $amcatconfig->defaultnextpage_adv);

        $numbers = array();
        for ($i = 100; $i >= 0; $i--) {
            $numbers[$i] = $i;
        }
        $mform->addElement('select', 'maxpages', get_string('numberofpagestoshow', 'amcat'), $numbers);
        $mform->addHelpButton('maxpages', 'numberofpagestoshow', 'amcat');
        $mform->setDefault('maxpages', $amcatconfig->numberofpagestoshow);
        $mform->setAdvanced('maxpages', $amcatconfig->numberofpagestoshow_adv);
*/
        // Grade.
        $this->standard_grading_coursemodule_elements();
/*
        // No header here, so that the following settings are displayed in the grade section.

        $mform->addElement('selectyesno', 'practice', get_string('practice', 'amcat'));
        $mform->addHelpButton('practice', 'practice', 'amcat');
        $mform->setDefault('practice', $amcatconfig->practice);
        $mform->setAdvanced('practice', $amcatconfig->practice_adv);

        $mform->addElement('selectyesno', 'custom', get_string('customscoring', 'amcat'));
        $mform->addHelpButton('custom', 'customscoring', 'amcat');
        $mform->setDefault('custom', $amcatconfig->customscoring);
        $mform->setAdvanced('custom', $amcatconfig->customscoring_adv);

        $mform->addElement('selectyesno', 'retake', get_string('retakesallowed', 'amcat'));
        $mform->addHelpButton('retake', 'retakesallowed', 'amcat');
        $mform->setDefault('retake', $amcatconfig->retakesallowed);
        $mform->setAdvanced('retake', $amcatconfig->retakesallowed_adv);

        $options = array();
        $options[0] = get_string('usemean', 'amcat');
        $options[1] = get_string('usemaximum', 'amcat');
        $mform->addElement('select', 'usemaxgrade', get_string('handlingofretakes', 'amcat'), $options);
        $mform->addHelpButton('usemaxgrade', 'handlingofretakes', 'amcat');
        $mform->setDefault('usemaxgrade', $amcatconfig->handlingofretakes);
        $mform->setAdvanced('usemaxgrade', $amcatconfig->handlingofretakes_adv);
        $mform->hideIf('usemaxgrade', 'retake', 'eq', '0');

        $numbers = array();
        for ($i = 100; $i >= 0; $i--) {
            $numbers[$i] = $i;
        }
        $mform->addElement('select', 'minquestions', get_string('minimumnumberofquestions', 'amcat'), $numbers);
        $mform->addHelpButton('minquestions', 'minimumnumberofquestions', 'amcat');
        $mform->setDefault('minquestions', $amcatconfig->minimumnumberofquestions);
        $mform->setAdvanced('minquestions', $amcatconfig->minimumnumberofquestions_adv);
*/
//-------------------------------------------------------------------------------
        $this->standard_coursemodule_elements();
//-------------------------------------------------------------------------------
// buttons
        $this->add_action_buttons();
    }

    /**
     * Enforce defaults here
     *
     * @param array $defaultvalues Form defaults
     * @return void
     **/
    public function data_preprocessing(&$defaultvalues) {
        if (isset($defaultvalues['conditions'])) {
            $conditions = unserialize($defaultvalues['conditions']);
            $defaultvalues['timespent'] = $conditions->timespent;
            $defaultvalues['completed'] = $conditions->completed;
            $defaultvalues['gradebetterthan'] = $conditions->gradebetterthan;
        }

        // Set up the completion checkbox which is not part of standard data.
        $defaultvalues['completiontimespentenabled'] =
            !empty($defaultvalues['completiontimespent']) ? 1 : 0;

        if ($this->current->instance) {
            // Editing existing instance - copy existing files into draft area.
            $draftitemid = file_get_submitted_draft_itemid('mediafile');
            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_amcat', 'mediafile', 0, array('subdirs'=>0, 'maxbytes' => $this->course->maxbytes, 'maxfiles' => 1));
            $defaultvalues['mediafile'] = $draftitemid;
        }
    }

    /**
     * Enforce validation rules here
     *
     * @param object $data Post data to validate
     * @return array
     **/
    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Check open and close times are consistent.
        // if ($data['available'] != 0 && $data['deadline'] != 0 &&
        //         $data['deadline'] < $data['available']) {
        //     $errors['deadline'] = get_string('closebeforeopen', 'amcat');
        // }

        if (!empty($data['usepassword']) && empty($data['password'])) {
            $errors['password'] = get_string('emptypassword', 'amcat');
        }

        return $errors;
    }

    /**
     * Display module-specific activity completion rules.
     * Part of the API defined by moodleform_mod
     * @return array Array of string IDs of added items, empty array if none
     */
    public function add_completion_rules() {
        $mform = $this->_form;

        $mform->addElement('checkbox', 'completionendreached', get_string('completionendreached', 'amcat'),
                get_string('completionendreached_desc', 'amcat'));
        // Enable this completion rule by default.
        $mform->setDefault('completionendreached', 1);

        $group = array();
        $group[] =& $mform->createElement('checkbox', 'completiontimespentenabled', '',
                get_string('completiontimespent', 'amcat'));
        $group[] =& $mform->createElement('duration', 'completiontimespent', '', array('optional' => false));
        $mform->addGroup($group, 'completiontimespentgroup', get_string('completiontimespentgroup', 'amcat'), array(' '), false);
        $mform->disabledIf('completiontimespent[number]', 'completiontimespentenabled', 'notchecked');
        $mform->disabledIf('completiontimespent[timeunit]', 'completiontimespentenabled', 'notchecked');

        return array('completionendreached', 'completiontimespentgroup');
    }

    /**
     * Called during validation. Indicates whether a module-specific completion rule is selected.
     *
     * @param array $data Input data (not yet validated)
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data) {
        return !empty($data['completionendreached']) || $data['completiontimespent'] > 0;
    }

    /**
     * Allows module to modify the data returned by form get_data().
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod.
     *
     * @param stdClass $data the form data to be modified.
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        // Turn off completion setting if the checkbox is not ticked.
        if (!empty($data->completionunlocked)) {
            $autocompletion = !empty($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->completiontimespentenabled) || !$autocompletion) {
                $data->completiontimespent = 0;
            }
            if (empty($data->completionendreached) || !$autocompletion) {
                $data->completionendreached = 0;
            }
        }
    }
}

